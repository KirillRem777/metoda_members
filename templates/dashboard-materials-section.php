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
                                <div id="editor-<?php echo $key; ?>-wrapper"></div>
                                <textarea name="content" rows="8" class="wysiwyg-editor w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none"></textarea>
                                <p class="text-xs text-gray-500 mt-1">Используйте редактор для форматирования текста</p>
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
</section>

<style>
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
</style>

<script>
jQuery(document).ready(function($) {
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
            success: function(response) {
                if (response.success) {
                    alert('Материал успешно добавлен!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data.message);
                }
            }
        });
    });

    // Delete material
    $(document).on('click', '.delete-material-btn', function() {
        if (!confirm('Удалить этот материал?')) return;

        var category = $(this).data('category');
        var index = $(this).data('index');

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_portfolio_material',
                nonce: memberDashboard.nonce,
                category: category,
                index: index
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data.message);
                }
            }
        });
    });
});
</script>
