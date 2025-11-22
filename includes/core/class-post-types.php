<?php
/**
 * Post Types Registration
 *
 * Handles registration of all custom post types
 *
 * @package Metoda_Members
 * @subpackage Core
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Post_Types
 *
 * Manages all custom post type registrations
 */
class Metoda_Post_Types {

    /**
     * Constructor - registers WordPress hooks
     */
    public function __construct() {
        add_action('init', array($this, 'register_members_post_type'));
        add_action('init', array($this, 'register_member_messages_post_type'));
        add_action('after_setup_theme', array($this, 'register_member_image_sizes'));
    }

    /**
     * Register Members custom post type
     *
     * @return void
     */
    public function register_members_post_type() {
        $labels = array(
            'name'                  => 'Участники',
            'singular_name'         => 'Участник',
            'menu_name'             => 'Участники сообщества',
            'add_new'               => 'Добавить участника',
            'add_new_item'          => 'Добавить нового участника',
            'edit_item'             => 'Редактировать участника',
            'new_item'              => 'Новый участник',
            'view_item'             => 'Просмотреть участника',
            'view_items'            => 'Просмотреть участников',
            'search_items'          => 'Найти участника',
            'not_found'             => 'Участники не найдены',
            'not_found_in_trash'    => 'В корзине участники не найдены',
            'all_items'             => 'Все участники',
        );

        $args = array(
            'label'                 => 'Участники',
            'labels'                => $labels,
            'description'           => 'Участники и эксперты сообщества',
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'members', 'with_front' => false),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-groups',
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
            'show_in_rest'          => true,
        );

        register_post_type('members', $args);
    }

    /**
     * Register Member Messages custom post type
     *
     * @return void
     */
    public function register_member_messages_post_type() {
        $labels = array(
            'name'                  => 'Сообщения',
            'singular_name'         => 'Сообщение',
            'menu_name'             => 'Личные сообщения',
            'add_new'               => 'Новое сообщение',
            'add_new_item'          => 'Написать сообщение',
            'edit_item'             => 'Просмотр сообщения',
            'view_item'             => 'Просмотреть сообщение',
            'search_items'          => 'Найти сообщение',
            'not_found'             => 'Сообщения не найдены',
            'all_items'             => 'Все сообщения',
        );

        $args = array(
            'label'                 => 'Сообщения',
            'labels'                => $labels,
            'description'           => 'Система личных сообщений участников',
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=members',
            'query_var'             => false,
            'rewrite'               => false,
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => null,
            'menu_icon'             => 'dashicons-email',
            'supports'              => array('title', 'editor', 'author'),
            'show_in_rest'          => false,
        );

        register_post_type('member_message', $args);
    }

    /**
     * Register custom image sizes for member photos
     *
     * @return void
     */
    public function register_member_image_sizes() {
        // Квадратная аватарка - будет кропиться в центр
        add_image_size('member-avatar', 400, 400, true); // hard crop

        // Размер для карточек в списке
        add_image_size('member-card', 300, 300, true); // hard crop

        // Размер для хедера профиля
        add_image_size('member-profile', 500, 500, true); // hard crop
    }
}
