<?php
/**
 * Plugin Name: EMERGENCY - Stop All Redirects
 * Description: БЛОКИРУЕТ ВСЕ редиректы и показывает кто пытается редиректить
 * Version: 1.0
 * Author: Debug Tool
 */

// Этот файл должен быть в wp-content/mu-plugins/EMERGENCY-STOP-REDIRECTS.php

// Лог всех попыток редиректа
global $redirect_attempts;
$redirect_attempts = array();

// Перехватываем wp_redirect ОЧЕНЬ рано
add_filter('wp_redirect', function($location, $status) {
    global $redirect_attempts;

    // Логируем КТО пытается редиректить
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

    $caller = 'Unknown';
    foreach ($backtrace as $trace) {
        if (isset($trace['file']) && strpos($trace['file'], 'wp-includes') === false) {
            $caller = $trace['file'] . ':' . ($trace['line'] ?? '?');
            if (isset($trace['function'])) {
                $caller .= ' in ' . $trace['function'] . '()';
            }
            break;
        }
    }

    $redirect_attempts[] = array(
        'location' => $location,
        'status' => $status,
        'caller' => $caller,
        'trace' => $backtrace
    );

    // БЛОКИРУЕМ редирект на member-dashboard
    if (strpos($location, 'member-dashboard') !== false) {
        // Показываем кто виноват
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>РЕДИРЕКТ ЗАБЛОКИРОВАН</title></head><body>";
        echo "<h1 style='color: red;'>🛑 РЕДИРЕКТ ЗАБЛОКИРОВАН!</h1>";
        echo "<h2>Кто-то пытался отправить тебя на: <code>" . esc_html($location) . "</code></h2>";
        echo "<h3>Виновник:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 20px; border: 2px solid red;'>";
        echo "Файл: " . esc_html($caller) . "\n\n";
        echo "Полный trace:\n";
        foreach ($backtrace as $i => $trace) {
            if (isset($trace['file'])) {
                echo "#$i " . esc_html($trace['file']) . ":" . ($trace['line'] ?? '?');
                if (isset($trace['function'])) {
                    echo " - " . esc_html($trace['function']) . "()";
                }
                echo "\n";
            }
        }
        echo "</pre>";

        echo "<h3>Что делать:</h3>";
        echo "<ol>";
        echo "<li>Скопируй всё что написано выше</li>";
        echo "<li>Отправь Claude (мне) этот текст</li>";
        echo "<li>Я точно скажу что нужно отключить</li>";
        echo "</ol>";

        echo "<p><a href='" . admin_url() . "' style='padding: 10px 20px; background: green; color: white; text-decoration: none; display: inline-block;'>Перейти в админку принудительно</a></p>";
        echo "</body></html>";
        exit;
    }

    // Другие редиректы пропускаем
    return $location;
}, 1, 2);

// Показываем все попытки редиректов в админке
add_action('admin_notices', function() {
    global $redirect_attempts;

    if (!empty($redirect_attempts)) {
        echo '<div class="notice notice-error"><h3>⚠️ Были попытки редиректа:</h3><ul>';
        foreach ($redirect_attempts as $attempt) {
            echo '<li><strong>' . esc_html($attempt['location']) . '</strong> из ' . esc_html($attempt['caller']) . '</li>';
        }
        echo '</ul></div>';
    }
});
