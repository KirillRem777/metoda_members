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
        add_action('init', array($this, 'register_onboarding_page'));
        add_shortcode('member_onboarding', array($this, 'render_onboarding'));
        add_action('wp_login', array($this, 'check_first_login'), 10, 2);
        add_action('wp_ajax_member_change_password', array($this, 'ajax_change_password'));
        add_action('wp_ajax_member_complete_onboarding', array($this, 'ajax_complete_onboarding'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_onboarding_assets'));
        add_action('template_redirect', array($this, 'force_onboarding_redirect'));
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
        if (!is_user_logged_in()) {
            return;
        }

        // Skip if already on onboarding page or login page
        if (is_page('member-onboarding') ||
            $GLOBALS['pagenow'] === 'wp-login.php' ||
            (defined('DOING_AJAX') && DOING_AJAX)) {
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
        check_ajax_referer('member_onboarding_nonce', 'nonce');

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

// Initialize the class
new Member_Onboarding();
