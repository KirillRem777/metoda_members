<?php
/**
 * Plugin Name: Metoda Community MGMT
 * Description: Полнофункциональная система управления участниками и экспертами сообщества. Включает: регистрацию с валидацией, систему кодов доступа для импортированных участников, личные кабинеты с онбордингом, управление материалами с WYSIWYG-редактором, форум в стиле Reddit с категориями и лайками, настраиваемые email-шаблоны, CSV-импорт, кроппер фото, систему ролей и прав доступа, поиск и фильтрацию участников, OTP-аутентификацию через email.
 * Version: 4.2.0
 * Author: Kirill Rem
 * Text Domain: metoda-community-mgmt
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определяем константу пути к плагину для использования в шаблонах
if (!defined('METODA_PLUGIN_DIR')) {
    define('METODA_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// 🔴 ЯДЕРНАЯ КНОПКА: Полное отключение плагина
// Добавь в wp-config.php: define('METODA_DISABLE_PLUGIN', true);
if (defined('METODA_DISABLE_PLUGIN') && METODA_DISABLE_PLUGIN) {
    return; // Плагин ПОЛНОСТЬЮ отключен - ничего не загружается!
}

// 📦 ЗАГРУЗКА LEGACY СЛОЯ (v4.2.0 Refactoring)
// Глобальные функции и хуки извлечены для модульной архитектуры
// Этот слой обеспечивает обратную совместимость со старым кодом
require_once plugin_dir_path(__FILE__) . 'includes/legacy/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/legacy/hooks.php';

// ============================================================================
// 🎯 CORE MODULES (New Modular Architecture - Phase 2)
// ============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/core/class-post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/class-taxonomies.php';
require_once plugin_dir_path(__FILE__) . 'includes/core/class-assets.php';

// ============================================================================
// 🎯 ADMIN MODULES (New Modular Architecture - Phase 2)
// ============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-meta-boxes.php';

// ============================================================================
// 🎯 AUTH MODULES (New Modular Architecture - Phase 2)
// ============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/auth/class-security.php';

// ============================================================================
// 🎯 AJAX MODULES (New Modular Architecture - Phase 2)
// ============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/ajax/class-ajax-members.php';

// ============================================================================
// 🔧 ЗАГРУЗКА КЛАССОВ (Legacy Architecture)
// ============================================================================
// Все классы загружаются всегда (в админке и на фронтенде)
// Защита от редиректов реализована ВНУТРИ классов через is_admin()

// Классы которые нужны в админке (метабоксы, AJAX, админ страницы)
require_once plugin_dir_path(__FILE__) . 'includes/class-member-user-link.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-page-templates.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-csv-importer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-email-templates.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-access-codes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-otp.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-bulk-users.php';

// Классы с AJAX обработчиками (AJAX = admin context)
require_once plugin_dir_path(__FILE__) . 'includes/class-member-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-file-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-archive.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-forum.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-onboarding.php';

// Шаблоны (имеют внутреннюю защиту !is_admin())
require_once plugin_dir_path(__FILE__) . 'includes/class-member-template-loader.php';

// Notification System (v5.0.0)
require_once plugin_dir_path(__FILE__) . 'includes/class-member-notification-email.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-notification-telegram.php';
require_once plugin_dir_path(__FILE__) . 'includes/notifications/class-email-reply-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/notifications/class-notification-dispatcher.php';
require_once plugin_dir_path(__FILE__) . 'includes/auth/class-otp-auth.php';

// ============================================================================
// 🚀 ИНИЦИАЛИЗАЦИЯ CORE MODULES (New Architecture)
// ============================================================================
new Metoda_Post_Types();
new Metoda_Taxonomies();
new Metoda_Assets();
new Metoda_Ajax_Members();

// Notification System (v5.0.0)
new Metoda_Email_Reply_Handler();
new Metoda_OTP_Auth();
new Metoda_Notification_Dispatcher();

// Admin modules (only in admin context)
if (is_admin()) {
    new Metoda_Meta_Boxes();
}

// ============================================================================
// 🚀 ИНИЦИАЛИЗАЦИЯ LEGACY КЛАССОВ
// ============================================================================
// Создаём экземпляры классов для регистрации хуков и шорткодов
new Member_Dashboard();
new Member_File_Manager();
new Member_Manager();
new Member_Archive();
new Member_Forum();
new Member_Onboarding();
new Member_Template_Loader();
new Member_Access_Codes();
new Member_OTP();

// ================================================================
// LEGACY CODE - MOVED TO includes/legacy/
// ================================================================
//
// This section has been moved to:
// - includes/legacy/functions.php (62 functions)
// - includes/legacy/hooks.php (47 hooks)
//
// Keeping this code here as reference (disabled via if(false)).
// Will be removed in Phase 2 of refactoring.
//
// Date moved: 2025-11-22
// ================================================================

if (false) { // LEGACY CODE DISABLED - All functions/hooks loaded from includes/legacy/

/**
 * Activation hook: создаём страницы при активации плагина
 */
function metoda_plugin_activation() {
    // Сбрасываем время последней проверки, чтобы страницы создались сразу
    delete_option('metoda_pages_check');
}
register_activation_hook(__FILE__, 'metoda_plugin_activation');

/**
 * SECURITY v3.7.3: Единая функция проверки прав на редактирование member_id
 *
 * Логика:
 * - Админ + member_id в запросе → редактирует чужой профиль (admin bypass)
 * - Обычный юзер → редактирует только свой профиль (игнорируем member_id из запроса)
 *
 * @param array $request POST или GET массив с данными
 * @return int|WP_Error member_id или ошибка
 */
function get_editable_member_id($request = null) {
    // Если не передан массив, используем $_POST по умолчанию
    if ($request === null) {
        $request = $_POST;
    }

    $is_admin = current_user_can('administrator');
    $requested_member_id = isset($request['member_id']) ? absint($request['member_id']) : null;

    // СЦЕНАРИЙ 1: Админ редактирует чужой профиль
    if ($is_admin && $requested_member_id) {
        // Проверяем существование member post
        $member_post = get_post($requested_member_id);

        if (!$member_post || $member_post->post_type !== 'members') {
            return new WP_Error(
                'invalid_member',
                'Участник не найден или имеет неверный тип',
                array('member_id' => $requested_member_id)
            );
        }

        // Проверяем что участник не в корзине
        if ($member_post->post_status === 'trash') {
            return new WP_Error(
                'member_trashed',
                'Участник находится в корзине',
                array('member_id' => $requested_member_id)
            );
        }

        return $requested_member_id;
    }

    // СЦЕНАРИЙ 2: Обычный пользователь (или админ без member_id) → редактирует свой профиль
    $current_member_id = Member_User_Link::get_current_user_member_id();

    if (!$current_member_id) {
        return new WP_Error(
            'no_member_linked',
            'Ваш аккаунт не привязан к профилю участника',
            array('user_id' => get_current_user_id())
        );
    }

    return $current_member_id;
}

// Хуки активации/деактивации плагина
register_activation_hook(__FILE__, 'metoda_members_activate');
register_deactivation_hook(__FILE__, 'metoda_members_deactivate');

/**
 * Функция активации плагина
 */
function metoda_members_activate() {
    // КРИТИЧНО: Блокируем ВСЕ редиректы на 5 минут
    set_transient('metoda_members_activating', true, 300);

    // Debug: записываем что активация началась
    update_option('metoda_activation_started', current_time('mysql'));

    try {
        // Регистрируем post types
        register_members_post_type();

        // Register forum post type (call the method directly during activation)
        if (class_exists('Member_Forum')) {
            $forum = new Member_Forum();
            $forum->register_post_type();
            $forum->register_taxonomies();
        }

        // Регистрируем таксономии
        register_member_type_taxonomy();
        register_member_role_taxonomy();
        register_member_location_taxonomy();

        // Создаем роли
        metoda_create_custom_roles();

        // Создаем дефолтные термины таксономий
        $terms_created = 0;
        if (!term_exists('Эксперт', 'member_type')) {
            wp_insert_term('Эксперт', 'member_type');
            $terms_created++;
        }
        if (!term_exists('Участник', 'member_type')) {
            wp_insert_term('Участник', 'member_type');
            $terms_created++;
        }

        $roles = ['Эксперт', 'Куратор секции', 'Лидер проектной группы', 'Амбассадор',
                  'Почетный член', 'Партнер', 'Активист', 'Слушатель', 'Волонтер'];
        foreach ($roles as $role) {
            if (!term_exists($role, 'member_role')) {
                wp_insert_term($role, 'member_role');
                $terms_created++;
            }
        }

        // Создаем шаблонные страницы (отложено - запланировано на следующую загрузку)
        update_option('metoda_needs_page_creation', '1');

        // Устанавливаем флаг, что страницы форума созданы
        update_option('metoda_forum_pages_created', '1');

        // Сбрасываем постоянные ссылки
        flush_rewrite_rules();

        // Debug: записываем успешную активацию
        update_option('metoda_activation_completed', current_time('mysql'));
        update_option('metoda_activation_terms_created', $terms_created);

    } catch (Exception $e) {
        // Debug: записываем ошибку
        update_option('metoda_activation_error', $e->getMessage());
    }
}

/**
 * Создание кастомных ролей
 */
function metoda_create_custom_roles() {
    // Роль участника/эксперта
    add_role('member', 'Участник', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'upload_files' => true
    ));

    add_role('expert', 'Эксперт', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
        'upload_files' => true
    ));

    // Роль менеджера
    add_role('manager', 'Менеджер', array(
        'read' => true,
        'edit_posts' => true,
        'edit_others_posts' => true,
        'edit_published_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_others_posts' => true,
        'delete_published_posts' => true,
        'upload_files' => true,
        'manage_members' => true
    ));

    // Add manage_members capability to administrators
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_members');
    }
}

/**
 * Создание шаблонных страниц
 */
function metoda_create_template_pages() {
    $pages = array(
        array(
            'title' => 'Участники',
            'slug' => 'uchastniki',
            'content' => '[members_directory]',
            'option' => 'metoda_members_page_id'
        ),
        array(
            'title' => 'Регистрация участника',
            'slug' => 'member-registration',
            'content' => '[member_registration]',
            'option' => 'metoda_registration_page_id'
        ),
        array(
            'title' => 'Личный кабинет',
            'slug' => 'member-dashboard',
            'content' => '[member_dashboard]',
            'option' => 'metoda_dashboard_page_id'
        ),
        array(
            'title' => 'Добро пожаловать',
            'slug' => 'member-onboarding',
            'content' => '[member_onboarding]',
            'option' => 'metoda_onboarding_page_id'
        ),
        array(
            'title' => 'Форум',
            'slug' => 'forum',
            'content' => '[member_forum]',
            'option' => 'metoda_forum_page_id'
        ),
        array(
            'title' => 'Панель менеджера',
            'slug' => 'manager-panel',
            'content' => '[manager_panel]',
            'option' => 'metoda_manager_page_id'
        ),
        array(
            'title' => 'Вход',
            'slug' => 'login',
            'content' => '[custom_login]',
            'option' => 'metoda_login_page_id'
        )
    );

    foreach ($pages as $page_data) {
        // Проверяем, не создана ли уже эта страница
        $page_id = get_option($page_data['option']);

        if (!$page_id || !get_post($page_id)) {
            // Создаем страницу
            $page_id = wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_name' => $page_data['slug'],
                'post_content' => $page_data['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));

            // Сохраняем ID страницы в опциях
            if ($page_id && !is_wp_error($page_id)) {
                update_option($page_data['option'], $page_id);
            }
        }
    }
}

/**
 * Создание страниц после активации (отложенно)
 * Вызывается один раз при первой загрузке админки после активации
 */
function metoda_create_pages_deferred() {
    // Проверяем флаг
    if (get_option('metoda_needs_page_creation') !== '1') {
        return;
    }

    // Только для администраторов
    if (!current_user_can('manage_options')) {
        return;
    }

    // Создаем страницы
    metoda_create_template_pages();

    // Удаляем флаг
    delete_option('metoda_needs_page_creation');

    // Debug
    update_option('metoda_pages_created_at', current_time('mysql'));
}
add_action('admin_init', 'metoda_create_pages_deferred', 1);

/**
 * Функция деактивации плагина
 */
function metoda_members_deactivate() {
    // Сбрасываем постоянные ссылки
    flush_rewrite_rules();

    // Очищаем debug опции
    delete_option('metoda_activation_started');
    delete_option('metoda_activation_completed');
    delete_option('metoda_activation_error');
    delete_option('metoda_activation_terms_created');
    delete_option('metoda_needs_page_creation');
    delete_option('metoda_pages_created_at');
}

// Регистрация Custom Post Type
function register_members_post_type() {
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
add_action('init', 'register_members_post_type');

/**
 * Регистрация Custom Post Type для личных сообщений
 */
function register_member_messages_post_type() {
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
add_action('init', 'register_member_messages_post_type');

// Регистрация специальных размеров изображений для участников
function register_member_image_sizes() {
    // Квадратная аватарка - будет кропиться в центр
    add_image_size('member-avatar', 400, 400, true); // hard crop

    // Размер для карточек в списке
    add_image_size('member-card', 300, 300, true); // hard crop

    // Размер для хедера профиля
    add_image_size('member-profile', 500, 500, true); // hard crop
}
add_action('after_setup_theme', 'register_member_image_sizes');

// Добавляем подсказку по кропу изображений в медиа-библиотеку
function add_image_crop_help_notice() {
    $screen = get_current_screen();

    // Показываем только на страницах редактирования участников и в медиа-библиотеке
    if (!$screen || ($screen->post_type !== 'members' && $screen->id !== 'upload')) {
        return;
    }

    // Проверяем, не скрыл ли пользователь уведомление
    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'dismissed_image_crop_notice', true)) {
        return;
    }

    ?>
    <div class="notice notice-warning is-dismissible" data-dismissible="image-crop-notice">
        <h3>⚠️ Важно! Настройка кропа фотографий участников</h3>

        <p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 10px 0;">
            <strong>Внимание:</strong> Автоматический кроп WordPress обрезает по математическому центру и может отрезать голову!
            Для правильного кропа нужна <strong>ручная настройка</strong> каждой фотографии.
        </p>

        <h4 style="margin-top: 15px;">✅ Рекомендуемое решение: Плагин Manual Image Crop</h4>
        <p>Этот плагин позволяет вручную выбрать область кропа для каждого размера изображения:</p>
        <ol style="margin-left: 20px; margin-bottom: 15px;">
            <li>Установите плагин <a href="<?php echo admin_url('plugin-install.php?s=manual+image+crop&tab=search'); ?>" target="_blank"><strong>Manual Image Crop</strong></a></li>
            <li>Откройте любую фотографию в медиа-библиотеке</li>
            <li>Нажмите <strong>"Crop thumbnails"</strong> под изображением</li>
            <li>Выберите нужный размер (member-avatar, member-card и т.д.)</li>
            <li>Вручную выделите область с лицом → Сохраните</li>
        </ol>

        <div style="background: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 10px 0;">
            <strong>💡 Совет:</strong> Лучше всего загружать уже обрезанные квадратные фото 500×500px или больше.
            Тогда не придется ничего кропить вручную.
        </div>

        <h4 style="margin-top: 15px;">📏 Размеры изображений для участников:</h4>
        <ul style="margin-left: 20px; margin-bottom: 15px;">
            <li><code>member-avatar</code> - 400×400px (используется в профиле)</li>
            <li><code>member-card</code> - 300×300px (используется в карточках списка)</li>
            <li><code>member-profile</code> - 500×500px (используется в шапке профиля)</li>
        </ul>

        <h4 style="margin-top: 15px;">🎯 Альтернативные варианты:</h4>
        <ul style="margin-left: 20px;">
            <li><strong>Встроенный редактор WP:</strong> Медиафайлы → выбрать фото → Редактировать изображение → Кадрирование (но придется делать вручную для каждой миниатюры)</li>
            <li><strong>Плагин Crop-Thumbnails:</strong> Альтернатива Manual Image Crop с похожим функционалом</li>
            <li><strong>AI-кроп (платно):</strong> Smush Pro или ShortPixel Adaptive Images - умное распознавание лиц</li>
        </ul>

        <p style="margin-top: 15px;">
            <a href="<?php echo admin_url('plugin-install.php?s=manual+image+crop&tab=search'); ?>" class="button button-primary">📦 Установить Manual Image Crop (рекомендуется)</a>
            <a href="<?php echo admin_url('plugin-install.php?s=crop-thumbnails&tab=search'); ?>" class="button">Установить Crop-Thumbnails</a>
            <a href="<?php echo admin_url('upload.php'); ?>" class="button">Перейти к медиафайлам</a>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Обработка закрытия уведомления
        $(document).on('click', '.notice[data-dismissible="image-crop-notice"] .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'dismiss_image_crop_notice',
                nonce: '<?php echo wp_create_nonce('dismiss_image_crop_notice'); ?>'
            });
        });
    });
    </script>
    <?php
}
add_action('admin_notices', 'add_image_crop_help_notice');

