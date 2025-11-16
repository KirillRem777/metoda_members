<?php
/**
 * CLEAR ALL CACHES
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== ОЧИСТКА КЭШЕЙ ===\n\n";

// 1. Clear PHP OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ PHP OPcache очищен\n";
} else {
    echo "⚠️ OPcache не доступен\n";
}

// 2. Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

// 3. Clear WordPress object cache
wp_cache_flush();
echo "✅ WordPress object cache очищен\n";

// 4. Clear transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
echo "✅ Transients очищены\n";

// 5. Verify file content
echo "\n=== ПРОВЕРКА ФАЙЛА ===\n\n";

$file = __DIR__ . '/members-management-pro.php';
$lines = file($file);

// Check line 2363
$line_2363 = isset($lines[2362]) ? trim($lines[2362]) : '';
echo "Строка 2363: $line_2363\n";

if (strpos($line_2363, '// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ') !== false) {
    echo "✅ Файл ОБНОВЛЁН! Хук отключен.\n";
} else {
    echo "❌ Файл НЕ ОБНОВЛЁН! Строка 2363 все еще содержит старый код.\n";
    echo "\nВозможные причины:\n";
    echo "1. Файл загружен в неправильную папку\n";
    echo "2. Сервер кэширует файл на уровне веб-сервера (nginx cache)\n";
    echo "3. Нужен перезапуск PHP-FPM\n";
}

echo "\n=== ИНСТРУКЦИЯ ===\n\n";
echo "1. Убедись что файл загружен в: $file\n";
echo "2. Перезапусти PHP-FPM (если есть доступ к серверу)\n";
echo "3. Или обратись в поддержку хостинга для очистки кэша\n";
echo "4. Попробуй зайти в админку: https://metoda-rf.ru/wp-admin/\n";
