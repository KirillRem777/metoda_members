<?php
/**
 * OTP Authentication System
 *
 * Handles One-Time Password authentication for member login
 * Supports Email and Telegram delivery channels
 *
 * @package Metoda_Members
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_OTP_Auth
 *
 * Manages OTP generation, delivery, verification, and device remembering
 */
class Metoda_OTP_Auth {

    /**
     * OTP code length
     */
    const OTP_LENGTH = 6;

    /**
     * OTP expiration time in seconds (5 minutes)
     */
    const OTP_EXPIRY = 300;

    /**
     * Device remember duration in seconds (30 days)
     */
    const DEVICE_REMEMBER = 2592000;

    /**
     * Maximum OTP attempts
     */
    const MAX_ATTEMPTS = 5;

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress authentication
        add_action('wp_authenticate', array($this, 'check_otp_requirement'), 30, 2);
        add_filter('authenticate', array($this, 'verify_otp'), 40, 3);

        // AJAX handlers
        add_action('wp_ajax_nopriv_request_otp', array($this, 'ajax_request_otp'));
        add_action('wp_ajax_nopriv_verify_otp_login', array($this, 'ajax_verify_otp_login'));
        add_action('wp_ajax_nopriv_resend_otp', array($this, 'ajax_resend_otp'));

