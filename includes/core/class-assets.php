<?php
/**
 * Assets Management
 *
 * Handles enqueuing of styles and scripts for the plugin
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Assets {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_global_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register global styles (Tailwind, Fonts, FontAwesome)
     */
    public function register_global_styles() {
        // Tailwind CSS
        wp_register_style(
            'metoda-tailwind',
            METODA_URL . 'assets/css/tailwind.min.css',
            array(),
            METODA_VERSION
        );

        // Google Fonts - Montserrat
        wp_register_style(
            'metoda-fonts',
            'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap',
            array(),
            null
        );

        // Font Awesome
        wp_register_style(
            'metoda-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        global $post;

        // jQuery for all pages
        wp_enqueue_script('jquery');

        // Global frontend styles
        $this->enqueue_global_styles();

        // Members archive and single pages
        if (is_post_type_archive('members') || is_singular('members')) {
            $this->enqueue_members_archive();
        }

        // Page-specific assets
        if (is_a($post, 'WP_Post')) {
            $this->enqueue_page_assets($post->post_name);
        }
    }

    /**
     * Enqueue global frontend styles
     */
    private function enqueue_global_styles() {
        wp_enqueue_style('metoda-fonts');
        wp_enqueue_style('metoda-fontawesome');
        wp_enqueue_style('metoda-tailwind');
    }

    /**
     * Enqueue members archive scripts
     */
    private function enqueue_members_archive() {
        wp_enqueue_script(
            'members-archive-ajax',
            METODA_URL . 'assets/js/members-archive-ajax.js',
            array('jquery'),
            METODA_VERSION,
            true
        );

        wp_localize_script('members-archive-ajax', 'membersAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('public_members_nonce')
        ));
    }

    /**
     * Enqueue page-specific assets
     *
     * @param string $page_slug Page slug
     */
    private function enqueue_page_assets($page_slug) {
        switch ($page_slug) {
            case 'member-registration':
                $this->enqueue_registration();
                break;

            case 'member-login':
            case 'login':
                $this->enqueue_login();
                break;

            case 'member-dashboard':
                $this->enqueue_dashboard();
                break;

            case 'manager-panel':
                $this->enqueue_manager_panel();
                break;
        }
    }

    /**
     * Enqueue registration page assets
     */
    private function enqueue_registration() {
        wp_enqueue_style(
            'member-registration-css',
            METODA_URL . 'assets/css/member-registration.css',
            array(),
            METODA_VERSION
        );

        wp_enqueue_script(
            'member-registration-js',
            METODA_URL . 'assets/js/member-registration.js',
            array('jquery'),
            METODA_VERSION,
            true
        );

        wp_localize_script('member-registration-js', 'memberRegistrationData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('member_registration_nonce')
        ));
    }

    /**
     * Enqueue login page assets
     */
    private function enqueue_login() {
        wp_enqueue_style(
            'custom-login-css',
            METODA_URL . 'assets/css/custom-login.css',
            array(),
            METODA_VERSION
        );

        wp_enqueue_script(
            'custom-login-js',
            METODA_URL . 'assets/js/custom-login.js',
            array('jquery'),
            METODA_VERSION,
            true
        );
    }

    /**
     * Enqueue dashboard page assets
     */
    private function enqueue_dashboard() {
        // Cropper.js library (CDN)
        wp_enqueue_style(
            'cropperjs-css',
            'https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css',
            array(),
            '1.6.1'
        );

        wp_enqueue_script(
            'cropperjs',
            'https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js',
            array(),
            '1.6.1',
            true
        );

        // Photo cropper
        wp_enqueue_style(
            'photo-cropper-css',
            METODA_URL . 'assets/css/photo-cropper.css',
            array('cropperjs-css'),
            METODA_VERSION
        );

        wp_enqueue_script(
            'photo-cropper-js',
            METODA_URL . 'assets/js/photo-cropper.js',
            array('jquery', 'cropperjs'),
            METODA_VERSION,
            true
        );

        // Dashboard styles and scripts
        wp_enqueue_style(
            'member-dashboard-css',
            METODA_URL . 'assets/css/member-dashboard.css',
            array('photo-cropper-css'),
            METODA_VERSION
        );

        wp_enqueue_script(
            'member-dashboard-js',
            METODA_URL . 'assets/js/member-dashboard.js',
            array('jquery', 'photo-cropper-js'),
            METODA_VERSION,
            true
        );

        wp_localize_script('member-dashboard-js', 'memberDashboardData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('member_dashboard_nonce')
        ));
    }

    /**
     * Enqueue manager panel assets
     */
    private function enqueue_manager_panel() {
        wp_enqueue_style(
            'manager-panel-css',
            METODA_URL . 'assets/css/manager-panel.css',
            array(),
            METODA_VERSION
        );

        wp_enqueue_script(
            'manager-panel-js',
            METODA_URL . 'assets/js/manager-panel.js',
            array('jquery'),
            METODA_VERSION,
            true
        );

        wp_localize_script('manager-panel-js', 'managerPanelData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('manager_actions_nonce')
        ));
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only on members post type screens
        if ('post.php' === $hook || 'post-new.php' === $hook || 'edit.php' === $hook) {
            global $post_type;
            if ('members' === $post_type) {
                wp_enqueue_media();

                wp_enqueue_script(
                    'metoda-admin',
                    METODA_URL . 'assets/js/admin.js',
                    array('jquery'),
                    METODA_VERSION,
                    true
                );
            }
        }
    }
}
