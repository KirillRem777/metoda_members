<?php
/**
 * Member File Manager Class
 *
 * Handles file uploads and link management for member materials
 * Supports both file uploads and external links
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_File_Manager {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('wp_ajax_member_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_member_add_link', array($this, 'ajax_add_link'));
        add_action('wp_ajax_member_delete_material', array($this, 'ajax_delete_material'));
        add_action('wp_ajax_member_get_materials', array($this, 'ajax_get_materials'));
    }

    /**
     * Upload file via AJAX
     */
    public function ajax_upload_file() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $member_id = Member_User_Link::get_current_user_member_id();

        if (!$member_id || !Member_User_Link::can_user_edit_member($member_id)) {
            wp_send_json_error(array('message' => 'Нет прав на редактирование'));
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (empty($category) || empty($title)) {
            wp_send_json_error(array('message' => 'Категория и название обязательны'));
        }

        // Check if file was uploaded
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'Файл не загружен'));
        }

        // Handle file upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $file = $_FILES['file'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Create attachment
        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $upload['type'],
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'inherit'
        ), $upload['file']);

        if (!$attachment_id) {
            wp_send_json_error(array('message' => 'Ошибка создания вложения'));
        }

        wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $upload['file']));

        // Add material to member
        $this->add_material($member_id, $category, array(
            'type' => 'file',
            'title' => $title,
            'description' => $description,
            'file_id' => $attachment_id,
            'url' => $upload['url'],
            'date' => current_time('mysql')
        ));

        wp_send_json_success(array(
            'message' => 'Файл успешно загружен',
            'material' => array(
                'title' => $title,
                'url' => $upload['url'],
                'type' => 'file'
            )
        ));
    }

    /**
     * Add link via AJAX
     */
    public function ajax_add_link() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $member_id = Member_User_Link::get_current_user_member_id();

        if (!$member_id || !Member_User_Link::can_user_edit_member($member_id)) {
            wp_send_json_error(array('message' => 'Нет прав на редактирование'));
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (empty($category) || empty($title) || empty($url)) {
            wp_send_json_error(array('message' => 'Все поля обязательны'));
        }

        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(array('message' => 'Неверный формат URL'));
        }

        // Add material to member
        $material_id = $this->add_material($member_id, $category, array(
            'type' => 'link',
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'date' => current_time('mysql')
        ));

        wp_send_json_success(array(
            'message' => 'Ссылка успешно добавлена',
            'material' => array(
                'id' => $material_id,
                'title' => $title,
                'url' => $url,
                'type' => 'link',
                'description' => $description
            )
        ));
    }

    /**
     * Delete material via AJAX
     */
    public function ajax_delete_material() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $member_id = Member_User_Link::get_current_user_member_id();

        if (!$member_id || !Member_User_Link::can_user_edit_member($member_id)) {
            wp_send_json_error(array('message' => 'Нет прав на редактирование'));
        }

        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $material_index = isset($_POST['index']) ? intval($_POST['index']) : -1;

        if (empty($category) || $material_index < 0) {
            wp_send_json_error(array('message' => 'Неверные параметры'));
        }

        $success = $this->delete_material($member_id, $category, $material_index);

        if ($success) {
            wp_send_json_success(array('message' => 'Материал удален'));
        } else {
            wp_send_json_error(array('message' => 'Ошибка удаления'));
        }
    }

    /**
     * Get materials via AJAX
     */
    public function ajax_get_materials() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $member_id = Member_User_Link::get_current_user_member_id();

        if (!$member_id) {
            wp_send_json_error(array('message' => 'Профиль не найден'));
        }

        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

        if (empty($category)) {
            wp_send_json_error(array('message' => 'Категория не указана'));
        }

        $materials = $this->get_materials($member_id, $category);

        wp_send_json_success(array('materials' => $materials));
    }

    /**
     * Add material to member
     */
    private function add_material($member_id, $category, $material_data) {
        $meta_key = 'member_' . $category;
        $materials = get_post_meta($member_id, $meta_key, true);

        if (!is_array($materials)) {
            $materials = array();
        }

        // Generate unique ID for the material
        $material_data['id'] = uniqid('material_');
        $materials[] = $material_data;

        update_post_meta($member_id, $meta_key, $materials);

        return $material_data['id'];
    }

    /**
     * Delete material from member
     */
    private function delete_material($member_id, $category, $material_index) {
        $meta_key = 'member_' . $category;
        $materials = get_post_meta($member_id, $meta_key, true);

        if (!is_array($materials) || !isset($materials[$material_index])) {
            return false;
        }

        // If it's a file, delete the attachment
        if (isset($materials[$material_index]['type']) &&
            $materials[$material_index]['type'] === 'file' &&
            isset($materials[$material_index]['file_id'])) {
            wp_delete_attachment($materials[$material_index]['file_id'], true);
        }

        // Remove material from array
        array_splice($materials, $material_index, 1);

        update_post_meta($member_id, $meta_key, $materials);

        return true;
    }

    /**
     * Get materials for a specific category
     */
    public function get_materials($member_id, $category) {
        $meta_key = 'member_' . $category;
        $materials = get_post_meta($member_id, $meta_key, true);

        if (!is_array($materials)) {
            return array();
        }

        return $materials;
    }

    /**
     * Get all categories
     */
    public static function get_categories() {
        return array(
            'testimonials' => 'Отзывы',
            'gratitudes' => 'Благодарности',
            'interviews' => 'Интервью',
            'videos' => 'Видео',
            'reviews' => 'Рецензии',
            'developments' => 'Разработки'
        );
    }

    /**
     * Parse material content for display
     */
    public static function parse_material_content($materials) {
        if (empty($materials) || !is_array($materials)) {
            return array();
        }

        $parsed = array();

        foreach ($materials as $index => $material) {
            $item = array(
                'index' => $index,
                'title' => isset($material['title']) ? $material['title'] : '',
                'description' => isset($material['description']) ? $material['description'] : '',
                'url' => isset($material['url']) ? $material['url'] : '',
                'type' => isset($material['type']) ? $material['type'] : 'link',
                'date' => isset($material['date']) ? $material['date'] : '',
            );

            // Format date
            if (!empty($item['date'])) {
                $item['formatted_date'] = date_i18n('d.m.Y', strtotime($item['date']));
            }

            $parsed[] = $item;
        }

        return $parsed;
    }
}

// Initialize the class
new Member_File_Manager();
