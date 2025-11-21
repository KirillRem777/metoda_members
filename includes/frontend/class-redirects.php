<?php
/**
 * Redirects
 *
 * Handles frontend redirects and access restrictions
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Redirects {

    /**
     * Constructor - register redirect hooks
     */
    public function __construct() {
        // Skip all redirects during plugin activation
        if (get_transient('metoda_members_activating')) {
            return;
        }

        // Skip if kill switch is enabled
        if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
            return;
        }

        add_action('template_redirect', array($this, 'restrict_forum_access'));
        add_action('template_redirect', array($this, 'restrict_dashboard_access'));
        add_action('template_redirect', array($this, 'restrict_manager_panel_access'));
    }

    /**
     * Restrict forum access to logged-in users
     *
     * Redirects non-logged users to login when accessing forum
     */
    public function restrict_forum_access() {
        // Check if viewing single forum topic or forum archive
        if (is_singular('forum_topic') || is_post_type_archive('forum_topic')) {
            if (!is_user_logged_in()) {
                auth_redirect();
            }
        }
    }

    /**
     * Restrict member dashboard access
     *
     * Redirects non-logged users to login when accessing dashboard
     */
    public function restrict_dashboard_access() {
        // Get dashboard page ID
        $dashboard_page_id = get_option('metoda_dashboard_page_id');

        if (!$dashboard_page_id) {
            return;
        }

        // Check if on dashboard page
        if (is_page($dashboard_page_id)) {
            if (!is_user_logged_in()) {
                wp_redirect(home_url('/login/'));
                exit;
            }

            // Check if user has linked member profile
            $member_id = $this->get_user_member_id();
            if (!$member_id && !current_user_can('administrator')) {
                // User doesn't have member profile - redirect to onboarding or error page
                $onboarding_page = get_option('metoda_onboarding_page_id');
                if ($onboarding_page) {
                    wp_redirect(get_permalink($onboarding_page));
                    exit;
                }
            }
        }
    }

    /**
     * Restrict manager panel access to managers and admins
     */
    public function restrict_manager_panel_access() {
        // Get manager panel page ID
        $manager_page_id = get_option('metoda_manager_page_id');

        if (!$manager_page_id) {
            return;
        }

        // Check if on manager panel page
        if (is_page($manager_page_id)) {
            if (!is_user_logged_in()) {
                wp_redirect(home_url('/login/'));
                exit;
            }

            // Check if user is manager or admin
            $user = wp_get_current_user();
            if (!in_array('manager', $user->roles) && !in_array('administrator', $user->roles)) {
                wp_redirect(home_url('/member-dashboard/'));
                exit;
            }
        }
    }

    /**
     * Get member ID for current user
     *
     * @return int|false Member ID or false
     */
    private function get_user_member_id() {
        if (!is_user_logged_in()) {
            return false;
        }

        // Use Member_User_Link if available
        if (class_exists('Member_User_Link') && method_exists('Member_User_Link', 'get_current_user_member_id')) {
            return Member_User_Link::get_current_user_member_id();
        }

        // Fallback: check user meta
        $user_id = get_current_user_id();
        $member_id = get_user_meta($user_id, 'member_id', true);

        return $member_id ? intval($member_id) : false;
    }

    /**
     * Redirect after login based on user role
     *
     * @param string $redirect_to Default redirect URL
     * @param string $request Requested redirect URL
     * @param WP_User $user User object
     * @return string Redirect URL
     */
    public static function login_redirect($redirect_to, $request, $user) {
        // Skip if kill switch is enabled
        if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
            return $redirect_to;
        }

        if (!is_a($user, 'WP_User')) {
            return $redirect_to;
        }

        // Administrators go to admin dashboard
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        }

        // Managers go to manager panel
        if (in_array('manager', $user->roles)) {
            return home_url('/manager-panel/');
        }

        // Regular users go to member dashboard
        return home_url('/member-dashboard/');
    }
}
