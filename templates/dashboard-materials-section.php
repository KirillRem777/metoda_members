<?php
/**
 * Dashboard Materials Section
 * Секция управления портфолио в личном кабинете (новая система с JSON repeater)
 */

if (!defined('ABSPATH')) exit;

// Получаем данные материалов
$testimonials_data = get_post_meta($member_id, 'member_testimonials_data', true);
$gratitudes_data = get_post_meta($member_id, 'member_gratitudes_data', true);
$interviews_data = get_post_meta($member_id, 'member_interviews_data', true);
$videos_data = get_post_meta($member_id, 'member_videos_data', true);
$reviews_data = get_post_meta($member_id, 'member_reviews_data', true);
$developments_data = get_post_meta($member_id, 'member_developments_data', true);

$testimonials_data = $testimonials_data ? json_decode($testimonials_data, true) : array();
$gratitudes_data = $gratitudes_data ? json_decode($gratitudes_data, true) : array();
$interviews_data = $interviews_data ? json_decode($interviews_data, true) : array();
$videos_data = $videos_data ? json_decode($videos_data, true) : array();
$reviews_data = $reviews_data ? json_decode($reviews_data, true) : array();
$developments_data = $developments_data ? json_decode($developments_data, true) : array();

// Категории материалов
$material_categories = array(
    'testimonials' => array('name' => 'Отзывы', 'icon' => '💬', 'data' => $testimonials_data),
    'gratitudes' => array('name' => 'Благодарности', 'icon' => '🏆', 'data' => $gratitudes_data),
    'interviews' => array('name' => 'Интервью', 'icon' => '🎤', 'data' => $interviews_data),
    'videos' => array('name' => 'Видео', 'icon' => '🎥', 'data' => $videos_data),
    'reviews' => array('name' => 'Рецензии', 'icon' => '📝', 'data' => $reviews_data),
    'developments' => array('name' => 'Разработки', 'icon' => '💾', 'data' => $developments_data),
);

$total_materials = count($testimonials_data) + count($gratitudes_data) + count($interviews_data) +
                   count($videos_data) + count($reviews_data) + count($developments_data);
?>

