<?php
/**
 * AJAX Manager
 *
 * Handles AJAX requests for manager/admin actions on members
 * Additional manager functionality exists in legacy Member_Manager class
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Ajax_Manager {

    /**
     * Valid member statuses
     */
    private $valid_statuses = array(
        'publish',
        'pending',
        'draft'
    );

    /**
     * Status labels for success messages
     */
    private $status_labels = array(
        'publish' => 'одобрен',
        'pending' => 'отправлен на модерацию',
        'draft' => 'переведен в черновики'
    );

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_manager_change_member_status', array($this, 'change_member_status'));
    }

    /**
     * Change member status
     *
     * Allows managers and admins to change member post status
     */
    public function change_member_status() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manager') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Нет прав доступа'));
        }

        $member_id = intval($_POST['member_id']);
        $status = sanitize_text_field($_POST['status']);

        // Validate status
        if (!in_array($status, $this->valid_statuses)) {
            wp_send_json_error(array('message' => 'Некорректный статус'));
        }

        // Update post status
        $result = wp_update_post(array(
            'ID' => $member_id,
            'post_status' => $status
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Ошибка при изменении статуса'));
        }

        wp_send_json_success(array(
            'message' => 'Участник ' . $this->status_labels[$status]
        ));
    }
}