// AJAX обработчик для скрытия уведомления
function dismiss_image_crop_notice_ajax() {
    check_ajax_referer('dismiss_image_crop_notice', 'nonce');

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'dismissed_image_crop_notice', true);

    wp_send_json_success();
}
add_action('wp_ajax_dismiss_image_crop_notice', 'dismiss_image_crop_notice_ajax');

// Регистрация таксономии для типов участников (Эксперт/Участник)
function register_member_type_taxonomy() {
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
add_action('init', 'register_member_type_taxonomy');

// Регистрация таксономии для ролей в ассоциации
function register_member_role_taxonomy() {
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
add_action('init', 'register_member_role_taxonomy');

// Регистрация таксономии для локаций
function register_member_location_taxonomy() {
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
add_action('init', 'register_member_location_taxonomy');

// Добавление метабоксов для дополнительных полей
function add_member_meta_boxes() {
    add_meta_box(
        'member_details',
        'Детали участника',
        'render_member_details_meta_box',
        'members',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_member_meta_boxes');

// Рендер метабокса
function render_member_details_meta_box($post) {
    wp_nonce_field('member_details_meta_box', 'member_details_meta_box_nonce');

    // Основные поля
    $position = get_post_meta($post->ID, 'member_position', true);
    $company = get_post_meta($post->ID, 'member_company', true);
    $city = get_post_meta($post->ID, 'member_city', true);

    // Новые поля по требованиям
    $specialization_experience = get_post_meta($post->ID, 'member_specialization_experience', true);
    $professional_interests = get_post_meta($post->ID, 'member_professional_interests', true);
    $expectations = get_post_meta($post->ID, 'member_expectations', true);
    $bio = get_post_meta($post->ID, 'member_bio', true);

    // Дополнительные поля
    $email = get_post_meta($post->ID, 'member_email', true);
    $phone = get_post_meta($post->ID, 'member_phone', true);
    $linkedin = get_post_meta($post->ID, 'member_linkedin', true);
    $website = get_post_meta($post->ID, 'member_website', true);
    $gallery_ids = get_post_meta($post->ID, 'member_gallery', true);

    // Данные для табов
    $testimonials = get_post_meta($post->ID, 'member_testimonials', true);
    $gratitudes = get_post_meta($post->ID, 'member_gratitudes', true);
    $interviews = get_post_meta($post->ID, 'member_interviews', true);
    $videos = get_post_meta($post->ID, 'member_videos', true);
    $reviews = get_post_meta($post->ID, 'member_reviews', true);
    $developments = get_post_meta($post->ID, 'member_developments', true);
    ?>
    <style>
        .member-field-group { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1; }
        .member-field-group h4 { margin-top: 0; color: #2271b1; }
        .member-repeater-item { background: white; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .member-repeater-item textarea { width: 100%; }
        .button-remove { color: #b32d2e; border-color: #b32d2e; }
        .button-remove:hover { background: #b32d2e; color: white; }
    </style>

    <div class="member-field-group">
        <h4>Основная информация</h4>
        <table class="form-table">
            <tr>
                <th><label for="member_company">Компания</label></th>
                <td><input type="text" id="member_company" name="member_company" value="<?php echo esc_attr($company); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label for="member_position">Должность</label></th>
                <td><input type="text" id="member_position" name="member_position" value="<?php echo esc_attr($position); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label for="member_city">Город</label></th>
                <td><input type="text" id="member_city" name="member_city" value="<?php echo esc_attr($city); ?>" class="regular-text" /></td>
            </tr>
        </table>
    </div>

    <div class="member-field-group">
        <h4>Специализация и стаж</h4>
        <p class="description">Каждый пункт с новой строки. Поддерживается форматирование: <code>• Название — X лет</code></p>
        <textarea id="member_specialization_experience" name="member_specialization_experience" rows="8" class="large-text code"><?php echo esc_textarea($specialization_experience); ?></textarea>
        <p class="description">Пример:<br>• Бизнес-тренер — 19 лет<br>• Методолог — 5 лет</p>
    </div>

    <div class="member-field-group">
        <h4>Сфера профессиональных интересов</h4>
        <p class="description">Каждый интерес с новой строки. Поддерживается форматирование: <code>• Название области</code></p>
        <textarea id="member_professional_interests" name="member_professional_interests" rows="8" class="large-text code"><?php echo esc_textarea($professional_interests); ?></textarea>
        <p class="description">Пример:<br>• Методология обучения взрослых<br>• Командообразование</p>
    </div>

    <div class="member-field-group">
        <h4>Ожидания от сотрудничества</h4>
        <?php
        wp_editor($expectations, 'member_expectations', array(
            'textarea_name' => 'member_expectations',
            'textarea_rows' => 8,
            'media_buttons' => false,
            'teeny' => true,
            'quicktags' => true
        ));
        ?>
    </div>

    <div class="member-field-group">
        <h4>О себе</h4>
        <?php
        wp_editor($bio, 'member_bio', array(
            'textarea_name' => 'member_bio',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => false,
            'quicktags' => true
        ));
        ?>
    </div>

    <div class="member-field-group">
        <h4>Контактные данные</h4>
        <table class="form-table">
            <tr>
                <th><label for="member_email">Email</label></th>
                <td><input type="email" id="member_email" name="member_email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="member_phone">Телефон</label></th>
                <td><input type="tel" id="member_phone" name="member_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" /></td>
            </tr>
        </table>
    </div>

    <div class="member-field-group">
        <h4>Социальные сети и сайты</h4>
        <table class="form-table">
            <tr>
                <th><label for="member_linkedin">LinkedIn</label></th>
                <td><input type="url" id="member_linkedin" name="member_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/in/username" /></td>
            </tr>
            <tr>
                <th><label for="member_website">Вебсайт</label></th>
                <td><input type="url" id="member_website" name="member_website" value="<?php echo esc_attr($website); ?>" class="regular-text" /></td>
            </tr>
        </table>
    </div>

    <hr style="margin: 30px 0;">
    <h3>Галерея фотографий</h3>
    <p class="description">Если добавлено более одной фотографии, на странице участника будет отображаться слайдер</p>
    <div id="member-gallery-container">
        <input type="hidden" id="member_gallery" name="member_gallery" value="<?php echo esc_attr($gallery_ids); ?>">
        <button type="button" class="button upload-gallery-button">Добавить фотографии</button>
        <div id="gallery-preview" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 10px;">
            <?php
            if ($gallery_ids) {
                $ids = explode(',', $gallery_ids);
                foreach ($ids as $id) {
                    $img_url = wp_get_attachment_image_url($id, 'thumbnail');
                    if ($img_url) {
                        echo '<div class="gallery-item" data-id="' . $id . '" style="position: relative;">
                            <img src="' . esc_url($img_url) . '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">
                            <button type="button" class="remove-gallery-item" style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; line-height: 1;">×</button>
                        </div>';
                    }
                }
            }
            ?>
        </div>
    </div>

    <hr style="margin: 30px 0;">
    <h3>📂 Портфолио и достижения</h3>
    <p class="description">Добавляйте отзывы, благодарности, интервью, видео, рецензии и разработки. Каждая категория может содержать текст, файлы или ссылки.</p>

    <?php
    // Получаем данные для материалов (теперь в формате JSON)
    $testimonials_data = get_post_meta($post->ID, 'member_testimonials_data', true);
    $gratitudes_data = get_post_meta($post->ID, 'member_gratitudes_data', true);
    $interviews_data = get_post_meta($post->ID, 'member_interviews_data', true);
    $videos_data = get_post_meta($post->ID, 'member_videos_data', true);
    $reviews_data = get_post_meta($post->ID, 'member_reviews_data', true);
    $developments_data = get_post_meta($post->ID, 'member_developments_data', true);

    $testimonials_data = $testimonials_data ? json_decode($testimonials_data, true) : array();
    $gratitudes_data = $gratitudes_data ? json_decode($gratitudes_data, true) : array();
    $interviews_data = $interviews_data ? json_decode($interviews_data, true) : array();
    $videos_data = $videos_data ? json_decode($videos_data, true) : array();
    $reviews_data = $reviews_data ? json_decode($reviews_data, true) : array();
    $developments_data = $developments_data ? json_decode($developments_data, true) : array();

    // Функция для рендера repeater поля
    function render_material_repeater($field_name, $label, $data, $icon = '📝') {
        ?>
        <div class="member-field-group">
            <h4><?php echo $icon; ?> <?php echo $label; ?> <span class="material-count">(<?php echo count($data); ?>)</span></h4>
            <div class="material-repeater" data-field="<?php echo $field_name; ?>">
                <div class="material-items">
                    <?php
                    if (!empty($data)) {
                        foreach ($data as $index => $item) {
                            render_material_item($field_name, $index, $item);
                        }
                    }
                    ?>
                </div>
                <button type="button" class="button button-primary add-material-item" data-field="<?php echo $field_name; ?>">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span> Добавить
                </button>
            </div>
        </div>
        <?php
    }

    // Функция для рендера одного элемента
    function render_material_item($field_name, $index, $item = array()) {
        $type = isset($item['type']) ? $item['type'] : 'text';
        $title = isset($item['title']) ? $item['title'] : '';
        $content = isset($item['content']) ? $item['content'] : '';
        $url = isset($item['url']) ? $item['url'] : '';
        $file_id = isset($item['file_id']) ? $item['file_id'] : '';
        $author = isset($item['author']) ? $item['author'] : '';
        $date = isset($item['date']) ? $item['date'] : '';
        $description = isset($item['description']) ? $item['description'] : '';
        ?>
        <div class="member-repeater-item" data-index="<?php echo $index; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <select name="<?php echo $field_name; ?>[<?php echo $index; ?>][type]" class="material-type-select" style="width: 150px;">
                    <option value="text" <?php selected($type, 'text'); ?>>💬 Текст</option>
                    <option value="file" <?php selected($type, 'file'); ?>>📄 Файл</option>
                    <option value="link" <?php selected($type, 'link'); ?>>🔗 Ссылка</option>
                    <option value="video" <?php selected($type, 'video'); ?>>🎥 Видео</option>
                </select>
                <button type="button" class="button button-remove remove-material-item">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Удалить
                </button>
            </div>

            <table class="form-table" style="margin: 0;">
                <tr>
                    <th style="width: 150px;"><label>Заголовок</label></th>
                    <td><input type="text" name="<?php echo $field_name; ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($title); ?>" class="large-text" placeholder="Название материала"></td>
                </tr>

                <!-- Поле для текста с WYSIWYG редактором -->
                <tr class="field-text" style="display: <?php echo $type === 'text' ? 'table-row' : 'none'; ?>;">
                    <th><label>Текст</label></th>
                    <td>
                        <?php
                        $editor_id = $field_name . '_' . $index . '_content';
                        $editor_id = str_replace(array('[', ']'), '_', $editor_id);

                        wp_editor($content, $editor_id, array(
                            'textarea_name' => $field_name . '[' . $index . '][content]',
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                            'teeny' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,blockquote',
                                'toolbar2' => '',
                            ),
                            'quicktags' => array('buttons' => 'strong,em,ul,ol,li,link,close'),
                        ));
                        ?>
                        <p class="description">Используйте редактор для форматирования текста: жирный, курсив, списки, ссылки.</p>
                    </td>
                </tr>

                <!-- Поле для файла -->
                <tr class="field-file" style="display: <?php echo $type === 'file' ? 'table-row' : 'none'; ?>;">
                    <th><label>Файл</label></th>
                    <td>
                        <input type="hidden" name="<?php echo $field_name; ?>[<?php echo $index; ?>][file_id]" value="<?php echo esc_attr($file_id); ?>" class="material-file-id">
                        <button type="button" class="button upload-material-file">
                            <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span> Выбрать файл
                        </button>
                        <div class="material-file-preview" style="margin-top: 10px;">
                            <?php if ($file_id):
                                $file_url = wp_get_attachment_url($file_id);
                                $file_name = basename($file_url);
                            ?>
                                <div style="padding: 10px; background: #f0f0f0; border-radius: 4px; display: inline-block;">
                                    📎 <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html($file_name); ?></a>
                                    <button type="button" class="button button-small remove-file" style="margin-left: 10px;">×</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <!-- Поле для ссылки -->
                <tr class="field-link" style="display: <?php echo $type === 'link' ? 'table-row' : 'none'; ?>;">
                    <th><label>Ссылка</label></th>
                    <td><input type="url" name="<?php echo $field_name; ?>[<?php echo $index; ?>][url]" value="<?php echo esc_attr($url); ?>" class="large-text" placeholder="https://example.com"></td>
                </tr>

                <!-- Поле для видео -->
                <tr class="field-video" style="display: <?php echo $type === 'video' ? 'table-row' : 'none'; ?>;">
                    <th><label>Видео URL</label></th>
                    <td>
                        <input type="url" name="<?php echo $field_name; ?>[<?php echo $index; ?>][url]" value="<?php echo esc_attr($url); ?>" class="large-text" placeholder="https://rutube.ru/video/... или https://vk.com/video...">
                        <p class="description">Поддерживаются: Rutube, VK Video, YouTube</p>
                    </td>
                </tr>

                <!-- Общие поля -->
                <tr>
                    <th><label>Автор/Источник</label></th>
                    <td><input type="text" name="<?php echo $field_name; ?>[<?php echo $index; ?>][author]" value="<?php echo esc_attr($author); ?>" class="regular-text" placeholder="Имя автора или источника"></td>
                </tr>
                <tr>
                    <th><label>Дата</label></th>
                    <td><input type="date" name="<?php echo $field_name; ?>[<?php echo $index; ?>][date]" value="<?php echo esc_attr($date); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Описание</label></th>
                    <td><input type="text" name="<?php echo $field_name; ?>[<?php echo $index; ?>][description]" value="<?php echo esc_attr($description); ?>" class="large-text" placeholder="Краткое описание (опционально)"></td>
                </tr>
            </table>
        </div>
        <?php
    }

    // Рендерим repeater для каждой категории
    render_material_repeater('member_testimonials_data', 'Отзывы', $testimonials_data, '💬');
    render_material_repeater('member_gratitudes_data', 'Благодарности', $gratitudes_data, '🏆');
    render_material_repeater('member_interviews_data', 'Интервью', $interviews_data, '🎤');
    render_material_repeater('member_videos_data', 'Видео', $videos_data, '🎥');
    render_material_repeater('member_reviews_data', 'Рецензии', $reviews_data, '📝');
    render_material_repeater('member_developments_data', 'Разработки', $developments_data, '💾');
    ?>

    <script>
    jQuery(document).ready(function($) {
        // Загрузка галереи
        var frame;
        $('.upload-gallery-button').on('click', function(e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Выберите фотографии',
                multiple: true,
                library: { type: 'image' },
                button: { text: 'Добавить в галерею' }
            });

            frame.on('select', function() {
                var selection = frame.state().get('selection');
                var currentIds = $('#member_gallery').val();
                var idsArray = currentIds ? currentIds.split(',') : [];

                selection.map(function(attachment) {
                    attachment = attachment.toJSON();
                    idsArray.push(attachment.id);

                    var html = '<div class="gallery-item" data-id="' + attachment.id + '" style="position: relative;">' +
                        '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px;">' +
                        '<button type="button" class="remove-gallery-item" style="position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; line-height: 1;">×</button>' +
                        '</div>';
                    $('#gallery-preview').append(html);
                });

                $('#member_gallery').val(idsArray.join(','));
            });

            frame.open();
        });

        // Удаление фото из галереи
        $(document).on('click', '.remove-gallery-item', function() {
            var $item = $(this).parent();
            var idToRemove = $item.data('id');
            var currentIds = $('#member_gallery').val();
            var idsArray = currentIds.split(',');
            idsArray = idsArray.filter(function(id) { return id != idToRemove; });
            $('#member_gallery').val(idsArray.join(','));
            $item.remove();
        });

        // === REPEATER ПОЛЯ ДЛЯ МАТЕРИАЛОВ ===

        // Добавление нового элемента
        $('.add-material-item').on('click', function() {
            var $button = $(this);
            var fieldName = $button.data('field');
            var $container = $button.siblings('.material-items');
            var index = $container.find('.member-repeater-item').length;

            // Создаем уникальный ID для редактора
            var editorId = fieldName.replace(/\[/g, '_').replace(/\]/g, '_') + index + '_content';

            var html = `
                <div class="member-repeater-item" data-index="${index}">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <select name="${fieldName}[${index}][type]" class="material-type-select" style="width: 150px;">
                            <option value="text">💬 Текст</option>
                            <option value="file">📄 Файл</option>
                            <option value="link">🔗 Ссылка</option>
                            <option value="video">🎥 Видео</option>
                        </select>
                        <button type="button" class="button button-remove remove-material-item">
                            <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Удалить
                        </button>
                    </div>

                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th style="width: 150px;"><label>Заголовок</label></th>
                            <td><input type="text" name="${fieldName}[${index}][title]" value="" class="large-text" placeholder="Название материала"></td>
                        </tr>
                        <tr class="field-text">
                            <th><label>Текст</label></th>
                            <td>
                                <div id="wp-${editorId}-wrap" class="wp-core-ui wp-editor-wrap html-active">
                                    <div id="wp-${editorId}-editor-container" class="wp-editor-container">
                                        <textarea id="${editorId}" name="${fieldName}[${index}][content]" class="wp-editor-area" rows="10" style="width: 100%;"></textarea>
                                    </div>
                                </div>
                                <p class="description">Используйте редактор для форматирования текста. Сохраните изменения, чтобы активировать полный редактор.</p>
                            </td>
                        </tr>
                        <tr class="field-file" style="display: none;">
                            <th><label>Файл</label></th>
                            <td>
                                <input type="hidden" name="${fieldName}[${index}][file_id]" value="" class="material-file-id">
                                <button type="button" class="button upload-material-file">
                                    <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span> Выбрать файл
                                </button>
                                <div class="material-file-preview" style="margin-top: 10px;"></div>
                            </td>
                        </tr>
                        <tr class="field-link" style="display: none;">
                            <th><label>Ссылка</label></th>
                            <td><input type="url" name="${fieldName}[${index}][url]" value="" class="large-text" placeholder="https://example.com"></td>
                        </tr>
                        <tr class="field-video" style="display: none;">
                            <th><label>Видео URL</label></th>
                            <td>
                                <input type="url" name="${fieldName}[${index}][url]" value="" class="large-text" placeholder="https://rutube.ru/video/... или https://vk.com/video...">
                                <p class="description">Поддерживаются: Rutube, VK Video, YouTube</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Автор/Источник</label></th>
                            <td><input type="text" name="${fieldName}[${index}][author]" value="" class="regular-text" placeholder="Имя автора или источника"></td>
                        </tr>
                        <tr>
                            <th><label>Дата</label></th>
                            <td><input type="date" name="${fieldName}[${index}][date]" value="" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label>Описание</label></th>
                            <td><input type="text" name="${fieldName}[${index}][description]" value="" class="large-text" placeholder="Краткое описание (опционально)"></td>
                        </tr>
                    </table>
                </div>
            `;

            $container.append(html);
            updateMaterialCount($button.closest('.member-field-group'));

            // Инициализируем TinyMCE для нового textarea
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.initialize(editorId, {
                    tinymce: {
                        wpautop: true,
                        toolbar1: 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,blockquote',
                        toolbar2: ''
                    },
                    quicktags: {buttons: 'strong,em,ul,ol,li,link,close'},
                    mediaButtons: false,
                });
            }
        });

        // Удаление элемента
        $(document).on('click', '.remove-material-item', function() {
            var $item = $(this).closest('.member-repeater-item');
            var $group = $item.closest('.member-field-group');

            // Удаляем TinyMCE редактор если он существует
            var $editor = $item.find('.wp-editor-area');
            if ($editor.length > 0 && typeof wp !== 'undefined' && wp.editor) {
                var editorId = $editor.attr('id');
                wp.editor.remove(editorId);
            }

            $item.remove();
            updateMaterialCount($group);
        });

        // Переключение типа поля
        $(document).on('change', '.material-type-select', function() {
            var type = $(this).val();
            var $item = $(this).closest('.member-repeater-item');

            $item.find('.field-text, .field-file, .field-link, .field-video').hide();
            $item.find('.field-' + type).show();
        });

        // Загрузка файла
        var fileFrame;
        $(document).on('click', '.upload-material-file', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $item = $button.closest('.member-repeater-item');
            var $fileInput = $item.find('.material-file-id');
            var $preview = $item.find('.material-file-preview');

            if (fileFrame) {
                fileFrame.open();
                return;
            }

            fileFrame = wp.media({
                title: 'Выберите файл',
                multiple: false,
                button: { text: 'Использовать этот файл' }
            });

            fileFrame.on('select', function() {
                var attachment = fileFrame.state().get('selection').first().toJSON();
                $fileInput.val(attachment.id);

                var html = '<div style="padding: 10px; background: #f0f0f0; border-radius: 4px; display: inline-block;">' +
                    '📎 <a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' +
                    '<button type="button" class="button button-small remove-file" style="margin-left: 10px;">×</button>' +
                    '</div>';
                $preview.html(html);
            });

            fileFrame.open();
        });

        // Удаление файла
        $(document).on('click', '.remove-file', function() {
            var $item = $(this).closest('.member-repeater-item');
            $item.find('.material-file-id').val('');
            $item.find('.material-file-preview').empty();
        });

        // Обновление счетчика материалов
        function updateMaterialCount($group) {
            var count = $group.find('.member-repeater-item').length;
            $group.find('.material-count').text('(' + count + ')');
        }
    });
    </script>
    <?php
}

// Сохранение метаданных
function save_member_details($post_id) {
    if (!isset($_POST['member_details_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['member_details_meta_box_nonce'], 'member_details_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Текстовые поля
    $text_fields = array(
        'member_position',
        'member_company',
        'member_city',
        'member_email',
        'member_phone',
        'member_linkedin',
        'member_website',
        'member_gallery'
    );

    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Textarea поля (могут содержать переносы строк)
    $textarea_fields = array(
        'member_specialization_experience',
        'member_professional_interests',
        'member_testimonials',
        'member_gratitudes',
        'member_interviews',
        'member_videos',
        'member_reviews',
        'member_developments'
    );

    foreach ($textarea_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_textarea_field($_POST[$field]));
        }
    }

    // HTML/WYSIWYG поля (разрешаем безопасный HTML)
    $html_fields = array(
        'member_expectations',
        'member_bio'
    );

    foreach ($html_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, wp_kses_post($_POST[$field]));
        }
    }

    // Сохранение repeater полей для материалов (в формате JSON)
    $material_fields = array(
        'member_testimonials_data',
        'member_gratitudes_data',
        'member_interviews_data',
        'member_videos_data',
        'member_reviews_data',
        'member_developments_data'
    );

    foreach ($material_fields as $field) {
        if (isset($_POST[$field]) && is_array($_POST[$field])) {
            // Очищаем и валидируем данные
            $clean_data = array();
            foreach ($_POST[$field] as $item) {
                $clean_item = array(
                    'type' => isset($item['type']) ? sanitize_text_field($item['type']) : 'text',
                    'title' => isset($item['title']) ? sanitize_text_field($item['title']) : '',
                    'content' => isset($item['content']) ? sanitize_textarea_field($item['content']) : '',
                    'url' => isset($item['url']) ? esc_url_raw($item['url']) : '',
                    'file_id' => isset($item['file_id']) ? intval($item['file_id']) : 0,
                    'author' => isset($item['author']) ? sanitize_text_field($item['author']) : '',
                    'date' => isset($item['date']) ? sanitize_text_field($item['date']) : '',
                    'description' => isset($item['description']) ? sanitize_text_field($item['description']) : '',
                );
                $clean_data[] = $clean_item;
            }
            update_post_meta($post_id, $field, wp_json_encode($clean_data, JSON_UNESCAPED_UNICODE));
        } else {
            // Если поле пустое - сохраняем пустой массив
            update_post_meta($post_id, $field, wp_json_encode(array(), JSON_UNESCAPED_UNICODE));
        }
    }
}
add_action('save_post_members', 'save_member_details');

// Шорткод для отображения участников с фильтрами
function members_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_filters' => 'yes',
        'columns' => '3',
        'show_search' => 'yes',
    ), $atts, 'members_directory');

    ob_start();
    ?>
    <div class="members-directory-wrapper">
        <?php if ($atts['show_filters'] === 'yes'): ?>
        <div class="members-filters">
            <?php if ($atts['show_search'] === 'yes'): ?>
            <div class="members-search">
                <input type="text" id="member-search" placeholder="Поиск участников..." class="search-field">
            </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <h4>Тип участника</h4>
                <div class="filter-buttons" data-filter="member_type">
                    <button class="filter-btn active" data-value="all">Все</button>
                    <?php
                    $types = get_terms(array('taxonomy' => 'member_type', 'hide_empty' => false));
                    foreach ($types as $type) {
                        echo '<button class="filter-btn" data-value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="filter-group">
                <h4>Роль в ассоциации</h4>
                <div class="filter-buttons" data-filter="member_role">
                    <button class="filter-btn active" data-value="all">Все роли</button>
                    <?php
                    $roles = get_terms(array('taxonomy' => 'member_role', 'hide_empty' => false));
                    foreach ($roles as $role) {
                        echo '<button class="filter-btn" data-value="' . esc_attr($role->slug) . '">' . esc_html($role->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="filter-group">
                <h4>Локация</h4>
                <select id="location-filter" class="filter-select">
                    <option value="all">Все локации</option>
                    <?php
                    $locations = get_terms(array('taxonomy' => 'member_location', 'hide_empty' => false));
                    foreach ($locations as $location) {
                        echo '<option value="' . esc_attr($location->slug) . '">' . esc_html($location->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="members-grid columns-<?php echo esc_attr($atts['columns']); ?>" id="members-grid">
            <?php
            $args = array(
                'post_type' => 'members',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $member_id = get_the_ID();
                    
                    // Получаем данные
                    $position = get_post_meta($member_id, 'member_position', true);
                    $company = get_post_meta($member_id, 'member_company', true);
                    
                    // Получаем таксономии
                    $types = wp_get_post_terms($member_id, 'member_type', array('fields' => 'slugs'));
                    $roles = wp_get_post_terms($member_id, 'member_role', array('fields' => 'slugs'));
                    $locations = wp_get_post_terms($member_id, 'member_location', array('fields' => 'slugs'));
                    
                    $data_attributes = 'data-types="' . esc_attr(implode(' ', $types)) . '"';
                    $data_attributes .= ' data-roles="' . esc_attr(implode(' ', $roles)) . '"';
                    $data_attributes .= ' data-locations="' . esc_attr(implode(' ', $locations)) . '"';
                    $data_attributes .= ' data-search="' . esc_attr(strtolower(get_the_title() . ' ' . $position . ' ' . $company)) . '"';
                    ?>
                    <div class="member-card" <?php echo $data_attributes; ?>>
                        <a href="<?php the_permalink(); ?>" class="member-card-link">
                            <div class="member-photo">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else : ?>
                                    <div class="member-avatar-placeholder">
                                        <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="member-info">
                                <h3 class="member-name"><?php the_title(); ?></h3>
                                <?php if ($position) : ?>
                                    <p class="member-position"><?php echo esc_html($position); ?></p>
                                <?php endif; ?>
                                <?php if ($company) : ?>
                                    <p class="member-company"><?php echo esc_html($company); ?></p>
                                <?php endif; ?>
                                
                                <div class="member-tags">
                                    <?php
                                    $type_terms = wp_get_post_terms($member_id, 'member_type');
                                    foreach ($type_terms as $term) {
                                        echo '<span class="tag tag-type">' . esc_html($term->name) . '</span>';
                                    }
                                    
                                    $role_terms = wp_get_post_terms($member_id, 'member_role');
                                    foreach ($role_terms as $term) {
                                        echo '<span class="tag tag-role">' . esc_html($term->name) . '</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php
                }
                wp_reset_postdata();
            }
            ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Фильтрация по типу и роли
        $('.filter-btn').on('click', function() {
            var $this = $(this);
            var filterGroup = $this.parent().data('filter');
            var filterValue = $this.data('value');
            
            // Активный класс
            $this.siblings().removeClass('active');
            $this.addClass('active');
            
            filterMembers();
        });
        
        // Фильтрация по локации
        $('#location-filter').on('change', function() {
            filterMembers();
        });
        
        // Поиск
        $('#member-search').on('keyup', function() {
            filterMembers();
        });
        
        function filterMembers() {
            var typeFilter = $('.filter-buttons[data-filter="member_type"] .filter-btn.active').data('value');
            var roleFilter = $('.filter-buttons[data-filter="member_role"] .filter-btn.active').data('value');
            var locationFilter = $('#location-filter').val();
            var searchTerm = $('#member-search').val().toLowerCase();
            
            $('.member-card').each(function() {
                var $card = $(this);
                var show = true;
                
                // Фильтр по типу
                if (typeFilter !== 'all') {
                    var types = $card.data('types') || '';
                    if (types.indexOf(typeFilter) === -1) {
                        show = false;
                    }
                }
                
                // Фильтр по роли
                if (show && roleFilter !== 'all') {
                    var roles = $card.data('roles') || '';
                    if (roles.indexOf(roleFilter) === -1) {
                        show = false;
                    }
                }
                
                // Фильтр по локации
                if (show && locationFilter !== 'all') {
                    var locations = $card.data('locations') || '';
                    if (locations.indexOf(locationFilter) === -1) {
                        show = false;
                    }
                }
                
                // Поиск
                if (show && searchTerm) {
                    var searchData = $card.data('search') || '';
                    if (searchData.indexOf(searchTerm) === -1) {
                        show = false;
                    }
                }
                
                if (show) {
                    $card.fadeIn();
                } else {
                    $card.fadeOut();
                }
            });
        }
    });
    </script>
    
    <style>
    /* ===== ОСНОВНЫЕ СТИЛИ ДИРЕКТОРИИ ===== */
    .members-directory-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 24px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    }

    /* ===== ФИЛЬТРЫ ===== */
    .members-filters {
        background: linear-gradient(135deg, #f8f9fb 0%, #e9ecef 100%);
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 48px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid rgba(255,255,255,0.6);
    }
    
    .members-search {
        margin-bottom: 36px;
    }

    .search-field {
        width: 100%;
        padding: 16px 28px;
        font-size: 16px;
        border: 2px solid #e2e8f0;
        border-radius: 50px;
        outline: none;
        transition: all 0.3s ease;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .search-field:focus {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        transform: translateY(-1px);
    }

    .search-field::placeholder {
        color: #94a3b8;
    }

    .filter-group {
        margin-bottom: 28px;
    }

    .filter-group:last-child {
        margin-bottom: 0;
    }

    .filter-group h4 {
        margin: 0 0 16px 0;
        color: #1a1a2e;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        font-weight: 700;
    }

    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .filter-btn {
        padding: 10px 24px;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 30px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }

    .filter-btn:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
    }

    .filter-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: transparent;
        color: white;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.4);
    }

    .filter-select {
        width: 100%;
        max-width: 320px;
        padding: 12px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 500;
        outline: none;
        background: white;
        color: #1a1a2e;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }

    .filter-select:hover,
    .filter-select:focus {
        border-color: #667eea;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    /* ===== СЕТКА УЧАСТНИКОВ ===== */
    .members-grid {
        display: grid;
        gap: 32px;
        margin-top: 48px;
    }

    .members-grid.columns-2 {
        grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    }

    .members-grid.columns-3 {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }

    .members-grid.columns-4 {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }

    /* ===== КАРТОЧКИ УЧАСТНИКОВ ===== */
    .member-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        display: block;
        position: relative;
    }

    .member-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .member-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
    }

    .member-card:hover::before {
        opacity: 1;
    }

    .member-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .member-photo {
        width: 100%;
        height: 320px;
        overflow: hidden;
        position: relative;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }

    .member-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .member-card:hover .member-photo img {
        transform: scale(1.08);
    }
    
    .member-avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 96px;
        font-weight: 700;
        color: white;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        text-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* ===== ИНФОРМАЦИЯ О УЧАСТНИКЕ ===== */
    .member-info {
        padding: 28px;
    }

    .member-name {
        margin: 0 0 10px 0;
        font-size: 22px;
        font-weight: 700;
        color: #1a1a2e;
        line-height: 1.3;
        transition: color 0.3s ease;
    }

    .member-card:hover .member-name {
        color: #667eea;
    }

    .member-position {
        margin: 0 0 6px 0;
        font-size: 15px;
        color: #64748b;
        font-weight: 500;
        line-height: 1.4;
    }

    .member-company {
        margin: 0 0 18px 0;
        font-size: 14px;
        color: #94a3b8;
        font-weight: 500;
    }

    /* ===== ТЕГИ ===== */
    .member-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 16px;
    }

    .tag {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 700;
        transition: all 0.3s ease;
    }

    .tag-type {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        color: #4c51bf;
        border: 1px solid #c7d2fe;
    }

    .tag-role {
        background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
        color: #be185d;
        border: 1px solid #fbcfe8;
    }

    .member-card:hover .tag {
        transform: translateY(-2px);
    }

    /* ===== АДАПТИВНОСТЬ ===== */
    @media (max-width: 1024px) {
        .members-grid.columns-3,
        .members-grid.columns-4 {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .members-directory-wrapper {
            padding: 24px 16px;
        }

        .members-filters {
            padding: 24px;
            border-radius: 16px;
        }

        .members-grid {
            grid-template-columns: 1fr !important;
            gap: 24px;
            margin-top: 32px;
        }

        .filter-buttons {
            flex-direction: column;
        }

        .filter-btn {
            width: 100%;
            justify-content: center;
        }

        .filter-select {
            max-width: 100%;
        }

        .member-photo {
            height: 280px;
        }

        .member-info {
            padding: 20px;
        }

        .member-name {
            font-size: 20px;
        }
    }

    @media (max-width: 480px) {
        .member-photo {
            height: 240px;
        }

        .search-field {
            padding: 14px 20px;
            font-size: 15px;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('members_directory', 'members_directory_shortcode');

/**
 * Шорткод для страницы регистрации
 */
function member_registration_shortcode() {
    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/member-registration.php');
    return ob_get_clean();
}
add_shortcode('member_registration', 'member_registration_shortcode');

// УДАЛЕНО: member_dashboard_shortcode() + add_shortcode() - дубль класса Member_Dashboard

/**
 * Шорткод для панели менеджера
 */
function manager_panel_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Пожалуйста, <a href="' . wp_login_url(get_permalink()) . '">войдите</a>, чтобы получить доступ к панели управления.</p>';
    }

    $user = wp_get_current_user();
    if (!in_array('manager', $user->roles) && !in_array('administrator', $user->roles)) {
        return '<p>У вас нет доступа к этой странице.</p>';
    }

    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/manager-panel.php');
    return ob_get_clean();
}
add_shortcode('manager_panel', 'manager_panel_shortcode');

/**
 * Шорткод для страницы логина
 */
function custom_login_shortcode() {
    // KILL SWITCH: Отключение всех редиректов
    if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
        return '<div style="padding: 20px; background: #ffeb3b; border: 2px solid #ff9800;">
            <h3>⚠️ Редиректы отключены (METODA_DISABLE_REDIRECTS)</h3>
            <p><a href="' . admin_url() . '">Перейти в админку →</a></p>
        </div>';
    }

    // Не показываем шорткод в админке
    if (is_admin()) {
        return '';
    }

    if (is_user_logged_in()) {
        $user = wp_get_current_user();

        // ВАЖНО: Администраторы НЕ должны редиректиться
        if (current_user_can('administrator') || current_user_can('manage_options')) {
            // Показываем сообщение вместо редиректа
            return '<div style="padding: 40px; text-align: center;">
                <h2>Вы уже авторизованы как администратор</h2>
                <p><a href="' . admin_url() . '">Перейти в админку →</a></p>
            </div>';
        }

        if (in_array('manager', $user->roles)) {
            wp_redirect(home_url('/manager-panel/'));
            exit;
        } else {
            wp_redirect(home_url('/member-dashboard/'));
            exit;
        }
    }

    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/custom-login.php');
    return ob_get_clean();
}
add_shortcode('custom_login', 'custom_login_shortcode');

// Создание таблицы для импорта при активации плагина
// Old activation hook removed - merged into metoda_members_activate() above

// Функция для импорта данных из CSV
function import_members_from_csv($file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return false;
    }
    
    // Пропускаем заголовок
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        // Создаем пост
        $post_data = array(
            'post_title'   => $data[0], // post_title
            'post_content' => $data[1], // post_content
            'post_status'  => $data[2], // post_status
            'post_type'    => 'members',
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            // Добавляем метаданные
            update_post_meta($post_id, 'member_position', $data[6]);
            update_post_meta($post_id, 'member_company', $data[7]);
            update_post_meta($post_id, 'member_email', $data[9]);
            update_post_meta($post_id, 'member_phone', $data[10]);
            update_post_meta($post_id, 'member_bio', $data[11]);
            
            // Добавляем таксономии
            // Тип участника
            if (!empty($data[4])) {
                $type = ($data[4] === 'expert') ? 'Эксперт' : 'Участник';
                wp_set_object_terms($post_id, $type, 'member_type');
            }
            
            // Роль в ассоциации
            if (!empty($data[13])) {
                $roles = explode(',', $data[13]);
                wp_set_object_terms($post_id, $roles, 'member_role');
            }
            
            // Локация
            if (!empty($data[8])) {
                wp_set_object_terms($post_id, $data[8], 'member_location');
            }
        }
    }
    
    fclose($handle);
    return true;
}

