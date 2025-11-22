<?php
/**
 * Dashboard Material Item
 * Отображение одного материала в списке (в личном кабинете)
 */

if (!defined('ABSPATH')) exit;

$type = isset($item['type']) ? $item['type'] : 'text';
$title = isset($item['title']) ? $item['title'] : '';
$content = isset($item['content']) ? $item['content'] : '';
$url = isset($item['url']) ? $item['url'] : '';
$file_id = isset($item['file_id']) ? intval($item['file_id']) : 0;
$author = isset($item['author']) ? $item['author'] : '';
$date = isset($item['date']) ? $item['date'] : '';
$description = isset($item['description']) ? $item['description'] : '';

$type_icons = array(
    'text' => '💬',
    'file' => '📄',
    'link' => '🔗',
    'video' => '🎥'
);
$icon = isset($type_icons[$type]) ? $type_icons[$type] : '📝';

$type_labels = array(
    'text' => 'Текст',
    'file' => 'Файл',
    'link' => 'Ссылка',
    'video' => 'Видео'
);
$type_label = isset($type_labels[$type]) ? $type_labels[$type] : 'Материал';

// Превью контента
$preview = '';
if ($type === 'text' && $content) {
    $preview = mb_substr(strip_tags($content), 0, 120) . '...';
} elseif ($type === 'file' && $file_id) {
    $file_url = wp_get_attachment_url($file_id);
    $preview = basename($file_url);
} elseif ($url) {
    $preview = $url;
}

$formatted_date = $date ? date('d.m.Y', strtotime($date)) : '';
?>

<div class="material-item p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-all" data-index="<?php echo $index; ?>">
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-2xl">
            <?php echo $icon; ?>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4 mb-2">
                <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 mb-1"><?php echo esc_html($title); ?></h4>
                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"><?php echo $type_label; ?></span>
                </div>

                <div class="flex gap-2">
                    <button type="button" class="edit-material-btn text-blue-600 hover:text-blue-700 p-2" data-category="<?php echo $key; ?>" data-index="<?php echo $index; ?>" title="Редактировать">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="delete-material-btn text-red-600 hover:text-red-700 p-2" data-category="<?php echo $key; ?>" data-index="<?php echo $index; ?>" title="Удалить">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <?php if ($preview): ?>
            <p class="text-sm text-gray-600 mb-2 line-clamp-2"><?php echo esc_html($preview); ?></p>
            <?php endif; ?>

            <div class="flex items-center gap-4 text-xs text-gray-500">
                <?php if ($author): ?>
                <span class="flex items-center gap-1">
                    <i class="fas fa-user"></i>
                    <?php echo esc_html($author); ?>
                </span>
                <?php endif; ?>

                <?php if ($formatted_date): ?>
                <span class="flex items-center gap-1">
                    <i class="fas fa-calendar"></i>
                    <?php echo esc_html($formatted_date); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
