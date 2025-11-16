<?php
/**
 * CSV Converter Script
 * Преобразует uchastniki_experts_corrected.csv в формат для импорта в WordPress
 */

// Проверка запуска из командной строки (CLI)
if (php_sapi_name() !== 'cli') {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>CLI Only</title><style>body{font-family:sans-serif;padding:50px;text-align:center;background:#f5f5f5;}h1{color:#dc3545;}</style></head><body><h1>❌ Этот скрипт можно запускать только из командной строки</h1><p>Используйте: <code>php convert_csv.php</code></p></body></html>');
}

$input_file = 'uchastniki_experts_final.csv';
$output_file = 'wordpress_members_import_FINAL.csv';

// Маппинг заголовков
$header_mapping = array(
    'ФИО' => 'post_title',
    'Компания' => 'member_company',
    'Должность' => 'member_position',
    'Город' => 'member_city',
    'Тип участника' => 'taxonomy_member_type',  // Эксперт или Участник
    'Роль в ассоциации' => 'taxonomy_member_role',
    'Специализация и стаж' => 'member_specialization_experience',
    'Сфера профессиональных интересов' => 'member_professional_interests',
    'Ожидания от сотрудничества' => 'member_expectations',
    'О себе' => 'member_bio'
);

if (!file_exists($input_file)) {
    die("❌ Файл $input_file не найден!\n");
}

echo "🔄 Начинаем конвертацию CSV...\n";

// Открываем файлы
$input = fopen($input_file, 'r');
$output = fopen($output_file, 'w');

if (!$input || !$output) {
    die("❌ Ошибка открытия файлов!\n");
}

// Читаем заголовки из входного файла
$input_headers = fgetcsv($input);
if (!$input_headers) {
    die("❌ Не удалось прочитать заголовки!\n");
}

// Убираем BOM если есть
$input_headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $input_headers[0]);
$input_headers = array_map('trim', $input_headers);

echo "📋 Исходные заголовки: " . implode(', ', $input_headers) . "\n";

// Создаем массив новых заголовков
$output_headers = array();
$header_indices = array();

foreach ($input_headers as $index => $header) {
    if (isset($header_mapping[$header])) {
        $new_header = $header_mapping[$header];
        $output_headers[] = $new_header;
        $header_indices[$new_header] = $index;
    } else {
        echo "⚠️ Неизвестный заголовок: $header\n";
    }
}

echo "✅ Новые заголовки: " . implode(', ', $output_headers) . "\n\n";

// Записываем заголовки в выходной файл
fputcsv($output, $output_headers);

// Обрабатываем строки
$row_count = 0;
while (($row = fgetcsv($input)) !== false) {
    // Пропускаем пустые строки
    if (empty(array_filter($row))) {
        continue;
    }

    $new_row = array();

    foreach ($output_headers as $new_header) {
        $index = $header_indices[$new_header];
        $value = isset($row[$index]) ? $row[$index] : '';
        $new_row[] = $value;
    }

    fputcsv($output, $new_row);
    $row_count++;

    if ($row_count % 10 == 0) {
        echo "⏳ Обработано строк: $row_count\n";
    }
}

fclose($input);
fclose($output);

echo "\n✅ Конвертация завершена!\n";
echo "📊 Всего обработано: $row_count записей\n";
echo "📁 Результат сохранен в: $output_file\n";
echo "\n💡 Теперь можно импортировать этот файл через админку WordPress:\n";
echo "   Участники → Импорт CSV → Загрузить файл $output_file\n";
