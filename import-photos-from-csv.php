<?php
/**
 * Автоматический импорт фотографий для участников из CSV
 *
 * ВАЖНО: После использования УДАЛИ этот файл!
 */

require_once('../../../wp-load.php');

// Проверка прав
if (!current_user_can('manage_options')) {
    die('Доступ запрещён');
}

$csv_file = __DIR__ . '/uchastniki_experts_FINAL_IMPORT.csv';
$photos_dir = __DIR__ . '/Photos/';

if (!file_exists($csv_file)) {
    die('CSV файл не найден');
}

if (!is_dir($photos_dir)) {
    die('Папка Photos не найдена');
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Импорт фотографий</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745; }
.error { background: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545; }
.warning { background: #fff3cd; padding: 10px; margin: 5px 0; border-left: 4px solid #ffc107; }
.info { background: #d1ecf1; padding: 10px; margin: 5px 0; border-left: 4px solid #0c5460; }
h1 { color: #333; }
</style></head><body>";

echo "<h1>📸 Импорт фотографий участников</h1><hr>";

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
    'volvatch' => 'Волвач Антон Станиславович',
    // Новые участники (добавлены для v3.2.2+)
    'berdnikova' => 'Бердникова Ирина Евгеньевна',
    'irina berdnikova' => 'Бердникова Ирина Евгеньевна',
    'golubitskaya' => 'Голубицкая Татьяна Анатольевна',
    'tatyana golubitskaya' => 'Голубицкая Татьяна Анатольевна',
    'ilina' => 'Ильина Людмила Николаевна',
    'lyudmila ilina' => 'Ильина Людмила Николаевна',
    'polovinko' => 'Половинко Владимир Семенович',
    'vladimir polovinko' => 'Половинко Владимир Семенович',
    'rukin' => 'Рукин Константин Николаевич',
    'konstantin rukin' => 'Рукин Константин Николаевич',
    'fakhretdinova' => 'Фахретдинова Айсылу Амировна',
    'aysylu fakhretdinova' => 'Фахретдинова Айсылу Амировна',
    'khametzyanov' => 'Хаметзянов Александр Владимирович',
    'alexander khametzyanov' => 'Хаметзянов Александр Владимирович'
);

// Создаём обратный маппинг (русское -> английское)
$reverse_mapping = array_flip($name_mapping);

// Читаем CSV
$csv_data = array_map('str_getcsv', file($csv_file));
$headers = array_shift($csv_data);

$imported = 0;
$not_found = 0;
$already_has_photo = 0;
$total_photos_imported = 0;

foreach ($csv_data as $row) {
    $data = array();
    foreach ($headers as $i => $header) {
        $data[$header] = isset($row[$i]) ? trim($row[$i]) : '';
    }

    $fio = $data['ФИО'];

    if (empty($fio)) {
        continue;
    }

    // Ищем участника в базе
    $member = get_posts(array(
        'post_type' => 'members',
        'title' => $fio,
        'posts_per_page' => 1,
        'post_status' => 'any'
    ));

    if (!$member) {
        echo "<div class='warning'>⚠️ Участник не найден в базе: $fio</div>";
        continue;
    }

    $member_id = $member[0]->ID;

    // Проверяем есть ли уже фото
    if (has_post_thumbnail($member_id)) {
        echo "<div class='info'>ℹ️ $fio — уже есть фото, пропускаем</div>";
        $already_has_photo++;
        continue;
    }

    // Ищем фотографии для этого участника
    $photos = array();

    // 1. ПРИОРИТЕТ: Точное совпадение русского имени (без дефиса и с дефисом)
    // "ФИО.jpg"
    $pattern_exact = $photos_dir . $fio . '.jpg';
    if (file_exists($pattern_exact)) {
        $photos[] = $pattern_exact;
    }

    // "ФИО-2.jpg", "ФИО-3.jpg" и т.д.
    $pattern_ru_dash = $photos_dir . $fio . '-*.jpg';
    $found_ru_dash = glob($pattern_ru_dash);
    if ($found_ru_dash) {
        $photos = array_merge($photos, $found_ru_dash);
    }

    // 2. Пробуем найти по английскому имени из маппинга
    if (isset($reverse_mapping[$fio])) {
        $english_name = $reverse_mapping[$fio];
        $pattern = $photos_dir . $english_name . '*.jpg';
        $found = glob($pattern);
        if ($found) {
            $photos = array_merge($photos, $found);
        }
    }

    // 3. Пробуем найти по старому формату (цифра в конце без дефиса)
    $pattern_ru = $photos_dir . $fio . '*.jpg';
    $found_ru = glob($pattern_ru);
    if ($found_ru) {
        $photos = array_merge($photos, $found_ru);
    }

    // 4. Пробуем найти по фамилии (первое слово из ФИО)
    if (empty($photos)) {
        $name_parts = explode(' ', $fio);
        if (count($name_parts) > 0) {
            // Ищем среди английских имён
            foreach ($reverse_mapping as $ru => $en) {
                if (stripos($ru, $name_parts[0]) === 0) {
                    $pattern = $photos_dir . $en . '*.jpg';
                    $found = glob($pattern);
                    if ($found) {
                        $photos = array_merge($photos, $found);
                        break;
                    }
                }
            }
        }
    }

    // Удаляем дубликаты
    $photos = array_unique($photos);

    if (empty($photos)) {
        echo "<div class='warning'>⚠️ $fio — фото не найдено</div>";
        $not_found++;
        continue;
    }

    // Сортируем фото по имени файла
    sort($photos);

    // Импортируем ПЕРВУЮ фотографию как featured image
    $first_photo = $photos[0];
    $photo_filename = basename($first_photo);

    $upload_file = wp_upload_bits($photo_filename, null, file_get_contents($first_photo));

    if ($upload_file['error']) {
        echo "<div class='error'>❌ $fio — ошибка загрузки: {$upload_file['error']}</div>";
        continue;
    }

    $wp_filetype = wp_check_filetype($photo_filename, null);

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => $fio,
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $member_id);

    if (is_wp_error($attach_id)) {
        echo "<div class='error'>❌ $fio — ошибка создания вложения</div>";
        continue;
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);
    set_post_thumbnail($member_id, $attach_id);

    echo "<div class='success'>✅ $fio — фото импортировано: $photo_filename</div>";

    // Если есть дополнительные фото, показываем их
    if (count($photos) > 1) {
        echo "<div class='info'>   → Найдено ещё " . (count($photos) - 1) . " фото: " . implode(', ', array_map('basename', array_slice($photos, 1))) . "</div>";
    }

    $imported++;
    $total_photos_imported += count($photos);
}

echo "<hr>";
echo "<h2>📊 Статистика:</h2>";
echo "<ul>";
echo "<li style='color: green;'><strong>✅ Участников с импортированным фото:</strong> $imported</li>";
echo "<li style='color: gray;'><strong>📸 Всего фото найдено:</strong> $total_photos_imported</li>";
echo "<li style='color: blue;'><strong>ℹ️ Уже были фото:</strong> $already_has_photo</li>";
echo "<li style='color: orange;'><strong>⚠️ Не найдено:</strong> $not_found</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>✅ Готово!</h2>";
echo "<p><strong>⚠️ УДАЛИ этот файл после использования!</strong></p>";
echo "<p><a href='" . admin_url('edit.php?post_type=members') . "'>Перейти к участникам →</a></p>";
echo "</body></html>";
