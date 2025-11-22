<?php
/**
 * Legacy Hooks Layer
 *
 * All WordPress hooks (add_action, add_filter) from v4.2.0
 * Extracted during refactoring to modular architecture.
 *
 * IMPORTANT: This file maintains backward compatibility.
 * All hooks are in the exact order as in the original file.
 *
 * @package Metoda_Members
 * @version 4.2.0-refactor
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================
// ACTIVATION/DEACTIVATION HOOKS
// ============================================

/**
 * Activation hook: создаём страницы при активации плагина
 */
register_activation_hook(__FILE__, 'metoda_plugin_activation');

// Хуки активации/деактивации плагина
register_activation_hook(__FILE__, 'metoda_members_activate');
register_deactivation_hook(__FILE__, 'metoda_members_deactivate');

// ============================================
// WORDPRESS CORE HOOKS
// ============================================

add_action('admin_init', 'metoda_create_pages_deferred', 1);

add_action('init', 'register_members_post_type');

add_action('init', 'register_member_messages_post_type');

// Регистрация специальных размеров изображений для участников
add_action('after_setup_theme', 'register_member_image_sizes');

add_action('init', 'register_member_type_taxonomy');

add_action('init', 'register_member_role_taxonomy');

add_action('init', 'register_member_location_taxonomy');

/**
 * Регистрация Tailwind CSS и общих стилей
 */
add_action('init', 'metoda_register_tailwind_styles');

/**
 * Скрываем админ-бар для участников
 */
add_action('after_setup_theme', 'hide_admin_bar_for_members');

/**
 * Ограничение доступа к форуму только для залогиненных пользователей
 */
add_action('template_redirect', 'metoda_restrict_forum_access');

// ============================================
// POST TYPE HOOKS
// ============================================

// Добавление метабоксов для дополнительных полей
add_action('add_meta_boxes', 'add_member_meta_boxes');

// Сохранение метаданных
add_action('save_post_members', 'save_member_details');

/**
 * Добавляет кастомные столбцы в список участников
 */
add_filter('manage_members_posts_columns', 'members_custom_columns');

/**
 * Заполняет кастомные столбцы данными
 */
add_action('manage_members_posts_custom_column', 'members_custom_columns_data', 10, 2);

/**
 * Добавление колонки "Личный кабинет" в список участников
 */
add_filter('manage_members_posts_columns', 'metoda_add_dashboard_column');

/**
 * Вывод кнопки доступа к ЛК в колонке
 */
add_action('manage_members_posts_custom_column', 'metoda_render_dashboard_column', 10, 2);

/**
 * Добавление колонок в список сообщений в админке
 */
add_filter('manage_member_message_posts_columns', 'metoda_add_message_columns');

/**
 * Вывод данных в колонках сообщений
 */
add_action('manage_member_message_posts_custom_column', 'metoda_render_message_columns', 10, 2);

// ============================================
// ADMIN HOOKS
// ============================================

// Добавляем подсказку по кропу изображений в медиа-библиотеку
add_action('admin_notices', 'add_image_crop_help_notice');

/**
 * Добавление страницы логов активности в админку
 */
add_action('admin_menu', 'metoda_add_activity_log_menu');

/**
 * Добавление ссылки на форум в админ-бар
 */
add_action('admin_bar_menu', 'metoda_add_forum_to_admin_bar', 100);

/**
 * Добавление пункта "Форум" в админ меню
 */
add_action('admin_menu', 'metoda_add_forum_admin_menu');

/**
 * Автосоздание всех важных страниц при загрузке админки
 */
add_action('admin_init', 'metoda_ensure_important_pages');

/**
 * Показываем уведомление о созданных страницах
 */
add_action('admin_notices', 'metoda_show_pages_created_notice');

// ============================================
// SCRIPTS/STYLES HOOKS
// ============================================

/**
 * Подключение скриптов и стилей для фронтенда
 */
add_action('wp_enqueue_scripts', 'members_enqueue_scripts');

// ============================================
// DASHBOARD HOOKS
// ============================================

/**
 * Добавляет виджет статистики участников в админку
 */
add_action('wp_dashboard_setup', 'members_add_dashboard_widget');

// ============================================
// AJAX HOOKS
// ============================================

// AJAX обработчик для скрытия уведомления
add_action('wp_ajax_dismiss_image_crop_notice', 'dismiss_image_crop_notice_ajax');

/**
 * AJAX обработчик регистрации нового участника
 */
add_action('wp_ajax_nopriv_member_register', 'member_register_ajax');

/**
 * AJAX обработчик изменения статуса участника (для менеджеров)
 */
add_action('wp_ajax_manager_change_member_status', 'manager_change_member_status_ajax');

/**
 * AJAX обработчик для сохранения галереи
 */
add_action('wp_ajax_member_save_gallery', 'member_save_gallery_ajax');

/**
 * AJAX обработчик для загрузки фото в галерею
 */
add_action('wp_ajax_member_upload_gallery_photo', 'member_upload_gallery_photo_ajax');

/**
 * AJAX обработчик для добавления материала (ссылка)
 */
add_action('wp_ajax_member_add_material_link', 'member_add_material_link_ajax');

/**
 * AJAX обработчик для добавления материала (файл)
 */
add_action('wp_ajax_member_add_material_file', 'member_add_material_file_ajax');

/**
 * AJAX обработчик для загрузки дополнительных участников (Load More)
 * SECURITY FIX v3.7.3: Добавлен nonce для защиты от CSRF
 */
add_action('wp_ajax_load_more_members', 'load_more_members_ajax');
add_action('wp_ajax_nopriv_load_more_members', 'load_more_members_ajax');

/**
 * AJAX обработчик для фильтрации участников
 * SECURITY FIX v3.7.3: Добавлен nonce для защиты от CSRF
 */
add_action('wp_ajax_filter_members', 'filter_members_ajax');
add_action('wp_ajax_nopriv_filter_members', 'filter_members_ajax');

/**
 * AJAX обработчик для добавления материала в портфолио (новая JSON система)
 */
add_action('wp_ajax_add_portfolio_material', 'ajax_add_portfolio_material');

/**
 * AJAX обработчик для удаления материала из портфолио (новая JSON система)
 */
add_action('wp_ajax_delete_portfolio_material', 'ajax_delete_portfolio_material');

/**
 * AJAX обработчик для редактирования материала портфолио (новая JSON система)
 */
add_action('wp_ajax_edit_portfolio_material', 'ajax_edit_portfolio_material');

/**
 * AJAX обработчик для создания темы форума из личного кабинета
 */
add_action('wp_ajax_create_forum_topic_dashboard', 'ajax_create_forum_topic_dashboard');

/**
 * AJAX обработчик для отправки личного сообщения
 */
add_action('wp_ajax_send_member_message', 'ajax_send_member_message');
add_action('wp_ajax_nopriv_send_member_message', 'ajax_send_member_message'); // Для незалогиненных

/**
 * AJAX обработчик для просмотра сообщения
 */
add_action('wp_ajax_view_member_message', 'ajax_view_member_message');
