<?php
/**
 * Исправление дефисов в именах файлов
 *
 * Было: Анучина Светлана Борисовна-2.jpg
 * Стало: Анучина Светлана Борисовна-2.jpg (но убираем лишний дефис перед номером)
 */

$photos_dir = __DIR__ . '/Photos/';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Исправление дефисов</title>";
echo "<style>
body { font-family: monospace; padding: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style></head><body>";

echo "<h1>🔧 Исправление дефисов в именах файлов</h1><hr>";

$files = scandir($photos_dir);
$fixed_count = 0;
$skipped_count = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $file_path = $photos_dir . $file;
    if (!is_file($file_path)) continue;

    // Проверяем есть ли паттерн "Имя-Цифра.расширение"
    // где перед дефисом не должно быть пробела
    if (preg_match('/^(.+)-(\d+)\.([^.]+)$/', $file, $matches)) {
        $name = $matches[1];
        $number = $matches[2];
        $ext = $matches[3];

        // Убираем пробел в конце имени если есть
        $name = rtrim($name);

        // Если имя уже нормальное (без пробела перед дефисом), пропускаем
        if ($name === $matches[1]) {
            continue;
        }

        // Формируем новое имя
        $new_filename = $name . '-' . $number . '.' . $ext;
        $new_file_path = $photos_dir . $new_filename;

        // Проверяем не существует ли уже
        if (file_exists($new_file_path)) {
            echo "<div class='error'>⚠️ ПРОПУЩЕНО: $file (целевой файл уже существует)</div>";
            $skipped_count++;
            continue;
        }

        // Переименовываем
        if (rename($file_path, $new_file_path)) {
            echo "<div class='success'>✓ $file → $new_filename</div>";
            $fixed_count++;
        } else {
            echo "<div class='error'>❌ ОШИБКА: $file</div>";
        }
    }
}

echo "<hr>";
echo "<h2>📊 Статистика:</h2>";
echo "<ul>";
echo "<li class='success'>✅ Исправлено: $fixed_count</li>";
echo "<li class='info'>⊘ Пропущено: $skipped_count</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Теперь запусти import-photos.php заново!</strong></p>";
echo "<p><em>После импорта УДАЛИ все скрипты (rename-photos.php, fix-dashes.php, diagnose-photos.php, import-photos.php)</em></p>";

echo "</body></html>";
