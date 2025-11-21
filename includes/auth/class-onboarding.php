<?php
/**
 * User Onboarding
 *
 * Wrapper for Member_Onboarding class with additional utilities
 * Handles first-login experience and access code registration
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Onboarding {

    /**
     * Legacy onboarding instance
     *
     * @var Member_Onboarding|null
     */
    private $legacy_onboarding = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize legacy onboarding class
        if (class_exists('Member_Onboarding')) {
            $this->legacy_onboarding = new Member_Onboarding();
        }
    }

    /**
     * Check if onboarding system is available
     *
     * @return bool
     */
    public function is_available() {
        return $this->legacy_onboarding !== null;
    }

    /**
     * Check if user needs onboarding
     *
     * @param int $user_id User ID
     * @return bool
     */
    public static function needs_onboarding($user_id) {
        if (class_exists('Member_Onboarding')) {
            return Member_Onboarding::user_needs_onboarding($user_id);
        }

        $needs = get_user_meta($user_id, '_member_needs_onboarding', true);
        return $needs === '1';
    }

    /**
     * Get current onboarding step for user
     *
     * @param int $user_id User ID
     * @return string Step name: 'password', 'welcome', or 'completed'
     */
    public static function get_step($user_id) {
        if (!self::needs_onboarding($user_id)) {
            return 'completed';
        }

        if (class_exists('Member_Onboarding')) {
            return Member_Onboarding::get_user_step($user_id);
        }

        $password_changed = get_user_meta($user_id, '_member_password_changed', true);
        return $password_changed === '1' ? 'welcome' : 'password';
    }

    /**
     * Mark onboarding as completed for user
     *
     * @param int $user_id User ID
     */
    public static function complete($user_id) {
        delete_user_meta($user_id, '_member_needs_onboarding');
        update_user_meta($user_id, '_member_onboarding_completed', current_time('mysql'));
    }

    /**
     * Mark password step as completed
     *
     * @param int $user_id User ID
     */
    public static function complete_password_step($user_id) {
        update_user_meta($user_id, '_member_password_changed', '1');
    }

    /**
     * Reset onboarding for user (for testing/admin)
     *
     * @param int $user_id User ID
     */
    public static function reset($user_id) {
        update_user_meta($user_id, '_member_needs_onboarding', '1');
        delete_user_meta($user_id, '_member_password_changed');
        delete_user_meta($user_id, '_member_onboarding_completed');
    }

    /**
     * Get onboarding completion date
     *
     * @param int $user_id User ID
     * @return string|false MySQL datetime or false
     */
    public static function get_completion_date($user_id) {
        return get_user_meta($user_id, '_member_onboarding_completed', true);
    }

    /**
     * Check if user is eligible for onboarding
     * Only members and experts, not admins/managers
     *
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_eligible($user_id) {
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return false;
        }

        // Admins and managers are not eligible
        if (in_array('administrator', $user->roles) || in_array('manager', $user->roles)) {
            return false;
        }

        // Only members and experts
        return in_array('member', $user->roles) || in_array('expert', $user->roles);
    }

    /**
     * Get onboarding page URL
     *
     * @return string Onboarding page URL
     */
    public static function get_url() {
        return home_url('/member-onboarding/');
    }

    /**
     * Get dashboard URL (redirect after onboarding)
     *
     * @return string Dashboard URL
     */
    public static function get_dashboard_url() {
        return home_url('/member-dashboard/');
    }

    /**
     * Trigger first login check
     * Sets up onboarding flag for new members
     *
     * @param int $user_id User ID
     */
    public static function trigger_first_login($user_id) {
        if (!self::is_eligible($user_id)) {
            return;
        }

        $first_login = get_user_meta($user_id, '_member_first_login', true);

        if (empty($first_login)) {
            update_user_meta($user_id, '_member_needs_onboarding', '1');
            update_user_meta($user_id, '_member_first_login', current_time('mysql'));
        }
    }
}
