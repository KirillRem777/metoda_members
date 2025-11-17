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

// Маппинг английских имён на русские (из rename-photos.php)
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

// Читаем CSV
$csv_data = array_map('str_getcsv', file($csv_file));
$headers = array_shift($csv_data);

$imported = 0;
$not_found = 0;
$already_has_photo = 0;

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
        echo "<div class='warning'>⚠️ Участник не найден: $fio</div>";
        continue;
    }

    $member_id = $member[0]->ID;

    // Проверяем есть ли уже фото
    if (has_post_thumbnail($member_id)) {
        echo "<div class='info'>ℹ️ $fio — уже есть фото, пропускаем</div>";
        $already_has_photo++;
        continue;
    }

    // Ищем фото по маппингу
    $photo_found = false;

    foreach ($name_mapping as $english_name => $russian_name) {
        if ($russian_name === $fio) {
            // Ищем все фотографии с этим именем
            $pattern = $photos_dir . $english_name . '*.jpg';
            $photos = glob($pattern);

            if (empty($photos)) {
                // Пробуем также искать файлы с русским именем
                $pattern_ru = $photos_dir . $fio . '*.jpg';
                $photos = glob($pattern_ru);
            }

            if (!empty($photos)) {
                // Берём первую фотографию
                $photo_path = $photos[0];
                $photo_filename = basename($photo_path);

                // Импортируем фото в медиатеку
                $upload_file = wp_upload_bits($photo_filename, null, file_get_contents($photo_path));

                if ($upload_file['error']) {
                    echo "<div class='error'>❌ $fio — ошибка загрузки: {$upload_file['error']}</div>";
                    break;
                }

                $wp_filetype = wp_check_filetype($photo_filename, null);

                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => $fio,
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $member_id);

                if (!is_wp_error($attach_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($member_id, $attach_id);

                    echo "<div class='success'>✅ $fio — фото импортировано ($photo_filename)</div>";
                    $imported++;
                    $photo_found = true;
                }

                break;
            }
        }
    }

    if (!$photo_found) {
        // Пробуем искать напрямую по русскому имени
        $pattern_direct = $photos_dir . $fio . '*.jpg';
        $photos_direct = glob($pattern_direct);

        if (!empty($photos_direct)) {
            $photo_path = $photos_direct[0];
            $photo_filename = basename($photo_path);

            $upload_file = wp_upload_bits($photo_filename, null, file_get_contents($photo_path));

            if (!$upload_file['error']) {
                $wp_filetype = wp_check_filetype($photo_filename, null);

                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => $fio,
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attach_id = wp_insert_attachment($attachment, $upload_file['file'], $member_id);

                if (!is_wp_error($attach_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($member_id, $attach_id);

                    echo "<div class='success'>✅ $fio — фото импортировано ($photo_filename)</div>";
                    $imported++;
                    $photo_found = true;
                }
            }
        }
    }

    if (!$photo_found) {
        echo "<div class='warning'>⚠️ $fio — фото не найдено</div>";
        $not_found++;
    }
}

echo "<hr>";
echo "<h2>📊 Статистика:</h2>";
echo "<ul>";
echo "<li style='color: green;'><strong>✅ Импортировано:</strong> $imported</li>";
echo "<li style='color: blue;'><strong>ℹ️ Уже были фото:</strong> $already_has_photo</li>";
echo "<li style='color: orange;'><strong>⚠️ Не найдено:</strong> $not_found</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>✅ Готово!</h2>";
echo "<p><a href='" . admin_url('edit.php?post_type=members') . "'>Перейти к участникам →</a></p>";
echo "</body></html>";