        // Shortcodes
        add_shortcode('metoda_otp_login', array($this, 'render_otp_login_form'));
    }

    /**
     * Check if user requires OTP authentication
     *
     * @param string $username Username
     * @param string $password Password
     * @return void
     */
    public function check_otp_requirement($username, $password) {
        if (empty($username) || empty($password)) {
            return;
        }

        // Get user
        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            return;
        }

        // Check if OTP enabled for this user
        $otp_enabled = get_user_meta($user->ID, 'otp_enabled', true);
        if ($otp_enabled !== '1') {
            return;
        }

        // Check if device is remembered
        if ($this->is_device_remembered($user->ID)) {
            return;
        }

        // Verify password first
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            return;
        }

        // Generate and send OTP
        $result = $this->generate_and_send_otp($user->ID);

        if (is_wp_error($result)) {
            // Store error for later display
            set_transient('otp_error_' . $user->ID, $result->get_error_message(), 60);
        }

        // Store pending OTP session
        set_transient('otp_pending_' . $user->ID, array(
            'username' => $username,
            'password' => $password,
            'time' => time()
        ), self::OTP_EXPIRY);
    }

    /**
     * Verify OTP during authentication
     *
     * @param WP_User|WP_Error|null $user User or error
     * @param string $username Username
     * @param string $password Password
     * @return WP_User|WP_Error
     */
    public function verify_otp($user, $username, $password) {
        // Skip if already error
        if (is_wp_error($user)) {
            return $user;
        }

        // Skip if no user
        if (!$user || !isset($user->ID)) {
            return $user;
        }

        // Check if OTP enabled
        $otp_enabled = get_user_meta($user->ID, 'otp_enabled', true);
        if ($otp_enabled !== '1') {
            return $user;
        }

        // Check if device is remembered
        if ($this->is_device_remembered($user->ID)) {
            return $user;
        }

        // Check if OTP pending
        $pending = get_transient('otp_pending_' . $user->ID);
        if (!$pending) {
            return $user;
        }

        // Require OTP - block login until verified
        return new WP_Error('otp_required', 'Требуется ввод OTP кода');
    }

    /**
     * Generate and send OTP to user
     *
     * @param int $user_id User ID
     * @return bool|WP_Error Success or error
     */
    public function generate_and_send_otp($user_id) {
        // Generate OTP code
        $otp_code = $this->generate_otp();

        // Store OTP with expiration
        $otp_data = array(
            'code' => $otp_code,
            'generated' => time(),
            'attempts' => 0
        );

        set_transient('otp_code_' . $user_id, $otp_data, self::OTP_EXPIRY);

        // Get delivery method
        $delivery_method = get_user_meta($user_id, 'otp_delivery', true) ?: 'email';

        // Send OTP
        if ($delivery_method === 'telegram') {
            return $this->send_otp_telegram($user_id, $otp_code);
        } else {
            return $this->send_otp_email($user_id, $otp_code);
        }
    }

    /**
     * Generate random OTP code
     *
     * @return string 6-digit OTP code
     */
    private function generate_otp() {
        return str_pad(wp_rand(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via Email
     *
     * @param int $user_id User ID
     * @param string $otp_code OTP code
     * @return bool|WP_Error
     */
    private function send_otp_email($user_id, $otp_code) {
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', 'Пользователь не найден');
        }

        // Get email address
        $custom_email = get_user_meta($user_id, 'notify_custom_email', true);
        $to = !empty($custom_email) ? $custom_email : $user->user_email;

        // Build email
        $subject = '🔐 Код доступа - Metoda Members';

        $message = "Здравствуйте, {$user->display_name}!\n\n";
        $message .= "Ваш одноразовый код для входа:\n\n";
        $message .= "──────────────────────────────────────\n";
        $message .= "         {$otp_code}\n";
        $message .= "──────────────────────────────────────\n\n";
        $message .= "Код действителен в течение 5 минут.\n\n";
        $message .= "Если вы не запрашивали этот код, проигнорируйте это письмо.\n\n";
        $message .= "──────────────────────────────────────\n";
        $message .= "С уважением,\n";
        $message .= "Команда Metoda Members\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: Metoda Members <' . get_option('admin_email') . '>'
        );

        $sent = wp_mail($to, $subject, $message, $headers);

        if (!$sent) {
            return new WP_Error('send_failed', 'Не удалось отправить код на email');
        }

        return true;
    }

    /**
     * Send OTP via Telegram
     *
     * @param int $user_id User ID
     * @param string $otp_code OTP code
     * @return bool|WP_Error
     */
    private function send_otp_telegram($user_id, $otp_code) {
        $chat_id = get_user_meta($user_id, 'telegram_chat_id', true);
        if (empty($chat_id)) {
            return new WP_Error('telegram_not_connected', 'Telegram не подключен');
        }

        $bot_token = get_option('metoda_telegram_bot_token');
        if (empty($bot_token)) {
            return new WP_Error('telegram_not_configured', 'Telegram бот не настроен');
        }

        $user = get_userdata($user_id);

        $message = "🔐 <b>Код доступа</b>\n\n";
        $message .= "Здравствуйте, " . esc_html($user->display_name) . "!\n\n";
        $message .= "Ваш одноразовый код для входа:\n\n";
        $message .= "<code>{$otp_code}</code>\n\n";
        $message .= "Код действителен в течение 5 минут.\n\n";
        $message .= "Если вы не запрашивали этот код, проигнорируйте это сообщение.";

        $response = wp_remote_post("https://api.telegram.org/bot{$bot_token}/sendMessage", array(
            'body' => array(
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML'
            )
        ));

        if (is_wp_error($response)) {
            return new WP_Error('telegram_failed', 'Ошибка отправки в Telegram: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['ok']) || !$body['ok']) {
            return new WP_Error('telegram_failed', 'Telegram API вернул ошибку');
        }

        return true;
    }

    /**
     * Verify OTP code
     *
     * @param int $user_id User ID
     * @param string $code OTP code entered by user
     * @return bool|WP_Error
     */
    public function verify_otp_code($user_id, $code) {
        // Get stored OTP data
        $otp_data = get_transient('otp_code_' . $user_id);

        if (!$otp_data) {
            return new WP_Error('expired', 'Код истек. Запросите новый код.');
        }

        // Check attempts
        if ($otp_data['attempts'] >= self::MAX_ATTEMPTS) {
            delete_transient('otp_code_' . $user_id);
            delete_transient('otp_pending_' . $user_id);
            return new WP_Error('max_attempts', 'Превышено количество попыток. Запросите новый код.');
        }

        // Increment attempts
        $otp_data['attempts']++;
        set_transient('otp_code_' . $user_id, $otp_data, self::OTP_EXPIRY);

        // Verify code
        if ($code !== $otp_data['code']) {
            return new WP_Error('invalid_code', 'Неверный код. Осталось попыток: ' . (self::MAX_ATTEMPTS - $otp_data['attempts']));
        }

        // Code is valid - clear OTP data
        delete_transient('otp_code_' . $user_id);
        delete_transient('otp_pending_' . $user_id);

        // Send login notification
        $this->send_login_notification($user_id);

        return true;
    }

    /**
     * Remember device for user
     *
     * @param int $user_id User ID
     * @return string Device token
     */
    public function remember_device($user_id) {
        $device_token = wp_generate_password(32, false);
        $device_fingerprint = $this->get_device_fingerprint();

        $device_data = array(
            'token' => $device_token,
            'fingerprint' => $device_fingerprint,
            'created' => time(),
            'last_used' => time()
        );

        // Store device
        $devices = get_user_meta($user_id, 'remembered_devices', true) ?: array();
        $devices[$device_token] = $device_data;
        update_user_meta($user_id, 'remembered_devices', $devices);

        // Set cookie
        setcookie('metoda_device_token', $device_token, time() + self::DEVICE_REMEMBER, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        return $device_token;
    }

    /**
     * Check if current device is remembered
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_device_remembered($user_id) {
        if (empty($_COOKIE['metoda_device_token'])) {
            return false;
        }

        $device_token = sanitize_text_field($_COOKIE['metoda_device_token']);
        $devices = get_user_meta($user_id, 'remembered_devices', true) ?: array();

        if (!isset($devices[$device_token])) {
            return false;
        }

        $device_data = $devices[$device_token];

        // Check expiration
        if (time() - $device_data['created'] > self::DEVICE_REMEMBER) {
            unset($devices[$device_token]);
            update_user_meta($user_id, 'remembered_devices', $devices);
            return false;
        }

        // Verify fingerprint
        $current_fingerprint = $this->get_device_fingerprint();
        if ($device_data['fingerprint'] !== $current_fingerprint) {
            return false;
        }

        // Update last used
        $devices[$device_token]['last_used'] = time();
        update_user_meta($user_id, 'remembered_devices', $devices);

        return true;
    }

    /**
     * Get device fingerprint
     *
     * @return string Device fingerprint hash
     */
    private function get_device_fingerprint() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

        return hash('sha256', $user_agent . $ip_address);
    }

    /**
     * Send login notification
     *
     * @param int $user_id User ID
     * @return void
     */
    private function send_login_notification($user_id) {
        $user = get_userdata($user_id);
        $login_time = current_time('d.m.Y H:i');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Prepare notification data
        $data = array(
            'type' => 'login',
            'title' => 'Вход в аккаунт',
            'content' => "Выполнен вход в ваш аккаунт.\n\nВремя: {$login_time}\nIP: {$ip_address}\nУстройство: {$user_agent}\n\nЕсли это были не вы, немедленно смените пароль.",
            'allow_reply' => false
        );

        // Send via enabled channels
        $email_enabled = get_user_meta($user_id, 'notify_channel_email', true);
        if ($email_enabled === '1') {
            $email_notifier = new Metoda_Notification_Email();
            $email_notifier->send($user_id, $data);
        }

        $telegram_enabled = get_user_meta($user_id, 'notify_channel_telegram', true);
        if ($telegram_enabled === '1') {
            $telegram_notifier = new Metoda_Notification_Telegram();
            $telegram_notifier->send($user_id, $data);
        }
    }

    /**
     * AJAX: Request OTP
     */
    public function ajax_request_otp() {
        check_ajax_referer('metoda_otp_nonce', 'nonce');

        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Заполните все поля'));
        }

        // Get user
        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            wp_send_json_error(array('message' => 'Неверный логин или пароль'));
        }

        // Verify password
        if (!wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_send_json_error(array('message' => 'Неверный логин или пароль'));
        }

        // Check if OTP enabled
        $otp_enabled = get_user_meta($user->ID, 'otp_enabled', true);
        if ($otp_enabled !== '1') {
            wp_send_json_error(array('message' => 'OTP не включен для этого пользователя'));
        }

        // Generate and send OTP
        $result = $this->generate_and_send_otp($user->ID);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Get delivery method for display
        $delivery_method = get_user_meta($user->ID, 'otp_delivery', true) ?: 'email';
        $delivery_label = $delivery_method === 'telegram' ? 'Telegram' : 'Email';

        wp_send_json_success(array(
            'message' => "Код отправлен на {$delivery_label}",
            'user_id' => $user->ID,
            'delivery_method' => $delivery_method
        ));
    }

    /**
     * AJAX: Verify OTP and login
     */
    public function ajax_verify_otp_login() {
        check_ajax_referer('metoda_otp_nonce', 'nonce');

        $username = sanitize_text_field($_POST['username'] ?? '');
        $otp_code = sanitize_text_field($_POST['otp_code'] ?? '');
        $remember_device = isset($_POST['remember_device']) ? (bool) $_POST['remember_device'] : false;

        if (empty($username) || empty($otp_code)) {
            wp_send_json_error(array('message' => 'Заполните все поля'));
        }

        // Get user
        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            wp_send_json_error(array('message' => 'Пользователь не найден'));
        }

        // Verify OTP
        $result = $this->verify_otp_code($user->ID, $otp_code);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // OTP verified - log user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        // Remember device if requested
        if ($remember_device) {
            $this->remember_device($user->ID);
        }

        wp_send_json_success(array(
            'message' => 'Вход выполнен успешно',
            'redirect_url' => home_url('/member-dashboard')
        ));
    }

    /**
     * AJAX: Resend OTP
     */
    public function ajax_resend_otp() {
        check_ajax_referer('metoda_otp_nonce', 'nonce');

        $username = sanitize_text_field($_POST['username'] ?? '');

        if (empty($username)) {
            wp_send_json_error(array('message' => 'Имя пользователя не указано'));
        }

        // Get user
        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            wp_send_json_error(array('message' => 'Пользователь не найден'));
        }

        // Clear old OTP
        delete_transient('otp_code_' . $user->ID);

        // Generate and send new OTP
        $result = $this->generate_and_send_otp($user->ID);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $delivery_method = get_user_meta($user->ID, 'otp_delivery', true) ?: 'email';
        $delivery_label = $delivery_method === 'telegram' ? 'Telegram' : 'Email';

        wp_send_json_success(array('message' => "Новый код отправлен на {$delivery_label}"));
    }

    /**
     * Render OTP login form shortcode
     *
     * @return string Form HTML
     */
    public function render_otp_login_form() {
        if (is_user_logged_in()) {
            return '<p>Вы уже авторизованы. <a href="' . wp_logout_url() . '">Выйти</a></p>';
        }

        ob_start();
        ?>
        <div class="metoda-otp-login-form">
            <form id="otp-login-form" method="post">
                <?php wp_nonce_field('metoda_otp_nonce', 'nonce'); ?>

                <div class="form-step" id="step-credentials">
                    <h3>Вход в личный кабинет</h3>
                    <div class="form-group">
                        <label for="username">Email или логин</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="button" id="btn-request-otp" class="btn-primary">Получить код</button>
                </div>

                <div class="form-step hidden" id="step-otp">
                    <h3>Введите код</h3>
                    <p class="info-message">Код отправлен на ваш <span id="delivery-method"></span></p>
                    <div class="form-group">
                        <label for="otp_code">Код из сообщения</label>
                        <input type="text" id="otp_code" name="otp_code" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember_device" value="1">
                            Запомнить это устройство на 30 дней
                        </label>
                    </div>
                    <button type="submit" id="btn-verify-otp" class="btn-primary">Войти</button>
                    <button type="button" id="btn-resend-otp" class="btn-link">Отправить код повторно</button>
                </div>

                <div id="form-message"></div>
            </form>
        </div>

        <script>
        (function() {
            const form = document.getElementById('otp-login-form');
            const stepCredentials = document.getElementById('step-credentials');
            const stepOtp = document.getElementById('step-otp');
            const btnRequestOtp = document.getElementById('btn-request-otp');
            const btnVerifyOtp = document.getElementById('btn-verify-otp');
            const btnResendOtp = document.getElementById('btn-resend-otp');
            const messageDiv = document.getElementById('form-message');
            const deliverySpan = document.getElementById('delivery-method');

            let currentUsername = '';

            btnRequestOtp.addEventListener('click', function() {
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;

                if (!username || !password) {
                    showMessage('Заполните все поля', 'error');
                    return;
                }

                currentUsername = username;
                btnRequestOtp.disabled = true;
                btnRequestOtp.textContent = 'Отправка...';

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'request_otp',
                        nonce: document.querySelector('[name="nonce"]').value,
                        username: username,
                        password: password
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        deliverySpan.textContent = data.data.delivery_method === 'telegram' ? 'Telegram' : 'Email';
                        stepCredentials.classList.add('hidden');
                        stepOtp.classList.remove('hidden');
                        showMessage(data.data.message, 'success');
                    } else {
                        showMessage(data.data.message, 'error');
                    }
                })
                .catch(err => {
                    showMessage('Ошибка отправки запроса', 'error');
                })
                .finally(() => {
                    btnRequestOtp.disabled = false;
                    btnRequestOtp.textContent = 'Получить код';
                });
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const otpCode = document.getElementById('otp_code').value;
                const rememberDevice = document.querySelector('[name="remember_device"]').checked;

                btnVerifyOtp.disabled = true;
                btnVerifyOtp.textContent = 'Проверка...';

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'verify_otp_login',
                        nonce: document.querySelector('[name="nonce"]').value,
                        username: currentUsername,
                        otp_code: otpCode,
                        remember_device: rememberDevice ? '1' : '0'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.data.message, 'success');
                        setTimeout(() => {
                            window.location.href = data.data.redirect_url;
                        }, 1000);
                    } else {
                        showMessage(data.data.message, 'error');
                        btnVerifyOtp.disabled = false;
                        btnVerifyOtp.textContent = 'Войти';
                    }
                })
                .catch(err => {
                    showMessage('Ошибка проверки кода', 'error');
                    btnVerifyOtp.disabled = false;
                    btnVerifyOtp.textContent = 'Войти';
                });
            });

            btnResendOtp.addEventListener('click', function() {
                btnResendOtp.disabled = true;

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'resend_otp',
                        nonce: document.querySelector('[name="nonce"]').value,
                        username: currentUsername
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.data.message, 'success');
                    } else {
                        showMessage(data.data.message, 'error');
                    }
                })
                .finally(() => {
                    setTimeout(() => {
                        btnResendOtp.disabled = false;
                    }, 3000);
                });
            });

            function showMessage(message, type) {
                messageDiv.textContent = message;
                messageDiv.className = 'message ' + type;
                setTimeout(() => {
                    messageDiv.textContent = '';
                    messageDiv.className = '';
                }, 5000);
            }
        })();
        </script>

        <style>
        .metoda-otp-login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-step {
            display: block;
        }
        .form-step.hidden {
            display: none;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary:hover {
            background: #0052a3;
        }
        .btn-link {
            background: none;
            border: none;
            color: #0066cc;
            cursor: pointer;
            text-decoration: underline;
            margin-top: 10px;
        }
        .info-message {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}
