<?php
/**
 * Main Plugin Class
 *
 * Orchestrates all plugin components and serves as the central bootstrap.
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Plugin {

    /**
     * Plugin instance
     * @var Metoda_Plugin
     */
    private static $instance = null;

    /**
     * Plugin components
     * @var array
     */
    private $components = array();

    /**
     * Get plugin instance (Singleton)
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - initialize all components
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }

    /**
     * Load all class dependencies
     */
    private function load_dependencies() {
        // Core components
        require_once METODA_PATH . 'includes/core/class-post-types.php';
        require_once METODA_PATH . 'includes/core/class-taxonomies.php';
        require_once METODA_PATH . 'includes/core/class-assets.php';

        // Admin components (only in admin)
        if (is_admin()) {
            require_once METODA_PATH . 'includes/admin/class-admin-columns.php';
            require_once METODA_PATH . 'includes/admin/class-meta-boxes.php';
            require_once METODA_PATH . 'includes/admin/class-dashboard-widget.php';
            require_once METODA_PATH . 'includes/admin/class-activity-log.php';
            require_once METODA_PATH . 'includes/admin/class-admin-menus.php';
        }

        // AJAX components
        require_once METODA_PATH . 'includes/ajax/class-ajax-members.php';
        require_once METODA_PATH . 'includes/ajax/class-ajax-gallery.php';
        require_once METODA_PATH . 'includes/ajax/class-ajax-materials.php';
        require_once METODA_PATH . 'includes/ajax/class-ajax-messages.php';
        require_once METODA_PATH . 'includes/ajax/class-ajax-manager.php';

        // Frontend components
        require_once METODA_PATH . 'includes/frontend/class-shortcodes.php';
        require_once METODA_PATH . 'includes/frontend/class-redirects.php';

        // Auth components
        require_once METODA_PATH . 'includes/auth/class-login.php';
        require_once METODA_PATH . 'includes/auth/class-otp.php';
        require_once METODA_PATH . 'includes/auth/class-onboarding.php';

        // Existing classes (legacy - will be refactored later)
        $this->load_legacy_classes();
    }

    /**
     * Load existing classes (temporary, until full refactor)
     */
    private function load_legacy_classes() {
        $legacy_classes = array(
            'class-member-access-codes.php',
            'class-member-archive.php',
            'class-member-bulk-users.php',
            'class-member-csv-importer.php',
            'class-member-dashboard.php',
            'class-member-email-templates.php',
            'class-member-file-manager.php',
            'class-member-forum.php',
            'class-member-manager.php',
            'class-member-page-templates.php',
            'class-member-template-loader.php',
            'class-member-user-link.php',
        );

        foreach ($legacy_classes as $class_file) {
            $file_path = METODA_PATH . 'includes/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Initialize all plugin components
     */
    private function init_components() {
        // Core
        $this->components['post_types'] = new Metoda_Post_Types();
        $this->components['taxonomies'] = new Metoda_Taxonomies();
        $this->components['assets'] = new Metoda_Assets();

        // Admin (only in admin area)
        if (is_admin()) {
            $this->components['admin_columns'] = new Metoda_Admin_Columns();
            $this->components['meta_boxes'] = new Metoda_Meta_Boxes();
            $this->components['dashboard_widget'] = new Metoda_Dashboard_Widget();
            $this->components['activity_log'] = new Metoda_Activity_Log();
            $this->components['admin_menus'] = new Metoda_Admin_Menus();
        }

        // AJAX
        $this->components['ajax_members'] = new Metoda_Ajax_Members();
        $this->components['ajax_gallery'] = new Metoda_Ajax_Gallery();
        $this->components['ajax_materials'] = new Metoda_Ajax_Materials();
        $this->components['ajax_messages'] = new Metoda_Ajax_Messages();
        $this->components['ajax_manager'] = new Metoda_Ajax_Manager();

        // Frontend
        $this->components['shortcodes'] = new Metoda_Shortcodes();
        $this->components['redirects'] = new Metoda_Redirects();

        // Auth
        $this->components['login'] = new Metoda_Login();
        $this->components['otp'] = new Metoda_Otp();
        $this->components['onboarding'] = new Metoda_Onboarding();

        // Legacy classes (temporary)
        $this->init_legacy_components();
    }

    /**
     * Initialize legacy components (temporary)
     */
    private function init_legacy_components() {
        // These will be refactored in later stages
        if (class_exists('Member_Access_Codes')) {
            $this->components['access_codes'] = new Member_Access_Codes();
        }
        if (class_exists('Member_Archive')) {
            $this->components['archive'] = new Member_Archive();
        }
        if (class_exists('Member_Bulk_Users')) {
            $this->components['bulk_users'] = new Member_Bulk_Users();
        }
        if (class_exists('Member_CSV_Importer')) {
            $this->components['csv_importer'] = new Member_CSV_Importer();
        }
        if (class_exists('Member_Dashboard')) {
            $this->components['dashboard'] = new Member_Dashboard();
        }
        if (class_exists('Member_Email_Templates')) {
            $this->components['email_templates'] = new Member_Email_Templates();
        }
        if (class_exists('Member_File_Manager')) {
            $this->components['file_manager'] = new Member_File_Manager();
        }
        if (class_exists('Member_Forum')) {
            $this->components['forum'] = new Member_Forum();
        }
        if (class_exists('Member_Manager')) {
            $this->components['manager'] = new Member_Manager();
        }
        if (class_exists('Member_Page_Templates')) {
            $this->components['page_templates'] = new Member_Page_Templates();
        }
        if (class_exists('Member_Template_Loader')) {
            $this->components['template_loader'] = new Member_Template_Loader();
        }
    }

    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Create pages (existing logic)
        delete_option('metoda_pages_check');
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear scheduled hooks
        wp_clear_scheduled_hook('metoda_cleanup_otp_codes');
    }

    /**
     * Get component by name
     */
    public function get_component($name) {
        return isset($this->components[$name]) ? $this->components[$name] : null;
    }
}