function members_import_page_callback() {
    ?>
    <div class="wrap">
        <h1>Импорт участников из CSV</h1>
        
        <?php
        if (isset($_POST['import_members']) && isset($_FILES['csv_file'])) {
            $uploaded_file = $_FILES['csv_file'];
            
            if ($uploaded_file['type'] === 'text/csv' || $uploaded_file['type'] === 'application/vnd.ms-excel') {
                $upload_dir = wp_upload_dir();
                $file_path = $upload_dir['path'] . '/' . $uploaded_file['name'];
                
                if (move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
                    if (import_members_from_csv($file_path)) {
                        echo '<div class="notice notice-success"><p>Импорт успешно завершен!</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>Ошибка при импорте данных.</p></div>';
                    }
                    
                    // Удаляем временный файл
                    unlink($file_path);
                }
            } else {
                echo '<div class="notice notice-error"><p>Пожалуйста, загрузите файл формата CSV.</p></div>';
            }
        }
        ?>
        
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr>
                    <th><label for="csv_file">CSV файл</label></th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        <p class="description">Загрузите файл wordpress_members_complete.csv</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Импортировать', 'primary', 'import_members'); ?>
        </form>
        
        <div style="margin-top: 40px; padding: 20px; background: #f9f9f9; border-left: 4px solid #007cba;">
            <h3>Инструкция по использованию</h3>
            <ol>
                <li>Загрузите CSV файл с данными участников</li>
                <li>Нажмите кнопку "Импортировать"</li>
                <li>После импорта проверьте данные в разделе "Участники сообщества"</li>
            </ol>
            
            <h4>Шорткод для вывода на сайте:</h4>
            <code>[members_directory]</code>
            
            <h4>Параметры шорткода:</h4>
            <ul>
                <li><code>show_filters="yes"</code> - показывать фильтры (yes/no)</li>
                <li><code>columns="3"</code> - количество колонок (2/3/4)</li>
                <li><code>show_search="yes"</code> - показывать поиск (yes/no)</li>
            </ul>
        </div>
    </div>
    <?php
}

