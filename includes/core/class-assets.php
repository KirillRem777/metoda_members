<?php
/**
 * Assets Management
 *
 * Handles loading of CSS and JS files
 *
 * @package Metoda_Members
 * @subpackage Core
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Assets
 *
 * Manages all frontend and admin assets (scripts and styles)
 */
class Metoda_Assets {

    /**
     * Constructor - registers WordPress hooks
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'register_tailwind_styles'));
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @return void
     */
    public function enqueue_frontend_scripts() {
        // jQuery для всех страниц
        wp_enqueue_script('jquery');

        // Архив участников
        if (is_post_type_archive('members') || is_singular('members')) {
            wp_enqueue_script(
                'members-archive-ajax',
                plugin_dir_url(dirname(__FILE__)) . '../assets/js/members-archive-ajax.js',
                array('jquery'),
                '1.0.0',
                true
            );

            wp_localize_script('members-archive-ajax', 'membersAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('public_members_nonce')
            ));
        }

        // Глобальные проверки по slug страницы
        global $post;
        if (is_a($post, 'WP_Post')) {
            // Страница регистрации
            if ($post->post_name === 'member-registration') {
                wp_enqueue_style(
                    'member-registration-css',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/css/member-registration.css',
                    array(),
                    '1.0.0'
                );

                wp_enqueue_script(
                    'member-registration-js',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/js/member-registration.js',
                    array('jquery'),
                    '1.0.0',
                    true
                );

                wp_localize_script('member-registration-js', 'memberRegistrationData', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('member_registration_nonce')
                ));
            }

            // Страница логина
            if ($post->post_name === 'login') {
                wp_enqueue_style(
                    'custom-login-css',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/css/custom-login.css',
                    array(),
                    '1.0.0'
                );

                wp_enqueue_script(
                    'custom-login-js',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/js/custom-login.js',
                    array('jquery'),
                    '1.0.0',
                    true
                );
            }

            // Личный кабинет
            if ($post->post_name === 'member-dashboard') {
                // Cropper.js библиотека (CDN)
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

                // Наш кроппер
                wp_enqueue_style(
                    'photo-cropper-css',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/css/photo-cropper.css',
                    array('cropperjs-css'),
                    '1.0.0'
                );

                wp_enqueue_script(
                    'photo-cropper-js',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/js/photo-cropper.js',
                    array('jquery', 'cropperjs'),
                    '1.0.0',
                    true
                );

                // Dashboard стили и скрипты
                wp_enqueue_style(
                    'member-dashboard-css',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/css/member-dashboard.css',
                    array('photo-cropper-css'),
                    '1.0.0'
                );

                wp_enqueue_script(
                    'member-dashboard-js',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/js/member-dashboard.js',
                    array('jquery', 'photo-cropper-js'),
                    '1.0.0',
                    true
                );

                wp_localize_script('member-dashboard-js', 'memberDashboardData', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('member_dashboard_nonce')
                ));
            }

            // Панель менеджера
            if ($post->post_name === 'manager-panel') {
                wp_enqueue_style(
                    'manager-panel-css',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/css/manager-panel.css',
                    array(),
                    '1.0.0'
                );

                wp_enqueue_script(
                    'manager-panel-js',
                    plugin_dir_url(dirname(__FILE__)) . '../assets/js/manager-panel.js',
                    array(),
                    '1.0.0',
                    true
                );

                wp_localize_script('manager-panel-js', 'managerPanelData', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('manager_actions_nonce')
                ));
            }
        }

        // Enqueue frontend styles
        $this->enqueue_frontend_styles();
    }

    /**
     * Register Tailwind CSS and common styles
     *
     * @return void
     */
    public function register_tailwind_styles() {
        $plugin_url = plugin_dir_url(dirname(__FILE__)) . '../assets/css/';

        wp_register_style('metoda-tailwind', $plugin_url . 'tailwind.min.css', array(), '4.1.0');
        wp_register_style('metoda-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap', array(), null);
        wp_register_style('metoda-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    }

    /**
     * Enqueue frontend styles (Tailwind + fonts)
     *
     * @return void
     */
    private function enqueue_frontend_styles() {
        wp_enqueue_style('metoda-fonts');
        wp_enqueue_style('metoda-fontawesome');
        wp_enqueue_style('metoda-tailwind');
    }
}
