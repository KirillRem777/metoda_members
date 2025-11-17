<?php
/**
 * Скрипт переименования фотографий из английских имён в русские
 *
 * ВАЖНО: Запускай ТОЛЬКО один раз, потом УДАЛИ этот файл!
 */

// Маппинг английских имён в русские ФИО
$name_mapping = array(
    'abramova viktoria' => 'Абрамова Виктория Викторовна',
    'alexey abolmasov' => 'Аболмасов Алексей Владимирович',
    'alexey novak' => 'Новак Алексей Иванович',
    'anuchina' => 'Анучина Светлана Борисовна',
    'bardina' => 'Бардина Ольга',
    'borovik' => 'Боровик Артём Сергеевич',
    'chernova galina' => 'Чернова Галина Александровна',
    'dolzhenko ruslan' => 'Долженко Руслан Алексеевич',
    'fedkina' => 'Федькина Ирина Владимировна',
    'kaidalov' => 'Кайдалов Лев Жоржевич',
    'kidyaeva' => 'Кидяева Галина Владимировна',
    'konovalova' => 'Коновалова Ольга Станиславовна',
    'krivovitsina' => 'Кривовицына Марина Михайловна',
    'maxim lebedev' => 'Лебедев Максим Андреевич',
    'letyaeva' => 'Летяева Ольга Валерьевна',
    'muminov' => 'Муминов Сухраб Файзуллаевич',
    'seletski' => 'Селецкий Эдуард Борисович',
    'sosnin' => 'Соснин Алексей Александрович',
    'stepan smirnov' => 'Смирнов Степан Алексеевич',
    'volvatch' => 'Вольвач Наталья Игоревна'
);

$photos_dir = __DIR__ . '/Photos/';

if (!file_exists($photos_dir)) {
    die("❌ Папка Photos не найдена!");
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Переименование фотографий</title>";
echo "<style>body { font-family: monospace; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
echo "</head><body>";

echo "<h1>🔄 Переименование фотографий</h1>";
echo "<hr>";

$renamed_count = 0;
$skipped_count = 0;
$error_count = 0;

// Получаем все файлы
$files = scandir($photos_dir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $file_path = $photos_dir . $file;
    if (!is_file($file_path)) continue;

    // Извлекаем имя без номера и расширения
    // Пример: "abramova viktoria1.jpg" → "abramova viktoria"
    $name_without_ext = pathinfo($file, PATHINFO_FILENAME);

    // Убираем номер в конце (1, 2, 3, и т.д.)
    $base_name = preg_replace('/[0-9]+$/', '', $name_without_ext);
    $base_name = trim($base_name);

    // Извлекаем номер фото
    preg_match('/([0-9]+)$/', $name_without_ext, $matches);
    $photo_number = isset($matches[1]) ? $matches[1] : '1';

    $ext = pathinfo($file, PATHINFO_EXTENSION);

    // Проверяем есть ли в маппинге
    $base_name_lower = mb_strtolower($base_name);

    if (isset($name_mapping[$base_name_lower])) {
        $russian_name = $name_mapping[$base_name_lower];

        // Формируем новое имя
        if ($photo_number === '1') {
            $new_filename = $russian_name . '.' . $ext;
        } else {
            $new_filename = $russian_name . '-' . $photo_number . '.' . $ext;
        }

        $new_file_path = $photos_dir . $new_filename;

        // Проверяем не существует ли уже такой файл
        if (file_exists($new_file_path)) {
            echo "<div class='error'>⚠️  ПРОПУЩЕНО: $file → $new_filename (файл уже существует)</div>";
            $skipped_count++;
            continue;
        }

        // Переименовываем
        if (rename($file_path, $new_file_path)) {
            echo "<div class='success'>✓ $file → $new_filename</div>";
            $renamed_count++;
        } else {
            echo "<div class='error'>❌ ОШИБКА: не удалось переименовать $file</div>";
            $error_count++;
        }
    } else {
        // Пропускаем файлы которые уже с русскими именами
        // или для которых нет маппинга
        if (preg_match('/[а-яА-ЯёЁ]/u', $file)) {
            // Уже русское имя - пропускаем
            continue;
        } else {
            echo "<div class='info'>⊘ ПРОПУЩЕНО: $file (не найден маппинг для '$base_name_lower')</div>";
            $skipped_count++;
        }
    }
}

echo "<hr>";
echo "<h2>📊 Статистика:</h2>";
echo "<ul>";
echo "<li class='success'>✅ Переименовано: $renamed_count</li>";
echo "<li class='info'>⊘ Пропущено: $skipped_count</li>";
echo "<li class='error'>❌ Ошибок: $error_count</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>🎯 Что дальше:</h3>";
echo "<ol>";
echo "<li>Запусти импорт фотографий заново (import-photos.php)</li>";
echo "<li>Теперь должно загрузиться гораздо больше фото!</li>";
echo "<li><strong>УДАЛИ этот файл (rename-photos.php) после использования!</strong></li>";
echo "</ol>";

echo "</body></html>";
