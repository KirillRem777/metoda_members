<?php
/**
 * Member OTP Class
 *
 * Handles One-Time Password authentication for members
 * - Generates 6-digit OTP codes
 * - Sends codes via email
 * - Validates codes with 10-minute expiry
 * - Provides admin interface for email template editing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_OTP {

    /**
     * Initialize the class
     */
    public function __construct() {
        // AJAX handlers для неавторизованных пользователей
        add_action('wp_ajax_nopriv_send_otp_code', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_nopriv_verify_otp_login', array($this, 'ajax_verify_otp'));

        // AJAX handlers для авторизованных (для тестирования)
        add_action('wp_ajax_send_otp_code', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_verify_otp_login', array($this, 'ajax_verify_otp'));

        // Админ меню для редактирования шаблона
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_save_otp_email_template', array($this, 'save_email_template'));

        // Очистка истёкших OTP кодов (раз в час)
        add_action('init', array($this, 'schedule_cleanup'));
        add_action('metoda_cleanup_expired_otps', array($this, 'cleanup_expired_otps'));
    }

    /**
     * Генерация 6-значного OTP кода
     *
     * @param int $user_id User ID
     * @return string|false 6-digit OTP code or false on failure
     */
    public function generate_otp($user_id) {
        if (!$user_id) {
            return false;
        }

        // Генерируем 6-значный код
        $otp = sprintf('%06d', wp_rand(100000, 999999));

        // Хешируем код для безопасного хранения
        $hashed_otp = wp_hash_password($otp);

        // Устанавливаем срок действия (10 минут)
        $expires = time() + 600;

        // Сохраняем в user meta
        update_user_meta($user_id, 'otp_code', $hashed_otp);
        update_user_meta($user_id, 'otp_expires', $expires);

        return $otp;
    }

    /**
     * Проверка OTP кода
     *
     * @param int $user_id User ID
     * @param string $otp OTP code to verify
     * @return bool True if valid, false otherwise
     */
    public function verify_otp($user_id, $otp) {
        if (!$user_id || !$otp) {
            return false;
        }

        $stored_hash = get_user_meta($user_id, 'otp_code', true);
        $expires = get_user_meta($user_id, 'otp_expires', true);

        // Проверка существования кода
        if (empty($stored_hash) || empty($expires)) {
            return false;
        }

        // Проверка срока действия
        if (time() > $expires) {
            $this->delete_otp($user_id);
            return false;
        }

        // Проверка совпадения кода
        if (!wp_check_password($otp, $stored_hash)) {
            return false;
        }

        // Код валиден - удаляем его
        $this->delete_otp($user_id);

        return true;
    }

    /**
     * Удаление OTP кода пользователя
     *
     * @param int $user_id User ID
     */
    public function delete_otp($user_id) {
        delete_user_meta($user_id, 'otp_code');
        delete_user_meta($user_id, 'otp_expires');
    }

    /**
     * Отправка OTP кода на email
     *
     * @param int $user_id User ID
     * @param string $otp OTP code
     * @return bool True if sent successfully
     */
    public function send_otp_email($user_id, $otp) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $to = $user->user_email;
        $subject = $this->get_email_subject();
        $message = $this->get_email_body($user, $otp);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Получить тему письма (редактируется в админке)
     *
     * @return string Email subject
     */
    private function get_email_subject() {
        $default = 'Код для входа в личный кабинет Метода';
        return get_option('metoda_otp_email_subject', $default);
    }

    /**
     * Получить тело письма (редактируется в админке)
     *
     * @param WP_User $user User object
     * @param string $otp OTP code
     * @return string Email body HTML
     */
    private function get_email_body($user, $otp) {
        $template = get_option('metoda_otp_email_template', '');

        // Если шаблон не задан, используем дефолтный
        if (empty($template)) {
            $template = $this->get_default_email_template();
        }

        // Замена плейсхолдеров
        $replacements = array(
            '{user_name}' => $user->display_name,
            '{otp_code}' => $otp,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url(),
        );

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Дефолтный шаблон email
     *
     * @return string Default email template HTML
     */
    private function get_default_email_template() {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0066cc 0%, #ff6600 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .otp-box { background: white; border: 2px dashed #0066cc; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .otp-code { font-size: 32px; font-weight: bold; color: #0066cc; letter-spacing: 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Код для входа</h1>
        </div>
        <div class="content">
            <p>Здравствуйте, <strong>{user_name}</strong>!</p>
            <p>Вы запросили одноразовый код для входа в личный кабинет ассоциации Метода.</p>

            <div class="otp-box">
                <p style="margin: 0; font-size: 14px; color: #666;">Ваш код:</p>
                <div class="otp-code">{otp_code}</div>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">Действителен 10 минут</p>
            </div>

            <p><strong>Важно:</strong> Никому не сообщайте этот код. Сотрудники Метода никогда не попросят вас назвать код.</p>
            <p>Если вы не запрашивали код, просто проигнорируйте это письмо.</p>
        </div>
        <div class="footer">
            <p>&copy; {site_name} | <a href="{site_url}">{site_url}</a></p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * AJAX handler: Отправка OTP кода
     */
    public function ajax_send_otp() {
        // Проверка nonce
        if (!check_ajax_referer('member_login_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Ошибка безопасности. Обновите страницу.'));
        }

        $email = sanitize_email($_POST['email'] ?? '');

        // Валидация email
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Некорректный email адрес.'));
        }

        // Поиск пользователя по email
        $user = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error(array('message' => 'Пользователь с таким email не найден.'));
        }

        // Проверка что у пользователя включён метод OTP
        $login_method = get_user_meta($user->ID, 'login_method', true);

        if ($login_method !== 'otp') {
            wp_send_json_error(array('message' => 'Вход по коду на почту не настроен для вашего аккаунта.'));
        }

        // Генерация и отправка OTP
        $otp = $this->generate_otp($user->ID);

        if (!$otp) {
            wp_send_json_error(array('message' => 'Ошибка генерации кода. Попробуйте позже.'));
        }

        $sent = $this->send_otp_email($user->ID, $otp);

        if (!$sent) {
            wp_send_json_error(array('message' => 'Ошибка отправки письма. Попробуйте позже.'));
        }

        wp_send_json_success(array(
            'message' => 'Код отправлен на ваш email. Проверьте почту.',
            'email' => $email
        ));
    }

    /**
     * AJAX handler: Проверка OTP и авторизация
     */
    public function ajax_verify_otp() {
        // Проверка nonce
        if (!check_ajax_referer('member_login_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Ошибка безопасности. Обновите страницу.'));
        }

        $email = sanitize_email($_POST['email'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');

        // Валидация
        if (!is_email($email) || empty($otp)) {
            wp_send_json_error(array('message' => 'Заполните все поля.'));
        }

        // Проверка формата OTP (6 цифр)
        if (!preg_match('/^\d{6}$/', $otp)) {
            wp_send_json_error(array('message' => 'Код должен состоять из 6 цифр.'));
        }

        // Поиск пользователя
        $user = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error(array('message' => 'Пользователь не найден.'));
        }

        // Проверка OTP
        if (!$this->verify_otp($user->ID, $otp)) {
            wp_send_json_error(array('message' => 'Неверный или истёкший код. Запросите новый.'));
        }

        // Авторизация пользователя
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        // Определяем redirect URL
        $redirect_url = $this->get_redirect_url($user);

        wp_send_json_success(array(
            'message' => 'Вход выполнен успешно!',
            'redirect' => $redirect_url
        ));
    }

    /**
     * Получить URL для редиректа после входа
     *
     * @param WP_User $user User object
     * @return string Redirect URL
     */
    private function get_redirect_url($user) {
        if (in_array('administrator', $user->roles) || user_can($user->ID, 'manage_options')) {
            return admin_url();
        }

        if (in_array('manager', $user->roles)) {
            return home_url('/manager-panel/');
        }

        return home_url('/member-dashboard/');
    }

    /**
     * Очистка истёкших OTP кодов
     */
    public function cleanup_expired_otps() {
        global $wpdb;

        $current_time = time();

        // Получаем все user_id с истёкшими OTP
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta}
            WHERE meta_key = 'otp_expires'
            AND meta_value < %d",
            $current_time
        ));

        foreach ($results as $row) {
            $this->delete_otp($row->user_id);
        }
    }

    /**
     * Настройка расписания очистки
     */
    public function schedule_cleanup() {
        if (!wp_next_scheduled('metoda_cleanup_expired_otps')) {
            wp_schedule_event(time(), 'hourly', 'metoda_cleanup_expired_otps');
        }
    }

    /**
     * Добавить меню в админке
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=members',
            'Настройки OTP',
            'OTP Email',
            'manage_options',
            'metoda-otp-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Рендер страницы настроек
     */
    public function render_settings_page() {
        $subject = get_option('metoda_otp_email_subject', '');
        $template = get_option('metoda_otp_email_template', '');

        if (empty($subject)) {
            $subject = 'Код для входа в личный кабинет Метода';
        }

        if (empty($template)) {
            $template = $this->get_default_email_template();
        }
        ?>
        <div class="wrap">
            <h1>Настройки OTP Email шаблона</h1>
            <p>Настройте шаблон письма с одноразовым кодом для входа.</p>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('metoda_otp_settings', 'metoda_otp_nonce'); ?>
                <input type="hidden" name="action" value="save_otp_email_template">

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="otp_subject">Тема письма</label>
                        </th>
                        <td>
                            <input type="text"
                                   id="otp_subject"
                                   name="otp_subject"
                                   value="<?php echo esc_attr($subject); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="otp_template">Шаблон письма (HTML)</label>
                        </th>
                        <td>
                            <textarea id="otp_template"
                                      name="otp_template"
                                      rows="20"
                                      class="large-text code"><?php echo esc_textarea($template); ?></textarea>
                            <p class="description">
                                <strong>Доступные плейсхолдеры:</strong><br>
                                <code>{user_name}</code> - имя пользователя<br>
                                <code>{otp_code}</code> - 6-значный OTP код<br>
                                <code>{site_name}</code> - название сайта<br>
                                <code>{site_url}</code> - URL сайта
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Сохранить настройки'); ?>
            </form>

            <hr>

            <h2>Тестовая отправка</h2>
            <p>Отправьте тестовое письмо себе на почту для проверки шаблона.</p>
            <button type="button" class="button" id="send-test-otp">Отправить тестовое письмо</button>
            <div id="test-result" style="margin-top: 10px;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#send-test-otp').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('Отправка...');

                $.post(ajaxurl, {
                    action: 'send_test_otp_email',
                    nonce: '<?php echo wp_create_nonce('test_otp_email'); ?>'
                }, function(response) {
                    button.prop('disabled', false).text('Отправить тестовое письмо');

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
     * Сохранение настроек email шаблона
     */
    public function save_email_template() {
        // Проверка nonce
        if (!isset($_POST['metoda_otp_nonce']) || !wp_verify_nonce($_POST['metoda_otp_nonce'], 'metoda_otp_settings')) {
            wp_die('Ошибка безопасности');
        }

        // Проверка прав
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }

        $subject = sanitize_text_field($_POST['otp_subject'] ?? '');
        $template = wp_kses_post($_POST['otp_template'] ?? '');

        update_option('metoda_otp_email_subject', $subject);
        update_option('metoda_otp_email_template', $template);

        wp_redirect(add_query_arg(
            array(
                'post_type' => 'members',
                'page' => 'metoda-otp-settings',
                'updated' => 'true'
            ),
            admin_url('edit.php')
        ));
        exit;
    }
}
