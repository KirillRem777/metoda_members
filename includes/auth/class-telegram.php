<?php
/**
 * Telegram Integration
 *
 * Handles Telegram bot for OTP delivery and user linking
 * Much faster than email (1 sec vs 30 sec) and more reliable (no spam folder)
 *
 * @package Metoda
 * @since 5.1.0
 */

namespace Metoda\Auth;

if (!defined('ABSPATH')) {
    exit;
}

class Telegram {

    /**
     * Bot token from wp_options
     * @var string
     */
    private $bot_token;

    /**
     * Bot username (without @)
     * @var string
     */
    private $bot_username;

    /**
     * Singleton instance
     * @var Telegram
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->bot_token = get_option('metoda_telegram_bot_token', '');
        $this->bot_username = get_option('metoda_telegram_bot_username', 'MetodaBot');

        // AJAX handlers for linking/unlinking
        add_action('wp_ajax_metoda_get_telegram_link', array($this, 'ajax_get_link'));
        add_action('wp_ajax_metoda_unlink_telegram', array($this, 'ajax_unlink'));
        add_action('wp_ajax_metoda_check_telegram_status', array($this, 'ajax_check_status'));

        // REST API webhook for bot
        add_action('rest_api_init', array($this, 'register_webhook'));

        // Admin settings
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('wp_ajax_metoda_test_telegram', array($this, 'ajax_test_connection'));
        }
    }

    /**
     * Check if bot is configured
     *
     * @return bool
     */
    public function is_configured(): bool {
        return !empty($this->bot_token);
    }

    /**
     * Check if Telegram is linked for user
     *
     * @param int $user_id
     * @return bool
     */
    public static function is_linked(int $user_id): bool {
        $chat_id = get_user_meta($user_id, 'telegram_chat_id', true);
        return !empty($chat_id);
    }

    /**
     * Get user's chat_id
     *
     * @param int $user_id
     * @return string|false
     */
    public static function get_chat_id(int $user_id) {
        return get_user_meta($user_id, 'telegram_chat_id', true) ?: false;
    }

    /**
     * Get user's Telegram username if available
     *
     * @param int $user_id
     * @return string
     */
    public static function get_username(int $user_id): string {
        return get_user_meta($user_id, 'telegram_username', true) ?: '';
    }

    /**
     * Link user to Telegram chat
     *
     * @param int $user_id
     * @param string $chat_id
     * @param string $username Optional Telegram username
     */
    public static function link_user(int $user_id, string $chat_id, string $username = ''): void {
        update_user_meta($user_id, 'telegram_chat_id', sanitize_text_field($chat_id));
        update_user_meta($user_id, 'telegram_linked_at', current_time('mysql'));

        if (!empty($username)) {
            update_user_meta($user_id, 'telegram_username', sanitize_text_field($username));
        }

        // Fire action for external integrations
        do_action('metoda_telegram_linked', $user_id, $chat_id);
    }

    /**
     * Unlink user from Telegram
     *
     * @param int $user_id
     */
    public static function unlink_user(int $user_id): void {
        delete_user_meta($user_id, 'telegram_chat_id');
        delete_user_meta($user_id, 'telegram_linked_at');
        delete_user_meta($user_id, 'telegram_username');

        do_action('metoda_telegram_unlinked', $user_id);
    }

    /**
     * Send message to Telegram chat
     *
     * @param string $chat_id
     * @param string $text Message text
     * @param string $parse_mode Markdown, HTML, or empty
     * @return true|\WP_Error
     */
    public function send_message(string $chat_id, string $text, string $parse_mode = 'Markdown') {
        if (!$this->is_configured()) {
            return new \WP_Error(
                'not_configured',
                __('Telegram bot is not configured.', 'metoda-community-mgmt')
            );
        }

        $url = "https://api.telegram.org/bot{$this->bot_token}/sendMessage";

        $body = array(
            'chat_id'    => $chat_id,
            'text'       => $text,
            'parse_mode' => $parse_mode,
        );

        $response = wp_remote_post($url, array(
            'timeout' => 10,
            'body'    => $body,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['ok'])) {
            $error_msg = $body['description'] ?? 'Unknown Telegram API error';
            return new \WP_Error('telegram_api_error', $error_msg);
        }

        return true;
    }

