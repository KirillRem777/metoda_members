<?php
/**
 * Helper Functions
 *
 * Global utility functions used throughout the plugin
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get editable member ID for current user
 * Returns member_id that current user can edit (for dashboard/profile)
 *
 * @return int|false Member ID or false if not found
 */
function metoda_get_editable_member_id() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user_id = get_current_user_id();
    $user = wp_get_current_user();

    // Admins and managers can edit any member (return false to indicate no specific member)
    if (in_array('administrator', $user->roles) || in_array('manager', $user->roles)) {
        return false;
    }

    // For regular members, get their linked member post
    $member_id = get_user_meta($user_id, 'member_id', true);

    if (!$member_id) {
        // Try reverse lookup: find member post with this user_id
        $args = array(
            'post_type' => 'members',
            'meta_key' => 'user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        );
        $members = get_posts($args);

        if (!empty($members)) {
            $member_id = $members[0];
            // Cache for future use
            update_user_meta($user_id, 'member_id', $member_id);
        }
    }

    return $member_id ? intval($member_id) : false;
}

/**
 * Get current user's member post
 *
 * @return WP_Post|null Member post or null
 */
function metoda_get_current_member() {
    $member_id = metoda_get_editable_member_id();

    if (!$member_id) {
        return null;
    }

    return get_post($member_id);
}

/**
 * Check if current user can manage members
 *
 * @return bool
 */
function metoda_user_can_manage_members() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();
    return in_array('administrator', $user->roles) || in_array('manager', $user->roles);
}

/**
 * Get member name
 *
 * @param int $member_id Member post ID
 * @return string Member name or empty string
 */
function metoda_get_member_name($member_id) {
    $name = get_post_meta($member_id, 'member_name', true);
    return $name ? $name : get_the_title($member_id);
}

/**
 * Get member photo URL
 *
 * @param int $member_id Member post ID
 * @param string $size Image size
 * @return string|false Photo URL or false
 */
function metoda_get_member_photo($member_id, $size = 'thumbnail') {
    $photo_id = get_post_meta($member_id, 'member_photo', true);

    if (!$photo_id) {
        // Fallback to featured image
        $photo_id = get_post_thumbnail_id($member_id);
    }

    if ($photo_id) {
        $photo = wp_get_attachment_image_src($photo_id, $size);
        return $photo ? $photo[0] : false;
    }

    return false;
}

/**
 * Get member email
 *
 * @param int $member_id Member post ID
 * @return string Email or empty string
 */
function metoda_get_member_email($member_id) {
    // First try member_email meta
    $email = get_post_meta($member_id, 'member_email', true);

    if (!$email) {
        // Try linked WordPress user
        $user_id = get_post_meta($member_id, 'user_id', true);
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $email = $user->user_email;
            }
        }
    }

    return $email ? $email : '';
}

/**
 * Format member status for display
 *
 * @param string $status Status key
 * @return array Array with 'label' and 'class'
 */
function metoda_format_member_status($status) {
    $statuses = array(
        'active' => array(
            'label' => 'Активен',
            'class' => 'bg-green-100 text-green-800'
        ),
        'pending' => array(
            'label' => 'Ожидает',
            'class' => 'bg-yellow-100 text-yellow-800'
        ),
        'suspended' => array(
            'label' => 'Приостановлен',
            'class' => 'bg-red-100 text-red-800'
        ),
        'archived' => array(
            'label' => 'Архив',
            'class' => 'bg-gray-100 text-gray-800'
        )
    );

    return isset($statuses[$status]) ? $statuses[$status] : $statuses['pending'];
}

/**
 * Log activity
 *
 * @param string $action Action performed
 * @param int $member_id Member post ID
 * @param string $details Additional details
 */
function metoda_log_activity($action, $member_id = 0, $details = '') {
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'action' => sanitize_text_field($action),
        'member_id' => intval($member_id),
        'details' => sanitize_textarea_field($details),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    );

    $logs = get_option('metoda_activity_logs', array());
    array_unshift($logs, $log_entry);

    // Keep only last 1000 entries
    $logs = array_slice($logs, 0, 1000);

    update_option('metoda_activity_logs', $logs);
}

/**
 * Get plugin version
 *
 * @return string Version number
 */
function metoda_get_version() {
    return defined('METODA_VERSION') ? METODA_VERSION : '5.0.0';
}

/**
 * Get plugin path
 *
 * @param string $path Optional path to append
 * @return string Full path
 */
function metoda_plugin_path($path = '') {
    return METODA_PATH . ltrim($path, '/');
}

/**
 * Get plugin URL
 *
 * @param string $path Optional path to append
 * @return string Full URL
 */
function metoda_plugin_url($path = '') {
    return METODA_URL . ltrim($path, '/');
}

/**
 * Include template file
 *
 * @param string $template_name Template file name
 * @param array $args Variables to pass to template
 */
function metoda_get_template($template_name, $args = array()) {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }

    $template_path = METODA_PATH . 'templates/' . $template_name;

    if (file_exists($template_path)) {
        include $template_path;
    }
}

/**
 * Check if user has completed onboarding
 *
 * @param int $user_id User ID (default: current user)
 * @return bool
 */
function metoda_user_completed_onboarding($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return (bool) get_user_meta($user_id, 'onboarding_completed', true);
}

/**
 * Get user's login method preference
 *
 * @param int $user_id User ID (default: current user)
 * @return string 'password' or 'otp'
 */
function metoda_get_user_login_method($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $method = get_user_meta($user_id, 'login_method', true);
    return in_array($method, array('password', 'otp')) ? $method : 'password';
}
