<?php
/**
 * CHECK WHAT'S ACTUALLY LOADED
 * Проверяет какие хуки реально зарегистрированы на ЖИВОМ сайте
 */
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "=== ПРОВЕРКА ЗАРЕГИСТРИРОВАННЫХ ХУКОВ ===\n\n";

global $wp_filter;

$hooks_to_check = array(
    'template_redirect' => 'Редирект на фронтенде',
    'admin_init' => 'Редирект в админке',
    'login_redirect' => 'Редирект после логина',
    'logout_redirect' => 'Редирект после выхода',
);

foreach ($hooks_to_check as $hook => $desc) {
    echo "[$hook] - $desc\n";
    echo str_repeat('-', 60) . "\n";

    if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks)) {
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $id => $callback) {
                $callback_name = 'unknown';

                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    $callback_name = $class . '::' . $method;
                } elseif (is_string($callback['function'])) {
                    $callback_name = $callback['function'];
                }

                // Подсвечиваем наши хуки
                $is_our_plugin = (stripos($callback_name, 'member') !== false || stripos($callback_name, 'metoda') !== false);
                $prefix = $is_our_plugin ? '❌ ' : '   ';

                echo $prefix . "[Приоритет $priority] $callback_name\n";
            }
        }
    } else {
        echo "✅ Хуки не зарегистрированы\n";
    }
    echo "\n";
}

echo "\n=== ПРОВЕРКА ФАЙЛОВ ===\n\n";

$files_to_check = array(
    'members-management-pro.php' => array(
        'line' => 2274,
        'should_contain' => '// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_filter(\'login_redirect\'',
        'desc' => 'login_redirect должен быть закомментирован'
    ),
    'members-management-pro.php' => array(
        'line' => 2282,
        'should_contain' => '// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_filter(\'logout_redirect\'',
        'desc' => 'logout_redirect должен быть закомментирован'
    ),
    'members-management-pro.php' => array(
        'line' => 2363,
        'should_contain' => '// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_action(\'admin_init\'',
        'desc' => 'admin_init должен быть закомментирован'
    ),
    'includes/class-member-onboarding.php' => array(
        'line' => 28,
        'should_contain' => '// ВРЕМЕННО ОТКЛЮЧЕНО: add_action(\'template_redirect\'',
        'desc' => 'template_redirect должен быть закомментирован'
    ),
);

foreach ($files_to_check as $file => $check) {
    $filepath = __DIR__ . '/' . $file;

    if (file_exists($filepath)) {
        $lines = file($filepath);
        $line_content = isset($lines[$check['line'] - 1]) ? trim($lines[$check['line'] - 1]) : '';

        $matches = (strpos($line_content, $check['should_contain']) !== false);

        echo ($matches ? '✅' : '❌') . " $file (строка {$check['line']})\n";
        echo "   Ожидаем: {$check['desc']}\n";
        echo "   Реально: $line_content\n\n";
    } else {
        echo "❌ Файл не найден: $file\n\n";
    }
}

echo "\n=== ТЕКУЩИЙ ПОЛЬЗОВАТЕЛЬ ===\n\n";

if (is_user_logged_in()) {
    $user = wp_get_current_user();
    echo "User ID: {$user->ID}\n";
    echo "Логин: {$user->user_login}\n";
    echo "Роли: " . implode(', ', $user->roles) . "\n";
    echo "Capabilities: manage_options = " . (current_user_can('manage_options') ? 'YES' : 'NO') . "\n";
} else {
    echo "НЕ АВТОРИЗОВАН\n";
}

echo "\n=== ВЫВОД ===\n\n";

$has_redirects = false;
foreach (array('template_redirect', 'admin_init', 'login_redirect', 'logout_redirect') as $hook) {
    if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks)) {
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $callback_name = '';
                if (is_array($callback['function'])) {
                    $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                    $method = $callback['function'][1];
                    $callback_name = $class . '::' . $method;
                } elseif (is_string($callback['function'])) {
                    $callback_name = $callback['function'];
                }

                if (stripos($callback_name, 'member') !== false || stripos($callback_name, 'metoda') !== false) {
                    $has_redirects = true;
                    echo "❌ НАЙДЕН АКТИВНЫЙ ХУК: [$hook] $callback_name\n";
                }
            }
        }
    }
}

if (!$has_redirects) {
    echo "✅ Все редиректы отключены! Проблема в чём-то другом.\n";
} else {
    echo "\n⚠️ ПРОБЛЕМА: Файлы на сервере НЕ ОБНОВИЛИСЬ!\n";
    echo "Замени файлы плагина вручную!\n";
}