// ==========================================
// AJAX обработчики для фильтрации участников
// ==========================================

/**
 * Подключение скриптов и стилей для фронтенда
 */
function members_enqueue_scripts() {
    // jQuery для всех страниц
    wp_enqueue_script('jquery');

    // Архив участников
    if (is_post_type_archive('members') || is_singular('members')) {
        wp_enqueue_script(
            'members-archive-ajax',
            plugin_dir_url(__FILE__) . 'assets/js/members-archive-ajax.js',
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
                plugin_dir_url(__FILE__) . 'assets/css/member-registration.css',
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'member-registration-js',
                plugin_dir_url(__FILE__) . 'assets/js/member-registration.js',
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
                plugin_dir_url(__FILE__) . 'assets/css/custom-login.css',
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'custom-login-js',
                plugin_dir_url(__FILE__) . 'assets/js/custom-login.js',
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
                plugin_dir_url(__FILE__) . 'assets/css/photo-cropper.css',
                array('cropperjs-css'),
                '1.0.0'
            );

            wp_enqueue_script(
                'photo-cropper-js',
                plugin_dir_url(__FILE__) . 'assets/js/photo-cropper.js',
                array('jquery', 'cropperjs'),
                '1.0.0',
                true
            );

            // Dashboard стили и скрипты
            wp_enqueue_style(
                'member-dashboard-css',
                plugin_dir_url(__FILE__) . 'assets/css/member-dashboard.css',
                array('photo-cropper-css'),
                '1.0.0'
            );

            wp_enqueue_script(
                'member-dashboard-js',
                plugin_dir_url(__FILE__) . 'assets/js/member-dashboard.js',
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
                plugin_dir_url(__FILE__) . 'assets/css/manager-panel.css',
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'manager-panel-js',
                plugin_dir_url(__FILE__) . 'assets/js/manager-panel.js',
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
}
add_action('wp_enqueue_scripts', 'members_enqueue_scripts');

/**
 * Регистрация Tailwind CSS и общих стилей
 */
function metoda_register_tailwind_styles() {
    wp_register_style('metoda-tailwind', plugin_dir_url(__FILE__) . 'assets/css/tailwind.min.css', array(), '4.1.0');
    wp_register_style('metoda-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap', array(), null);
    wp_register_style('metoda-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
}
add_action('init', 'metoda_register_tailwind_styles');

/**
 * Подключение frontend стилей (Tailwind + шрифты)
 */
function metoda_enqueue_frontend_styles() {
    wp_enqueue_style('metoda-fonts');
    wp_enqueue_style('metoda-fontawesome');
    wp_enqueue_style('metoda-tailwind');
}

/**
 * AJAX обработчик фильтрации участников
 */
function ajax_filter_members() {
    // Проверка nonce
    check_ajax_referer('public_members_nonce', 'nonce');

    // Получаем параметры фильтрации
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $roles = isset($_POST['roles']) ? array_map('sanitize_text_field', $_POST['roles']) : array();
    $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'title-asc';
    $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

    // Определяем сортировку
    $orderby = 'title';
    $order = 'ASC';

    switch ($sort) {
        case 'title-desc':
            $orderby = 'title';
            $order = 'DESC';
            break;
        case 'date-desc':
            $orderby = 'date';
            $order = 'DESC';
            break;
        case 'date-asc':
            $orderby = 'date';
            $order = 'ASC';
            break;
    }

    // Формируем запрос
    $args = array(
        'post_type' => 'members',
        'posts_per_page' => 12,
        'paged' => $paged,
        'orderby' => $orderby,
        'order' => $order
    );

    // Добавляем поиск
    if (!empty($search)) {
        $args['s'] = $search;
    }

    // Добавляем фильтр по городу
    if (!empty($city)) {
        $args['meta_query'][] = array(
            'key' => 'member_city',
            'value' => $city,
            'compare' => 'LIKE'
        );
    }

    // Добавляем фильтр по ролям
    if (!empty($roles)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'member_role',
            'field' => 'slug',
            'terms' => $roles,
            'operator' => 'IN'
        );
    }

    $query = new WP_Query($args);

    // Генерируем HTML карточек
    ob_start();

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            $member_id = get_the_ID();
            $position = get_post_meta($member_id, 'member_position', true);
            $company = get_post_meta($member_id, 'member_company', true);
            $city_meta = get_post_meta($member_id, 'member_city', true);
            $roles_terms = wp_get_post_terms($member_id, 'member_role');
            ?>
            <article class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                <a href="<?php the_permalink(); ?>" class="flex items-start gap-4">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover')); ?>
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-300">
                                <?php echo mb_substr(get_the_title(), 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1 truncate"><?php the_title(); ?></h3>

                        <?php if ($position): ?>
                        <p class="text-sm text-gray-600 mb-1"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>

                        <?php if ($company): ?>
                        <p class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>

                        <?php if ($city_meta): ?>
                        <div class="flex items-center text-sm text-gray-500 mb-3">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span><?php echo esc_html($city_meta); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($roles_terms && !is_wp_error($roles_terms)): ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (array_slice($roles_terms, 0, 3) as $role): ?>
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                                <?php echo esc_html($role->name); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
            </article>
            <?php
        endwhile;
    } else {
        ?>
        <div class="col-span-2 bg-white rounded-xl shadow-sm border p-12 text-center">
            <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Участники не найдены</h3>
            <p class="text-gray-600">Попробуйте изменить параметры поиска</p>
        </div>
        <?php
    }

    $html = ob_get_clean();

    // Генерируем пагинацию
    $pagination = '';
    if ($query->max_num_pages > 1) {
        ob_start();
        ?>
        <div class="flex justify-center items-center space-x-2 mt-8">
            <?php if ($paged > 1): ?>
            <a href="#" data-page="<?php echo ($paged - 1); ?>" class="pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $query->max_num_pages; $i++): ?>
                <?php if ($i == $paged): ?>
                <span class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="#" data-page="<?php echo $i; ?>" class="pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <?php echo $i; ?>
                </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($paged < $query->max_num_pages): ?>
            <a href="#" data-page="<?php echo ($paged + 1); ?>" class="pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php
        $pagination = ob_get_clean();
    }

    wp_reset_postdata();

    // Возвращаем результат
    wp_send_json_success(array(
        'html' => $html,
        'found' => $query->found_posts,
        'pagination' => $pagination,
        'max_pages' => $query->max_num_pages
    ));
}
// Закомментировано - используется filter_members_ajax() вместо этого
// add_action('wp_ajax_filter_members', 'ajax_filter_members');
// add_action('wp_ajax_nopriv_filter_members', 'ajax_filter_members');

// ==========================================
// Виджет статистики в админке
// ==========================================

/**
 * Добавляет виджет статистики участников в админку
 */
function members_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'members_statistics_widget',
        '📊 Статистика участников',
        'members_render_dashboard_widget'
    );
}
add_action('wp_dashboard_setup', 'members_add_dashboard_widget');

