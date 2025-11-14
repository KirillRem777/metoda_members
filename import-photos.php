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

// Проверяем авторизацию
if (!is_user_logged_in()) {
    echo '<h1>🔐 Требуется авторизация</h1>';
    echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f5f5f5; }</style>';
    echo '<div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    echo '<h2 style="color: #ef4444;">❌ Ты не авторизован</h2>';
    echo '<p>Для запуска этого скрипта нужно:</p>';
    echo '<ol style="line-height: 2;">';
    echo '<li>Открой новую вкладку</li>';
    echo '<li>Зайди в админку WordPress: <code>' . admin_url() . '</code></li>';
    echo '<li>Авторизуйся как администратор</li>';
    echo '<li>Вернись на эту страницу и обнови (F5)</li>';
    echo '</ol>';
    echo '<p style="margin-top: 20px;"><a href="' . wp_login_url($_SERVER['REQUEST_URI']) . '" style="background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">🔑 Войти</a></p>';
    echo '</div>';
    exit;
}

if (!current_user_can('manage_options')) {
    echo '<h1>🔐 Недостаточно прав</h1>';
    echo '<style>body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f5f5f5; }</style>';
    echo '<div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
    echo '<h2 style="color: #ef4444;">❌ У тебя нет прав администратора</h2>';
    echo '<p>Ты авторизован как: <strong>' . wp_get_current_user()->user_login . '</strong></p>';
    echo '<p>Этот скрипт могут запускать только администраторы сайта.</p>';
    echo '<p>Попроси владельца сайта дать тебе права администратора или запустить скрипт самостоятельно.</p>';
    echo '</div>';
    exit;
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

// Группируем файлы по участникам
$members_photos = array();
foreach ($files as $file) {
    $member_name = extract_member_name($file);
    if (!isset($members_photos[$member_name])) {
        $members_photos[$member_name] = array();
    }
    $members_photos[$member_name][] = $file;
}

$stats = array(
    'total_files' => count($files),
    'total_members' => count($members_photos),
    'imported' => 0,
    'photos_added' => 0,
    'skipped' => 0,
    'not_found' => 0,
    'errors' => 0
);

foreach ($members_photos as $member_name => $photos) {
    echo "═══════════════════════════════════════════════════════\n";
    echo "👤 Участник: <span class='info'>{$member_name}</span>\n";
    echo "📸 Найдено фотографий: " . count($photos) . "\n\n";

    // Ищем участника по имени
    $members = get_posts(array(
        'post_type' => 'members',
        'title' => $member_name,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    ));

    if (empty($members)) {
        echo "<span class='warning'>⚠️  Участник не найден в базе</span>\n\n";
        $stats['not_found']++;
        foreach ($photos as $photo) {
            echo "   ⊘ Пропущено: " . basename($photo) . "\n";
        }
        echo "\n";
        continue;
    }

    $member = $members[0];
    $member_id = $member->ID;

    echo "✓ Найден в базе (ID: {$member_id})\n\n";

    // Получаем текущую галерею
    $current_gallery = get_post_meta($member_id, 'member_gallery', true);
    $gallery_ids = !empty($current_gallery) ? explode(',', $current_gallery) : array();

    $member_imported = 0;
    $first_photo = true;

    foreach ($photos as $photo) {
        $filename = basename($photo);
        echo "   📄 Загружаю: {$filename}\n";

        // Загружаем фото
        $attachment_id = upload_photo_as_attachment($photo, $member_id);

        if ($attachment_id) {
            // Первую фотографию ставим как Featured Image (если еще нет)
            if ($first_photo && !has_post_thumbnail($member_id)) {
                set_post_thumbnail($member_id, $attachment_id);
                echo "      ✓ Установлено как главное фото\n";
                $first_photo = false;
            }

            // Добавляем в галерею
            $gallery_ids[] = $attachment_id;
            echo "      ✓ Добавлено в галерею\n";
            $member_imported++;
        } else {
            echo "      <span class='error'>✗ Ошибка загрузки</span>\n";
            $stats['errors']++;
        }
    }

    if ($member_imported > 0) {
        // Сохраняем галерею
        update_post_meta($member_id, 'member_gallery', implode(',', $gallery_ids));
        echo "\n<span class='success'>✅ Импортировано: {$member_imported} фото</span>\n";
        echo "   📊 Всего в галерее: " . count($gallery_ids) . " фото\n\n";
        $stats['imported']++;
        $stats['photos_added'] += $member_imported;
    }
}

echo "═══════════════════════════════════════════════════════\n";
echo "📊 СТАТИСТИКА\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Всего файлов:              {$stats['total_files']}\n";
echo "Всего участников:          {$stats['total_members']}\n";
echo "<span class='success'>✅ Участников обработано:  {$stats['imported']}</span>\n";
echo "<span class='success'>📸 Фото загружено:         {$stats['photos_added']}</span>\n";
echo "<span class='warning'>⚠️  Участников не найдено:  {$stats['not_found']}</span>\n";
echo "<span class='error'>❌ Ошибок загрузки:        {$stats['errors']}</span>\n\n";

if ($stats['imported'] > 0) {
    echo "<span class='success'>═══════════════════════════════════════════════════════\n";
    echo "✅ ИМПОРТ ЗАВЕРШЁН!\n";
    echo "═══════════════════════════════════════════════════════</span>\n\n";

    echo "Фотографии успешно загружены:\n";
    echo "• Первая фотография каждого участника = главное фото\n";
    echo "• ВСЕ фотографии добавлены в галерею/слайдер\n\n";

    echo "<span class='info'>📋 ЧТО ДАЛЬШЕ:</span>\n";
    echo "1. Открой страницу любого участника\n";
    echo "2. Нажми Ctrl+F5 (очистить кэш браузера)\n";
    echo "3. Увидишь слайдер с фотографиями (если у участника > 1 фото)! ✨\n\n";
}

if ($stats['not_found'] > 0) {
    echo "<span class='warning'>⚠️  ВАЖНО:</span>\n";
    echo "{$stats['not_found']} участников не найдено.\n";
    echo "Проверь что имена в файлах точно совпадают с именами участников в базе.\n\n";
}

echo "<span class='warning'>⚠️  НЕ ЗАБУДЬ:</span>\n";
echo "• УДАЛИ этот файл (import-photos.php) после использования!\n";

echo '</pre>';
