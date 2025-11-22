<?php
/**
 * Member Onboarding Class
 *
 * Handles the onboarding process for new members
 * Forces password change on first login and shows welcome screen
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_Onboarding {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Don't auto-create pages on init - only during plugin activation
        // add_action('init', array($this, 'register_onboarding_page'));
        add_shortcode('member_onboarding', array($this, 'render_onboarding'));
        add_action('wp_login', array($this, 'check_first_login'), 10, 2);
        add_action('wp_ajax_member_change_password', array($this, 'ajax_change_password'));
        add_action('wp_ajax_member_complete_onboarding', array($this, 'ajax_complete_onboarding'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_onboarding_assets'));

        // Onboarding redirect (with proper admin/manager checks)
        // ВРЕМЕННО ОТКЛЮЧЕНО: add_action('template_redirect', array($this, 'force_onboarding_redirect'));
    }

    /**
     * Register onboarding page
     */
    public function register_onboarding_page() {
        $page = get_page_by_path('member-onboarding');

        if (!$page) {
            wp_insert_post(array(
                'post_title' => 'Добро пожаловать',
                'post_name' => 'member-onboarding',
                'post_content' => '[member_onboarding]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
        }
    }

    /**
     * Check if this is user's first login
     */
    public function check_first_login($user_login, $user) {
        // KILL SWITCH: Отключение всех редиректов для диагностики
        if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
            return;
        }

        // ЯДЕРНАЯ ЗАЩИТА: User ID 1 никогда не проходит онбординг
        if (isset($user->ID) && $user->ID === 1) {
            return;
        }

        // ВАЖНО: Администраторы и менеджеры НЕ проходят онбординг
        if (in_array('administrator', (array) $user->roles) ||
            in_array('manager', (array) $user->roles)) {
            return;
        }

        // Check if user has member role
        if (!in_array('member', (array) $user->roles)) {
            return;
        }

        // Check if first login flag is set
        $first_login = get_user_meta($user->ID, '_member_first_login', true);

        // If not set, this is first login
        if (empty($first_login)) {
            update_user_meta($user->ID, '_member_needs_onboarding', '1');
            update_user_meta($user->ID, '_member_first_login', current_time('mysql'));
        }
    }

    /**
     * Force redirect to onboarding if needed
     */
    public function force_onboarding_redirect() {
        // KILL SWITCH: Отключение всех редиректов для диагностики
        if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        // Skip if in admin area or AJAX requests
        if (is_admin() || wp_doing_ajax() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        // Skip for REST API requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        // Skip for cron jobs
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        // ЯДЕРНАЯ ЗАЩИТА: User ID 1 никогда не редиректится на онбординг
        if (get_current_user_id() === 1) {
            return;
        }

        // ВАЖНО: Администраторы и менеджеры НЕ должны проходить онбординг
        $user = wp_get_current_user();
        if (current_user_can('manage_options') ||
            current_user_can('administrator') ||
            in_array('administrator', (array) $user->roles) ||
            in_array('manager', (array) $user->roles)) {
            // Убираем флаг онбординга для админов если он случайно установлен
            delete_user_meta(get_current_user_id(), '_member_needs_onboarding');
            return;
        }

        // Онбординг только для member и expert
        if (!in_array('member', (array) $user->roles) && !in_array('expert', (array) $user->roles)) {
            return;
        }

        // Skip if already on onboarding page or login page
        if (is_page('member-onboarding') ||
            $GLOBALS['pagenow'] === 'wp-login.php') {
            return;
        }

        $user_id = get_current_user_id();
        $needs_onboarding = get_user_meta($user_id, '_member_needs_onboarding', true);

        // Redirect to onboarding if flag is set
        if ($needs_onboarding === '1') {
            $onboarding_url = home_url('/member-onboarding/');
            if ($_SERVER['REQUEST_URI'] !== parse_url($onboarding_url, PHP_URL_PATH)) {
                wp_redirect($onboarding_url);
                exit;
            }
        }
    }

    /**
     * Enqueue onboarding assets
     */
    public function enqueue_onboarding_assets() {
        $current_post = get_post();
        if (is_page('member-onboarding') || (function_exists('has_shortcode') && $current_post && has_shortcode($current_post->post_content, 'member_onboarding'))) {
            wp_enqueue_style('member-onboarding', plugin_dir_url(dirname(__FILE__)) . 'assets/css/member-onboarding.css', array(), '1.0.0');
            wp_enqueue_script('member-onboarding', plugin_dir_url(dirname(__FILE__)) . 'assets/js/member-onboarding.js', array('jquery'), '1.0.0', true);

            wp_localize_script('member-onboarding', 'memberOnboarding', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('member_onboarding_nonce'),
                'dashboardUrl' => home_url('/member-dashboard/'),
            ));
        }
    }

    /**
     * Render onboarding shortcode
     */
    public function render_onboarding() {
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }

        $user_id = get_current_user_id();
        $needs_onboarding = get_user_meta($user_id, '_member_needs_onboarding', true);
        $current_user = wp_get_current_user();
        $member_id = Member_User_Link::get_current_user_member_id();

        // Check if user is a member
        if (!in_array('member', (array) $current_user->roles)) {
            return '<p>Эта страница доступна только участникам.</p>';
        }

        // If already completed onboarding, show success message
        if ($needs_onboarding !== '1') {
            return $this->render_completed_message();
        }

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'templates/member-onboarding.php';
        return ob_get_clean();
    }

    /**
     * Render completed message
     */
    private function render_completed_message() {
        ob_start();
        ?>
        <div class="onboarding-completed">
            <div class="completed-icon">✅</div>
            <h2>Вы уже прошли регистрацию!</h2>
            <p>Добро пожаловать в личный кабинет.</p>
            <a href="<?php echo home_url('/member-dashboard/'); ?>" class="btn btn-primary">Перейти в личный кабинет</a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Change password via AJAX
     */
    public function ajax_change_password() {
        check_ajax_referer('member_onboarding_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $user_id = get_current_user_id();
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Validate passwords
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => 'Все поля обязательны для заполнения'));
        }

        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => 'Новые пароли не совпадают'));
        }

        if (strlen($new_password) < 8) {
            wp_send_json_error(array('message' => 'Пароль должен содержать минимум 8 символов'));
        }

        // Check current password
        $user = get_user_by('id', $user_id);
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'Текущий пароль неверен'));
        }

        // Update password
        wp_set_password($new_password, $user_id);

        // Mark password as changed
        update_user_meta($user_id, '_member_password_changed', '1');

        wp_send_json_success(array('message' => 'Пароль успешно изменен!'));
    }

    /**
     * Complete onboarding via AJAX
     */
    public function ajax_complete_onboarding() {
        // Поддерживаем оба nonce: старый и новый
        $nonce_valid = check_ajax_referer('member_onboarding_nonce', 'nonce', false) ||
                      check_ajax_referer('member_login_nonce', 'nonce', false);

        if (!$nonce_valid) {
            wp_send_json_error(array('message' => 'Ошибка безопасности'));
        }

        // Новый онбординг с Access Code (пользователь может быть не залогинен)
        if (!is_user_logged_in() && isset($_POST['member_id'])) {
            $this->ajax_complete_access_code_onboarding();
            return;
        }

        // Старый онбординг (смена пароля после первого входа)
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $user_id = get_current_user_id();

        // Check if password was changed
        $password_changed = get_user_meta($user_id, '_member_password_changed', true);
        if ($password_changed !== '1') {
            wp_send_json_error(array('message' => 'Сначала необходимо сменить пароль'));
        }

        // Mark onboarding as completed
        delete_user_meta($user_id, '_member_needs_onboarding');
        update_user_meta($user_id, '_member_onboarding_completed', current_time('mysql'));

        wp_send_json_success(array(
            'message' => 'Добро пожаловать!',
            'redirect' => home_url('/member-dashboard/')
        ));
    }

    /**
     * Complete onboarding for Access Code users (NEW)
     */
    private function ajax_complete_access_code_onboarding() {
        $member_id = intval($_POST['member_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $login_method = sanitize_text_field($_POST['login_method'] ?? 'password');

        // Валидация member_id
        if (!$member_id) {
            wp_send_json_error(array('message' => 'Неверный ID участника'));
        }

        // Проверяем что member существует
        $member = get_post($member_id);
        if (!$member || $member->post_type !== 'members') {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }

        // Валидация email
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Некорректный email'));
        }

        // Проверка уникальности email
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Этот email уже используется'));
        }

        // Валидация пароля
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Пароль должен содержать минимум 8 символов'));
        }

        if ($password !== $password_confirm) {
            wp_send_json_error(array('message' => 'Пароли не совпадают'));
        }

        // Валидация метода входа
        if (!in_array($login_method, array('password', 'otp'))) {
            $login_method = 'password';
        }

        // Создание WordPress пользователя
        $member_name = get_post_meta($member_id, 'member_name', true);
        $username = sanitize_user($email); // Используем email как username

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Ошибка создания пользователя: ' . $user_id->get_error_message()));
        }

        // Установка роли
        $user = new WP_User($user_id);
        $user->set_role('member');

        // Установка display_name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $member_name,
            'first_name' => $member_name
        ));

        // Связка user_id с member_id
        update_post_meta($member_id, 'user_id', $user_id);
        update_user_meta($user_id, 'member_id', $member_id);

        // Сохранение метода входа
        update_user_meta($user_id, 'login_method', $login_method);

        // Отметка что онбординг завершён
        update_user_meta($user_id, 'onboarding_completed', true);
        update_user_meta($user_id, 'onboarding_date', current_time('mysql'));

        // Удаление Access Code (одноразовый)
        delete_post_meta($member_id, '_access_code');
        delete_post_meta($member_id, '_access_code_used');
        update_post_meta($member_id, '_access_code_used_date', current_time('mysql'));

        // Авторизация пользователя
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Отправка приветственного email
        $this->send_welcome_email($user_id, $email, $member_name);

        wp_send_json_success(array(
            'message' => 'Регистрация завершена! Добро пожаловать!',
            'redirect' => home_url('/member-dashboard/')
        ));
    }

    /**
     * Отправка приветственного письма после регистрации
     */
    private function send_welcome_email($user_id, $email, $name) {
        $subject = 'Добро пожаловать в ассоциацию Метода!';
        $message = '<html><body>';
        $message .= '<h2>Здравствуйте, ' . esc_html($name) . '!</h2>';
        $message .= '<p>Ваш аккаунт успешно создан.</p>';
        $message .= '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
        $message .= '<p><a href="' . home_url('/member-login/') . '">Войти в личный кабинет</a></p>';
        $message .= '</body></html>';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Get onboarding step for user
     */
    public static function get_user_step($user_id) {
        $password_changed = get_user_meta($user_id, '_member_password_changed', true);

        if ($password_changed !== '1') {
            return 'password';
        }

        return 'welcome';
    }

    /**
     * Check if user needs onboarding
     */
    public static function user_needs_onboarding($user_id) {
        $needs_onboarding = get_user_meta($user_id, '_member_needs_onboarding', true);
        return $needs_onboarding === '1';
    }
}
