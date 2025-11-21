<?php
/**
 * Member Dashboard Class
 *
 * Handles the personal cabinet functionality for members
 * Allows members to edit their profiles and manage materials
 * 
 * FIXED: Добавлена передача member_id в JS для корректной работы админского просмотра
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
            // v3.7.4: Подключаем variables.css первым для всей дизайн-системы
            wp_enqueue_style('metoda-variables', plugin_dir_url(dirname(__FILE__)) . 'assets/css/variables.css', array(), '1.0.0');
            wp_enqueue_style('member-dashboard', plugin_dir_url(dirname(__FILE__)) . 'assets/css/member-dashboard.css', array('metoda-variables'), '1.0.1');
            wp_enqueue_script('member-dashboard', plugin_dir_url(dirname(__FILE__)) . 'assets/js/member-dashboard.js', array('jquery'), '1.0.1', true);

            // FIXED: Определяем member_id для JS (критично для админского просмотра)
            $is_admin = current_user_can('administrator');
            $viewing_member_id = isset($_GET['member_id']) ? absint($_GET['member_id']) : null;
            
            if ($is_admin && $viewing_member_id) {
                // Админ смотрит чужой кабинет
                $member_id_for_js = $viewing_member_id;
                $is_admin_view = true;
            } else {
                // Обычный пользователь или админ без параметра
                $member_id_for_js = Member_User_Link::get_current_user_member_id();
                $is_admin_view = false;
            }

            wp_localize_script('member-dashboard', 'memberDashboard', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('member_dashboard_nonce'),
                'memberId' => $member_id_for_js,        // ADDED: ID участника для AJAX
                'isAdminView' => $is_admin_view,        // ADDED: флаг админского просмотра
            ));

            // Enqueue WordPress media library
            wp_enqueue_media();

            // Enqueue onboarding for first-time users (только для своего кабинета)
            if (is_user_logged_in() && !$is_admin_view) {
                $user_id = get_current_user_id();
                $onboarding_seen = get_user_meta($user_id, 'metoda_onboarding_seen', true);

                if (!$onboarding_seen) {
                    wp_enqueue_style('onboarding', plugin_dir_url(dirname(__FILE__)) . 'assets/css/onboarding.css', array('metoda-variables'), '1.0.0');
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
        $viewing_member_id = isset($_GET['member_id']) ? absint($_GET['member_id']) : null;

        // Если админ указал member_id - пропускаем проверку своего member_id
        if ($is_admin && $viewing_member_id) {
            // Админ просматривает чужой кабинет

            // Проверяем существование member post
            $member_post = get_post($viewing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                return '<div class="dashboard-alert dashboard-alert--error">
                    <h3 class="dashboard-alert__title">❌ Участник не найден</h3>
                    <p class="dashboard-alert__text">Участник с ID ' . esc_html($viewing_member_id) . ' не существует.</p>
                    <p><a href="' . esc_url(admin_url('admin.php?page=metoda-activity-log')) . '" class="dashboard-alert__link">Вернуться к логам</a></p>
                </div>';
            }

            // ВАЖНО: Устанавливаем переменные ДО загрузки шаблона!
            $member_id = $viewing_member_id;
            $is_viewing_other = true;

            ob_start();
            
            // ADDED: Админ-панель сверху
            echo $this->render_admin_view_bar($member_id);
            
            include plugin_dir_path(dirname(__FILE__)) . 'templates/member-dashboard.php';
            return ob_get_clean();
        } else if (!$is_admin && $viewing_member_id) {
            // SECURITY FIX v3.7.3: IDOR Protection - блокируем просмотр чужих кабинетов обычными пользователями
            return '<div class="dashboard-alert dashboard-alert--error">
                <h3 class="dashboard-alert__title">🚫 Доступ запрещён</h3>
                <p class="dashboard-alert__text">У вас нет прав для просмотра этого кабинета.</p>
                <p><a href="' . esc_url(home_url('/member-dashboard/')) . '" class="dashboard-alert__link">← Вернуться к своему кабинету</a></p>
            </div>';
        }

        // Для обычных пользователей проверяем наличие своего member_id
        $member_id = Member_User_Link::get_current_user_member_id();

        if (!$member_id) {
            // Если это админ без своего member_id и без параметра member_id
            if ($is_admin) {
                return '<div class="dashboard-alert dashboard-alert--warning">
                    <h3 class="dashboard-alert__title">⚠️ Режим администратора</h3>
                    <p class="dashboard-alert__text">Укажите ID участника в URL для просмотра кабинета:</p>
                    <code class="dashboard-alert__code">?member_id=XXX</code>
                    <p><a href="' . esc_url(admin_url('admin.php?page=metoda-activity-log')) . '" class="dashboard-alert__link">Перейти к логам активности</a></p>
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
     * ADDED: Render admin view bar
     * Панель для администратора при просмотре чужого кабинета
     */
    private function render_admin_view_bar($member_id) {
        $member_name = get_the_title($member_id);
        $member_email = get_post_meta($member_id, 'member_email', true);
        $edit_link = get_edit_post_link($member_id);
        $profile_link = get_permalink($member_id);
        
        return '
        <div id="metoda-admin-view-bar" style="
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: white;
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            flex-wrap: wrap;
            gap: 15px;
        ">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 24px;">👁</span>
                <div>
                    <div style="font-weight: 600; font-size: 16px;">Просмотр кабинета: ' . esc_html($member_name) . '</div>
                    <div style="opacity: 0.8; font-size: 13px;">ID: ' . esc_html($member_id) . ($member_email ? ' • ' . esc_html($member_email) : '') . '</div>
                </div>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="' . esc_url(admin_url('edit.php?post_type=members')) . '" style="
                    color: white;
                    text-decoration: none;
                    padding: 8px 16px;
                    background: rgba(255,255,255,0.15);
                    border-radius: 6px;
                    font-size: 14px;
                    transition: background 0.2s;
                " onmouseover="this.style.background=\'rgba(255,255,255,0.25)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.15)\'">
                    ← К списку
                </a>
                <a href="' . esc_url($profile_link) . '" target="_blank" style="
                    color: white;
                    text-decoration: none;
                    padding: 8px 16px;
                    background: rgba(255,255,255,0.15);
                    border-radius: 6px;
                    font-size: 14px;
                    transition: background 0.2s;
                " onmouseover="this.style.background=\'rgba(255,255,255,0.25)\'" onmouseout="this.style.background=\'rgba(255,255,255,0.15)\'">
                    👤 Публичный профиль
                </a>
                <a href="' . esc_url($edit_link) . '" style="
                    color: #1e3a5f;
                    text-decoration: none;
                    padding: 8px 16px;
                    background: #ffd700;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 600;
                    transition: background 0.2s;
                " onmouseover="this.style.background=\'#ffed4a\'" onmouseout="this.style.background=\'#ffd700\'">
                    ✏️ В админке
                </a>
            </div>
        </div>';
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
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="btn btn-primary">Войти</a>
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
        $editing_member_id = isset($_POST['member_id']) ? absint($_POST['member_id']) : null;

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
        $editing_member_id = isset($_POST['member_id']) ? absint($_POST['member_id']) : null;

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
                $id = absint($id);
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