    /**
     * Send OTP code via Telegram
     *
     * @param int $user_id
     * @param string $otp 6-digit code
     * @return true|\WP_Error
     */
    public function send_otp(int $user_id, string $otp) {
        $chat_id = self::get_chat_id($user_id);

        if (empty($chat_id)) {
            return new \WP_Error(
                'not_linked',
                __('Telegram is not linked to this account.', 'metoda-community-mgmt')
            );
        }

        $message = "🔐 *Код для входа в METODA*\n\n";
        $message .= "Ваш код: `{$otp}`\n\n";
        $message .= "⏱ Действует 10 минут.\n";
        $message .= "_Никому не сообщайте этот код._";

        $result = $this->send_message($chat_id, $message);

        if (is_wp_error($result)) {
            // Log error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Metoda Telegram] OTP send failed: ' . $result->get_error_message());
            }
        }

        return $result;
    }

    /**
     * Generate link URL for connecting Telegram
     *
     * @param int $user_id
     * @return string Telegram deep link
     */
    public function get_link_url(int $user_id): string {
        // Generate unique token for linking
        $link_token = wp_generate_password(32, false);

        // Store token for 10 minutes
        set_transient("metoda_tg_link_{$link_token}", $user_id, 600);

        return "https://t.me/{$this->bot_username}?start={$link_token}";
    }

    /**
     * Register REST API webhook endpoint
     */
    public function register_webhook(): void {
        register_rest_route('metoda/v1', '/telegram-webhook', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle incoming webhook from Telegram
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_webhook(\WP_REST_Request $request): \WP_REST_Response {
        $data = $request->get_json_params();

        // Validate request has message
        if (empty($data['message'])) {
            return new \WP_REST_Response('OK', 200);
        }

        $message = $data['message'];
        $chat_id = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        $username = $message['from']['username'] ?? '';

        if (empty($chat_id)) {
            return new \WP_REST_Response('OK', 200);
        }

        // Handle /start command with link token
        if (preg_match('/^\/start\s+([a-zA-Z0-9]+)$/', $text, $matches)) {
            $this->handle_start_with_token($chat_id, $matches[1], $username);
        }
        // Handle plain /start command
        elseif ($text === '/start') {
            $this->handle_start($chat_id);
        }
        // Handle /help command
        elseif ($text === '/help') {
            $this->handle_help($chat_id);
        }
        // Handle /status command
        elseif ($text === '/status') {
            $this->handle_status($chat_id);
        }

        return new \WP_REST_Response('OK', 200);
    }

    /**
     * Handle /start with link token
     */
    private function handle_start_with_token(string $chat_id, string $token, string $username): void {
        $user_id = get_transient("metoda_tg_link_{$token}");

        if ($user_id) {
            // Link accounts
            self::link_user($user_id, $chat_id, $username);
            delete_transient("metoda_tg_link_{$token}");

            $user = get_user_by('ID', $user_id);
            $name = $user ? $user->display_name : '';

            $this->send_message($chat_id,
                "✅ *Telegram успешно подключен!*\n\n" .
                ($name ? "Привет, {$name}!\n\n" : "") .
                "Теперь коды для входа будут приходить сюда.\n\n" .
                "Можете закрыть это окно и вернуться на сайт."
            );
        } else {
            $this->send_message($chat_id,
                "❌ *Ссылка устарела или недействительна.*\n\n" .
                "Попробуйте подключить Telegram заново в личном кабинете на сайте."
            );
        }
    }

    /**
     * Handle plain /start command
     */
    private function handle_start(string $chat_id): void {
        $site_name = get_bloginfo('name');

        $this->send_message($chat_id,
            "👋 *Привет!*\n\n" .
            "Это бот сообщества {$site_name}.\n\n" .
            "Чтобы подключить уведомления, нажмите кнопку " .
            "«Подключить Telegram» в личном кабинете на сайте.\n\n" .
            "Доступные команды:\n" .
            "/help — справка\n" .
            "/status — статус подключения"
        );
    }

    /**
     * Handle /help command
     */
    private function handle_help(string $chat_id): void {
        $this->send_message($chat_id,
            "ℹ️ *Справка*\n\n" .
            "Этот бот отправляет коды для входа на сайт.\n\n" .
            "*Как подключить:*\n" .
            "1. Войдите в личный кабинет на сайте\n" .
            "2. Откройте настройки профиля\n" .
            "3. Нажмите «Подключить Telegram»\n" .
            "4. Перейдите по ссылке\n\n" .
            "*Команды:*\n" .
            "/start — приветствие\n" .
            "/help — эта справка\n" .
            "/status — статус подключения"
        );
    }

    /**
     * Handle /status command
     */
    private function handle_status(string $chat_id): void {
        global $wpdb;

        // Find user by chat_id
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'telegram_chat_id' AND meta_value = %s",
            $chat_id
        ));

        if ($user_id) {
            $user = get_user_by('ID', $user_id);
            $linked_at = get_user_meta($user_id, 'telegram_linked_at', true);

            $this->send_message($chat_id,
                "✅ *Telegram подключен*\n\n" .
                "Аккаунт: {$user->user_email}\n" .
                "Подключен: {$linked_at}"
            );
        } else {
            $this->send_message($chat_id,
                "❌ *Telegram не подключен*\n\n" .
                "Этот чат не связан ни с одним аккаунтом.\n" .
                "Подключите Telegram в личном кабинете на сайте."
            );
        }
    }

    /**
     * AJAX: Get link URL for current user
     */
    public function ajax_get_link(): void {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Authentication required.', 'metoda-community-mgmt')));
        }

        $user_id = get_current_user_id();

        // Check if already linked
        if (self::is_linked($user_id)) {
            wp_send_json_error(array(
                'message' => __('Telegram is already linked.', 'metoda-community-mgmt'),
                'linked'  => true,
            ));
        }

        // Check if bot is configured
        if (!$this->is_configured()) {
            wp_send_json_error(array(
                'message' => __('Telegram integration is not available.', 'metoda-community-mgmt'),
            ));
        }

        $link_url = $this->get_link_url($user_id);

        wp_send_json_success(array(
            'link_url'     => $link_url,
            'bot_username' => $this->bot_username,
            'expires_in'   => 600, // 10 minutes
        ));
    }

    /**
     * AJAX: Check if Telegram is linked (for polling after link attempt)
     */
    public function ajax_check_status(): void {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Authentication required.', 'metoda-community-mgmt')));
        }

        $user_id = get_current_user_id();
        $is_linked = self::is_linked($user_id);

        wp_send_json_success(array(
            'linked'    => $is_linked,
            'username'  => $is_linked ? self::get_username($user_id) : null,
            'linked_at' => $is_linked ? get_user_meta($user_id, 'telegram_linked_at', true) : null,
        ));
    }

    /**
     * AJAX: Unlink Telegram
     */
    public function ajax_unlink(): void {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Authentication required.', 'metoda-community-mgmt')));
        }

        $user_id = get_current_user_id();

        if (!self::is_linked($user_id)) {
            wp_send_json_error(array('message' => __('Telegram is not linked.', 'metoda-community-mgmt')));
        }

        // Notify user in Telegram before unlinking
        $chat_id = self::get_chat_id($user_id);
        if ($chat_id) {
            $this->send_message($chat_id,
                "🔓 *Telegram отключен*\n\n" .
                "Ваш аккаунт больше не связан с этим чатом.\n" .
                "Коды для входа будут приходить на email."
            );
        }

        self::unlink_user($user_id);

        wp_send_json_success(array(
            'message' => __('Telegram has been unlinked.', 'metoda-community-mgmt'),
        ));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'edit.php?post_type=members',
            __('Telegram Settings', 'metoda-community-mgmt'),
            __('Telegram', 'metoda-community-mgmt'),
            'manage_options',
            'metoda-telegram',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('metoda_telegram', 'metoda_telegram_bot_token', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));

        register_setting('metoda_telegram', 'metoda_telegram_bot_username', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }

    /**
     * Render admin settings page
     */
    public function render_admin_page(): void {
        $webhook_url = rest_url('metoda/v1/telegram-webhook');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Telegram Integration', 'metoda-community-mgmt'); ?></h1>

            <p><?php esc_html_e('Configure Telegram bot for instant OTP delivery.', 'metoda-community-mgmt'); ?></p>

            <form method="post" action="options.php">
                <?php settings_fields('metoda_telegram'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="metoda_telegram_bot_token">Bot Token</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="metoda_telegram_bot_token"
                                   name="metoda_telegram_bot_token"
                                   value="<?php echo esc_attr(get_option('metoda_telegram_bot_token', '')); ?>"
                                   class="regular-text"
                                   placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                            <p class="description">
                                <?php esc_html_e('Get token from @BotFather in Telegram', 'metoda-community-mgmt'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="metoda_telegram_bot_username">Bot Username</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="metoda_telegram_bot_username"
                                   name="metoda_telegram_bot_username"
                                   value="<?php echo esc_attr(get_option('metoda_telegram_bot_username', 'MetodaBot')); ?>"
                                   class="regular-text"
                                   placeholder="MetodaBot">
                            <p class="description">
                                <?php esc_html_e('Bot username without @', 'metoda-community-mgmt'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <code id="webhook-url"><?php echo esc_html($webhook_url); ?></code>
                            <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_url); ?>'); this.textContent='Copied!';">
                                <?php esc_html_e('Copy', 'metoda-community-mgmt'); ?>
                            </button>
                            <p class="description">
                                <?php esc_html_e('Set this URL as webhook in Telegram API', 'metoda-community-mgmt'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'metoda-community-mgmt')); ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Test Connection', 'metoda-community-mgmt'); ?></h2>
            <p><?php esc_html_e('Send a test message to verify bot configuration.', 'metoda-community-mgmt'); ?></p>

            <p>
                <input type="text" id="test-chat-id" placeholder="Chat ID" class="regular-text">
                <button type="button" class="button button-secondary" id="test-telegram-btn">
                    <?php esc_html_e('Send Test Message', 'metoda-community-mgmt'); ?>
                </button>
            </p>
            <div id="test-result"></div>

            <hr>

            <h2><?php esc_html_e('Setup Instructions', 'metoda-community-mgmt'); ?></h2>
            <ol>
                <li><?php esc_html_e('Open @BotFather in Telegram', 'metoda-community-mgmt'); ?></li>
                <li><?php esc_html_e('Send /newbot command', 'metoda-community-mgmt'); ?></li>
                <li><?php esc_html_e('Set bot name: METODA Notifications', 'metoda-community-mgmt'); ?></li>
                <li><?php esc_html_e('Set username: MetodaNotifyBot (or your choice)', 'metoda-community-mgmt'); ?></li>
                <li><?php esc_html_e('Copy the token and paste it above', 'metoda-community-mgmt'); ?></li>
                <li>
                    <?php esc_html_e('Set webhook by opening this URL in browser:', 'metoda-community-mgmt'); ?><br>
                    <code>https://api.telegram.org/bot<strong>YOUR_TOKEN</strong>/setWebhook?url=<?php echo urlencode($webhook_url); ?></code>
                </li>
            </ol>

            <hr>

            <h2><?php esc_html_e('Statistics', 'metoda-community-mgmt'); ?></h2>
            <?php
            global $wpdb;
            $linked_count = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'telegram_chat_id'"
            );
            ?>
            <p>
                <strong><?php esc_html_e('Users with Telegram linked:', 'metoda-community-mgmt'); ?></strong>
                <?php echo intval($linked_count); ?>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-telegram-btn').on('click', function() {
                var btn = $(this);
                var chatId = $('#test-chat-id').val();

                if (!chatId) {
                    $('#test-result').html('<div class="notice notice-error"><p>Enter Chat ID</p></div>');
                    return;
                }

                btn.prop('disabled', true).text('Sending...');

                $.post(ajaxurl, {
                    action: 'metoda_test_telegram',
                    nonce: '<?php echo wp_create_nonce('metoda_test_telegram'); ?>',
                    chat_id: chatId
                }, function(response) {
                    btn.prop('disabled', false).text('<?php esc_html_e('Send Test Message', 'metoda-community-mgmt'); ?>');

                    if (response.success) {
                        $('#test-result').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    } else {
                        $('#test-result').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Test Telegram connection
     */
    public function ajax_test_connection(): void {
        check_ajax_referer('metoda_test_telegram', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $chat_id = sanitize_text_field($_POST['chat_id'] ?? '');

        if (empty($chat_id)) {
            wp_send_json_error(array('message' => 'Chat ID is required'));
        }

        if (!$this->is_configured()) {
            wp_send_json_error(array('message' => 'Bot token is not configured. Save settings first.'));
        }

        $result = $this->send_message($chat_id,
            "✅ *Test message from METODA*\n\n" .
            "Telegram integration is working correctly!\n" .
            "Time: " . current_time('Y-m-d H:i:s')
        );

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Test message sent successfully!'));
    }
}

// Backwards compatibility alias
class_alias('Metoda\\Auth\\Telegram', 'Metoda_Telegram');