/**
 * Рендерит виджет статистики
 */
function members_render_dashboard_widget() {
    // Подсчитываем участников
    $total_members = wp_count_posts('members');
    $published = $total_members->publish;
    $draft = $total_members->draft;

    // Получаем статистику по ролям
    $roles = get_terms(array(
        'taxonomy' => 'member_role',
        'hide_empty' => false
    ));

    // Получаем города
    global $wpdb;
    $cities_count = $wpdb->get_var("
        SELECT COUNT(DISTINCT meta_value)
        FROM {$wpdb->postmeta}
        WHERE meta_key = 'member_city'
        AND meta_value != ''
    ");

    // Получаем недавно добавленных участников
    $recent_members = get_posts(array(
        'post_type' => 'members',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    ?>
    <style>
        .members-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .members-stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0066cc;
        }

        .members-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0066cc;
            line-height: 1;
            margin-bottom: 5px;
        }

        .members-stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .members-recent-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .members-recent-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .members-recent-list li:last-child {
            border-bottom: none;
        }

        .members-recent-name {
            font-weight: 500;
            color: #0066cc;
            text-decoration: none;
        }

        .members-recent-name:hover {
            text-decoration: underline;
        }

        .members-recent-date {
            font-size: 12px;
            color: #999;
        }

        .members-view-all {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 16px;
            background: #0066cc;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            font-size: 13px;
            transition: opacity 0.2s;
        }

        .members-view-all:hover {
            opacity: 0.9;
        }
    </style>

    <div class="members-stats-grid">
        <div class="members-stat-card">
            <div class="members-stat-number"><?php echo $published; ?></div>
            <div class="members-stat-label">Опубликовано</div>
        </div>

        <div class="members-stat-card">
            <div class="members-stat-number"><?php echo $draft; ?></div>
            <div class="members-stat-label">Черновики</div>
        </div>

        <div class="members-stat-card">
            <div class="members-stat-number"><?php echo $cities_count; ?></div>
            <div class="members-stat-label">Городов</div>
        </div>

        <div class="members-stat-card">
            <div class="members-stat-number"><?php echo count($roles); ?></div>
            <div class="members-stat-label">Ролей</div>
        </div>
    </div>

    <?php if (!empty($recent_members)): ?>
    <h4 style="margin-top: 20px; margin-bottom: 10px;">Недавно добавленные</h4>
    <ul class="members-recent-list">
        <?php foreach ($recent_members as $member): ?>
        <li>
            <a href="<?php echo get_edit_post_link($member->ID); ?>" class="members-recent-name">
                <?php echo esc_html($member->post_title); ?>
            </a>
            <span class="members-recent-date">
                <?php echo human_time_diff(strtotime($member->post_date), current_time('timestamp')); ?> назад
            </span>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <a href="<?php echo admin_url('edit.php?post_type=members'); ?>" class="members-view-all">
        Посмотреть всех участников →
    </a>

    <?php
    // Ссылки на импорт и страницы
    ?>
    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
        <p style="margin: 0 0 10px 0; font-weight: 500;">Быстрые действия:</p>
        <a href="<?php echo admin_url('edit.php?post_type=members&page=member-csv-import'); ?>" class="button">
            📥 Импорт из CSV
        </a>
        <a href="<?php echo admin_url('post-new.php?post_type=members'); ?>" class="button button-primary">
            ➕ Добавить участника
        </a>
    </div>
    <?php
}

/**
 * Добавляет кастомные столбцы в список участников
 */
function members_custom_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'ФИО';
    $new_columns['member_photo'] = 'Фото';
    $new_columns['member_company'] = 'Компания';
    $new_columns['member_city'] = 'Город';
    $new_columns['member_role'] = 'Роль';
    $new_columns['date'] = 'Дата';
    return $new_columns;
}
add_filter('manage_members_posts_columns', 'members_custom_columns');

/**
 * Заполняет кастомные столбцы данными
 */
function members_custom_columns_data($column, $post_id) {
    switch ($column) {
        case 'member_photo':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(50, 50), array('style' => 'border-radius: 50%; object-fit: cover;'));
            } else {
                echo '<div style="width: 50px; height: 50px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #999;">'
                    . mb_substr(get_the_title($post_id), 0, 1) .
                    '</div>';
            }
            break;

        case 'member_company':
            $company = get_post_meta($post_id, 'member_company', true);
            echo $company ? esc_html($company) : '—';
            break;

        case 'member_city':
            $city = get_post_meta($post_id, 'member_city', true);
            echo $city ? esc_html($city) : '—';
            break;

        case 'member_role':
            $roles = wp_get_post_terms($post_id, 'member_role');
            if (!empty($roles) && !is_wp_error($roles)) {
                $role_names = array_map(function($role) {
                    return $role->name;
                }, $roles);
                echo implode(', ', array_slice($role_names, 0, 2));
                if (count($role_names) > 2) {
                    echo ' <span style="color: #999;">+' . (count($role_names) - 2) . '</span>';
                }
            } else {
                echo '—';
            }
            break;
    }
}
add_action('manage_members_posts_custom_column', 'members_custom_columns_data', 10, 2);

/**
 * AJAX обработчик регистрации нового участника
 */
function member_register_ajax() {
    check_ajax_referer('member_registration_nonce', 'nonce');

    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    // Валидация пароля
    if (strlen($password) < 8) {
        wp_send_json_error(array('message' => 'Пароль должен содержать не менее 8 символов'));
    }

    // Дополнительная проверка на слабый пароль (опционально)
    if (preg_match('/^[0-9]+$/', $password)) {
        wp_send_json_error(array('message' => 'Пароль не должен состоять только из цифр'));
    }

    $fullname = sanitize_text_field($_POST['fullname']);
    $account_type = sanitize_text_field($_POST['account_type']);
    $company = sanitize_text_field($_POST['company']);
    $position = sanitize_text_field($_POST['position']);
    $city = sanitize_text_field($_POST['city']);
    $roles = sanitize_text_field($_POST['roles']);
    $specializations = sanitize_textarea_field($_POST['specializations']);
    $interests = sanitize_textarea_field($_POST['interests']);
    $bio = wp_kses_post($_POST['bio']);
    $expectations = wp_kses_post($_POST['expectations']);
    $access_code = isset($_POST['access_code']) ? sanitize_text_field($_POST['access_code']) : '';

    // Проверка email
    if (email_exists($email)) {
        wp_send_json_error(array('message' => 'Этот email уже зарегистрирован'));
    }

    // Создаем пользователя WordPress
    $user_id = wp_create_user($email, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    }

    // Устанавливаем роль
    $user = new WP_User($user_id);
    $user->set_role($account_type); // member or expert

    // Проверяем наличие кода доступа
    $member_id = null;
    $is_claimed_profile = false;

    if (!empty($access_code)) {
        // Ищем профиль по коду доступа
        $existing_member = Member_Access_Codes::find_member_by_code($access_code);

        if ($existing_member) {
            // Проверяем, не занят ли профиль
            $linked_user = get_post_meta($existing_member->ID, '_linked_user_id', true);

            if ($linked_user) {
                wp_delete_user($user_id);
                wp_send_json_error(array('message' => 'Этот код доступа уже активирован'));
            }

            // Используем существующий профиль
            $member_id = $existing_member->ID;
            $is_claimed_profile = true;

            // Обновляем существующий профиль новой информацией (опционально, если пользователь заполнил дополнительные данные)
            if (!empty($company)) {
                update_post_meta($member_id, 'member_company', $company);
            }
            if (!empty($position)) {
                update_post_meta($member_id, 'member_position', $position);
            }
            if (!empty($city)) {
                update_post_meta($member_id, 'member_city', $city);
            }
        } else {
            // Код неверный
            wp_delete_user($user_id);
            wp_send_json_error(array('message' => 'Неверный код доступа'));
        }
    }

    // Если код не указан или не найден - создаем новый профиль
    if (!$member_id) {
        $member_id = wp_insert_post(array(
            'post_title' => $fullname,
            'post_type' => 'members',
            'post_status' => 'publish',
            'post_author' => $user_id
        ));

        if (is_wp_error($member_id)) {
            wp_delete_user($user_id);
            wp_send_json_error(array('message' => 'Ошибка создания профиля'));
        }

        // Сохраняем метаданные для нового профиля
        update_post_meta($member_id, 'member_company', $company);
        update_post_meta($member_id, 'member_position', $position);
        update_post_meta($member_id, 'member_city', $city);
        update_post_meta($member_id, 'member_email', $email);
    }

    // Сохраняем общие метаданные (для обоих случаев)
    update_post_meta($member_id, 'member_specialization_experience', $specializations);
    update_post_meta($member_id, 'member_professional_interests', $interests);
    update_post_meta($member_id, 'member_bio', $bio);
    update_post_meta($member_id, 'member_expectations', $expectations);

    // Связываем пользователя с участником
    update_post_meta($member_id, '_linked_user_id', $user_id);
    update_user_meta($user_id, 'member_id', $member_id);

    // Добавляем роли
    if (!empty($roles)) {
        $role_slugs = array_map('sanitize_title', explode(',', $roles));
        wp_set_object_terms($member_id, $role_slugs, 'member_role');
    }

    // Автоматический вход
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Отправка email уведомлений
    do_action('metoda_member_registered', $user_id, $member_id, $is_claimed_profile);

    // Если профиль был активирован по коду доступа
    if ($is_claimed_profile && !empty($access_code)) {
        do_action('metoda_profile_activated', $user_id, $member_id, $access_code);
    }

    $message = $is_claimed_profile
        ? 'Регистрация завершена! Ваш профиль успешно активирован.'
        : 'Регистрация успешно завершена!';

    wp_send_json_success(array(
        'message' => $message,
        'redirect' => home_url('/member-dashboard/')
    ));
}
add_action('wp_ajax_nopriv_member_register', 'member_register_ajax');

// УДАЛЕНО: member_update_profile_ajax() + add_action() - дубль класса Member_Dashboard

/**
 * Редирект после логина - отправляем в соответствующие кабинеты
 */
function member_login_redirect($redirect_to, $request, $user) {
    // KILL SWITCH: Отключение всех редиректов для диагностики
    // Добавь в wp-config.php: define('METODA_DISABLE_REDIRECTS', true);
    if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
        return $redirect_to;
    }

    // ЯДЕРНАЯ ЗАЩИТА: User ID 1 всегда идет в админку
    if (isset($user->ID) && $user->ID === 1) {
        return admin_url();
    }

    if (isset($user->roles) && is_array($user->roles)) {
        // ВАЖНО: Администраторы идут в АДМИНКУ, не в manager-panel!
        if (in_array('administrator', $user->roles)) {
            return admin_url(); // В админку WordPress
        }

        // Менеджеры в панель управления
        if (in_array('manager', $user->roles)) {
            return home_url('/manager-panel/');
        }

        // Участники и эксперты в личный кабинет
        if (in_array('member', $user->roles) || in_array('expert', $user->roles)) {
            return home_url('/member-dashboard/');
        }
    }
    return $redirect_to;
}
// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_filter('login_redirect', 'member_login_redirect', 10, 3);

