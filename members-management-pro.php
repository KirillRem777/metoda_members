<?php
/**
 * Plugin Name: Metoda Community MGMT
 * Description: Полнофункциональная система управления участниками и экспертами сообщества. Включает: регистрацию с валидацией, систему кодов доступа для импортированных участников, личные кабинеты с онбордингом, управление материалами с WYSIWYG-редактором, форум в стиле Reddit с категориями и лайками, настраиваемые email-шаблоны, CSV-импорт, кроппер фото, систему ролей и прав доступа, поиск и фильтрацию участников.
 * Version: 3.0.0
 * Author: Kirill Rem
 * Text Domain: metoda-community-mgmt
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Подключение классов личного кабинета
require_once plugin_dir_path(__FILE__) . 'includes/class-member-user-link.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-file-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-archive.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-onboarding.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-access-codes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-bulk-users.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-csv-importer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-page-templates.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-template-loader.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-email-templates.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-forum.php';

// Хуки активации/деактивации плагина
register_activation_hook(__FILE__, 'metoda_members_activate');
register_deactivation_hook(__FILE__, 'metoda_members_deactivate');

/**
 * Функция активации плагина
 */
function metoda_members_activate() {
    // Регистрируем post type
    register_members_post_type();

    // Регистрируем таксономии
    register_member_type_taxonomy();
    register_member_role_taxonomy();
    register_member_location_taxonomy();

    // Создаем роли
    metoda_create_custom_roles();

    // Создаем шаблонные страницы
    metoda_create_template_pages();

    // Сбрасываем постоянные ссылки
    flush_rewrite_rules();
}

/**
 * Создание кастомных ролей
 */
function metoda_create_custom_roles() {
    // Роль участника/эксперта
    add_role('member', 'Участник', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false
    ));

    add_role('expert', 'Эксперт', array(
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false
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
        'manage_members' => true
    ));
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
 * Функция деактивации плагина
 */
function metoda_members_deactivate() {
    // Сбрасываем постоянные ссылки
    flush_rewrite_rules();
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
    <h3>Дополнительные материалы</h3>
    <table class="form-table">
        <tr>
            <th><label for="member_testimonials">Отзывы</label></th>
            <td><textarea id="member_testimonials" name="member_testimonials" rows="4" class="large-text"><?php echo esc_textarea($testimonials); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_gratitudes">Благодарности</label></th>
            <td><textarea id="member_gratitudes" name="member_gratitudes" rows="4" class="large-text"><?php echo esc_textarea($gratitudes); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_interviews">Интервью (ссылки через запятую)</label></th>
            <td><textarea id="member_interviews" name="member_interviews" rows="3" class="large-text"><?php echo esc_textarea($interviews); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_videos">Видео (YouTube/Vimeo ссылки через запятую)</label></th>
            <td><textarea id="member_videos" name="member_videos" rows="3" class="large-text"><?php echo esc_textarea($videos); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_reviews">Рецензии</label></th>
            <td><textarea id="member_reviews" name="member_reviews" rows="4" class="large-text"><?php echo esc_textarea($reviews); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_developments">Разработки (ссылки через запятую)</label></th>
            <td><textarea id="member_developments" name="member_developments" rows="3" class="large-text"><?php echo esc_textarea($developments); ?></textarea></td>
        </tr>
    </table>

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

/**
 * Шорткод для личного кабинета
 */
function member_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Пожалуйста, <a href="' . wp_login_url(get_permalink()) . '">войдите</a>, чтобы получить доступ к личному кабинету.</p>';
    }

    $user = wp_get_current_user();
    if (!in_array('member', $user->roles) && !in_array('expert', $user->roles)) {
        return '<p>У вас нет доступа к этой странице.</p>';
    }

    ob_start();
    include(plugin_dir_path(__FILE__) . 'templates/member-dashboard.php');
    return ob_get_clean();
}
add_shortcode('member_dashboard', 'member_dashboard_shortcode');

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
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('manager', $user->roles) || in_array('administrator', $user->roles)) {
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
function members_plugin_activate() {
    // Создаем термины по умолчанию
    wp_insert_term('Эксперт', 'member_type');
    wp_insert_term('Участник', 'member_type');
    
    wp_insert_term('Эксперт', 'member_role');
    wp_insert_term('Куратор секции', 'member_role');
    wp_insert_term('Лидер проектной группы', 'member_role');
    wp_insert_term('Амбассадор', 'member_role');
    wp_insert_term('Почетный член', 'member_role');
    wp_insert_term('Партнер', 'member_role');
    wp_insert_term('Активист', 'member_role');
    wp_insert_term('Слушатель', 'member_role');
    wp_insert_term('Волонтер', 'member_role');
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'members_plugin_activate');

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
            'nonce' => wp_create_nonce('members_ajax_nonce')
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
                'nonce' => wp_create_nonce('member_registration')
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
                'nonce' => wp_create_nonce('member_dashboard')
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
                'nonce' => wp_create_nonce('manager_actions')
            ));
        }
    }
}
add_action('wp_enqueue_scripts', 'members_enqueue_scripts');

