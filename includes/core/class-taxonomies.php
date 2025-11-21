<?php
/**
 * Taxonomies Registration
 *
 * Handles registration of all custom taxonomies for the plugin
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Taxonomies {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_member_type'));
        add_action('init', array($this, 'register_member_role'));
        add_action('init', array($this, 'register_member_location'));
    }

    /**
     * Register Member Type taxonomy
     *
     * Hierarchical taxonomy for categorizing members (participant, expert, etc.)
     */
    public function register_member_type() {
        $labels = array(
            'name'              => 'Типы участников',
            'singular_name'     => 'Тип участника',
            'search_items'      => 'Искать типы',
            'all_items'         => 'Все типы',
            'edit_item'         => 'Редактировать тип',
            'update_item'       => 'Обновить тип',
            'add_new_item'      => 'Добавить новый тип',
            'new_item_name'     => 'Название нового типа',
            'menu_name'         => 'Типы участников',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'member-type'),
            'show_in_rest'      => true,
        );

        register_taxonomy('member_type', array('members'), $args);
    }

    /**
     * Register Member Role taxonomy
     *
     * Non-hierarchical taxonomy for association roles (chairman, board member, etc.)
     */
    public function register_member_role() {
        $labels = array(
            'name'              => 'Роли в ассоциации',
            'singular_name'     => 'Роль',
            'search_items'      => 'Искать роли',
            'all_items'         => 'Все роли',
            'edit_item'         => 'Редактировать роль',
            'update_item'       => 'Обновить роль',
            'add_new_item'      => 'Добавить новую роль',
            'new_item_name'     => 'Название новой роли',
            'menu_name'         => 'Роли в ассоциации',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'member-role'),
            'show_in_rest'      => true,
        );

        register_taxonomy('member_role', array('members'), $args);
    }

    /**
     * Register Member Location taxonomy
     *
     * Hierarchical taxonomy for geographic locations (cities, regions)
     */
    public function register_member_location() {
        $labels = array(
            'name'              => 'Локации',
            'singular_name'     => 'Локация',
            'search_items'      => 'Искать локации',
            'all_items'         => 'Все локации',
            'edit_item'         => 'Редактировать локацию',
            'update_item'       => 'Обновить локацию',
            'add_new_item'      => 'Добавить новую локацию',
            'new_item_name'     => 'Название новой локации',
            'menu_name'         => 'Локации',
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'location'),
            'show_in_rest'      => true,
        );

        register_taxonomy('member_location', array('members'), $args);
    }
}
