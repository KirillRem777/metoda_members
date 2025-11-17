<?php
/**
 * Импорт участников из CSV (ФИНАЛЬНАЯ версия)
 *
 * ВАЖНО: После использования УДАЛИ этот файл!
 */

require_once('../../../wp-load.php');

// Проверка прав
if (!current_user_can('manage_options')) {
    die('Доступ запрещён');
}

$csv_file = __DIR__ . '/uchastniki_experts_FINAL_IMPORT.csv';

if (!file_exists($csv_file)) {
    die('CSV файл не найден: uchastniki_experts_FINAL_IMPORT.csv');
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Импорт участников</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745; }
.error { background: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545; }
.warning { background: #fff3cd; padding: 10px; margin: 5px 0; border-left: 4px solid #ffc107; }
.info { background: #d1ecf1; padding: 10px; margin: 5px 0; border-left: 4px solid #0c5460; }
h1 { color: #333; }
h2 { color: #555; margin-top: 30px; }
pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
</style></head><body>";

echo "<h1>📥 Импорт участников и экспертов</h1>";
echo "<hr>";

// Читаем CSV
$csv_data = array_map('str_getcsv', file($csv_file));
$headers = array_shift($csv_data); // Первая строка - заголовки

$imported = 0;
$updated = 0;
$errors = 0;

foreach ($csv_data as $row_index => $row) {
    $row_number = $row_index + 2; // +2 потому что пропустили заголовок и индексы с 0

    // Парсим строку
    $data = array();
    foreach ($headers as $i => $header) {
        $data[$header] = isset($row[$i]) ? trim($row[$i]) : '';
    }

    $fio = $data['ФИО'];

    if (empty($fio)) {
        echo "<div class='warning'>⚠️ Строка $row_number: пропущена (пустое ФИО)</div>";
        continue;
    }

    echo "<div class='info'><strong>$row_number. $fio</strong></div>";

    // Проверяем существует ли участник
    $existing = get_posts(array(
        'post_type' => 'members',
        'title' => $fio,
        'posts_per_page' => 1,
        'post_status' => 'any'
    ));

    $member_id = null;
    $is_update = false;

    if ($existing) {
        $member_id = $existing[0]->ID;
        $is_update = true;
        echo "<div class='info'>   → Найден существующий участник (ID: $member_id), обновляем...</div>";
    } else {
        // Создаём нового участника
        $member_id = wp_insert_post(array(
            'post_title' => $fio,
            'post_type' => 'members',
            'post_status' => 'publish',
            'post_content' => $data['О себе'] // Полная био в контент
        ));

        if (is_wp_error($member_id)) {
            echo "<div class='error'>   ❌ Ошибка создания: " . $member_id->get_error_message() . "</div>";
            $errors++;
            continue;
        }

        echo "<div class='success'>   ✓ Создан новый участник (ID: $member_id)</div>";
        $imported++;
    }

    // Сохраняем мета-поля
    update_post_meta($member_id, 'member_company', $data['Компания']);
    update_post_meta($member_id, 'member_position', $data['Должность']);
    update_post_meta($member_id, 'member_location', $data['Город']);
    update_post_meta($member_id, 'member_bio', $data['О себе']);

    echo "<div class='info'>   → Компания: " . esc_html($data['Компания']) . "</div>";
    echo "<div class='info'>   → Должность: " . esc_html($data['Должность']) . "</div>";

    // Специализация и стаж - объединённое поле, парсим его
    $spec_and_exp = $data['Специализация и стаж'];
    if (!empty($spec_and_exp)) {
        update_post_meta($member_id, 'member_specialization', $spec_and_exp);
        $spec_preview = mb_substr($spec_and_exp, 0, 100);
        echo "<div class='info'>   → Специализация: " . esc_html($spec_preview) . "...</div>";
    }

    // Сфера профессиональных интересов
    $interests = $data['Сфера профессиональных интересов'];
    if (!empty($interests)) {
        update_post_meta($member_id, 'member_interests', $interests);
        $interests_preview = mb_substr($interests, 0, 100);
        echo "<div class='info'>   → Интересы: " . esc_html($interests_preview) . "...</div>";
    }

    // Ожидания от сотрудничества
    $expectations = $data['Ожидания от сотрудничества'];
    if (!empty($expectations)) {
        update_post_meta($member_id, 'member_expectations', $expectations);
    }

    // Устанавливаем таксономию "Тип мембера"
    $member_type = $data['Тип мембера']; // Участник или Эксперт
    if (!empty($member_type)) {
        $term = get_term_by('name', $member_type, 'member_type');
        if (!$term) {
            // Создаём если не существует
            $term_data = wp_insert_term($member_type, 'member_type');
            if (!is_wp_error($term_data)) {
                wp_set_post_terms($member_id, array($term_data['term_id']), 'member_type');
                echo "<div class='info'>   → Тип: $member_type (создан и назначен)</div>";
            }
        } else {
            wp_set_post_terms($member_id, array($term->term_id), 'member_type');
            echo "<div class='info'>   → Тип: $member_type</div>";
        }
    }

    // Роли в ассоциации (может быть несколько, разделённых запятыми)
    $roles_str = $data['Роль в ассоциации'];
    if (!empty($roles_str)) {
        $roles = array_map('trim', explode(',', $roles_str));
        $role_ids = array();

        foreach ($roles as $role_name) {
            $term = get_term_by('name', $role_name, 'member_role');
            if (!$term) {
                $term_data = wp_insert_term($role_name, 'member_role');
                if (!is_wp_error($term_data)) {
                    $role_ids[] = $term_data['term_id'];
                }
            } else {
                $role_ids[] = $term->term_id;
            }
        }

        if (!empty($role_ids)) {
            wp_set_post_terms($member_id, $role_ids, 'member_role');
            echo "<div class='info'>   → Роли: " . implode(', ', $roles) . "</div>";
        }
    }

    // Город - таксономия member_location
    $city = $data['Город'];
    if (!empty($city)) {
        $term = get_term_by('name', $city, 'member_location');
        if (!$term) {
            $term_data = wp_insert_term($city, 'member_location');
            if (!is_wp_error($term_data)) {
                wp_set_post_terms($member_id, array($term_data['term_id']), 'member_location');
            }
        } else {
            wp_set_post_terms($member_id, array($term->term_id), 'member_location');
        }
    }

    if ($is_update) {
        $updated++;
    }
}

echo "<hr>";
echo "<h2>📊 Статистика импорта:</h2>";
echo "<ul>";
echo "<li style='color: green;'><strong>✅ Создано:</strong> $imported</li>";
echo "<li style='color: blue;'><strong>🔄 Обновлено:</strong> $updated</li>";
echo "<li style='color: red;'><strong>❌ Ошибок:</strong> $errors</li>";
echo "<li><strong>📝 Всего обработано:</strong> " . ($imported + $updated) . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>✅ Готово!</h2>";
echo "<p><strong>⚠️ УДАЛИ этот файл после использования!</strong></p>";
echo "<p><a href='" . admin_url('edit.php?post_type=members') . "'>Перейти к участникам →</a></p>";

echo "</body></html>";