/**
 * AJAX обработчик фильтрации участников
 */
function ajax_filter_members() {
    // Проверка nonce
    check_ajax_referer('members_ajax_nonce', 'nonce');

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
add_action('wp_ajax_filter_members', 'ajax_filter_members');
add_action('wp_ajax_nopriv_filter_members', 'ajax_filter_members');

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
    check_ajax_referer('member_registration', 'nonce');

    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
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
            $linked_user = get_post_meta($existing_member->ID, 'member_user_id', true);

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
    update_post_meta($member_id, 'member_user_id', $user_id);
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

/**
 * AJAX обработчик обновления профиля участника
 */
function member_update_profile_ajax() {
    check_ajax_referer('member_dashboard_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
    }

    $user_id = get_current_user_id();
    $member_id = get_user_meta($user_id, 'member_id', true);

    if (!$member_id) {
        wp_send_json_error(array('message' => 'Профиль не найден'));
    }

    // Обновляем заголовок
    if (isset($_POST['member_name'])) {
        wp_update_post(array(
            'ID' => $member_id,
            'post_title' => sanitize_text_field($_POST['member_name'])
        ));
    }

    // Обновляем метаданные
    $meta_fields = array(
        'member_company',
        'member_position',
        'member_city',
        'member_email',
        'member_phone',
        'member_linkedin',
        'member_website'
    );

    foreach ($meta_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($member_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Textarea поля
    $textarea_fields = array(
        'member_specialization_experience',
        'member_professional_interests'
    );

    foreach ($textarea_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($member_id, $field, sanitize_textarea_field($_POST[$field]));
        }
    }

    // HTML поля
    $html_fields = array(
        'member_bio',
        'member_expectations'
    );

    foreach ($html_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($member_id, $field, wp_kses_post($_POST[$field]));
        }
    }

    wp_send_json_success(array('message' => 'Профиль успешно обновлен!'));
}
add_action('wp_ajax_member_update_profile', 'member_update_profile_ajax');

/**
 * Редирект после логина - отправляем в соответствующие кабинеты
 */
function member_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // Менеджеры и админы в панель управления
        if (in_array('manager', $user->roles) || in_array('administrator', $user->roles)) {
            return home_url('/manager-panel/');
        }
        // Участники и эксперты в личный кабинет
        if (in_array('member', $user->roles) || in_array('expert', $user->roles)) {
            return home_url('/member-dashboard/');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'member_login_redirect', 10, 3);

/**
 * Редирект после логаута
 */
function member_logout_redirect() {
    return home_url();
}
add_filter('logout_redirect', 'member_logout_redirect');

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
    // Only run in admin area, not during AJAX
    if (!is_admin() || wp_doing_ajax()) {
        return;
    }

    // Administrators and users with manage_options capability always have access
    if (current_user_can('manage_options') || current_user_can('administrator')) {
        return;
    }

    // Don't redirect on plugin management pages
    global $pagenow;
    $allowed_pages = array('plugins.php', 'plugin-install.php', 'plugin-editor.php', 'update-core.php');
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

    // Get current user
    $user = wp_get_current_user();

    // Check if user has member or expert role (not checking capabilities to avoid conflicts)
    if (!empty($user->roles)) {
        $member_roles = array('member', 'expert');
        $user_roles = (array) $user->roles;

        // Only redirect if user has member/expert role and no admin privileges
        if (array_intersect($member_roles, $user_roles)) {
            wp_redirect(home_url('/member-dashboard/'));
            exit;
        }
    }
}
add_action('admin_init', 'block_admin_access_for_members');

