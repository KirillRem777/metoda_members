<?php
/**
 * Обновление шаблона single-members.php в теме
 *
 * Запусти этот файл ОДИН РАЗ чтобы обновить старый шаблон в теме на новый
 * https://ваш-сайт.ru/wp-content/plugins/metoda_members/update-theme-template.php
 *
 * УДАЛИ файл после использования!
 */

// Поднимаемся на 3 уровня вверх чтобы найти wp-load.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('У тебя нет прав для выполнения этой операции!');
}

echo '<h1>Обновление шаблона профиля участника</h1>';
echo '<pre>';

// Пути
$plugin_template = plugin_dir_path(__FILE__) . 'single-members.php';
$theme_dir = get_stylesheet_directory();
$theme_template = $theme_dir . '/single-members.php';

echo "Плагин: {$plugin_template}\n";
echo "Тема:   {$theme_template}\n\n";

// Проверяем существование файлов
if (!file_exists($plugin_template)) {
    die("❌ ОШИБКА: Файл шаблона не найден в плагине!\n");
}

$plugin_size = filesize($plugin_template);
$plugin_date = date('Y-m-d H:i:s', filemtime($plugin_template));

echo "Шаблон в плагине:\n";
echo "  Размер: " . number_format($plugin_size) . " байт\n";
echo "  Дата:   {$plugin_date}\n\n";

if (file_exists($theme_template)) {
    $theme_size = filesize($theme_template);
    $theme_date = date('Y-m-d H:i:s', filemtime($theme_template));

    echo "Шаблон в теме (СТАРЫЙ):\n";
    echo "  Размер: " . number_format($theme_size) . " байт\n";
    echo "  Дата:   {$theme_date}\n\n";

    // Создаём бэкап
    $backup_path = $theme_template . '.backup-' . date('Y-m-d-H-i-s');
    if (copy($theme_template, $backup_path)) {
        echo "✅ Создан бэкап старого файла:\n";
        echo "   {$backup_path}\n\n";
    }
} else {
    echo "ℹ️  Файл в теме НЕ существует (будет создан)\n\n";
}

// Копируем новый файл
if (copy($plugin_template, $theme_template)) {
    echo "✅ Шаблон успешно обновлён!\n\n";

    echo "Новый шаблон в теме:\n";
    echo "  Размер: " . number_format(filesize($theme_template)) . " байт\n";
    echo "  Дата:   " . date('Y-m-d H:i:s', filemtime($theme_template)) . "\n\n";

    echo "===========================================\n";
    echo "✅ ГОТОВО!\n";
    echo "===========================================\n\n";
    echo "Теперь:\n";
    echo "1. Открой страницу любого участника\n";
    echo "2. Нажми Ctrl+F5 (очистить кэш браузера)\n";
    echo "3. Увидишь новый дизайн!\n\n";
    echo "⚠️  УДАЛИ этот файл (update-theme-template.php) после использования!\n";
} else {
    echo "❌ ОШИБКА: Не удалось скопировать файл!\n";
    echo "Проверь права доступа к папке темы.\n";
}

echo '</pre>';
