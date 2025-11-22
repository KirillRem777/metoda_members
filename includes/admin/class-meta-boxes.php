<?php
/**
 * Meta Boxes
 *
 * Handles all custom meta boxes for members post type
 *
 * @package Metoda_Members
 * @subpackage Admin
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Meta_Boxes {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_member_meta_boxes'));
        add_action('save_post_members', array($this, 'save_member_details'));
    }

    // Добавление метабоксов для дополнительных полей
    public function add_member_meta_boxes() {
        add_meta_box(
            'member_details',
            'Детали участника',
            array($this, 'render_member_details_meta_box'),
            'members',
            'normal',
            'high'
        );
    }


    // Рендер метабокса
    public function render_member_details_meta_box($post) {
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
        $telegram = get_post_meta($post->ID, 'member_telegram', true);
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
                    <th><label for="member_telegram">Telegram</label></th>
                    <td><input type="text" id="member_telegram" name="member_telegram" value="<?php echo esc_attr($telegram); ?>" class="regular-text" placeholder="@username или username" /></td>
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
    public function save_member_details($post_id) {
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
            'member_telegram',
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
}