/**
 * Редирект после логаута
 */
function member_logout_redirect() {
    return home_url();
}
// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_filter('logout_redirect', 'member_logout_redirect');

/**
 * Скрываем админ-бар для участников
 */
function hide_admin_bar_for_members() {
    if (current_user_can('member') || current_user_can('expert')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'hide_admin_bar_for_members');

/**
 * Блокируем доступ к админке для участников
 */
function block_admin_access_for_members() {
    // KILL SWITCH: Отключение всех редиректов для диагностики
    // Добавь в wp-config.php: define('METODA_DISABLE_REDIRECTS', true);
    if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
        return;
    }

    // КРИТИЧНАЯ ЗАЩИТА: User ID 1 ВСЕГДА имеет доступ
    if (get_current_user_id() === 1) {
        return;
    }

    // Don't redirect if plugin is being activated (transient set during activation)
    if (get_transient('metoda_members_activating')) {
        return;
    }

    // Don't redirect if we're creating pages after activation
    if (get_option('metoda_needs_page_creation') === '1') {
        return;
    }

    // Only run in admin area, not during AJAX
    if (!is_admin() || wp_doing_ajax()) {
        return;
    }

    // ДВОЙНАЯ ПРОВЕРКА: Administrators and users with manage_options capability always have access
    $user = wp_get_current_user();
    if (current_user_can('manage_options') ||
        current_user_can('administrator') ||
        in_array('administrator', (array) $user->roles)) {
        return;
    }

    // Don't redirect on plugin management pages
    global $pagenow;
    $allowed_pages = array('plugins.php', 'plugin-install.php', 'plugin-editor.php', 'update-core.php', 'index.php');
    if (in_array($pagenow, $allowed_pages)) {
        return;
    }

    // Don't redirect if activating/deactivating plugins
    if (isset($_GET['action']) && in_array($_GET['action'], array('activate', 'deactivate', 'activate-selected', 'deactivate-selected'))) {
        return;
    }

    // Don't redirect if on admin page just after plugin activation (check referer)
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'plugins.php') !== false) {
        return;
    }

    // Check if user has member or expert role (not checking capabilities to avoid conflicts)
    if (!empty($user->roles)) {
        $member_roles = array('member', 'expert');
        $admin_roles = array('administrator', 'manager');
        $user_roles = (array) $user->roles;

        // ВАЖНО: НЕ редиректим если у пользователя есть админская роль
        // Даже если у него также есть member/expert (смешанные роли)
        if (array_intersect($admin_roles, $user_roles)) {
            return; // Админские роли имеют приоритет
        }

        // Редиректим только если есть member/expert И НЕТ админских ролей
        if (array_intersect($member_roles, $user_roles)) {
            wp_redirect(home_url('/member-dashboard/'));
            exit;
        }
    }
}
// Приоритет 20 - чтобы срабатывать ПОСЛЕ других плагинов (например, Royal Elementor Addons)
// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_action('admin_init', 'block_admin_access_for_members', 20);

/**
 * AJAX обработчик изменения статуса участника (для менеджеров)
 */
function manager_change_member_status_ajax() {
    check_ajax_referer('manager_actions_nonce', 'nonce');

    if (!current_user_can('manager') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Нет прав доступа'));
    }

    $member_id = intval($_POST['member_id']);
    $status = sanitize_text_field($_POST['status']);

    if (!in_array($status, array('publish', 'pending', 'draft'))) {
        wp_send_json_error(array('message' => 'Некорректный статус'));
    }

    $result = wp_update_post(array(
        'ID' => $member_id,
        'post_status' => $status
    ));

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => 'Ошибка при изменении статуса'));
    }

    $status_labels = array(
        'publish' => 'одобрен',
        'pending' => 'отправлен на модерацию',
        'draft' => 'переведен в черновики'
    );

    wp_send_json_success(array(
        'message' => 'Участник ' . $status_labels[$status]
    ));
}
add_action('wp_ajax_manager_change_member_status', 'manager_change_member_status_ajax');

// УДАЛЕНО: manager_delete_member_ajax() + add_action() - дубль класса Member_Manager

/**
 * AJAX обработчик для сохранения галереи
 */
function member_save_gallery_ajax() {
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
    $member_id = get_editable_member_id();
    if (is_wp_error($member_id)) {
        wp_send_json_error(array('message' => $member_id->get_error_message()));
    }

    $gallery_ids = sanitize_text_field($_POST['gallery_ids']);

    // Сохраняем IDs изображений галереи
    update_post_meta($member_id, 'member_gallery', $gallery_ids);

    wp_send_json_success(array(
        'message' => 'Галерея успешно сохранена!'
    ));
}
add_action('wp_ajax_member_save_gallery', 'member_save_gallery_ajax');

/**
 * AJAX обработчик для загрузки фото в галерею
 */
function member_upload_gallery_photo_ajax() {
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
    $member_id = get_editable_member_id();
    if (is_wp_error($member_id)) {
        wp_send_json_error(array('message' => $member_id->get_error_message()));
    }

    // Проверяем, был ли загружен файл
    if (empty($_FILES['photo'])) {
        wp_send_json_error(array('message' => 'Файл не загружен'));
    }

    // SECURITY FIX v3.7.3: Валидация типа файла и размера
    $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif');
    $file_type = $_FILES['photo']['type'];

    if (!in_array($file_type, $allowed_types)) {
        wp_send_json_error(array('message' => 'Недопустимый тип файла. Разрешены только изображения (JPEG, PNG, WebP, GIF)'));
    }

    // Проверка размера файла (максимум 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB в байтах
    if ($_FILES['photo']['size'] > $max_size) {
        wp_send_json_error(array('message' => 'Файл слишком большой. Максимальный размер: 5MB'));
    }

    // Дополнительная проверка на реальный MIME-тип (защита от подмены расширения)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($real_mime, $allowed_types)) {
        wp_send_json_error(array('message' => 'Обнаружена попытка загрузки файла с поддельным типом'));
    }

    // Подключаем необходимые файлы WordPress
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Загружаем файл в медиабиблиотеку
    $attachment_id = media_handle_upload('photo', $member_id);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => 'Ошибка загрузки файла: ' . $attachment_id->get_error_message()));
    }

    // Получаем URL миниатюры
    $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'medium');

    // Получаем текущие ID галереи
    $current_gallery = get_post_meta($member_id, 'member_gallery', true);
    $gallery_ids = !empty($current_gallery) ? explode(',', $current_gallery) : array();

    // Добавляем новое фото
    $gallery_ids[] = $attachment_id;

    // Сохраняем обновленную галерею
    update_post_meta($member_id, 'member_gallery', implode(',', $gallery_ids));

    wp_send_json_success(array(
        'message' => 'Фото успешно загружено!',
        'attachment_id' => $attachment_id,
        'thumbnail_url' => $thumbnail_url
    ));
}
add_action('wp_ajax_member_upload_gallery_photo', 'member_upload_gallery_photo_ajax');

/**
 * AJAX обработчик для добавления материала (ссылка)
 */
function member_add_material_link_ajax() {
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    // Проверяем, редактирует ли админ чужой профиль
    $is_admin = current_user_can('administrator');
    $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

    if ($is_admin && $editing_member_id) {
        $member_post = get_post($editing_member_id);
        if (!$member_post || $member_post->post_type !== 'members') {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }
        $member_id = $editing_member_id;
    } else {
        $member_id = Member_User_Link::get_current_user_member_id();
        if (!$member_id) {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }
    }

    $category = sanitize_text_field($_POST['category']);
    $title = sanitize_text_field($_POST['title']);
    $url = esc_url_raw($_POST['url']);
    $description = sanitize_textarea_field($_POST['description']);

    // Получаем текущие материалы
    $current_materials = get_post_meta($member_id, 'member_' . $category, true);

    // Создаем новую запись материала
    $new_material = sprintf(
        "[LINK|%s|%s|%s|%s]",
        $title,
        $url,
        $description,
        current_time('Y-m-d H:i:s')
    );

    // Добавляем новый материал
    if (empty($current_materials)) {
        $updated_materials = $new_material;
    } else {
        $updated_materials = $current_materials . "\n" . $new_material;
    }

    update_post_meta($member_id, 'member_' . $category, $updated_materials);

    wp_send_json_success(array(
        'message' => 'Ссылка успешно добавлена!',
        'reload' => true
    ));
}
add_action('wp_ajax_member_add_material_link', 'member_add_material_link_ajax');

/**
 * AJAX обработчик для добавления материала (файл)
 */
function member_add_material_file_ajax() {
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    // Проверяем, редактирует ли админ чужой профиль
    $is_admin = current_user_can('administrator');
    $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

    if ($is_admin && $editing_member_id) {
        $member_post = get_post($editing_member_id);
        if (!$member_post || $member_post->post_type !== 'members') {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }
        $member_id = $editing_member_id;
    } else {
        $member_id = Member_User_Link::get_current_user_member_id();
        if (!$member_id) {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }
    }

    // Проверяем, был ли загружен файл
    if (empty($_FILES['file'])) {
        wp_send_json_error(array('message' => 'Файл не загружен'));
    }

    $category = sanitize_text_field($_POST['category']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);

    // Загружаем файл в медиабиблиотеку
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attachment_id = media_handle_upload('file', $member_id);

    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => 'Ошибка загрузки файла: ' . $attachment_id->get_error_message()));
    }

    $file_url = wp_get_attachment_url($attachment_id);

    // Получаем текущие материалы
    $current_materials = get_post_meta($member_id, 'member_' . $category, true);

    // Создаем новую запись материала
    $new_material = sprintf(
        "[FILE|%s|%s|%s|%s]",
        $title,
        $file_url,
        $description,
        current_time('Y-m-d H:i:s')
    );

    // Добавляем новый материал
    if (empty($current_materials)) {
        $updated_materials = $new_material;
    } else {
        $updated_materials = $current_materials . "\n" . $new_material;
    }

    update_post_meta($member_id, 'member_' . $category, $updated_materials);

    wp_send_json_success(array(
        'message' => 'Файл успешно загружен!',
        'reload' => true
    ));
}
add_action('wp_ajax_member_add_material_file', 'member_add_material_file_ajax');

// УДАЛЕНО: member_delete_material_ajax() + add_action() - дубль класса Member_File_Manager

/**
 * AJAX обработчик для загрузки дополнительных участников (Load More)
 * SECURITY FIX v3.7.3: Добавлен nonce для защиты от CSRF
 */
function load_more_members_ajax() {
    // CSRF protection - публичный nonce
    check_ajax_referer('public_members_nonce', 'nonce');

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
    $type_filter = isset($_POST['member_type']) ? sanitize_text_field($_POST['member_type']) : '';

    $posts_per_page = 12;

    // Если нет фильтра по типу - делаем два отдельных запроса и объединяем
    if (empty($type_filter)) {
        // Запрос для экспертов
        $experts_args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => 'ekspert'
                )
            )
        );

        if (!empty($search)) {
            $experts_args['s'] = $search;
        }
        if (!empty($city)) {
            $experts_args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }
        if (!empty($role)) {
            $experts_args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role
            );
        }

        $experts_query = new WP_Query($experts_args);

        // Запрос для участников
        $members_args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => 'uchastnik'
                )
            )
        );

        if (!empty($search)) {
            $members_args['s'] = $search;
        }
        if (!empty($city)) {
            $members_args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }
        if (!empty($role)) {
            $members_args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role
            );
        }

        $members_query = new WP_Query($members_args);

        // Объединяем результаты
        $all_members = array_merge($experts_query->posts, $members_query->posts);

        // Берем порцию с offset
        $paged_members = array_slice($all_members, $offset, $posts_per_page);

    } else {
        // Если выбран конкретный тип
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => $posts_per_page,
            'offset' => $offset,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }
        if (!empty($city)) {
            $args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }

        $args['tax_query'] = array();
        if (!empty($type_filter)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => $type_filter
            );
        }
        if (!empty($role)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role
            );
        }

        $members_query = new WP_Query($args);
        $paged_members = $members_query->posts;
    }

    // Генерируем HTML для карточек
    ob_start();
    foreach ($paged_members as $post) {
        setup_postdata($post);
        $member_id = $post->ID;
        include(plugin_dir_path(__FILE__) . 'templates/member-card.php');
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html,
        'count' => count($paged_members)
    ));
}
add_action('wp_ajax_load_more_members', 'load_more_members_ajax');
add_action('wp_ajax_nopriv_load_more_members', 'load_more_members_ajax');

/**
 * AJAX обработчик для фильтрации участников
 * SECURITY FIX v3.7.3: Добавлен nonce для защиты от CSRF
 */
function filter_members_ajax() {
    // CSRF protection - публичный nonce
    check_ajax_referer('public_members_nonce', 'nonce');

    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
    $type_filter = isset($_POST['member_type']) ? sanitize_text_field($_POST['member_type']) : '';

    $posts_per_page = 12;

    // Если нет фильтра по типу - делаем два отдельных запроса и объединяем
    if (empty($type_filter)) {
        // Запрос для экспертов
        $experts_args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => 'ekspert'
                )
            )
        );

        if (!empty($search)) {
            $experts_args['s'] = $search;
        }
        if (!empty($city)) {
            $experts_args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }
        if (!empty($role)) {
            $experts_args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role
            );
        }

        $experts_query = new WP_Query($experts_args);

        // Запрос для участников
        $members_args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => 'uchastnik'
                )
            )
        );

        if (!empty($search)) {
            $members_args['s'] = $search;
        }
        if (!empty($city)) {
            $members_args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }
        if (!empty($role)) {
            $members_args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role
            );
        }

        $members_query = new WP_Query($members_args);

        // Объединяем результаты
        $all_members = array_merge($experts_query->posts, $members_query->posts);
        $total_found = count($all_members);

        // Берем первые N
        $paged_members = array_slice($all_members, 0, $posts_per_page);

    } else {
        // Если выбран конкретный тип
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        if (!empty($search)) {
            $args['s'] = $search;
        }
        if (!empty($city)) {
            $args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }

        $args['tax_query'] = array();
        if (!empty($type_filter)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => $type_filter
            );
        }
        if (!empty($role)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role
            );
        }

        $members_query = new WP_Query($args);
        $paged_members = $members_query->posts;
        $total_found = $members_query->found_posts;
    }

    // Генерируем HTML для карточек
    ob_start();
    foreach ($paged_members as $post) {
        setup_postdata($post);
        $member_id = $post->ID;
        include(plugin_dir_path(__FILE__) . 'templates/member-card.php');
    }
    wp_reset_postdata();
    $html = ob_get_clean();

    error_log('Sending JSON response: shown=' . count($paged_members) . ', total=' . $total_found);

    wp_send_json_success(array(
        'html' => $html,
        'shown' => count($paged_members),
        'total' => $total_found,
        'has_more' => $total_found > count($paged_members)
    ));

    exit; // Принудительно завершаем выполнение
}
add_action('wp_ajax_filter_members', 'filter_members_ajax');
add_action('wp_ajax_nopriv_filter_members', 'filter_members_ajax');