/**
 * AJAX обработчик изменения статуса участника (для менеджеров)
 */
function manager_change_member_status_ajax() {
    check_ajax_referer('manager_actions', 'nonce');

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

/**
 * AJAX обработчик удаления участника (для менеджеров)
 */
function manager_delete_member_ajax() {
    check_ajax_referer('manager_actions', 'nonce');

    if (!current_user_can('manager') && !current_user_can('administrator')) {
        wp_send_json_error(array('message' => 'Нет прав доступа'));
    }

    $member_id = intval($_POST['member_id']);

    // Получаем связанного пользователя
    $post = get_post($member_id);
    if ($post && $post->post_author) {
        $user_id = $post->post_author;
        // Удаляем пользователя WordPress
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        wp_delete_user($user_id);
    }

    // Удаляем запись участника
    $result = wp_delete_post($member_id, true);

    if (!$result) {
        wp_send_json_error(array('message' => 'Ошибка при удалении участника'));
    }

    wp_send_json_success(array(
        'message' => 'Участник успешно удален'
    ));
}
add_action('wp_ajax_manager_delete_member', 'manager_delete_member_ajax');

/**
 * AJAX обработчик для сохранения галереи
 */
function member_save_gallery_ajax() {
    check_ajax_referer('member_dashboard', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    $member_id = Member_User_Link::get_current_user_member_id();
    if (!$member_id) {
        wp_send_json_error(array('message' => 'Участник не найден'));
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
    check_ajax_referer('member_dashboard', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    $member_id = Member_User_Link::get_current_user_member_id();
    if (!$member_id) {
        wp_send_json_error(array('message' => 'Участник не найден'));
    }

    // Проверяем, был ли загружен файл
    if (empty($_FILES['photo'])) {
        wp_send_json_error(array('message' => 'Файл не загружен'));
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
    check_ajax_referer('member_dashboard', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    $member_id = Member_User_Link::get_current_user_member_id();
    if (!$member_id) {
        wp_send_json_error(array('message' => 'Участник не найден'));
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
    check_ajax_referer('member_dashboard', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    $member_id = Member_User_Link::get_current_user_member_id();
    if (!$member_id) {
        wp_send_json_error(array('message' => 'Участник не найден'));
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

/**
 * AJAX обработчик для удаления материала
 */
function member_delete_material_ajax() {
    check_ajax_referer('member_dashboard', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Необходима авторизация'));
    }

    $member_id = Member_User_Link::get_current_user_member_id();
    if (!$member_id) {
        wp_send_json_error(array('message' => 'Участник не найден'));
    }

    $category = sanitize_text_field($_POST['category']);
    $index = intval($_POST['index']);

    // Получаем текущие материалы
    $current_materials = get_post_meta($member_id, 'member_' . $category, true);

    if (empty($current_materials)) {
        wp_send_json_error(array('message' => 'Материалы не найдены'));
    }

    // Разбиваем на строки
    $materials_array = explode("\n", $current_materials);

    // Удаляем элемент по индексу
    if (isset($materials_array[$index])) {
        unset($materials_array[$index]);

        // Пересобираем строку
        $updated_materials = implode("\n", array_values($materials_array));

        update_post_meta($member_id, 'member_' . $category, $updated_materials);

        wp_send_json_success(array(
            'message' => 'Материал успешно удален!',
            'reload' => true
        ));
    } else {
        wp_send_json_error(array('message' => 'Материал не найден'));
    }
}
add_action('wp_ajax_member_delete_material', 'member_delete_material_ajax');
