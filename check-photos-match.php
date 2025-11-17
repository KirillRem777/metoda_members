<?php
/**
 * Проверка наличия фотографий для участников из CSV
 */

$csv_file = __DIR__ . '/uchastniki_experts_FINAL_IMPORT.csv';
$photos_dir = __DIR__ . '/Photos/';

if (!file_exists($csv_file)) {
    die('CSV файл не найден');
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Проверка фотографий</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.found { background: #d4edda; padding: 5px; margin: 2px 0; border-left: 4px solid #28a745; }
.not-found { background: #f8d7da; padding: 5px; margin: 2px 0; border-left: 4px solid #dc3545; }
h1 { color: #333; }
h2 { color: #555; margin-top: 20px; }
</style></head><body>";

echo "<h1>📸 Проверка фотографий для участников</h1><hr>";

// Маппинг английских имён на русские
$name_mapping = array(
    'abramova viktoria' => 'Абрамова Виктория Викторовна',
    'alexey abolmasov' => 'Аболмасов Алексей Владимирович',
    'alexey novak' => 'Новак Алексей Иванович',
    'anuchina' => 'Анучина Светлана Борисовна',
    'bardina' => 'Бардина Татьяна Викторовна',
    'borovik' => 'Боровик Артём Сергеевич',
    'chernova galina' => 'Чернова Галина Николаевна',
    'dolzhenko ruslan' => 'Долженко Руслан Владимирович',
    'fedkina' => 'Федькина Наталья Владимировна',
    'kaidalov' => 'Кайдалов Василий Олегович',
    'kidyaeva' => 'Кидяева Валентина Валерьевна',
    'konovalova' => 'Коновалова Елена Александровна',
    'krivovitsina' => 'Кривовицина Анна Викторовна',
    'letyaeva' => 'Летяева Ольга Валерьевна',
    'maxim lebedev' => 'Лебедев Максим Андреевич',
    'muminov' => 'Муминов Артём Ринатович',
    'seletski' => 'Селецкий Эдуард Борисович',
    'sosnin' => 'Соснин Владимир Николаевич',
    'stepan smirnov' => 'Смирнов Степан Евгеньевич',
    'volvatch' => 'Волвач Антон Станиславович'
);

$reverse_mapping = array_flip($name_mapping);

// Читаем CSV
$csv_data = array_map('str_getcsv', file($csv_file));
$headers = array_shift($csv_data);

$found = 0;
$not_found = 0;
$maybe_found = 0;

foreach ($csv_data as $row) {
    $data = array();
    foreach ($headers as $i => $header) {
        $data[$header] = isset($row[$i]) ? trim($row[$i]) : '';
    }

    $fio = $data['ФИО'];

    if (empty($fio)) {
        continue;
    }

    // Ищем фотографии
    $photos = array();

    // 1. Точное совпадение через маппинг
    if (isset($reverse_mapping[$fio])) {
        $english_name = $reverse_mapping[$fio];
        $pattern = $photos_dir . $english_name . '*.jpg';
        $found_photos = glob($pattern);
        if ($found_photos) {
            $photos = $found_photos;
            echo "<div class='found'>✅ $fio — " . count($found_photos) . " фото: " . implode(', ', array_map('basename', $found_photos)) . "</div>";
            $found++;
            continue;
        }
    }

    // 2. Поиск по русскому имени напрямую
    $pattern_ru = $photos_dir . $fio . '*.jpg';
    $found_ru = glob($pattern_ru);
    if ($found_ru) {
        $photos = $found_ru;
        echo "<div class='found'>✅ $fio — " . count($found_ru) . " фото: " . implode(', ', array_map('basename', $found_ru)) . "</div>";
        $found++;
        continue;
    }

    // 3. Поиск по фамилии (возможно другой человек с той же фамилией)
    $name_parts = explode(' ', $fio);
    if (count($name_parts) > 0) {
        $lastname = $name_parts[0];

        $maybe_photos = array();
        foreach ($reverse_mapping as $ru => $en) {
            if (stripos($ru, $lastname) === 0) {
                $pattern = $photos_dir . $en . '*.jpg';
                $found_maybe = glob($pattern);
                if ($found_maybe) {
                    $maybe_photos = array_merge($maybe_photos, $found_maybe);
                }
            }
        }

        if (!empty($maybe_photos)) {
            echo "<div class='not-found' style='background: #fff3cd; border-color: #ffc107;'>⚠️ $fio — НЕТ точного совпадения, но есть фото с фамилией '$lastname': " . implode(', ', array_map('basename', $maybe_photos)) . " (возможно ДРУГОЙ человек!)</div>";
            $maybe_found++;
            continue;
        }
    }

    // Совсем не найдено
    echo "<div class='not-found'>❌ $fio — фото НЕ НАЙДЕНО</div>";
    $not_found++;
}

echo "<hr>";
echo "<h2>📊 Итого:</h2>";
echo "<ul>";
echo "<li style='color: green;'><strong>✅ Найдено фото:</strong> $found</li>";
echo "<li style='color: orange;'><strong>⚠️ Возможно есть (другой человек):</strong> $maybe_found</li>";
echo "<li style='color: red;'><strong>❌ Нет фото:</strong> $not_found</li>";
echo "<li><strong>📝 Всего участников:</strong> " . ($found + $maybe_found + $not_found) . "</li>";
echo "</ul>";

echo "</body></html>";
