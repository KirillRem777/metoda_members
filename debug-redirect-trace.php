<?php
/**
 * ДИАГНОСТИКА РЕДИРЕКТОВ
 *
 * Загрузи этот файл в корень плагина и открой в браузере:
 * https://metoda-rf.ru/wp-content/plugins/metoda_members/debug-redirect-trace.php
 */

// Буферизация вывода чтобы поймать редиректы
ob_start();

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug Redirects</title></head><body>";
echo "<h1>🔍 Диагностика редиректов</h1>";

// Перехватываем wp_redirect
function debug_wp_redirect($location, $status = 302) {
    global $redirect_trace;

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $redirect_trace[] = array(
        'location' => $location,
        'status' => $status,
        'trace' => $backtrace
    );

    // НЕ делаем редирект, просто логируем
    return false;
}

// Загружаем WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

echo "<h2>Текущий пользователь:</h2>";
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $user->ID . "</li>";
    echo "<li><strong>Login:</strong> " . $user->user_login . "</li>";
    echo "<li><strong>Roles:</strong> " . implode(', ', $user->roles) . "</li>";
    echo "<li><strong>Administrator?</strong> " . (current_user_can('administrator') ? 'ДА' : 'НЕТ') . "</li>";
    echo "<li><strong>manage_options?</strong> " . (current_user_can('manage_options') ? 'ДА' : 'НЕТ') . "</li>";
    echo "</ul>";
} else {
    echo "<p>НЕ АВТОРИЗОВАН</p>";
}

echo "<h2>Активные хуки на редиректы:</h2>";

// Проверяем какие хуки зарегистрированы
global $wp_filter;

$hooks_to_check = array(
    'template_redirect',
    'admin_init',
    'login_redirect',
    'wp_redirect',
    'wp_loaded',
    'init'
);

foreach ($hooks_to_check as $hook) {
    if (isset($wp_filter[$hook])) {
        echo "<h3>Хук: $hook</h3>";
        echo "<pre>";

        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            echo "Priority $priority:\n";
            foreach ($callbacks as $callback) {
                if (is_array($callback['function'])) {
                    if (is_object($callback['function'][0])) {
                        echo "  - " . get_class($callback['function'][0]) . "->" . $callback['function'][1] . "\n";
                    } else {
                        echo "  - " . $callback['function'][0] . "::" . $callback['function'][1] . "\n";
                    }
                } else {
                    echo "  - " . $callback['function'] . "\n";
                }
            }
        }

        echo "</pre>";
    }
}

echo "<h2>Проверка файла members-management-pro.php:</h2>";
$plugin_file = __DIR__ . '/members-management-pro.php';
$content = file_get_contents($plugin_file);

// Проверяем версию
if (preg_match('/\* Version: (.+)/', $content, $matches)) {
    echo "<p><strong>Версия плагина:</strong> " . trim($matches[1]) . "</p>";
}

// Проверяем активные add_action/add_filter
echo "<h3>Активные хуки в плагине:</h3>";
preg_match_all('/^(?!\/\/)\s*(add_action|add_filter)\s*\([^)]+\)/m', $content, $matches);
if (!empty($matches[0])) {
    echo "<pre>";
    foreach ($matches[0] as $match) {
        if (strpos($match, 'admin_init') !== false ||
            strpos($match, 'template_redirect') !== false ||
            strpos($match, 'login_redirect') !== false) {
            echo htmlspecialchars($match) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>Не найдено активных хуков</p>";
}

// Проверяем загрузку классов
echo "<h3>Загрузка классов:</h3>";
if (strpos($content, 'if (!is_admin())') !== false) {
    echo "<p style='color: red;'>❌ НАЙДЕНА условная загрузка if (!is_admin())</p>";

    // Показываем контекст
    $lines = explode("\n", $content);
    foreach ($lines as $num => $line) {
        if (strpos($line, 'if (!is_admin())') !== false) {
            echo "<pre>Строка " . ($num + 1) . ": " . htmlspecialchars($line) . "</pre>";
        }
    }
} else {
    echo "<p style='color: green;'>✅ Классы загружаются без условия is_admin()</p>";
}

echo "<h2>Проверка .htaccess редиректов:</h2>";
$htaccess_file = '../../../.htaccess';
if (file_exists($htaccess_file)) {
    $htaccess = file_get_contents($htaccess_file);
    if (strpos($htaccess, 'member-dashboard') !== false) {
        echo "<p style='color: red;'>❌ НАЙДЕН редирект в .htaccess!</p>";
        echo "<pre>" . htmlspecialchars($htaccess) . "</pre>";
    } else {
        echo "<p style='color: green;'>✅ Редиректов на member-dashboard в .htaccess нет</p>";
    }
} else {
    echo "<p>.htaccess не найден</p>";
}

echo "</body></html>";

// Показываем буфер
ob_end_flush();
