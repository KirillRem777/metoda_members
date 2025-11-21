<?php
/**
 * Uninstall Script
 *
 * Runs when plugin is deleted (not deactivated)
 * Cleans up all plugin data from the database
 *
 * @package Metoda
 * @since 5.1.0
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Main uninstall class
 */
class Metoda_Uninstaller {

    /**
     * Run uninstallation
     */
    public static function uninstall() {
        global $wpdb;

        // Check if we should preserve data
        $preserve_data = get_option('metoda_preserve_data_on_uninstall', false);

        if ($preserve_data) {
            return;
        }

        // Delete plugin options
        self::delete_options();

        // Delete user meta
        self::delete_user_meta();

        // Delete post meta
        self::delete_post_meta();

        // Delete transients
        self::delete_transients();

        // Delete custom posts (optional - controlled by setting)
        if (get_option('metoda_delete_posts_on_uninstall', false)) {
            self::delete_custom_posts();
        }

        // Delete custom tables (if any)
        self::delete_custom_tables();

        // Clean up scheduled events
        self::clear_scheduled_events();

        // Clear any cached data
        wp_cache_flush();
    }

    /**
     * Delete all plugin options
     */
    private static function delete_options() {
        global $wpdb;

        // Options to delete
        $options = array(
            'metoda_version',
            'metoda_pages_check',
            'metoda_otp_email_subject',
            'metoda_otp_email_template',
            'metoda_welcome_email_subject',
            'metoda_welcome_email_template',
            'metoda_preserve_data_on_uninstall',
            'metoda_delete_posts_on_uninstall',
            'metoda_activity_log',
            // Telegram settings
            'metoda_telegram_bot_token',
            'metoda_telegram_bot_username',
        );

        foreach ($options as $option) {
            delete_option($option);
        }

        // Delete options with metoda_ prefix
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE 'metoda_%'"
        );
    }

    /**
     * Delete user meta fields
     */
    private static function delete_user_meta() {
        global $wpdb;

        // Meta keys to delete
        $meta_keys = array(
            'member_id',
            'login_method',
            'otp_code',
            'otp_expires',
            'onboarding_completed',
            'onboarding_date',
            '_member_needs_onboarding',
            '_member_first_login',
            '_member_password_changed',
            '_member_onboarding_completed',
            // Telegram meta
            'telegram_chat_id',
            'telegram_username',
            'telegram_linked_at',
        );

        foreach ($meta_keys as $key) {
            $wpdb->delete(
                $wpdb->usermeta,
                array('meta_key' => $key),
                array('%s')
            );
        }
    }

    /**
     * Delete post meta fields
     */
    private static function delete_post_meta() {
        global $wpdb;

        // Meta keys to delete (for members post type)
        $meta_keys = array(
            '_access_code',
            '_access_code_used',
            '_access_code_used_date',
            'member_name',
            'member_email',
            'member_phone',
            'member_city',
            'member_company',
            'member_position',
            'member_about',
            'member_expertise',
            'member_telegram',
            'member_linkedin',
            'member_type',
            'user_id',
        );

        foreach ($meta_keys as $key) {
            $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $key),
                array('%s')
            );
        }
    }

    /**
     * Delete transients
     */
    private static function delete_transients() {
        global $wpdb;

        // Delete rate limiter transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_metoda_rl_%'
             OR option_name LIKE '_transient_timeout_metoda_rl_%'"
        );

        // Delete other plugin transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_metoda_%'
             OR option_name LIKE '_transient_timeout_metoda_%'"
        );
    }

    /**
     * Delete custom post types
     */
    private static function delete_custom_posts() {
        global $wpdb;

        // Get all members posts
        $post_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type IN ('members', 'materials', 'forum_topics', 'forum_replies')"
        );

        foreach ($post_ids as $post_id) {
            // Delete post meta
            $wpdb->delete(
                $wpdb->postmeta,
                array('post_id' => $post_id),
                array('%d')
            );

            // Delete post
            $wpdb->delete(
                $wpdb->posts,
                array('ID' => $post_id),
                array('%d')
            );

            // Delete from term relationships
            $wpdb->delete(
                $wpdb->term_relationships,
                array('object_id' => $post_id),
                array('%d')
            );
        }

        // Clean up orphaned term taxonomy counts
        $wpdb->query(
            "UPDATE {$wpdb->term_taxonomy}
             SET count = 0
             WHERE taxonomy IN ('member_city', 'member_role', 'material_category', 'forum_category')"
        );
    }

    /**
     * Delete custom database tables
     */
    private static function delete_custom_tables() {
        global $wpdb;

        // Add your custom tables here if any
        $tables = array(
            // $wpdb->prefix . 'metoda_messages',
            // $wpdb->prefix . 'metoda_activity_log',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('metoda_cleanup_otp_codes');
        wp_clear_scheduled_hook('metoda_cleanup_expired_otps');
        wp_clear_scheduled_hook('metoda_daily_cleanup');
    }
}

// Run uninstaller
Metoda_Uninstaller::uninstall();
