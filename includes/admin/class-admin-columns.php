<?php
/**
 * Admin Columns
 *
 * Handles custom columns in the WordPress admin members list
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Admin_Columns {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter('manage_members_posts_columns', array($this, 'add_dashboard_column'));
        add_action('manage_members_posts_custom_column', array($this, 'render_dashboard_column'), 10, 2);
        add_action('manage_member_message_posts_columns', array($this, 'add_message_columns'));
        add_action('manage_member_message_posts_custom_column', array($this, 'render_message_columns'), 10, 2);
    }

    /**
     * Add dashboard access column to members list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_dashboard_column($columns) {
        $columns['dashboard_access'] = '<span class="dashicons dashicons-admin-home"></span> Личный кабинет';
        return $columns;
    }

    /**
     * Render dashboard access button in column
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_dashboard_column($column, $post_id) {
        if ($column === 'dashboard_access') {
            $dashboard_url = add_query_arg('member_id', $post_id, home_url('/member-dashboard/'));
            echo '<a href="' . esc_url($dashboard_url) . '" class="button button-small" target="_blank" title="Открыть личный кабинет этого участника">';
            echo '<span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> Просмотр ЛК';
            echo '</a>';
        }
    }

    /**
     * Add custom columns for messages
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_message_columns($columns) {
        // Remove date, add custom columns
        unset($columns['date']);

        $columns['message_from'] = 'От кого';
        $columns['message_to'] = 'Кому';
        $columns['message_status'] = 'Статус';
        $columns['message_date'] = 'Дата';

        return $columns;
    }

    /**
     * Render message columns
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_message_columns($column, $post_id) {
        switch ($column) {
            case 'message_from':
                $from_id = get_post_meta($post_id, 'message_from', true);
                if ($from_id) {
                    $from_member = get_post($from_id);
                    if ($from_member) {
                        $member_name = get_post_meta($from_id, 'member_name', true);
                        echo esc_html($member_name ? $member_name : $from_member->post_title);
                    }
                }
                break;

            case 'message_to':
                $to_id = get_post_meta($post_id, 'message_to', true);
                if ($to_id) {
                    $to_member = get_post($to_id);
                    if ($to_member) {
                        $member_name = get_post_meta($to_id, 'member_name', true);
                        echo esc_html($member_name ? $member_name : $to_member->post_title);
                    }
                }
                break;

            case 'message_status':
                $read = get_post_meta($post_id, 'message_read', true);
                if ($read) {
                    echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="Прочитано"></span> Прочитано';
                } else {
                    echo '<span class="dashicons dashicons-marker" style="color: #f56e28;" title="Не прочитано"></span> Не прочитано';
                }
                break;

            case 'message_date':
                echo get_the_date('d.m.Y H:i', $post_id);
                break;
        }
    }
}