/**
 * AJAX обработчик для добавления материала в портфолио (новая JSON система)
 */
function ajax_add_portfolio_material() {
    // Проверка nonce
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    // Проверяем, редактирует ли админ чужой профиль
    $is_admin = current_user_can('administrator');
    $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

    if ($is_admin && $editing_member_id) {
        $member_post = get_post($editing_member_id);
        if (!$member_post || $member_post->post_type !== 'members') {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }
        $member_id = $editing_member_id;
    } else {
        $member_id = Member_User_Link::get_current_user_member_id();
        if (!$member_id) {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }
    }

    if (!$member_id) {
        wp_send_json_error(array('message' => 'Участник не найден'));
    }

    $category = sanitize_text_field($_POST['category']);
    $material_type = sanitize_text_field($_POST['material_type']);

    // Валидируем категорию
    $valid_categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
    if (!in_array($category, $valid_categories)) {
        wp_send_json_error(array('message' => 'Неверная категория'));
    }

    // Получаем текущие данные
    $field_name = 'member_' . $category . '_data';
    $current_data = get_post_meta($member_id, $field_name, true);
    $data_array = $current_data ? json_decode($current_data, true) : array();

    // Собираем новый материал
    $new_material = array(
        'type' => $material_type,
        'title' => sanitize_text_field($_POST['title']),
        'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
        'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
        'file_id' => 0,
        'author' => isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '',
        'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
    );

    // Обработка загрузки файла
    if ($material_type === 'file' && !empty($_FILES['file'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $file_id = media_handle_upload('file', $member_id);

        if (is_wp_error($file_id)) {
            wp_send_json_error(array('message' => 'Ошибка загрузки файла: ' . $file_id->get_error_message()));
        }

        $new_material['file_id'] = $file_id;
        $new_material['url'] = wp_get_attachment_url($file_id);
    }

    // Добавляем новый материал
    $data_array[] = $new_material;

    // Сохраняем
    update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

    wp_send_json_success(array(
        'message' => 'Материал успешно добавлен!',
        'reload' => true
    ));
}
add_action('wp_ajax_add_portfolio_material', 'ajax_add_portfolio_material');

/**
 * AJAX обработчик для удаления материала из портфолио (новая JSON система)
 */
function ajax_delete_portfolio_material() {
    // Проверка nonce
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
    $member_id = get_editable_member_id();
    if (is_wp_error($member_id)) {
        wp_send_json_error(array('message' => $member_id->get_error_message()));
    }

    $category = sanitize_text_field($_POST['category']);
    $index = intval($_POST['index']);

    // Валидируем категорию
    $valid_categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
    if (!in_array($category, $valid_categories)) {
        wp_send_json_error(array('message' => 'Неверная категория'));
    }

    // Получаем текущие данные
    $field_name = 'member_' . $category . '_data';
    $current_data = get_post_meta($member_id, $field_name, true);
    $data_array = $current_data ? json_decode($current_data, true) : array();

    // Проверяем что элемент существует
    if (!isset($data_array[$index])) {
        wp_send_json_error(array('message' => 'Материал не найден'));
    }

    // Удаляем файл если это был файл
    if (isset($data_array[$index]['type']) && $data_array[$index]['type'] === 'file' && isset($data_array[$index]['file_id'])) {
        wp_delete_attachment($data_array[$index]['file_id'], true);
    }

    // Удаляем элемент
    unset($data_array[$index]);
    $data_array = array_values($data_array); // Переиндексируем массив

    // Сохраняем
    update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

    wp_send_json_success(array(
        'message' => 'Материал успешно удален!',
        'reload' => true
    ));
}
add_action('wp_ajax_delete_portfolio_material', 'ajax_delete_portfolio_material');

/**
 * AJAX обработчик для редактирования материала портфолио (новая JSON система)
 */
function ajax_edit_portfolio_material() {
    // Проверка nonce
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
    $member_id = get_editable_member_id();
    if (is_wp_error($member_id)) {
        wp_send_json_error(array('message' => $member_id->get_error_message()));
    }

    $category = sanitize_text_field($_POST['category']);
    $index = intval($_POST['index']);
    $material_type = sanitize_text_field($_POST['material_type']);

    // Валидируем категорию
    $valid_categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
    if (!in_array($category, $valid_categories)) {
        wp_send_json_error(array('message' => 'Неверная категория'));
    }

    // Получаем текущие данные
    $field_name = 'member_' . $category . '_data';
    $current_data = get_post_meta($member_id, $field_name, true);
    $data_array = $current_data ? json_decode($current_data, true) : array();

    // Проверяем что элемент существует
    if (!isset($data_array[$index])) {
        wp_send_json_error(array('message' => 'Материал не найден'));
    }

    // Обновляем данные материала (сохраняем file_id если был файл)
    $updated_material = array(
        'type' => $material_type,
        'title' => sanitize_text_field($_POST['title']),
        'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
        'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
        'file_id' => isset($data_array[$index]['file_id']) ? $data_array[$index]['file_id'] : 0,
        'author' => isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '',
        'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
    );

    // Если это файл, сохраняем URL из старых данных
    if ($material_type === 'file' && isset($data_array[$index]['url'])) {
        $updated_material['url'] = $data_array[$index]['url'];
    }

    // Заменяем элемент
    $data_array[$index] = $updated_material;

    // Сохраняем
    update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

    wp_send_json_success(array(
        'message' => 'Материал успешно обновлен!',
        'reload' => true
    ));
}
add_action('wp_ajax_edit_portfolio_material', 'ajax_edit_portfolio_material');

/**
 * AJAX обработчик для создания темы форума из личного кабинета
 */
function ajax_create_forum_topic_dashboard() {
    // Проверка nonce
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходимо войти в систему'));
    }

    $title = sanitize_text_field($_POST['title']);
    $content = wp_kses_post($_POST['content']);
    $category_id = !empty($_POST['category']) ? intval($_POST['category']) : 0;

    if (empty($title) || empty($content)) {
        wp_send_json_error(array('message' => 'Заполните все обязательные поля'));
    }

    // Создаем новую тему
    $topic_data = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_type' => 'forum_topic',
        'post_status' => 'publish',
        'post_author' => get_current_user_id()
    );

    $topic_id = wp_insert_post($topic_data);

    if (is_wp_error($topic_id)) {
        wp_send_json_error(array('message' => 'Ошибка создания темы: ' . $topic_id->get_error_message()));
    }

    // Устанавливаем категорию если указана
    if ($category_id > 0) {
        wp_set_post_terms($topic_id, array($category_id), 'forum_category');
    }

    // Инициализируем счетчики
    update_post_meta($topic_id, 'views_count', 0);

    wp_send_json_success(array(
        'message' => 'Тема успешно создана!',
        'url' => get_permalink($topic_id),
        'reload' => true
    ));
}
add_action('wp_ajax_create_forum_topic_dashboard', 'ajax_create_forum_topic_dashboard');

/**
 * AJAX обработчик для отправки личного сообщения
 */
