<?php
/**
 * Диагностика фотографий - показывает какие файлы не импортировались
 */

require_once('../../../wp-load.php');

$photos_dir = __DIR__ . '/Photos/';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Диагностика фотографий</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.found { background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745; }
.notfound { background: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545; }
.english { background: #fff3cd; padding: 10px; margin: 5px 0; border-left: 4px solid #ffc107; }
h2 { color: #333; }
</style>";
echo "</head><body>";

echo "<h1>🔍 Диагностика фотографий</h1>";

// Получаем все участники из базы
$members = get_posts(array(
    'post_type' => 'members',
    'posts_per_page' => -1,
    'post_status' => 'publish'
));

$members_names = array();
foreach ($members as $member) {
    $members_names[] = $member->post_title;
}

echo "<h2>📋 Участники в базе (" . count($members_names) . "):</h2>";
echo "<div style='background: white; padding: 15px; margin: 10px 0; max-height: 200px; overflow-y: auto;'>";
foreach ($members_names as $name) {
    echo "• " . esc_html($name) . "<br>";
}
echo "</div>";

// Сканируем фотографии
$files = scandir($photos_dir);
$not_found = array();
$found = array();
$english = array();

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    if (!is_file($photos_dir . $file)) continue;

    $name_without_ext = pathinfo($file, PATHINFO_FILENAME);

    // Убираем номер и дефис в конце
    $base_name = preg_replace('/-?[0-9]+$/', '', $name_without_ext);
    $base_name = trim($base_name);

    // Проверяем есть ли английские буквы
    if (preg_match('/[a-zA-Z]/', $base_name)) {
        $english[] = $file . " (база: '" . $base_name . "')";
        continue;
    }

    // Ищем в базе
    $found_in_db = false;
    foreach ($members_names as $member_name) {
        if ($member_name === $base_name) {
            $found_in_db = true;
            $found[] = $file . " → " . $member_name;
            break;
        }
    }

    if (!$found_in_db) {
        $not_found[] = $file . " (ищу: '" . $base_name . "')";
    }
}

echo "<h2>❌ Не найдены в базе (" . count($not_found) . "):</h2>";
if (empty($not_found)) {
    echo "<p style='color: green;'>Все русские имена найдены!</p>";
} else {
    foreach ($not_found as $item) {
        echo "<div class='notfound'>" . esc_html($item) . "</div>";
    }
}

echo "<h2>⚠️  Английские имена (нужно переименовать) (" . count($english) . "):</h2>";
if (empty($english)) {
    echo "<p style='color: green;'>Все файлы переименованы!</p>";
} else {
    foreach ($english as $item) {
        echo "<div class='english'>" . esc_html($item) . "</div>";
    }
}

echo "<h2>✅ Найдены в базе (" . count($found) . "):</h2>";
echo "<div style='max-height: 300px; overflow-y: auto;'>";
foreach ($found as $item) {
    echo "<div class='found'>" . esc_html($item) . "</div>";
}
echo "</div>";

echo "<hr>";
echo "<h3>💡 Рекомендации:</h3>";
echo "<ul>";
if (!empty($english)) {
    echo "<li><strong>Добавь недостающие имена в rename-photos.php</strong> и запусти его заново</li>";
}
if (!empty($not_found)) {
    echo "<li><strong>Проверь правильность имён</strong> - они должны ТОЧНО совпадать с именами в базе</li>";
    echo "<li>Возможно нужно убрать лишние пробелы или исправить опечатки</li>";
}
echo "</ul>";

echo "</body></html>";
