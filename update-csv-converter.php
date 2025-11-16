<?php
/**
 * CSV Converter Update Script
 *
 * Обновляет convert_csv.php для работы с новым файлом uchastniki_experts_final.csv
 */

if (php_sapi_name() !== 'cli') {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>CLI Only</title><style>body{font-family:sans-serif;padding:50px;text-align:center;background:#f5f5f5;}h1{color:#dc3545;}</style></head><body><h1>❌ Этот скрипт можно запускать только из командной строки</h1><p>Используйте: <code>php update-csv-converter.php</code></p></body></html>');
}

echo "🔄 Обновление конвертера CSV...\n\n";

$converter_file = __DIR__ . '/convert_csv.php';

if (!file_exists($converter_file)) {
    die("❌ Файл convert_csv.php не найден!\n");
}

// Читаем текущий файл
$content = file_get_contents($converter_file);

// Обновляем имя входного файла
$content = str_replace(
    "\$input_file = 'uchastniki_experts_corrected.csv';",
    "\$input_file = 'uchastniki_experts_final.csv';",
    $content
);

// Сохраняем
file_put_contents($converter_file, $content);

echo "✅ Конвертер обновлен!\n";
echo "📁 Входной файл: uchastniki_experts_final.csv\n";
echo "📁 Выходной файл: wordpress_members_import_FINAL.csv\n\n";

echo "💡 Следующие шаги:\n";
echo "1. Положите файл uchastniki_experts_final.csv в корень плагина\n";
echo "2. Запустите: php convert_csv.php\n";
echo "3. Импортируйте wordpress_members_import_FINAL.csv через админку\n";
