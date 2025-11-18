<?php
/**
 * Карточка материала
 * Отображает превью материала с возможностью открыть в модальном окне
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

// Генерируем уникальный ID для модального окна
$modal_id = 'material-modal-' . uniqid();

// Иконки для разных типов
$icons = array(
    'text' => '💬',
    'file' => '📄',
    'link' => '🔗',
    'video' => '🎥'
);
$icon = isset($icons[$type]) ? $icons[$type] : '📝';

// Превью текста (первые 150 символов)
$preview_text = '';
if ($content) {
    $preview_text = mb_substr(strip_tags($content), 0, 150);
    if (mb_strlen(strip_tags($content)) > 150) {
        $preview_text .= '...';
    }
}

// Для файлов
$file_url = '';
$file_name = '';
$file_size = '';
if ($file_id) {
    $file_url = wp_get_attachment_url($file_id);
    $file_name = basename($file_url);
    $file_path = get_attached_file($file_id);
    if (file_exists($file_path)) {
        $file_size = size_format(filesize($file_path));
    }
}

// Форматируем дату
$formatted_date = '';
if ($date) {
    $formatted_date = date('d.m.Y', strtotime($date));
}
?>

<div class="material-card bg-white rounded-lg border border-gray-200 p-5 hover:shadow-lg transition-all cursor-pointer"
     onclick="openMaterialModal('<?php echo $modal_id; ?>')">

    <div class="flex items-start justify-between mb-3">
        <div class="text-3xl"><?php echo $icon; ?></div>
        <?php if ($type === 'file'): ?>
        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded"><?php echo strtoupper(pathinfo($file_name, PATHINFO_EXTENSION)); ?></span>
        <?php elseif ($type === 'video'): ?>
        <span class="text-xs text-gray-500 bg-red-50 text-red-600 px-2 py-1 rounded">ВИДЕО</span>
        <?php elseif ($type === 'link'): ?>
        <span class="text-xs text-gray-500 bg-blue-50 text-blue-600 px-2 py-1 rounded">ССЫЛКА</span>
        <?php endif; ?>
    </div>

    <?php if ($title): ?>
    <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2"><?php echo esc_html($title); ?></h4>
    <?php endif; ?>

    <?php if ($preview_text): ?>
    <p class="text-sm text-gray-600 mb-3 line-clamp-3"><?php echo esc_html($preview_text); ?></p>
    <?php elseif ($description): ?>
    <p class="text-sm text-gray-600 mb-3 line-clamp-3"><?php echo esc_html($description); ?></p>
    <?php elseif ($type === 'file' && $file_name): ?>
    <p class="text-sm text-gray-600 mb-3">📎 <?php echo esc_html($file_name); ?></p>
    <?php endif; ?>

    <div class="flex items-center justify-between text-xs text-gray-500 border-t pt-3 mt-3">
        <div class="flex items-center gap-3">
            <?php if ($author): ?>
            <span class="flex items-center gap-1">
                <i class="fa-solid fa-user"></i>
                <?php echo esc_html($author); ?>
            </span>
            <?php endif; ?>
            <?php if ($formatted_date): ?>
            <span class="flex items-center gap-1">
                <i class="fa-solid fa-calendar"></i>
                <?php echo esc_html($formatted_date); ?>
            </span>
            <?php endif; ?>
        </div>
        <span class="text-blue-600 font-medium hover:underline">Подробнее →</span>
    </div>
</div>

<!-- Модальное окно для этого материала -->
<div id="<?php echo $modal_id; ?>" class="material-modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4"
     onclick="if(event.target === this) closeMaterialModal('<?php echo $modal_id; ?>')">
    <div class="bg-white rounded-xl max-w-4xl max-h-[90vh] overflow-y-auto w-full" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between z-10">
            <div class="flex items-center gap-3">
                <span class="text-3xl"><?php echo $icon; ?></span>
                <?php if ($title): ?>
                <h3 class="text-xl font-bold text-gray-900"><?php echo esc_html($title); ?></h3>
                <?php endif; ?>
            </div>
            <button onclick="closeMaterialModal('<?php echo $modal_id; ?>')" class="text-gray-400 hover:text-gray-600 text-2xl">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <!-- Modal Content -->
        <div class="p-6">
            <?php if ($type === 'text'): ?>
                <!-- Текстовый контент -->
                <div class="prose prose-gray max-w-none">
                    <?php echo wpautop($content); ?>
                </div>

            <?php elseif ($type === 'video'): ?>
                <!-- Видео -->
                <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden mb-4">
                    <?php
                    // Функция для встраивания видео
                    $embed_html = get_video_embed_html($url);
                    if ($embed_html) {
                        echo $embed_html;
                    } else {
                        ?>
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <i class="fa-solid fa-video text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600 mb-4">Видео доступно по ссылке:</p>
                                <a href="<?php echo esc_url($url); ?>" target="_blank" class="inline-block metoda-primary-bg text-white px-6 py-3 rounded-lg hover:opacity-90">
                                    <i class="fa-solid fa-external-link-alt mr-2"></i>
                                    Открыть видео
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php if ($description): ?>
                <p class="text-gray-700"><?php echo esc_html($description); ?></p>
                <?php endif; ?>

            <?php elseif ($type === 'file'): ?>
                <!-- Файл -->
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-4">
                        <i class="fa-solid fa-file text-4xl text-gray-400"></i>
                    </div>
                    <h4 class="text-lg font-semibold mb-2"><?php echo esc_html($file_name); ?></h4>
                    <?php if ($file_size): ?>
                    <p class="text-gray-500 mb-4"><?php echo esc_html($file_size); ?></p>
                    <?php endif; ?>
                    <?php if ($description): ?>
                    <p class="text-gray-700 mb-6"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($file_url); ?>" download class="inline-block metoda-primary-bg text-white px-6 py-3 rounded-lg hover:opacity-90">
                        <i class="fa-solid fa-download mr-2"></i>
                        Скачать файл
                    </a>
                </div>

            <?php elseif ($type === 'link'): ?>
                <!-- Ссылка -->
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-50 rounded-full mb-4">
                        <i class="fa-solid fa-link text-4xl text-blue-500"></i>
                    </div>
                    <?php if ($description): ?>
                    <p class="text-gray-700 mb-6 text-lg"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="inline-block metoda-primary-bg text-white px-6 py-3 rounded-lg hover:opacity-90">
                        <i class="fa-solid fa-external-link-alt mr-2"></i>
                        Перейти по ссылке
                    </a>
                    <p class="text-sm text-gray-500 mt-4 break-all"><?php echo esc_html($url); ?></p>
                </div>
            <?php endif; ?>

            <!-- Meta info -->
            <?php if ($author || $formatted_date): ?>
            <div class="border-t mt-6 pt-4 flex items-center gap-4 text-sm text-gray-600">
                <?php if ($author): ?>
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-user"></i>
                    <?php echo esc_html($author); ?>
                </span>
                <?php endif; ?>
                <?php if ($formatted_date): ?>
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-calendar"></i>
                    <?php echo esc_html($formatted_date); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Функция для встраивания видео (Rutube, VK Video, YouTube)
function get_video_embed_html($url) {
    if (empty($url)) {
        return false;
    }

    // Rutube
    if (preg_match('/rutube\.ru\/video\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
        return '<iframe width="100%" height="100%" src="https://rutube.ru/play/embed/' . esc_attr($video_id) . '" frameBorder="0" allow="clipboard-write; autoplay" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
    }

    // VK Video
    if (preg_match('/vk\.com\/video(-?\d+_\d+)/', $url, $matches)) {
        $video_id = $matches[1];
        return '<iframe src="https://vk.com/video_ext.php?oid=' . esc_attr($video_id) . '" width="100%" height="100%" allow="autoplay; encrypted-media; fullscreen; picture-in-picture;" frameborder="0" allowfullscreen></iframe>';
    }

    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
        return '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
    }

    return false;
}
?>
