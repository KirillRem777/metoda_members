<?php
/**
 * Import Photos from photos/ folder
 *
 * Этот скрипт автоматически импортирует фотографии из папки photos/
 * и привязывает их к соответствующим участникам как Featured Image.
 *
 * Запусти ОДИН РАЗ: https://ваш-сайт.ru/wp-content/plugins/metoda_members/import-photos.php
 * УДАЛИ файл после использования!
 */

// Поднимаемся на 3 уровня вверх чтобы найти wp-load.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('❌ У тебя нет прав для выполнения этой операции!');
}

echo '<h1>📸 Импорт фотографий участников</h1>';
echo '<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); line-height: 1.6; }
    .success { color: #10b981; }
    .warning { color: #f59e0b; }
    .error { color: #ef4444; }
    .info { color: #3b82f6; }
</style>';
echo '<pre>';

$photos_dir = plugin_dir_path(__FILE__) . 'photos/';

echo "📁 Сканирование папки: {$photos_dir}\n\n";

if (!file_exists($photos_dir)) {
    echo "<span class='error'>❌ Папка photos/ не найдена!</span>\n";
    exit;
}

$files = glob($photos_dir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);

if (empty($files)) {
    echo "<span class='warning'>⚠️  Фотографии не найдены в папке photos/</span>\n";
    exit;
}

echo "Найдено фотографий: <span class='info'>" . count($files) . "</span>\n\n";
echo "═══════════════════════════════════════════════════════\n";
echo "🔍 ОБРАБОТКА ФОТОГРАФИЙ\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Функция для очистки имени участника из названия файла
function extract_member_name($filename) {
    $name = basename($filename);

    // Удаляем расширение
    $name = preg_replace('/\.(jpg|jpeg|png)$/i', '', $name);

    // Удаляем -2, -3 и т.д. в конце (дубликаты фото)
    $name = preg_replace('/-\d+$/', '', $name);

    // Удаляем всё после "Руководитель", "Директор" и т.д.
    $name = preg_replace('/(Руководитель|Директор|Менеджер|Специалист).*$/u', '', $name);

    return trim($name);
}

// Функция для загрузки фото как attachment
function upload_photo_as_attachment($file_path, $post_id) {
    $filename = basename($file_path);
    $upload_file = wp_upload_bits($filename, null, file_get_contents($file_path));

    if (!$upload_file['error']) {
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $post_id);

        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            return $attachment_id;
        }
    }

    return false;
}

$stats = array(
    'total' => count($files),
    'imported' => 0,
    'skipped' => 0,
    'not_found' => 0,
    'errors' => 0
);

foreach ($files as $file) {
    $member_name = extract_member_name($file);
    $filename = basename($file);

    echo "📄 Файл: <span class='info'>{$filename}</span>\n";
    echo "👤 Ищу участника: {$member_name}\n";

    // Ищем участника по имени
    $members = get_posts(array(
        'post_type' => 'members',
        'title' => $member_name,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));

    if (empty($members)) {
        echo "<span class='warning'>⚠️  Участник не найден: {$member_name}</span>\n\n";
        $stats['not_found']++;
        continue;
    }

    $member = $members[0];
    $member_id = $member->ID;

    echo "✓ Найден: {$member->post_title} (ID: {$member_id})\n";

    // Проверяем есть ли уже фото
    if (has_post_thumbnail($member_id)) {
        echo "<span class='warning'>⊘ Фото уже установлено, пропускаю...</span>\n\n";
        $stats['skipped']++;
        continue;
    }

    // Загружаем фото
    $attachment_id = upload_photo_as_attachment($file, $member_id);

    if ($attachment_id) {
        set_post_thumbnail($member_id, $attachment_id);
        echo "<span class='success'>✅ Фото успешно загружено!</span>\n\n";
        $stats['imported']++;
    } else {
        echo "<span class='error'>❌ Ошибка загрузки фото</span>\n\n";
        $stats['errors']++;
    }
}

echo "═══════════════════════════════════════════════════════\n";
echo "📊 СТАТИСТИКА\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Всего файлов:           {$stats['total']}\n";
echo "<span class='success'>✅ Импортировано:       {$stats['imported']}</span>\n";
echo "<span class='warning'>⊘ Пропущено (уже есть): {$stats['skipped']}</span>\n";
echo "<span class='warning'>⚠️  Участник не найден:  {$stats['not_found']}</span>\n";
echo "<span class='error'>❌ Ошибок:              {$stats['errors']}</span>\n\n";

if ($stats['imported'] > 0) {
    echo "<span class='success'>═══════════════════════════════════════════════════════\n";
    echo "✅ ИМПОРТ ЗАВЕРШЁН!\n";
    echo "═══════════════════════════════════════════════════════</span>\n\n";

    echo "Фотографии успешно загружены и привязаны к участникам!\n\n";

    echo "<span class='info'>📋 ЧТО ДАЛЬШЕ:</span>\n";
    echo "1. Открой страницу архива участников\n";
    echo "2. Нажми Ctrl+F5 (очистить кэш браузера)\n";
    echo "3. Увидишь фотографии участников! ✨\n\n";
}

if ($stats['not_found'] > 0) {
    echo "<span class='warning'>⚠️  ВАЖНО:</span>\n";
    echo "{$stats['not_found']} участников не найдено.\n";
    echo "Проверь что имена в файлах точно совпадают с именами участников в базе.\n\n";
}

echo "<span class='warning'>⚠️  НЕ ЗАБУДЬ:</span>\n";
echo "• УДАЛИ этот файл (import-photos.php) после использования!\n";

echo '</pre>';
