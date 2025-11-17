<?php
/**
 * Исправление имён файлов фотографий
 *
 * Было: Анучина Светлана Борисовна-2.jpg
 * Стало: Анучина Светлана Борисовна2.jpg (убираем дефис)
 */

$photos_dir = __DIR__ . '/Photos/';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Исправление имён фотографий</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { background: #d4edda; padding: 5px; margin: 2px 0; }
.error { background: #f8d7da; padding: 5px; margin: 2px 0; }
</style></head><body>";

echo "<h1>🔧 Исправление имён фотографий</h1><hr>";

$files = scandir($photos_dir);
$fixed_count = 0;
$skipped_count = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $file_path = $photos_dir . $file;
    if (!is_file($file_path)) continue;

    // Ищем паттерн: "Имя-Цифра.расширение"
    if (preg_match('/^(.+)-(\d+)\.([^.]+)$/', $file, $matches)) {
        $name = trim($matches[1]);
        $number = $matches[2];
        $ext = $matches[3];

        // Формируем новое имя БЕЗ дефиса
        $new_filename = $name . $number . '.' . $ext;
        $new_file_path = $photos_dir . $new_filename;

        // Проверяем не существует ли уже
        if (file_exists($new_file_path)) {
            echo "<div class='error'>⚠️ ПРОПУЩЕНО: $file (файл уже существует)</div>";
            $skipped_count++;
            continue;
        }

        // Переименовываем
        if (rename($file_path, $new_file_path)) {
            echo "<div class='success'>✓ $file → $new_filename</div>";
            $fixed_count++;
        } else {
            echo "<div class='error'>❌ ОШИБКА: не удалось переименовать $file</div>";
        }
    }
}

echo "<hr>";
echo "<h2>📊 Статистика:</h2>";
echo "<ul>";
echo "<li style='color: green;'>✅ Исправлено: $fixed_count файлов</li>";
echo "<li style='color: blue;'>⊘ Пропущено: $skipped_count файлов</li>";
echo "</ul>";

if ($fixed_count > 0) {
    echo "<hr>";
    echo "<h3>✅ Готово!</h3>";
    echo "<p><strong>Теперь запусти import-photos.php заново</strong> - должно загрузиться больше фото!</p>";
}

echo "<hr>";
echo "<p><em>⚠️ После импорта УДАЛИ все скрипты (.php файлы) из папки плагина!</em></p>";

echo "</body></html>";
