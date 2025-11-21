<?php
/**
 * Login Handler
 *
 * Coordinates login process and integrates with OTP/Access Codes systems
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Login {

    /**
     * Constructor
     */
    public function __construct() {
        // Login redirect filter
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);

        // Logout redirect
        add_action('wp_logout', array($this, 'logout_redirect'));

        // Customize login page
        add_action('login_enqueue_scripts', array($this, 'custom_login_styles'));

        // Hide admin bar for members
        add_action('after_setup_theme', array($this, 'hide_admin_bar_for_members'));

        // AJAX for login form
        add_action('wp_ajax_nopriv_member_password_login', array($this, 'ajax_password_login'));
    }

    /**
     * Redirect after login based on user role
     *
     * @param string $redirect_to Default redirect URL
     * @param string $requested_redirect Requested redirect URL
     * @param WP_User|WP_Error $user User object or error
     * @return string Redirect URL
     */
    public function login_redirect($redirect_to, $requested_redirect, $user) {
        // Skip if kill switch is enabled
        if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
            return $redirect_to;
        }

        if (!is_a($user, 'WP_User')) {
            return $redirect_to;
        }

        // Administrators go to admin dashboard
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }

        // Managers go to manager panel
        if (in_array('manager', $user->roles)) {
            return home_url('/manager-panel/');
        }

        // Check if user needs onboarding
        if (class_exists('Member_Onboarding') && Member_Onboarding::user_needs_onboarding($user->ID)) {
            return home_url('/member-onboarding/');
        }

        // Regular users go to member dashboard
        return home_url('/member-dashboard/');
    }

    /**
     * Redirect after logout
     */
    public function logout_redirect() {
        wp_redirect(home_url('/login/'));
        exit;
    }

    /**
     * Add custom styles to default WordPress login page
     */
    public function custom_login_styles() {
        ?>
        <style>
            .login h1 a {
                background-image: url('<?php echo METODA_URL; ?>assets/images/logo.png');
                background-size: contain;
                width: 200px;
                height: 80px;
            }
            .login #nav a, .login #backtoblog a {
                color: #667eea !important;
            }
            .login .button-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                border: none !important;
            }
        </style>
        <?php
    }

    /**
     * Hide admin bar for member role
     */
    public function hide_admin_bar_for_members() {
        if (!current_user_can('edit_posts')) {
            show_admin_bar(false);
        }
    }

    /**
     * AJAX handler for password-based login
     *
     * Alternative to OTP login for users who prefer password
     */
    public function ajax_password_login() {
        check_ajax_referer('member_login_nonce', 'nonce');

        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate input
        if (!is_email($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Заполните все поля'));
        }

        // Find user by email
        $user = get_user_by('email', $email);

        if (!$user) {
            wp_send_json_error(array('message' => 'Пользователь с таким email не найден'));
        }

        // Check if user has password login method enabled
        $login_method = get_user_meta($user->ID, 'login_method', true);

        if ($login_method === 'otp') {
            wp_send_json_error(array(
                'message' => 'Для вашего аккаунта настроен вход по коду из email. Используйте вкладку "Код на почту".',
                'method' => 'otp'
            ));
        }

        // Authenticate
        $authenticated = wp_authenticate($email, $password);

        if (is_wp_error($authenticated)) {
            wp_send_json_error(array('message' => 'Неверный email или пароль'));
        }

        // Log user in
        wp_clear_auth_cookie();
        wp_set_current_user($authenticated->ID);
        wp_set_auth_cookie($authenticated->ID, true);

        // Get redirect URL
        $redirect_url = $this->get_redirect_url($authenticated);

        wp_send_json_success(array(
            'message' => 'Вход выполнен успешно!',
            'redirect' => $redirect_url
        ));
    }

    /**
     * Get redirect URL for user after login
     *
     * @param WP_User $user User object
     * @return string Redirect URL
     */
    private function get_redirect_url($user) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }

        if (in_array('manager', $user->roles)) {
            return home_url('/manager-panel/');
        }

        // Check onboarding
        if (class_exists('Member_Onboarding') && Member_Onboarding::user_needs_onboarding($user->ID)) {
            return home_url('/member-onboarding/');
        }

        return home_url('/member-dashboard/');
    }

    /**
     * Check if email has associated member
     *
     * @param string $email Email address
     * @return int|false Member ID or false
     */
    public static function get_member_by_email($email) {
        $user = get_user_by('email', $email);

        if ($user) {
            $member_id = get_user_meta($user->ID, 'member_id', true);
            if ($member_id) {
                return intval($member_id);
            }
        }

        return false;
    }

    /**
     * Get user's preferred login method
     *
     * @param int $user_id User ID
     * @return string 'password' or 'otp'
     */
    public static function get_login_method($user_id) {
        $method = get_user_meta($user_id, 'login_method', true);
        return in_array($method, array('password', 'otp')) ? $method : 'password';
    }
}