function ajax_send_member_message() {
    // Проверка nonce
    check_ajax_referer('send_member_message', 'nonce');

    // Honeypot check (антиспам)
    if (!empty($_POST['website'])) {
        wp_send_json_error(array('message' => 'Обнаружена подозрительная активность'));
    }

    $is_logged_in = is_user_logged_in();
    $recipient_member_id = intval($_POST['recipient_id']);
    $subject = sanitize_text_field($_POST['subject']);
    $content = wp_kses_post($_POST['content']);

    // Данные отправителя
    if ($is_logged_in) {
        $sender_user_id = get_current_user_id();
        $sender_member_id = Member_User_Link::get_current_user_member_id();
        $sender_name = get_the_title($sender_member_id);
        $sender_email = wp_get_current_user()->user_email;
    } else {
        // Для незалогиненных - получаем из формы
        $sender_user_id = 0;
        $sender_member_id = 0;
        $sender_name = sanitize_text_field($_POST['sender_name']);
        $sender_email = sanitize_email($_POST['sender_email']);

        // Валидация для незалогиненных
        if (empty($sender_name) || empty($sender_email)) {
            wp_send_json_error(array('message' => 'Укажите ваше имя и email'));
        }

        if (!is_email($sender_email)) {
            wp_send_json_error(array('message' => 'Укажите корректный email'));
        }
    }

    // Валидация
    if (empty($subject) || empty($content)) {
        wp_send_json_error(array('message' => 'Заполните все обязательные поля'));
    }

    if (empty($recipient_member_id)) {
        wp_send_json_error(array('message' => 'Получатель не указан'));
    }

    // Проверка: нельзя отправить сообщение самому себе (только для залогиненных)
    if ($is_logged_in && $sender_member_id == $recipient_member_id) {
        wp_send_json_error(array('message' => 'Нельзя отправить сообщение самому себе'));
    }

    // === АНТИСПАМ ЗАЩИТА ===

    if ($is_logged_in) {
        // 1. Rate limiting для залогиненных: не более 10 сообщений в день
        $today_start = strtotime('today');
        $messages_today = get_posts(array(
            'post_type' => 'member_message',
            'author' => $sender_user_id,
            'date_query' => array(
                array(
                    'after' => date('Y-m-d 00:00:00', $today_start),
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        if (count($messages_today) >= 10) {
            wp_send_json_error(array('message' => 'Вы достигли лимита сообщений на сегодня (10 в день)'));
        }

        // 2. Cooldown: минимум 2 минуты между сообщениями
        $last_message_time = get_user_meta($sender_user_id, 'last_message_sent_time', true);
        if ($last_message_time) {
            $time_diff = time() - intval($last_message_time);
            if ($time_diff < 120) { // 120 секунд = 2 минуты
                $wait_time = 120 - $time_diff;
                wp_send_json_error(array('message' => 'Пожалуйста, подождите ' . $wait_time . ' секунд перед отправкой следующего сообщения'));
            }
        }
    } else {
        // Антиспам для незалогиненных - по IP и email
        $sender_ip = $_SERVER['REMOTE_ADDR'];

        // 1. Rate limiting по IP: не более 5 сообщений в день
        $messages_from_ip = get_posts(array(
            'post_type' => 'member_message',
            'meta_query' => array(
                array(
                    'key' => 'sender_ip',
                    'value' => $sender_ip,
                ),
            ),
            'date_query' => array(
                array(
                    'after' => date('Y-m-d 00:00:00', strtotime('today')),
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        if (count($messages_from_ip) >= 5) {
            wp_send_json_error(array('message' => 'Превышен лимит сообщений на сегодня'));
        }

        // 2. Cooldown по IP: минимум 5 минут между сообщениями
        $last_message_from_ip = get_posts(array(
            'post_type' => 'member_message',
            'meta_query' => array(
                array(
                    'key' => 'sender_ip',
                    'value' => $sender_ip,
                ),
            ),
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (!empty($last_message_from_ip)) {
            $last_time = strtotime($last_message_from_ip[0]->post_date);
            $time_diff = time() - $last_time;
            if ($time_diff < 300) { // 300 секунд = 5 минут
                $wait_time = ceil((300 - $time_diff) / 60);
                wp_send_json_error(array('message' => 'Пожалуйста, подождите ' . $wait_time . ' мин. перед отправкой следующего сообщения'));
            }
        }
    }

    // Создаем сообщение
    $message_data = array(
        'post_title' => $subject,
        'post_content' => $content,
        'post_type' => 'member_message',
        'post_status' => 'publish',
        'post_author' => $sender_user_id
    );

    $message_id = wp_insert_post($message_data);

    if (is_wp_error($message_id)) {
        wp_send_json_error(array('message' => 'Ошибка отправки сообщения'));
    }

    // Сохраняем мета-данные
    update_post_meta($message_id, 'recipient_member_id', $recipient_member_id);
    update_post_meta($message_id, 'sender_member_id', $sender_member_id);
    update_post_meta($message_id, 'is_read', 0);
    update_post_meta($message_id, 'sent_at', current_time('mysql'));

    // Для незалогиненных - сохраняем дополнительные данные
    if (!$is_logged_in) {
        update_post_meta($message_id, 'sender_name', $sender_name);
        update_post_meta($message_id, 'sender_email', $sender_email);
        update_post_meta($message_id, 'sender_ip', $_SERVER['REMOTE_ADDR']);
    }

    // Обновляем время последней отправки
    if ($is_logged_in) {
        update_user_meta($sender_user_id, 'last_message_sent_time', time());
    }

    // Отправляем email уведомление получателю
    $recipient_user = get_user_by('ID', get_post_field('post_author', $recipient_member_id));
    if ($recipient_user) {
        $recipient_name = get_the_title($recipient_member_id);

        $email_subject = '[Метода] Новое сообщение от ' . $sender_name;
        $email_body = "Здравствуйте, {$recipient_name}!\n\n";
        $email_body .= "Вам пришло новое личное сообщение от {$sender_name}";

        if (!$is_logged_in) {
            $email_body .= " ({$sender_email})";
        }

        $email_body .= ".\n\nТема: {$subject}\n\n";

        if ($is_logged_in) {
            $email_body .= "Чтобы прочитать сообщение и ответить, войдите в личный кабинет:\n";
            $email_body .= get_permalink(get_option('metoda_dashboard_page_id')) . "\n\n";
        } else {
            $email_body .= "Для ответа напишите на: {$sender_email}\n\n";
            $email_body .= "Или прочитайте сообщение в личном кабинете:\n";
            $email_body .= get_permalink(get_option('metoda_dashboard_page_id')) . "\n\n";
        }

        $email_body .= "---\n";
        $email_body .= "Это сообщение отправлено через форму на сайте Метода.";

        wp_mail($recipient_user->user_email, $email_subject, $email_body);
    }

    wp_send_json_success(array(
        'message' => 'Сообщение успешно отправлено!',
        'message_id' => $message_id
    ));
}
add_action('wp_ajax_send_member_message', 'ajax_send_member_message');
add_action('wp_ajax_nopriv_send_member_message', 'ajax_send_member_message'); // Для незалогиненных

/**
 * AJAX обработчик для просмотра сообщения
 */
function ajax_view_member_message() {
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходимо войти в систему'));
    }

    $message_id = intval($_POST['message_id']);
    $message = get_post($message_id);

    if (!$message || $message->post_type !== 'member_message') {
        wp_send_json_error(array('message' => 'Сообщение не найдено'));
    }

    $current_member_id = Member_User_Link::get_current_user_member_id();
    $recipient_id = get_post_meta($message_id, 'recipient_member_id', true);
    $sender_id = get_post_meta($message_id, 'sender_member_id', true);

    // Проверка доступа: только отправитель или получатель могут просмотреть
    if ($current_member_id != $recipient_id && $current_member_id != $sender_id) {
        wp_send_json_error(array('message' => 'Доступ запрещен'));
    }

    // Помечаем как прочитанное (если это получатель)
    if ($current_member_id == $recipient_id) {
        update_post_meta($message_id, 'is_read', 1);
        update_post_meta($message_id, 'read_at', current_time('mysql'));
    }

    // Формируем мета информацию
    $meta = '';
    if ($current_member_id == $recipient_id) {
        // Показываем отправителя
        if (empty($sender_id)) {
            // Сообщение от незалогиненного пользователя
            $sender_name = get_post_meta($message_id, 'sender_name', true);
            $sender_email = get_post_meta($message_id, 'sender_email', true);
            $meta .= '<strong>От:</strong> ' . esc_html($sender_name) . ' (' . esc_html($sender_email) . ')<br>';
        } else {
            $meta .= '<strong>От:</strong> ' . get_the_title($sender_id) . '<br>';
        }
    } else {
        $meta .= '<strong>Кому:</strong> ' . get_the_title($recipient_id) . '<br>';
    }
    $meta .= '<strong>Дата:</strong> ' . get_the_date('d.m.Y H:i', $message_id);

    wp_send_json_success(array(
        'title' => $message->post_title,
        'content' => $message->post_content,
        'meta' => $meta
    ));
}
add_action('wp_ajax_view_member_message', 'ajax_view_member_message');

/**
 * Добавление страницы логов активности в админку
 */
function metoda_add_activity_log_menu() {
    add_menu_page(
        'Логи активности',
        'Активность',
        'manage_options',
        'metoda-activity-log',
        'metoda_render_activity_log_page',
        'dashicons-visibility',
        30
    );
}
add_action('admin_menu', 'metoda_add_activity_log_menu');

/**
 * Рендер страницы логов активности
 */
function metoda_render_activity_log_page() {
    if (!current_user_can('manage_options')) {
        wp_die('У вас нет прав для просмотра этой страницы');
    }

    // Получаем последние сообщения
    $messages_args = array(
        'post_type' => 'member_message',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC',
        'post_status' => array('publish', 'private')
    );
    $messages_query = new WP_Query($messages_args);

    // Получаем последние посты форума
    $forum_args = array(
        'post_type' => 'forum_topic',
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    );
    $forum_query = new WP_Query($forum_args);

    // Получаем всех участников для быстрого доступа
    $members_args = array(
        'post_type' => 'members',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );
    $members_query = new WP_Query($members_args);

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-visibility" style="font-size: 30px; width: 30px; height: 30px;"></span>
            Логи активности участников
        </h1>
        <p class="description">Мониторинг активности пользователей: сообщения, посты на форуме и доступ к личным кабинетам</p>
        
        <hr class="wp-header-end">

        <!-- Быстрый доступ к кабинетам -->
        <div class="card" style="margin-top: 20px;">
            <h2>🚀 Быстрый доступ к личным кабинетам</h2>
            <p>Выберите участника для просмотра его личного кабинета:</p>
            <select id="member-select" style="width: 400px; max-width: 100%;" onchange="if(this.value) window.open(this.value, '_blank')">
                <option value="">-- Выберите участника --</option>
                <?php
                while ($members_query->have_posts()) {
                    $members_query->the_post();
                    $dashboard_url = add_query_arg('member_id', get_the_ID(), home_url('/member-dashboard/'));
                    echo '<option value="' . esc_url($dashboard_url) . '">' . esc_html(get_the_title()) . '</option>';
                }
                wp_reset_postdata();
                ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <!-- Последние сообщения -->
            <div class="card">
                <h2>💬 Последние сообщения (<?php echo $messages_query->found_posts; ?>)</h2>
                <?php if ($messages_query->have_posts()): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>От кого → Кому</th>
                                <th>Тема</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($messages_query->have_posts()): $messages_query->the_post();
                                $sender_id = get_post_meta(get_the_ID(), 'sender_member_id', true);
                                $recipient_id = get_post_meta(get_the_ID(), 'recipient_member_id', true);

                                // Определяем имя отправителя
                                if ($sender_id) {
                                    $sender_name = get_the_title($sender_id);
                                } else {
                                    // Проверяем, не администратор ли это
                                    $post_author_id = get_post_field('post_author', get_the_ID());
                                    if ($post_author_id && user_can($post_author_id, 'administrator')) {
                                        $sender_name = '👑 Администратор';
                                    } else {
                                        $sender_name = get_post_meta(get_the_ID(), 'sender_name', true) ?: 'Неизвестно';
                                    }
                                }

                                $recipient_name = $recipient_id ? get_the_title($recipient_id) : 'Неизвестно';
                            ?>
                                <tr>
                                    <td><?php echo get_the_date('d.m.Y H:i'); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($sender_name); ?></strong>
                                        →
                                        <strong><?php echo esc_html($recipient_name); ?></strong>
                                    </td>
                                    <td><?php the_title(); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Сообщений пока нет</p>
                <?php endif; wp_reset_postdata(); ?>
            </div>

            <!-- Последние посты форума -->
            <div class="card">
                <h2>📝 Последние посты на форуме (<?php echo $forum_query->found_posts; ?>)</h2>
                <?php if ($forum_query->have_posts()): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Автор</th>
                                <th>Тема</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($forum_query->have_posts()): $forum_query->the_post(); 
                                $author_member_id = get_post_meta(get_the_ID(), 'author_member_id', true);
                                $author_name = $author_member_id ? get_the_title($author_member_id) : get_the_author();
                            ?>
                                <tr>
                                    <td><?php echo get_the_date('d.m.Y H:i'); ?></td>
                                    <td><strong><?php echo esc_html($author_name); ?></strong></td>
                                    <td>
                                        <a href="<?php the_permalink(); ?>" target="_blank">
                                            <?php the_title(); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Постов на форуме пока нет</p>
                <?php endif; wp_reset_postdata(); ?>
            </div>
        </div>

        <style>
            .card {
                background: white;
                padding: 20px;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .card h2 {
                margin-top: 0;
                font-size: 18px;
                font-weight: 600;
            }
            .card table {
                margin-top: 15px;
            }
            .card table th {
                font-weight: 600;
                background: #f6f7f7;
            }
            .card table td {
                vertical-align: middle;
            }
            #member-select {
                padding: 8px;
                font-size: 14px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
        </style>
    </div>
    <?php
}

/**
 * Добавление колонки "Личный кабинет" в список участников
 */
function metoda_add_dashboard_column($columns) {
    $columns['dashboard_access'] = '<span class="dashicons dashicons-admin-home"></span> Личный кабинет';
    return $columns;
}
add_filter('manage_members_posts_columns', 'metoda_add_dashboard_column');

/**
 * Вывод кнопки доступа к ЛК в колонке
 */
function metoda_render_dashboard_column($column, $post_id) {
    if ($column === 'dashboard_access') {
        $dashboard_url = add_query_arg('member_id', $post_id, home_url('/member-dashboard/'));
        echo '<a href="' . esc_url($dashboard_url) . '" class="button button-small" target="_blank" title="Открыть личный кабинет этого участника">';
        echo '<span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> Просмотр ЛК';
        echo '</a>';
    }
}
add_action('manage_members_posts_custom_column', 'metoda_render_dashboard_column', 10, 2);

/**
 * Ограничение доступа к форуму только для залогиненных пользователей
 */
function metoda_restrict_forum_access() {
    // Проверяем, открыт ли single форум или архив форума
    if (is_singular('forum_topic') || is_post_type_archive('forum_topic')) {
        if (!is_user_logged_in()) {
            // Перенаправляем на страницу входа
            auth_redirect();
        }
    }
}
add_action('template_redirect', 'metoda_restrict_forum_access');

/**
 * Добавление ссылки на форум в админ-бар
 */
function metoda_add_forum_to_admin_bar($wp_admin_bar) {
    if (!is_user_logged_in()) {
        return;
    }

    $forum_url = get_post_type_archive_link('forum_topic');
    if ($forum_url) {
        $wp_admin_bar->add_node(array(
            'id' => 'metoda-forum',
            'title' => '<span class="ab-icon dashicons dashicons-format-chat"></span> Форум',
            'href' => $forum_url,
            'meta' => array(
                'target' => '_blank'
            )
        ));
    }
}
add_action('admin_bar_menu', 'metoda_add_forum_to_admin_bar', 100);

/**
 * Добавление пункта "Форум" в админ меню
 */
function metoda_add_forum_admin_menu() {
    add_menu_page(
        'Форум сообщества',
        'Форум',
        'read',
        'metoda-forum-redirect',
        'metoda_forum_redirect_handler',
        'dashicons-format-chat',
        31
    );
}
add_action('admin_menu', 'metoda_add_forum_admin_menu');

/**
 * Редирект на форум из админки
 */
function metoda_forum_redirect_handler() {
    $forum_url = get_post_type_archive_link('forum_topic');
    if ($forum_url) {
        ?>
        <script type="text/javascript">
            window.location.href = '<?php echo esc_url($forum_url); ?>';
        </script>
        <div class="wrap">
            <h1>Перенаправление на форум...</h1>
            <p>Если вы не были перенаправлены, <a href="<?php echo esc_url($forum_url); ?>">нажмите здесь</a>.</p>
        </div>
        <?php
    } else {
        echo '<div class="wrap"><h1>Форум недоступен</h1><p>Страница форума не настроена.</p></div>';
    }
}

/**
 * Добавление колонок в список сообщений в админке
 */
function metoda_add_message_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        if ($key === 'title') {
            $new_columns['sender'] = 'От кого';
            $new_columns['recipient'] = 'Кому';
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
}
add_filter('manage_member_message_posts_columns', 'metoda_add_message_columns');

/**
 * Вывод данных в колонках сообщений
 */
function metoda_render_message_columns($column, $post_id) {
    if ($column === 'sender') {
        $sender_id = get_post_meta($post_id, 'sender_member_id', true);
        if ($sender_id) {
            echo '<strong>' . esc_html(get_the_title($sender_id)) . '</strong>';
        } else {
            // Проверяем, не администратор ли это
            $post_author_id = get_post_field('post_author', $post_id);
            if ($post_author_id && user_can($post_author_id, 'administrator')) {
                echo '<strong>👑 Администратор</strong>';
            } else {
                $sender_name = get_post_meta($post_id, 'sender_name', true);
                $sender_email = get_post_meta($post_id, 'sender_email', true);
                if ($sender_name) {
                    echo '<strong>' . esc_html($sender_name) . '</strong><br>';
                    echo '<small style="color: #999;">' . esc_html($sender_email) . '</small>';
                } else {
                    echo '<span style="color: #999;">Неизвестно</span>';
                }
            }
        }
    }
    
    if ($column === 'recipient') {
        $recipient_id = get_post_meta($post_id, 'recipient_member_id', true);
        if ($recipient_id) {
            $dashboard_url = add_query_arg('member_id', $recipient_id, home_url('/member-dashboard/'));
            echo '<strong><a href="' . esc_url(get_permalink($recipient_id)) . '" target="_blank">' . esc_html(get_the_title($recipient_id)) . '</a></strong>';
            echo '<br><small><a href="' . esc_url($dashboard_url) . '" target="_blank" style="color: #0073aa;">→ Личный кабинет</a></small>';
        } else {
            echo '<span style="color: #999;">Неизвестно</span>';
        }
    }
}
add_action('manage_member_message_posts_custom_column', 'metoda_render_message_columns', 10, 2);

/**
 * Автосоздание всех важных страниц при загрузке админки
 */
function metoda_ensure_important_pages() {
    // Проверяем только в админке
    if (!is_admin()) {
        return;
    }

    // Проверяем раз в день (чтобы не нагружать)
    $last_check = get_option('metoda_pages_check');
    if ($last_check && (time() - $last_check) < DAY_IN_SECONDS) {
        return;
    }

    // Обновляем время проверки
    update_option('metoda_pages_check', time());

    // Список важных страниц
    $important_pages = array(
        array(
            'slug' => 'member-dashboard',
            'title' => 'Личный кабинет',
            'shortcode' => '[member_dashboard]',
            'description' => 'Личный кабинет участника'
        ),
        array(
            'slug' => 'member-login',
            'title' => 'Вход для участников',
            'template' => 'templates/member-login.php',
            'description' => 'Новая страница входа с тремя способами (пароль / код доступа / OTP)'
        ),
        array(
            'slug' => 'member-onboarding',
            'title' => 'Добро пожаловать',
            'template' => 'templates/member-onboarding.php',
            'description' => 'Онбординг в стиле Apple для новых участников'
        ),
        array(
            'slug' => 'manager-panel',
            'title' => 'Панель менеджера',
            'shortcode' => '[member_manager]',
            'description' => 'Панель управления для менеджеров'
        ),
        array(
            'slug' => 'forgot-password',
            'title' => 'Восстановление пароля',
            'shortcode' => '[forgot_password]',
            'description' => 'Страница восстановления пароля'
        )
    );

    $created_pages = array();

    foreach ($important_pages as $page_config) {
        // Проверяем, существует ли страница
        $page = get_page_by_path($page_config['slug']);

        if (!$page) {
            // Определяем контент страницы
            $post_content = isset($page_config['shortcode']) ? $page_config['shortcode'] : '';

            // Создаем страницу
            $page_id = wp_insert_post(array(
                'post_title' => $page_config['title'],
                'post_name' => $page_config['slug'],
                'post_content' => $post_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1,
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));

            if ($page_id && !is_wp_error($page_id)) {
                // Если указан template, устанавливаем его
                if (isset($page_config['template'])) {
                    update_post_meta($page_id, '_wp_page_template', $page_config['template']);
                }

                $created_pages[] = $page_config['title'] . ' (/' . $page_config['slug'] . '/)';
                error_log('Metoda: Создана страница "' . $page_config['title'] . '" (ID: ' . $page_id . ')');
            }
        }
    }

    // Если были созданы страницы, показываем уведомление админу
    if (!empty($created_pages)) {
        set_transient('metoda_pages_created_notice', $created_pages, 300);
    }
}
add_action('admin_init', 'metoda_ensure_important_pages');

/**
 * Показываем уведомление о созданных страницах
 */
function metoda_show_pages_created_notice() {
    $created_pages = get_transient('metoda_pages_created_notice');
    if ($created_pages) {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Metoda Community:</strong> Автоматически созданы следующие страницы:</p>
            <ul style="list-style: disc; padding-left: 20px;">
                <?php foreach ($created_pages as $page): ?>
                    <li><?php echo esc_html($page); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Вы можете найти их в разделе <a href="<?php echo admin_url('edit.php?post_type=page'); ?>">Страницы</a>.</p>
        </div>
        <?php
        delete_transient('metoda_pages_created_notice');
    }
}
add_action('admin_notices', 'metoda_show_pages_created_notice');

} // END OF if(false) - LEGACY CODE DISABLED
// ================================================================
// END OF LEGACY CODE SECTION
// ================================================================
