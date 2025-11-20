<?php
/**
 * Member Dashboard Class
 *
 * Handles the personal cabinet functionality for members
 * Allows members to edit their profiles and manage materials
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_Dashboard {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Don't auto-create pages on init - only during plugin activation
        // add_action('init', array($this, 'register_dashboard_page'));
        add_shortcode('member_dashboard', array($this, 'render_dashboard'));
        add_action('wp_ajax_member_update_profile', array($this, 'ajax_update_profile'));
        add_action('wp_ajax_member_update_gallery', array($this, 'ajax_update_gallery'));
        add_action('wp_ajax_mark_onboarding_seen', array($this, 'ajax_mark_onboarding_seen'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_assets'));
    }

    /**
     * Register dashboard page on activation
     */
    public function register_dashboard_page() {
        // Check if page exists
        $page = get_page_by_path('member-dashboard');

        if (!$page) {
            // Create the page
            $page_id = wp_insert_post(array(
                'post_title' => 'Личный кабинет',
                'post_name' => 'member-dashboard',
                'post_content' => '[member_dashboard]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
        }
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueue_dashboard_assets() {
        $current_post = get_post();
        if (is_page('member-dashboard') || (function_exists('has_shortcode') && $current_post && has_shortcode($current_post->post_content, 'member_dashboard'))) {
            wp_enqueue_style('member-dashboard', plugin_dir_url(dirname(__FILE__)) . 'assets/css/member-dashboard.css', array(), '1.0.0');
            wp_enqueue_script('member-dashboard', plugin_dir_url(dirname(__FILE__)) . 'assets/js/member-dashboard.js', array('jquery'), '1.0.0', true);

            wp_localize_script('member-dashboard', 'memberDashboard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('member_dashboard_nonce'),
            ));

            // Enqueue WordPress media library
            wp_enqueue_media();

            // Enqueue onboarding for first-time users
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $onboarding_seen = get_user_meta($user_id, 'metoda_onboarding_seen', true);

                if (!$onboarding_seen) {
                    wp_enqueue_style('onboarding', plugin_dir_url(dirname(__FILE__)) . 'assets/css/onboarding.css', array(), '1.0.0');
                    wp_enqueue_script('onboarding', plugin_dir_url(dirname(__FILE__)) . 'assets/js/onboarding.js', array('jquery'), '1.0.0', true);

                    wp_localize_script('onboarding', 'onboardingData', array(
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('onboarding_nonce'),
                        'showOnboarding' => '1',
                    ));
                }
            }
        }
    }

    /**
     * Render dashboard shortcode
     */
    public function render_dashboard() {
        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        // Проверяем, админ ли смотрит чужой кабинет
        $is_admin = current_user_can('administrator');
        $viewing_member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : null;

        // Если админ указал member_id - пропускаем проверку своего member_id
        if ($is_admin && $viewing_member_id) {
            // Админ просматривает чужой кабинет

            // Проверяем существование member post
            $member_post = get_post($viewing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                return '<div style="padding: 40px; text-align: center; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; margin: 20px;">
                    <h3 style="color: #721c24;">❌ Участник не найден</h3>
                    <p style="color: #721c24;">Участник с ID ' . $viewing_member_id . ' не существует.</p>
                    <p><a href="' . admin_url('admin.php?page=metoda-activity-log') . '" style="color: #0066cc;">Вернуться к логам</a></p>
                </div>';
            }

            // ВАЖНО: Устанавливаем переменные ДО загрузки шаблона!
            $member_id = $viewing_member_id;
            $is_viewing_other = true;

            ob_start();
            include plugin_dir_path(dirname(__FILE__)) . 'templates/member-dashboard.php';
            return ob_get_clean();
        }

        // Для обычных пользователей проверяем наличие своего member_id
        $member_id = Member_User_Link::get_current_user_member_id();

        if (!$member_id) {
            // Если это админ без своего member_id и без параметра member_id
            if ($is_admin) {
                return '<div style="padding: 40px; text-align: center; background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; margin: 20px;">
                    <h3 style="color: #856404; margin-bottom: 10px;">⚠️ Режим администратора</h3>
                    <p style="color: #856404;">Укажите ID участника в URL для просмотра кабинета:</p>
                    <code style="background: #fff; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-top: 10px;">?member_id=XXX</code>
                    <p style="margin-top: 15px;"><a href="' . admin_url('admin.php?page=metoda-activity-log') . '" style="color: #0066cc;">Перейти к логам активности</a></p>
                </div>';
            }
            return $this->render_no_profile_message();
        }

        // Устанавливаем для обычного пользователя
        $is_viewing_other = false;

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'templates/member-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Render login message
     */
    private function render_login_message() {
        ob_start();
        ?>
        <div class="member-dashboard-message">
            <div class="message-icon">🔒</div>
            <h2>Требуется авторизация</h2>
            <p>Для доступа к личному кабинету необходимо войти в систему.</p>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary">Войти</a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render no profile message
     */
    private function render_no_profile_message() {
        ob_start();
        ?>
        <div class="member-dashboard-message">
            <div class="message-icon">👤</div>
            <h2>Профиль не найден</h2>
            <p>К вашей учетной записи не привязан профиль участника.</p>
            <p>Обратитесь к администратору для создания профиля.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Update profile via AJAX
     */
    public function ajax_update_profile() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        // Проверяем, редактирует ли админ чужой профиль
        $is_admin = current_user_can('administrator');
        $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

        if ($is_admin && $editing_member_id) {
            // Админ редактирует чужой профиль - проверяем существование
            $member_post = get_post($editing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
            $member_id = $editing_member_id;
        } else {
            // Обычный пользователь редактирует свой профиль
            $member_id = Member_User_Link::get_current_user_member_id();

            if (!$member_id || !Member_User_Link::can_user_edit_member($member_id)) {
                wp_send_json_error(array('message' => 'Нет прав на редактирование'));
            }
        }

        // Get and sanitize form data
        $fields = array(
            'member_position' => 'sanitize_text_field',
            'member_company' => 'sanitize_text_field',
            'member_email' => 'sanitize_email',
            'member_phone' => 'sanitize_text_field',
            'member_bio' => 'sanitize_textarea_field',
            'member_specialization' => 'sanitize_textarea_field',
            'member_experience' => 'sanitize_textarea_field',
            'member_interests' => 'sanitize_textarea_field',
            'member_linkedin' => 'esc_url_raw',
            'member_website' => 'esc_url_raw',
            'member_expectations' => 'sanitize_textarea_field',
        );

        foreach ($fields as $field => $sanitize_function) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_function, $_POST[$field]);
                update_post_meta($member_id, $field, $value);
            }
        }

        // Update post title if provided
        if (isset($_POST['member_name'])) {
            wp_update_post(array(
                'ID' => $member_id,
                'post_title' => sanitize_text_field($_POST['member_name']),
            ));
        }

        wp_send_json_success(array('message' => 'Профиль успешно обновлен'));
    }

    /**
     * Update gallery via AJAX
     */
    public function ajax_update_gallery() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        // Проверяем, редактирует ли админ чужой профиль
        $is_admin = current_user_can('administrator');
        $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

        if ($is_admin && $editing_member_id) {
            // Админ редактирует чужой профиль - проверяем существование
            $member_post = get_post($editing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
            $member_id = $editing_member_id;
        } else {
            // Обычный пользователь редактирует свой профиль
            $member_id = Member_User_Link::get_current_user_member_id();

            if (!$member_id || !Member_User_Link::can_user_edit_member($member_id)) {
                wp_send_json_error(array('message' => 'Нет прав на редактирование'));
            }
        }

        if (!isset($_POST['gallery_ids'])) {
            wp_send_json_error(array('message' => 'Данные галереи не получены'));
        }

        $gallery_ids = sanitize_text_field($_POST['gallery_ids']);
        update_post_meta($member_id, 'member_gallery', $gallery_ids);

        wp_send_json_success(array('message' => 'Галерея обновлена'));
    }

    /**
     * Get member data for dashboard
     */
    public static function get_member_data($member_id) {
        $member = get_post($member_id);

        if (!$member) {
            return null;
        }

        $data = array(
            'id' => $member_id,
            'name' => $member->post_title,
            'permalink' => get_permalink($member_id),
            'thumbnail_url' => get_the_post_thumbnail_url($member_id, 'medium'),
        );

        // Get all meta fields
        $meta_fields = array(
            'member_position',
            'member_company',
            'member_email',
            'member_phone',
            'member_bio',
            'member_specialization',
            'member_experience',
            'member_interests',
            'member_linkedin',
            'member_website',
            'member_expectations',
            'member_gallery',
        );

        foreach ($meta_fields as $field) {
            $data[$field] = get_post_meta($member_id, $field, true);
        }

        // Get gallery images
        $gallery_ids = $data['member_gallery'];
        $gallery_images = array();

        if ($gallery_ids) {
            $ids = explode(',', $gallery_ids);
            foreach ($ids as $id) {
                $id = intval($id);
                if ($id) {
                    $gallery_images[] = array(
                        'id' => $id,
                        'url' => wp_get_attachment_url($id),
                        'thumb' => wp_get_attachment_image_url($id, 'thumbnail'),
                    );
                }
            }
        }

        $data['gallery_images'] = $gallery_images;

        // Get taxonomies
        $member_types = wp_get_post_terms($member_id, 'member_type', array('fields' => 'names'));
        $member_roles = wp_get_post_terms($member_id, 'member_role', array('fields' => 'names'));
        $member_locations = wp_get_post_terms($member_id, 'member_location', array('fields' => 'names'));

        $data['member_types'] = $member_types;
        $data['member_roles'] = $member_roles;
        $data['member_locations'] = $member_locations;

        return $data;
    }

    /**
     * Get member statistics
     */
    public static function get_member_stats($member_id) {
        $stats = array(
            'profile_views' => get_post_meta($member_id, '_profile_views', true) ?: 0,
            'materials_count' => 0,
        );

        // Count materials
        $categories = Member_File_Manager::get_categories();
        foreach ($categories as $key => $label) {
            $materials = get_post_meta($member_id, 'member_' . $key, true);
            if (is_array($materials)) {
                $stats['materials_count'] += count($materials);
            }
        }

        return $stats;
    }

    /**
     * Mark onboarding as seen via AJAX
     */
    public function ajax_mark_onboarding_seen() {
        check_ajax_referer('onboarding_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'metoda_onboarding_seen', '1');

        wp_send_json_success(array('message' => 'Onboarding отмечен как просмотренный'));
    }
}

// Initialize the class only on frontend
if (!is_admin()) {
    new Member_Dashboard();
}
