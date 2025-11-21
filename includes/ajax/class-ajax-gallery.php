<?php
/**
 * AJAX Gallery
 *
 * Handles AJAX requests for gallery photo uploads and management
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Ajax_Gallery {

    /**
     * Maximum file size for uploads (5MB)
     */
    const MAX_FILE_SIZE = 5242880; // 5MB in bytes

    /**
     * Allowed image MIME types
     */
    private $allowed_types = array(
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif'
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_member_save_gallery', array($this, 'save_gallery'));
        add_action('wp_ajax_member_upload_gallery_photo', array($this, 'upload_photo'));
    }

    /**
     * AJAX handler for saving gallery order
     *
     * Saves comma-separated gallery image IDs
     */
    public function save_gallery() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Get editable member ID (supports admin bypass)
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $gallery_ids = isset($_POST['gallery_ids']) ? sanitize_text_field($_POST['gallery_ids']) : '';

        // Save gallery IDs
        update_post_meta($member_id, 'member_gallery', $gallery_ids);

        wp_send_json_success(array(
            'message' => 'Галерея успешно сохранена!'
        ));
    }

    /**
     * AJAX handler for uploading gallery photo
     *
     * Validates file type and size, uploads to media library, adds to gallery
     */
    public function upload_photo() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Get editable member ID (supports admin bypass)
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        // Check if file was uploaded
        if (empty($_FILES['photo'])) {
            wp_send_json_error(array('message' => 'Файл не загружен'));
        }

        // Validate file
        $validation_result = $this->validate_upload();
        if (is_wp_error($validation_result)) {
            wp_send_json_error(array('message' => $validation_result->get_error_message()));
        }

        // Upload to media library
        $attachment_id = $this->handle_upload($member_id);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }

        // Add to gallery
        $this->add_to_gallery($member_id, $attachment_id);

        // Get thumbnail URL
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'medium');

        wp_send_json_success(array(
            'message' => 'Фото успешно загружено!',
            'attachment_id' => $attachment_id,
            'thumbnail_url' => $thumbnail_url
        ));
    }

    /**
     * Validate uploaded file
     *
     * Checks file type, size, and real MIME type
     *
     * @return true|WP_Error True on success, WP_Error on failure
     */
    private function validate_upload() {
        $file_type = $_FILES['photo']['type'];

        // Check file type
        if (!in_array($file_type, $this->allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                'Недопустимый тип файла. Разрешены только изображения (JPEG, PNG, WebP, GIF)'
            );
        }

        // Check file size
        if ($_FILES['photo']['size'] > self::MAX_FILE_SIZE) {
            return new WP_Error(
                'file_too_large',
                'Файл слишком большой. Максимальный размер: 5MB'
            );
        }

        // Check real MIME type (prevents file extension spoofing)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($real_mime, $this->allowed_types)) {
            return new WP_Error(
                'file_type_spoofed',
                'Обнаружена попытка загрузки файла с поддельным типом'
            );
        }

        return true;
    }

    /**
     * Handle file upload to WordPress media library
     *
     * @param int $member_id Member post ID
     * @return int|WP_Error Attachment ID on success, WP_Error on failure
     */
    private function handle_upload($member_id) {
        // Load WordPress upload functions
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Upload file to media library
        $attachment_id = media_handle_upload('photo', $member_id);

        if (is_wp_error($attachment_id)) {
            return new WP_Error(
                'upload_failed',
                'Ошибка загрузки файла: ' . $attachment_id->get_error_message()
            );
        }

        return $attachment_id;
    }

    /**
     * Add photo to member gallery
     *
     * @param int $member_id Member post ID
     * @param int $attachment_id Attachment ID
     */
    private function add_to_gallery($member_id, $attachment_id) {
        // Get current gallery IDs
        $current_gallery = get_post_meta($member_id, 'member_gallery', true);
        $gallery_ids = !empty($current_gallery) ? explode(',', $current_gallery) : array();

        // Add new photo
        $gallery_ids[] = $attachment_id;

        // Save updated gallery
        update_post_meta($member_id, 'member_gallery', implode(',', $gallery_ids));
    }
}