<!-- Quill.js CDN -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<!-- Materials Section -->
<section id="materials-section" class="section-content hidden">
    <div class="bg-white border-b border-gray-200 px-8 py-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Портфолио и достижения</h2>
                    <p class="text-sm text-gray-500 mt-1">Управляйте отзывами, благодарностями, видео и публикациями</p>
                </div>
                <div class="text-2xl font-bold" style="color: <?php echo $primary_color; ?>;">
                    <?php echo $total_materials; ?>
                    <span class="text-sm font-normal text-gray-500">материалов</span>
                </div>
            </div>
        </div>
    </div>

    <div class="p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Category Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="flex gap-2 p-4 overflow-x-auto">
                    <?php
                    $first = true;
                    foreach ($material_categories as $key => $category):
                        $count = count($category['data']);
                        $active = $first ? 'active' : '';
                        $first = false;
                    ?>
                    <button class="material-category-tab <?php echo $active; ?> px-4 py-2.5 rounded-lg font-medium text-sm transition-all whitespace-nowrap flex items-center gap-2"
                            data-category="<?php echo $key; ?>">
                        <span><?php echo $category['icon']; ?></span>
                        <span><?php echo esc_html($category['name']); ?></span>
                        <span class="badge px-2 py-0.5 rounded-full text-xs"><?php echo $count; ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Category Content -->
            <?php
            $first = true;
            foreach ($material_categories as $key => $category):
                $active = $first ? '' : 'hidden';
                $first = false;
            ?>
            <div class="material-category-content <?php echo $active; ?>" data-category="<?php echo $key; ?>">
                <!-- Add Material Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <?php echo $category['icon']; ?> Добавить <?php echo mb_strtolower($category['name']); ?>
                    </h3>

                    <!-- Type Selector -->
                    <div class="flex gap-2 mb-6">
                        <button type="button" class="material-type-btn active flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-all" data-type="text">
                            💬 Текст
                        </button>
                        <button type="button" class="material-type-btn flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-all" data-type="file">
                            📄 Файл
                        </button>
                        <button type="button" class="material-type-btn flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-all" data-type="link">
                            🔗 Ссылка
                        </button>
                        <button type="button" class="material-type-btn flex-1 px-4 py-2.5 rounded-lg font-medium text-sm transition-all" data-type="video">
                            🎥 Видео
                        </button>
                    </div>

                    <!-- Forms for each type -->
                    <form class="add-material-form" data-category="<?php echo $key; ?>">
                        <input type="hidden" name="material_type" value="text" class="material-type-input">

                        <div class="space-y-4">
                            <!-- Заголовок (общий) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Заголовок *</label>
                                <input type="text" name="title" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                            </div>

                            <!-- Поле для текста -->
                            <div class="material-field material-field-text">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Текст *</label>
                                <div class="quill-editor-wrapper">
                                    <div id="editor-<?php echo $key; ?>" class="quill-editor"></div>
                                </div>
                                <textarea name="content" class="quill-content-hidden" style="display: none;"></textarea>
                                <p class="text-xs text-gray-500 mt-2">✨ Используйте панель инструментов для форматирования</p>
                            </div>

                            <!-- Поле для файла -->
                            <div class="material-field material-field-file hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Файл *</label>
                                <input type="file" name="file" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                                <input type="hidden" name="file_id" class="file-id-input">
                                <div class="file-preview mt-2"></div>
                            </div>

                            <!-- Поле для ссылки -->
                            <div class="material-field material-field-link hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">URL *</label>
                                <input type="url" name="url" placeholder="https://example.com" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                            </div>

                            <!-- Поле для видео -->
                            <div class="material-field material-field-video hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">URL видео *</label>
                                <input type="url" name="url" placeholder="https://rutube.ru/video/... или https://vk.com/video..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                <p class="text-xs text-gray-500 mt-1">Поддерживаются: Rutube, VK Video, YouTube</p>
                            </div>

                            <!-- Общие поля -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Автор/Источник</label>
                                    <input type="text" name="author" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                                    <input type="date" name="date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                                <textarea name="description" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none"></textarea>
                            </div>

                            <button type="submit" class="w-full px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity" style="background-color: <?php echo $primary_color; ?>;">
                                <i class="fas fa-plus mr-2"></i>
                                Добавить материал
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Materials List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Существующие материалы (<?php echo count($category['data']); ?>)
                    </h3>

                    <?php if (empty($category['data'])): ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4"><?php echo $category['icon']; ?></div>
                            <p class="text-gray-500">Пока нет материалов в этой категории</p>
                            <p class="text-sm text-gray-400 mt-1">Используйте форму выше чтобы добавить</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4 materials-list" data-category="<?php echo $key; ?>">
                            <?php foreach ($category['data'] as $index => $item): ?>
                                <?php include plugin_dir_path(__DIR__) . 'templates/dashboard-material-item.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Material Modal -->
    <div id="edit-material-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Редактировать материал</h3>
                <button type="button" class="close-modal text-gray-400 hover:text-gray-600 text-2xl leading-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="edit-material-form" class="p-6">
                <input type="hidden" name="edit_category" id="edit_category">
                <input type="hidden" name="edit_index" id="edit_index">
                <input type="hidden" name="edit_material_type" id="edit_material_type">

                <div class="space-y-4">
                    <!-- Заголовок -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Заголовок *</label>
                        <input type="text" name="edit_title" id="edit_title" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                    </div>

                    <!-- Поле для текста -->
                    <div id="edit-field-text" class="edit-field">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Текст *</label>
                        <div class="quill-editor-wrapper">
                            <div id="editor-edit" class="quill-editor"></div>
                        </div>
                        <textarea name="edit_content" id="edit_content_hidden" style="display: none;"></textarea>
                    </div>

                    <!-- Поле для ссылки -->
                    <div id="edit-field-link" class="edit-field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL *</label>
                        <input type="url" name="edit_url" id="edit_url" placeholder="https://example.com" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                    </div>

                    <!-- Поле для видео -->
                    <div id="edit-field-video" class="edit-field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL видео *</label>
                        <input type="url" name="edit_video_url" id="edit_video_url" placeholder="https://rutube.ru/video/..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                        <p class="text-xs text-gray-500 mt-1">Поддерживаются: Rutube, VK Video, YouTube</p>
                    </div>

                    <!-- Поле для файла (показываем текущий файл, новый загружать нельзя в edit) -->
                    <div id="edit-field-file" class="edit-field" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Файл</label>
                        <div id="edit_current_file" class="text-sm text-gray-600 bg-gray-50 px-4 py-3 rounded-lg"></div>
                        <p class="text-xs text-gray-500 mt-1">Для изменения файла удалите материал и создайте новый</p>
                    </div>

                    <!-- Общие поля -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Автор/Источник</label>
                            <input type="text" name="edit_author" id="edit_author" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Дата</label>
                            <input type="date" name="edit_date" id="edit_date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                        <textarea name="edit_description" id="edit_description" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity" style="background-color: <?php echo $primary_color; ?>;">
                            <i class="fas fa-save mr-2"></i>
                            Сохранить изменения
                        </button>
                        <button type="button" class="close-modal px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                            Отмена
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    /* Category Tabs */
    .material-category-tab {
        background: #f3f4f6;
        color: #6b7280;
    }

    .material-category-tab:hover {
        background: #e5e7eb;
    }

    .material-category-tab.active {
        background: <?php echo $primary_color; ?>;
        color: white;
    }

    .material-category-tab.active .badge {
        background: rgba(255,255,255,0.2);
        color: white;
    }

    .material-category-tab:not(.active) .badge {
        background: #d1d5db;
        color: #6b7280;
    }

    /* Type Buttons */
    .material-type-btn {
        background: #f3f4f6;
        color: #6b7280;
    }

    .material-type-btn:hover {
        background: #e5e7eb;
    }

    .material-type-btn.active {
        background: <?php echo $primary_color; ?>;
        color: white;
    }

    /* Quill Editor - iOS Style */
    .quill-editor-wrapper {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .quill-editor {
        min-height: 200px;
        font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 15px;
        line-height: 1.6;
    }

    /* Quill Toolbar - iOS Style */
    .ql-toolbar.ql-snow {
        border: none;
        border-bottom: 1px solid #f3f4f6;
        background: #fafbfc;
        padding: 10px 12px;
        border-radius: 12px 12px 0 0;
    }

    .ql-toolbar button {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .ql-toolbar button:hover {
        background: rgba(0, 102, 204, 0.08);
    }

    .ql-toolbar button.ql-active {
        background: <?php echo $primary_color; ?> !important;
        color: white;
    }

    .ql-toolbar button.ql-active svg .ql-stroke {
        stroke: white !important;
    }

    .ql-toolbar button.ql-active svg .ql-fill {
        fill: white !important;
    }

    /* Quill Container - iOS Style */
    .ql-container.ql-snow {
        border: none;
        font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .ql-editor {
        padding: 16px 20px;
        font-size: 15px;
        line-height: 1.7;
        min-height: 200px;
    }

    .ql-editor.ql-blank::before {
        color: #9ca3af;
        font-style: normal;
        left: 20px;
    }

    /* Quill Content Styles - iOS Typography */
    .ql-editor p {
        margin-bottom: 0.75em;
    }

    .ql-editor strong {
        font-weight: 600;
    }

    .ql-editor h1 {
        font-size: 1.5em;
        font-weight: 600;
        margin: 0.5em 0;
    }

    .ql-editor h2 {
        font-size: 1.3em;
        font-weight: 600;
        margin: 0.5em 0;
    }

    .ql-editor ul, .ql-editor ol {
        padding-left: 1.5em;
    }

    .ql-editor li {
        margin-bottom: 0.3em;
    }

    .ql-editor blockquote {
        border-left: 3px solid <?php echo $primary_color; ?>;
        padding-left: 1em;
        margin-left: 0;
        font-style: italic;
        color: #6b7280;
    }

    .ql-editor a {
        color: <?php echo $primary_color; ?>;
        text-decoration: none;
    }

    .ql-editor a:hover {
        text-decoration: underline;
    }

    /* Hide default Quill tooltips */
    .ql-tooltip {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e5e7eb;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Store Quill instances
    var quillEditors = {};

    // Initialize Quill editors for each category
    <?php foreach ($material_categories as $key => $category): ?>
    var quill_<?php echo $key; ?> = new Quill('#editor-<?php echo $key; ?>', {
        theme: 'snow',
        placeholder: 'Начните писать...',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'link'],
                ['clean']
            ]
        }
    });
    quillEditors['<?php echo $key; ?>'] = quill_<?php echo $key; ?>;
    <?php endforeach; ?>

    // Initialize Quill editor for edit modal
    var quillEdit = new Quill('#editor-edit', {
        theme: 'snow',
        placeholder: 'Начните писать...',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'link'],
                ['clean']
            ]
        }
    });

    // Store all materials data for editing
    var materialsData = <?php echo json_encode($material_categories); ?>;

    // Tab switching
    $('.material-category-tab').on('click', function() {
        var category = $(this).data('category');

        $('.material-category-tab').removeClass('active');
        $(this).addClass('active');

        $('.material-category-content').addClass('hidden');
        $('.material-category-content[data-category="' + category + '"]').removeClass('hidden');
    });

    // Type switching
    $('.material-type-btn').on('click', function() {
        var $form = $(this).closest('.add-material-form');
        var type = $(this).data('type');

        // Update buttons
        $form.find('.material-type-btn').removeClass('active');
        $(this).addClass('active');

        // Update hidden input
        $form.find('.material-type-input').val(type);

        // Show/hide fields
        $form.find('.material-field').addClass('hidden');
        $form.find('.material-field-' + type).removeClass('hidden');
    });

    // Form submission
    $('.add-material-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var category = $form.data('category');

        // Get HTML from Quill editor and put it in hidden textarea
        var quillContent = quillEditors[category].root.innerHTML;
        $form.find('.quill-content-hidden').val(quillContent);

        var formData = new FormData(this);
        formData.append('action', 'add_portfolio_material');
        formData.append('nonce', memberDashboard.nonce);
        formData.append('category', category);

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Добавление...');
            },
            success: function(response) {
                if (response.success) {
                    // Success notification
                    var notification = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3">' +
                        '<i class="fas fa-check-circle text-xl"></i>' +
                        '<span>Материал успешно добавлен!</span>' +
                    '</div>');
                    $('body').append(notification);

                    setTimeout(function() {
                        notification.fadeOut(function() {
                            $(this).remove();
                            location.reload();
                        });
                    }, 1500);
                } else {
                    alert('Ошибка: ' + response.data.message);
                    $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus mr-2"></i>Добавить материал');
                }
            },
            error: function() {
                alert('Произошла ошибка при добавлении материала');
                $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus mr-2"></i>Добавить материал');
            }
        });
    });

    // Delete material
    $(document).on('click', '.delete-material-btn', function() {
        if (!confirm('Вы уверены что хотите удалить этот материал?')) return;

        var category = $(this).data('category');
        var index = $(this).data('index');
        var $item = $(this).closest('.material-item');

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_portfolio_material',
                nonce: memberDashboard.nonce,
                category: category,
                index: index
            },
            beforeSend: function() {
                $item.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    $item.slideUp(300, function() {
                        $(this).remove();
                    });

                    // Update counter in tab
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    alert('Ошибка: ' + response.data.message);
                    $item.css('opacity', '1');
                }
            },
            error: function() {
                alert('Произошла ошибка при удалении');
                $item.css('opacity', '1');
            }
        });
    });

    // Open edit modal
    $(document).on('click', '.edit-material-btn', function() {
        var category = $(this).data('category');
        var index = $(this).data('index');

        // Get material data
        var material = materialsData[category].data[index];
        if (!material) {
            alert('Материал не найден');
            return;
        }

        // Fill form fields
        $('#edit_category').val(category);
        $('#edit_index').val(index);
        $('#edit_material_type').val(material.type);
        $('#edit_title').val(material.title || '');
        $('#edit_author').val(material.author || '');
        $('#edit_date').val(material.date || '');
        $('#edit_description').val(material.description || '');

        // Show/hide fields based on type
        $('.edit-field').hide();

        if (material.type === 'text') {
            $('#edit-field-text').show();
            // Set Quill content
            quillEdit.root.innerHTML = material.content || '';
        } else if (material.type === 'link') {
            $('#edit-field-link').show();
            $('#edit_url').val(material.url || '');
        } else if (material.type === 'video') {
            $('#edit-field-video').show();
            $('#edit_video_url').val(material.url || '');
        } else if (material.type === 'file') {
            $('#edit-field-file').show();
            if (material.file_id && material.url) {
                var fileName = material.url.split('/').pop();
                $('#edit_current_file').html('<i class="fas fa-file mr-2"></i>' + fileName);
            }
        }

        // Show modal
        $('#edit-material-modal').removeClass('hidden').css('display', 'flex');
        $('body').css('overflow', 'hidden');
    });

    // Close edit modal
    $('.close-modal').on('click', function() {
        $('#edit-material-modal').addClass('hidden').css('display', 'none');
        $('body').css('overflow', 'auto');
    });

    // Close modal on background click
    $('#edit-material-modal').on('click', function(e) {
        if ($(e.target).is('#edit-material-modal')) {
            $(this).addClass('hidden').css('display', 'none');
            $('body').css('overflow', 'auto');
        }
    });

    // Submit edit form
    $('#edit-material-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);

        var category = $('#edit_category').val();
        var index = $('#edit_index').val();
        var type = $('#edit_material_type').val();

        // Prepare data
        var data = {
            action: 'edit_portfolio_material',
            nonce: memberDashboard.nonce,
            category: category,
            index: index,
            material_type: type,
            title: $('#edit_title').val(),
            author: $('#edit_author').val(),
            date: $('#edit_date').val(),
            description: $('#edit_description').val()
        };

        // Add type-specific data
        if (type === 'text') {
            data.content = quillEdit.root.innerHTML;
        } else if (type === 'link') {
            data.url = $('#edit_url').val();
        } else if (type === 'video') {
            data.url = $('#edit_video_url').val();
        }
        // For files, we keep the existing file_id and url (can't change files in edit)

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение...');
            },
            success: function(response) {
                if (response.success) {
                    // Success notification
                    var notification = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3">' +
                        '<i class="fas fa-check-circle text-xl"></i>' +
                        '<span>Изменения сохранены!</span>' +
                    '</div>');
                    $('body').append(notification);

                    setTimeout(function() {
                        notification.fadeOut(function() {
                            $(this).remove();
                            location.reload();
                        });
                    }, 1500);
                } else {
                    alert('Ошибка: ' + response.data.message);
                    $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Сохранить изменения');
                }
            },
            error: function() {
                alert('Произошла ошибка при сохранении');
                $form.find('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>Сохранить изменения');
            }
        });
    });
});
</script>
