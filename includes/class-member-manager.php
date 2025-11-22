<?php
/**
 * Member Manager Class
 *
 * Frontend admin panel for managers to manage members and their materials
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_Manager {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Role and page are created during plugin activation
        // add_action('init', array($this, 'create_manager_role'));
        // add_action('init', array($this, 'register_manager_page'));
        add_shortcode('member_manager', array($this, 'render_manager_panel'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_manager_assets'));

        // AJAX endpoints
        add_action('wp_ajax_manager_get_members', array($this, 'ajax_get_members'));
        add_action('wp_ajax_manager_create_member', array($this, 'ajax_create_member'));
        add_action('wp_ajax_manager_update_member', array($this, 'ajax_update_member'));
        add_action('wp_ajax_manager_delete_member', array($this, 'ajax_delete_member'));
        add_action('wp_ajax_manager_get_member', array($this, 'ajax_get_member'));
        add_action('wp_ajax_manager_upload_photo', array($this, 'ajax_upload_photo'));
    }

    /**
     * Create manager role
     */
    public function create_manager_role() {
        if (!get_role('manager')) {
            add_role(
                'manager',
                'Менеджер',
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'upload_files' => true,
                    'manage_members' => true, // Custom capability
                )
            );
        }

        // Add capability to administrators
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_members');
        }
    }

    /**
     * Register manager page
     */
    public function register_manager_page() {
        $page = get_page_by_path('manager-panel');

        if (!$page) {
            wp_insert_post(array(
                'post_title' => 'Панель управления',
                'post_name' => 'manager-panel',
                'post_content' => '[member_manager]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
        }
    }

    /**
     * Enqueue manager assets
     */
    public function enqueue_manager_assets() {
        $current_post = get_post();
        if (is_page('manager-panel') || (function_exists('has_shortcode') && $current_post && has_shortcode($current_post->post_content, 'member_manager'))) {
            wp_enqueue_style('member-manager', plugin_dir_url(dirname(__FILE__)) . 'assets/css/member-manager.css', array(), '4.1.0');
            wp_enqueue_script('member-manager', plugin_dir_url(dirname(__FILE__)) . 'assets/js/member-manager.js', array('jquery'), '4.1.0', true);

            wp_localize_script('member-manager', 'memberManager', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('manager_actions_nonce'),
            ));

            // Enqueue WordPress media library
            wp_enqueue_media();
        }
    }

    /**
     * Render manager panel
     */
    public function render_manager_panel() {
        if (!is_user_logged_in()) {
            return '<p>Необходимо авторизоваться для доступа к панели управления.</p>';
        }

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            return '<p>У вас нет прав для доступа к панели управления.</p>';
        }

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'templates/member-manager.php';
        return ob_get_clean();
    }

    /**
     * Get members via AJAX
     */
    public function ajax_get_members() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = 20;

        $args = array(
            'post_type' => 'members',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => array('publish', 'pending', 'draft'), // Показываем все статусы
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Фильтры
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $city = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        if (!empty($type)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => $type,
            );
        }

        if (!empty($city)) {
            $args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => '=',
            );
        }

        if (!empty($status)) {
            $args['post_status'] = $status;
        }

        $query = new WP_Query($args);
        $members = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $member_id = get_the_ID();

                // Получаем тип участника
                $member_types = wp_get_post_terms($member_id, 'member_type', array('fields' => 'names'));
                $member_type = !empty($member_types) ? $member_types[0] : '';

                // URL личного кабинета
                $dashboard_url = get_permalink(get_page_by_path('member-dashboard'));
                $dashboard_link = add_query_arg('member_id', $member_id, $dashboard_url);

                $members[] = array(
                    'id' => $member_id,
                    'title' => get_the_title(),
                    'position' => get_post_meta($member_id, 'member_position', true),
                    'company' => get_post_meta($member_id, 'member_company', true),
                    'city' => get_post_meta($member_id, 'member_city', true),
                    'email' => get_post_meta($member_id, 'member_email', true),
                    'phone' => get_post_meta($member_id, 'member_phone', true),
                    'thumbnail' => get_the_post_thumbnail_url($member_id, 'thumbnail'),
                    'permalink' => get_permalink($member_id),
                    'dashboard_url' => $dashboard_link,
                    'member_type' => $member_type,
                    'post_status' => get_post_status(),
                    'post_date' => get_the_date('Y-m-d H:i:s'),
                );
            }
        }

        wp_reset_postdata();

        wp_send_json_success(array(
            'members' => $members,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ));
    }

    /**
     * Get single member via AJAX
     */
    public function ajax_get_member() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        $member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

        if (!$member_id) {
            wp_send_json_error(array('message' => 'ID участника не указан'));
        }

        $member = get_post($member_id);

        if (!$member || $member->post_type !== 'members') {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }

        $data = array(
            'id' => $member_id,
            'title' => $member->post_title,
            'content' => $member->post_content,
            'position' => get_post_meta($member_id, 'member_position', true),
            'company' => get_post_meta($member_id, 'member_company', true),
            'email' => get_post_meta($member_id, 'member_email', true),
            'phone' => get_post_meta($member_id, 'member_phone', true),
            'bio' => get_post_meta($member_id, 'member_bio', true),
            'specialization' => get_post_meta($member_id, 'member_specialization', true),
            'experience' => get_post_meta($member_id, 'member_experience', true),
            'interests' => get_post_meta($member_id, 'member_interests', true),
            'linkedin' => get_post_meta($member_id, 'member_linkedin', true),
            'website' => get_post_meta($member_id, 'member_website', true),
            'expectations' => get_post_meta($member_id, 'member_expectations', true),
            'thumbnail_id' => get_post_thumbnail_id($member_id),
            'thumbnail_url' => get_the_post_thumbnail_url($member_id, 'medium'),
        );

        // Get taxonomies
        $data['member_types'] = wp_get_post_terms($member_id, 'member_type', array('fields' => 'ids'));
        $data['member_roles'] = wp_get_post_terms($member_id, 'member_role', array('fields' => 'ids'));
        $data['member_locations'] = wp_get_post_terms($member_id, 'member_location', array('fields' => 'ids'));

        // Get materials
        $categories = Member_File_Manager::get_categories();
        $data['materials'] = array();
        foreach ($categories as $key => $label) {
            $materials = get_post_meta($member_id, 'member_' . $key, true);
            $data['materials'][$key] = Member_File_Manager::parse_material_content($materials);
        }

        // Get gallery
        $gallery_ids = get_post_meta($member_id, 'member_gallery', true);
        $data['gallery'] = array();
        if (!empty($gallery_ids) && is_array($gallery_ids)) {
            foreach ($gallery_ids as $img_id) {
                $img_url = wp_get_attachment_url($img_id);
                if ($img_url) {
                    $data['gallery'][] = array(
                        'id' => $img_id,
                        'url' => $img_url
                    );
                }
            }
        }

        wp_send_json_success($data);
    }

    /**
     * Create member via AJAX
     */
    public function ajax_create_member() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

        if (empty($title)) {
            wp_send_json_error(array('message' => 'Имя участника обязательно'));
        }

        // Create post
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => 'members',
            'post_status' => 'publish',
        ));

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }

        // Save meta fields
        $this->save_member_meta($post_id, $_POST);

        wp_send_json_success(array(
            'message' => 'Участник успешно создан',
            'member_id' => $post_id,
        ));
    }

    /**
     * Update member via AJAX
     */
    public function ajax_update_member() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;

        if (!$member_id) {
            wp_send_json_error(array('message' => 'ID участника не указан'));
        }

        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

        if (empty($title)) {
            wp_send_json_error(array('message' => 'Имя участника обязательно'));
        }

        // Update post
        $result = wp_update_post(array(
            'ID' => $member_id,
            'post_title' => $title,
            'post_content' => $content,
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Save meta fields
        $this->save_member_meta($member_id, $_POST);

        wp_send_json_success(array('message' => 'Участник успешно обновлен'));
    }

    /**
     * Delete member via AJAX
     */
    public function ajax_delete_member() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;

        if (!$member_id) {
            wp_send_json_error(array('message' => 'ID участника не указан'));
        }

        $result = wp_delete_post($member_id, true);

        if (!$result) {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }

        wp_send_json_success(array('message' => 'Участник успешно удален'));
    }

    /**
     * Upload photo via AJAX
     */
    public function ajax_upload_photo() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manage_members') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        if (empty($_FILES['photo'])) {
            wp_send_json_error(array('message' => 'Файл не загружен'));
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $file = $_FILES['photo'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        ), $upload['file']);

        if (!$attachment_id) {
            wp_send_json_error(array('message' => 'Ошибка создания вложения'));
        }

        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));

        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'thumb' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
        ));
    }

    /**
     * Save member meta fields
     */
    private function save_member_meta($member_id, $data) {
        $fields = array(
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
        );

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($member_id, $field, sanitize_text_field($data[$field]));
            }
        }

        // Save thumbnail
        if (isset($data['thumbnail_id'])) {
            set_post_thumbnail($member_id, intval($data['thumbnail_id']));
        }

        // Save taxonomies
        if (isset($data['member_types'])) {
            wp_set_object_terms($member_id, array_map('intval', (array)$data['member_types']), 'member_type');
        }

        if (isset($data['member_roles'])) {
            wp_set_object_terms($member_id, array_map('intval', (array)$data['member_roles']), 'member_role');
        }

        if (isset($data['member_locations'])) {
            wp_set_object_terms($member_id, array_map('intval', (array)$data['member_locations']), 'member_location');
        }

        // Save materials
        if (isset($data['materials']) && is_array($data['materials'])) {
            $categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
            foreach ($categories as $category) {
                $materials = array();
                if (isset($data['materials'][$category]) && is_array($data['materials'][$category])) {
                    foreach ($data['materials'][$category] as $material) {
                        if (!empty($material['title']) && !empty($material['link'])) {
                            $materials[] = array(
                                'title' => sanitize_text_field($material['title']),
                                'link' => esc_url_raw($material['link'])
                            );
                        }
                    }
                }
                update_post_meta($member_id, 'member_' . $category, $materials);
            }
        }

        // Save gallery
        if (isset($data['gallery_ids'])) {
            $gallery_ids = array_filter(array_map('intval', explode(',', $data['gallery_ids'])));
            update_post_meta($member_id, 'member_gallery', $gallery_ids);
        }
    }

    /**
     * Check if user is manager
     */
    public static function is_manager() {
        if (!is_user_logged_in()) {
            return false;
        }

        return current_user_can('manage_members') || current_user_can('manage_options');
    }
}

// Initialize the class
new Member_Manager();
