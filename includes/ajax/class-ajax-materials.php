<?php
/**
 * AJAX Materials
 *
 * Handles AJAX requests for portfolio materials management
 * Supports both old string-based and new JSON-based storage systems
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Ajax_Materials {

    /**
     * Valid portfolio categories
     */
    private $valid_categories = array(
        'testimonials',
        'gratitudes',
        'interviews',
        'videos',
        'reviews',
        'developments'
    );

    /**
     * Constructor
     */
    public function __construct() {
        // New JSON-based system
        add_action('wp_ajax_add_portfolio_material', array($this, 'add_portfolio_material'));
        add_action('wp_ajax_delete_portfolio_material', array($this, 'delete_portfolio_material'));
        add_action('wp_ajax_edit_portfolio_material', array($this, 'edit_portfolio_material'));

        // Old string-based system (legacy)
        add_action('wp_ajax_member_add_material_link', array($this, 'add_material_link'));
        add_action('wp_ajax_member_add_material_file', array($this, 'add_material_file'));
    }

    /**
     * Add portfolio material (new JSON system)
     *
     * Supports text, link, file, and video material types
     */
    public function add_portfolio_material() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Get member ID with admin bypass support
        $member_id = $this->get_member_id_with_admin_bypass();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $category = sanitize_text_field($_POST['category']);
        $material_type = sanitize_text_field($_POST['material_type']);

        // Validate category
        if (!in_array($category, $this->valid_categories)) {
            wp_send_json_error(array('message' => 'Неверная категория'));
        }

        // Get current data
        $field_name = 'member_' . $category . '_data';
        $current_data = get_post_meta($member_id, $field_name, true);
        $data_array = $current_data ? json_decode($current_data, true) : array();

        // Build new material
        $new_material = array(
            'type' => $material_type,
            'title' => sanitize_text_field($_POST['title']),
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
            'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
            'file_id' => 0,
            'author' => isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '',
            'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        );

        // Handle file upload
        if ($material_type === 'file' && !empty($_FILES['file'])) {
            $file_id = $this->upload_material_file($member_id);
            if (is_wp_error($file_id)) {
                wp_send_json_error(array('message' => $file_id->get_error_message()));
            }

            $new_material['file_id'] = $file_id;
            $new_material['url'] = wp_get_attachment_url($file_id);
        }

        // Add new material
        $data_array[] = $new_material;

        // Save
        update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

        wp_send_json_success(array(
            'message' => 'Материал успешно добавлен!',
            'reload' => true
        ));
    }

    /**
     * Delete portfolio material (new JSON system)
     */
    public function delete_portfolio_material() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        // Get member ID
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $category = sanitize_text_field($_POST['category']);
        $index = intval($_POST['index']);

        // Validate category
        if (!in_array($category, $this->valid_categories)) {
            wp_send_json_error(array('message' => 'Неверная категория'));
        }

        // Get current data
        $field_name = 'member_' . $category . '_data';
        $current_data = get_post_meta($member_id, $field_name, true);
        $data_array = $current_data ? json_decode($current_data, true) : array();

        // Check if element exists
        if (!isset($data_array[$index])) {
            wp_send_json_error(array('message' => 'Материал не найден'));
        }

        // Delete file if it was a file
        if (isset($data_array[$index]['type']) && $data_array[$index]['type'] === 'file' && isset($data_array[$index]['file_id'])) {
            wp_delete_attachment($data_array[$index]['file_id'], true);
        }

        // Remove element
        unset($data_array[$index]);
        $data_array = array_values($data_array); // Reindex array

        // Save
        update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

        wp_send_json_success(array(
            'message' => 'Материал успешно удален!',
            'reload' => true
        ));
    }

    /**
     * Edit portfolio material (new JSON system)
     */
    public function edit_portfolio_material() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        // Get member ID
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $category = sanitize_text_field($_POST['category']);
        $index = intval($_POST['index']);
        $material_type = sanitize_text_field($_POST['material_type']);

        // Validate category
        if (!in_array($category, $this->valid_categories)) {
            wp_send_json_error(array('message' => 'Неверная категория'));
        }

        // Get current data
        $field_name = 'member_' . $category . '_data';
        $current_data = get_post_meta($member_id, $field_name, true);
        $data_array = $current_data ? json_decode($current_data, true) : array();

        // Check if element exists
        if (!isset($data_array[$index])) {
            wp_send_json_error(array('message' => 'Материал не найден'));
        }

        // Update material data (preserve file_id if it was a file)
        $updated_material = array(
            'type' => $material_type,
            'title' => sanitize_text_field($_POST['title']),
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
            'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
            'file_id' => isset($data_array[$index]['file_id']) ? $data_array[$index]['file_id'] : 0,
            'author' => isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '',
            'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        );

        // If file type, preserve URL from old data
        if ($material_type === 'file' && isset($data_array[$index]['url'])) {
            $updated_material['url'] = $data_array[$index]['url'];
        }

        // Replace element
        $data_array[$index] = $updated_material;

        // Save
        update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

        wp_send_json_success(array(
            'message' => 'Материал успешно обновлен!',
            'reload' => true
        ));
    }

    /**
     * Add material link (old string system - legacy)
     */
    public function add_material_link() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Get member ID with admin bypass support
        $member_id = $this->get_member_id_with_admin_bypass();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $category = sanitize_text_field($_POST['category']);
        $title = sanitize_text_field($_POST['title']);
        $url = esc_url_raw($_POST['url']);
        $description = sanitize_textarea_field($_POST['description']);

        // Get current materials
        $current_materials = get_post_meta($member_id, 'member_' . $category, true);

        // Create new material entry
        $new_material = sprintf(
            "[LINK|%s|%s|%s|%s]",
            $title,
            $url,
            $description,
            current_time('Y-m-d H:i:s')
        );

        // Add new material
        if (empty($current_materials)) {
            $updated_materials = $new_material;
        } else {
            $updated_materials = $current_materials . "\n" . $new_material;
        }

        update_post_meta($member_id, 'member_' . $category, $updated_materials);

        wp_send_json_success(array(
            'message' => 'Ссылка успешно добавлена!',
            'reload' => true
        ));
    }

    /**
     * Add material file (old string system - legacy)
     */
    public function add_material_file() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Get member ID with admin bypass support
        $member_id = $this->get_member_id_with_admin_bypass();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        // Check if file was uploaded
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'Файл не загружен'));
        }

        $category = sanitize_text_field($_POST['category']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);

        // Upload file
        $attachment_id = $this->upload_material_file($member_id);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        $file_url = wp_get_attachment_url($attachment_id);

        // Get current materials
        $current_materials = get_post_meta($member_id, 'member_' . $category, true);

        // Create new material entry
        $new_material = sprintf(
            "[FILE|%s|%s|%s|%s]",
            $title,
            $file_url,
            $description,
            current_time('Y-m-d H:i:s')
        );

        // Add new material
        if (empty($current_materials)) {
            $updated_materials = $new_material;
        } else {
            $updated_materials = $current_materials . "\n" . $new_material;
        }

        update_post_meta($member_id, 'member_' . $category, $updated_materials);

        wp_send_json_success(array(
            'message' => 'Файл успешно загружен!',
            'reload' => true
        ));
    }

    /**
     * Get member ID with admin bypass support
     *
     * Admins can edit other members by passing member_id in POST
     *
     * @return int|WP_Error Member ID or error
     */
    private function get_member_id_with_admin_bypass() {
        $is_admin = current_user_can('administrator');
        $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

        if ($is_admin && $editing_member_id) {
            $member_post = get_post($editing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                return new WP_Error('invalid_member', 'Участник не найден');
            }
            return $editing_member_id;
        }

        // Regular user - get their linked member
        $member_id = Member_User_Link::get_current_user_member_id();
        if (!$member_id) {
            return new WP_Error('no_member', 'Участник не найден');
        }

        return $member_id;
    }

    /**
     * Upload material file to WordPress media library
     *
     * @param int $member_id Member post ID
     * @return int|WP_Error Attachment ID or error
     */
    private function upload_material_file($member_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('file', $member_id);

        if (is_wp_error($attachment_id)) {
            return new WP_Error(
                'upload_failed',
                'Ошибка загрузки файла: ' . $attachment_id->get_error_message()
            );
        }

        return $attachment_id;
    }
}
