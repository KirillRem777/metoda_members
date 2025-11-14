<?php
/**
 * Очистка шаблонов плагина из темы
 *
 * Этот скрипт находит и удаляет старые шаблоны плагина из темы,
 * чтобы WordPress использовал новые шаблоны из плагина.
 *
 * Запусти ОДИН РАЗ: https://ваш-сайт.ru/wp-content/plugins/metoda_members/cleanup-theme-templates.php
 * УДАЛИ файл после использования!
 */

// Поднимаемся на 3 уровня вверх чтобы найти wp-load.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('❌ У тебя нет прав для выполнения этой операции!');
}

echo '<h1>🧹 Очистка старых шаблонов из темы</h1>';
echo '<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
    pre { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #10b981; }
    .warning { color: #f59e0b; }
    .error { color: #ef4444; }
    .info { color: #3b82f6; }
</style>';
echo '<pre>';

// Шаблоны плагина, которые НЕ должны быть в теме
$plugin_templates = [
    'single-members.php',
    'archive-members.php',
    'member-dashboard.php',
    'member-onboarding.php',
    'manager-panel.php',
    'member-registration.php',
];

$theme_dir = get_stylesheet_directory();
$backup_dir = $theme_dir . '/metoda-templates-backup-' . date('Y-m-d-H-i-s');

echo "📁 Проверка темы: " . basename($theme_dir) . "\n";
echo "📂 Путь: {$theme_dir}\n\n";

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 ПОИСК СТАРЫХ ШАБЛОНОВ В ТЕМЕ\n";
echo "═══════════════════════════════════════════════════════\n\n";

$found_templates = [];

foreach ($plugin_templates as $template) {
    $theme_template_path = $theme_dir . '/' . $template;

    if (file_exists($theme_template_path)) {
        $found_templates[] = $template;
        $size = filesize($theme_template_path);
        $date = date('Y-m-d H:i:s', filemtime($theme_template_path));

        echo "❌ НАЙДЕН: {$template}\n";
        echo "   Размер: " . number_format($size) . " байт\n";
        echo "   Дата:   {$date}\n\n";
    }
}

if (empty($found_templates)) {
    echo "<span class='success'>✅ ОТЛИЧНО!</span>\n\n";
    echo "В теме НЕТ старых шаблонов плагина.\n";
    echo "WordPress уже использует шаблоны из плагина.\n\n";
    echo "Можешь удалить этот файл (cleanup-theme-templates.php).\n";
    echo '</pre>';
    exit;
}

echo "═══════════════════════════════════════════════════════\n";
echo "🗑️  УДАЛЕНИЕ СТАРЫХ ШАБЛОНОВ\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Создаём папку для бэкапа
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    echo "📦 Создана папка для бэкапа:\n";
    echo "   {$backup_dir}\n\n";
}

$deleted_count = 0;
$backup_count = 0;

foreach ($found_templates as $template) {
    $theme_template_path = $theme_dir . '/' . $template;
    $backup_path = $backup_dir . '/' . $template;

    // Создаём бэкап
    if (copy($theme_template_path, $backup_path)) {
        $backup_count++;
        echo "💾 Бэкап создан: {$template}\n";
    }

    // Удаляем файл
    if (unlink($theme_template_path)) {
        $deleted_count++;
        echo "<span class='success'>✅ Удалён: {$template}</span>\n\n";
    } else {
        echo "<span class='error'>❌ ОШИБКА: Не удалось удалить {$template}</span>\n\n";
    }
}

echo "═══════════════════════════════════════════════════════\n";
echo "📊 СТАТИСТИКА\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "Найдено старых шаблонов: " . count($found_templates) . "\n";
echo "Создано бэкапов:         {$backup_count}\n";
echo "Удалено файлов:          {$deleted_count}\n\n";

if ($deleted_count === count($found_templates)) {
    echo "<span class='success'>═══════════════════════════════════════════════════════\n";
    echo "✅ УСПЕШНО ЗАВЕРШЕНО!\n";
    echo "═══════════════════════════════════════════════════════</span>\n\n";

    echo "Теперь WordPress будет использовать НОВЫЕ шаблоны из плагина!\n\n";

    echo "<span class='info'>📋 ЧТО ДАЛЬШЕ:</span>\n";
    echo "1. Открой любую страницу участника\n";
    echo "2. Нажми Ctrl+F5 (очистить кэш браузера)\n";
    echo "3. Увидишь новый дизайн с Tailwind CSS! ✨\n\n";

    echo "<span class='warning'>⚠️  ВАЖНО:</span>\n";
    echo "• Бэкап сохранён в: " . basename($backup_dir) . "\n";
    echo "• Можешь удалить папку с бэкапом если всё работает\n";
    echo "• УДАЛИ этот файл (cleanup-theme-templates.php)\n";
} else {
    echo "<span class='error'>═══════════════════════════════════════════════════════\n";
    echo "⚠️  ЧАСТИЧНО ВЫПОЛНЕНО\n";
    echo "═══════════════════════════════════════════════════════</span>\n\n";

    echo "Проверь права доступа к папке темы.\n";
}

echo '</pre>';
