<?php
/**
 * Plugin Name: Members Management Pro
 * Description: Продвинутая система управления участниками и экспертами сообщества
 * Version: 2.0
 * Author: Your Name
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Подключение классов личного кабинета
require_once plugin_dir_path(__FILE__) . 'includes/class-member-user-link.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-file-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-onboarding.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-bulk-users.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-csv-importer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-member-page-templates.php';

// Хук активации плагина (создание страниц при активации - опционально)
// register_activation_hook(__FILE__, array('Member_Page_Templates', 'activate'));

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

    $position = get_post_meta($post->ID, 'member_position', true);
    $company = get_post_meta($post->ID, 'member_company', true);
    $email = get_post_meta($post->ID, 'member_email', true);
    $phone = get_post_meta($post->ID, 'member_phone', true);
    $bio = get_post_meta($post->ID, 'member_bio', true);
    $specialization = get_post_meta($post->ID, 'member_specialization', true);
    $experience = get_post_meta($post->ID, 'member_experience', true);
    $interests = get_post_meta($post->ID, 'member_interests', true);
    $linkedin = get_post_meta($post->ID, 'member_linkedin', true);
    $website = get_post_meta($post->ID, 'member_website', true);
    $expectations = get_post_meta($post->ID, 'member_expectations', true);
    $gallery_ids = get_post_meta($post->ID, 'member_gallery', true);

    // Данные для табов
    $testimonials = get_post_meta($post->ID, 'member_testimonials', true);
    $gratitudes = get_post_meta($post->ID, 'member_gratitudes', true);
    $interviews = get_post_meta($post->ID, 'member_interviews', true);
    $videos = get_post_meta($post->ID, 'member_videos', true);
    $reviews = get_post_meta($post->ID, 'member_reviews', true);
    $developments = get_post_meta($post->ID, 'member_developments', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="member_position">Должность</label></th>
            <td><input type="text" id="member_position" name="member_position" value="<?php echo esc_attr($position); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="member_company">Организация</label></th>
            <td><input type="text" id="member_company" name="member_company" value="<?php echo esc_attr($company); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="member_specialization">Специализация</label></th>
            <td><textarea id="member_specialization" name="member_specialization" rows="3" class="large-text"><?php echo esc_textarea($specialization); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_experience">Опыт работы</label></th>
            <td><input type="text" id="member_experience" name="member_experience" value="<?php echo esc_attr($experience); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="member_interests">Профессиональные интересы</label></th>
            <td><textarea id="member_interests" name="member_interests" rows="3" class="large-text"><?php echo esc_textarea($interests); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_email">Email</label></th>
            <td><input type="email" id="member_email" name="member_email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="member_phone">Телефон</label></th>
            <td><input type="tel" id="member_phone" name="member_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="member_linkedin">LinkedIn</label></th>
            <td><input type="url" id="member_linkedin" name="member_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/in/username" /></td>
        </tr>
        <tr>
            <th><label for="member_website">Вебсайт</label></th>
            <td><input type="url" id="member_website" name="member_website" value="<?php echo esc_attr($website); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="member_bio">Расширенная биография</label></th>
            <td><textarea id="member_bio" name="member_bio" rows="5" class="large-text"><?php echo esc_textarea($bio); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="member_expectations">Ожидания от сотрудничества с ассоциацией</label></th>
            <td><textarea id="member_expectations" name="member_expectations" rows="4" class="large-text"><?php echo esc_textarea($expectations); ?></textarea></td>
        </tr>
    </table>

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

    $fields = array(
        'member_position',
        'member_company',
        'member_email',
        'member_phone',
        'member_bio',
        'member_specialization',
        'member_experience',
        'member_interests',
        'member_linkedin',
        'member_website',
        'member_expectations',
        'member_gallery',
        'member_testimonials',
        'member_gratitudes',
        'member_interviews',
        'member_videos',
        'member_reviews',
        'member_developments'
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
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

// Добавление страницы импорта в админку
function add_members_import_page() {
    add_submenu_page(
        'edit.php?post_type=members',
        'Импорт участников',
        'Импорт из CSV',
        'manage_options',
        'members-import',
        'members_import_page_callback'
    );
}
add_action('admin_menu', 'add_members_import_page');

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
