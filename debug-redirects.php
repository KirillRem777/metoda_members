<?php
/**
 * 🔍 REDIRECT DEBUGGER - Показывает откуда идет редирект
 *
 * Использование:
 * 1. Загрузи этот файл на сервер в wp-content/plugins/metoda_members/
 * 2. Открой в браузере: /wp-content/plugins/metoda_members/debug-redirects.php
 * 3. Скрипт покажет ВСЕ редиректы которые пытаются сработать
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

// Включаем отображение ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Собираем информацию о редиректах
$redirects_log = array();

// Перехватываем все wp_redirect
function capture_redirect($location, $status = 302) {
    global $redirects_log;

    // Получаем stack trace
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

    $redirects_log[] = array(
        'location' => $location,
        'status' => $status,
        'backtrace' => $backtrace,
        'time' => microtime(true)
    );

    // НЕ делаем редирект, просто логируем
    return false;
}

// Хукаемся на wp_redirect ОЧЕНЬ рано
add_filter('wp_redirect', 'capture_redirect', 1, 2);

// Хукаемся на все возможные места редиректа
add_action('template_redirect', function() {
    global $redirects_log;
    $redirects_log[] = array(
        'hook' => 'template_redirect',
        'user' => wp_get_current_user(),
        'time' => microtime(true),
        'url' => $_SERVER['REQUEST_URI']
    );
}, 1);

add_action('admin_init', function() {
    global $redirects_log;
    $redirects_log[] = array(
        'hook' => 'admin_init',
        'user' => wp_get_current_user(),
        'time' => microtime(true),
        'url' => $_SERVER['REQUEST_URI']
    );
}, 1);

// Загружаем WordPress и даем хукам сработать
do_action('init');
do_action('wp_loaded');

if (is_user_logged_in()) {
    // Если залогинен, пробуем вызвать хуки которые могут редиректить
    do_action('template_redirect');

    if (is_admin()) {
        do_action('admin_init');
    }
}

// Выводим результаты
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔍 Redirect Debugger</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .box {
            background: #252526;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #3e3e42;
        }
        h1 {
            color: #4ec9b0;
            margin-top: 0;
        }
        h2 {
            color: #569cd6;
            border-bottom: 2px solid #569cd6;
            padding-bottom: 10px;
        }
        h3 {
            color: #dcdcaa;
        }
        .good {
            color: #4ec9b0;
            font-weight: bold;
        }
        .bad {
            color: #f48771;
            font-weight: bold;
        }
        .warning {
            color: #ce9178;
            font-weight: bold;
        }
        .info {
            background: #264f78;
            padding: 15px;
            border-left: 4px solid #569cd6;
            margin: 10px 0;
        }
        .error {
            background: #3a1f1f;
            padding: 15px;
            border-left: 4px solid #f48771;
            margin: 10px 0;
        }
        .success {
            background: #1e3a1e;
            padding: 15px;
            border-left: 4px solid #4ec9b0;
            margin: 10px 0;
        }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #3e3e42;
        }
        code {
            color: #ce9178;
            font-family: 'Consolas', 'Monaco', monospace;
        }
        .backtrace {
            font-size: 12px;
            color: #858585;
        }
        .file-line {
            color: #569cd6;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔍 Redirect Debugger</h1>

        <h2>Статус авторизации</h2>
        <?php if (is_user_logged_in()):
            $current_user = wp_get_current_user();
        ?>
            <div class="success">
                <strong>✅ Вы авторизованы</strong><br>
                User ID: <?php echo $current_user->ID; ?><br>
                Логин: <?php echo $current_user->user_login; ?><br>
                Роли: <?php echo implode(', ', $current_user->roles); ?>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>❌ Вы НЕ авторизованы</strong><br>
                Этот скрипт работает только для залогиненных пользователей.
            </div>
        <?php endif; ?>

        <h2>Проверка констант Kill Switch</h2>
        <?php
        $kill_switches = array(
            'METODA_DISABLE_PLUGIN' => 'Полное отключение плагина',
            'METODA_DISABLE_REDIRECTS' => 'Отключение всех редиректов',
        );

        foreach ($kill_switches as $const => $desc) {
            $defined = defined($const) && constant($const);
            echo '<div class="info">';
            echo '<code>' . $const . '</code>: ';
            if ($defined) {
                echo '<span class="good">✅ ВКЛЮЧЕН</span> - ' . $desc;
            } else {
                echo '<span class="warning">❌ ВЫКЛЮЧЕН</span>';
            }
            echo '</div>';
        }
        ?>

        <h2>Проверка хуков редиректа</h2>
        <?php
        global $wp_filter;

        $hooks_to_check = array(
            'template_redirect' => 'Редирект на фронтенде',
            'admin_init' => 'Редирект в админке',
            'wp_login' => 'После авторизации',
        );

        foreach ($hooks_to_check as $hook => $desc) {
            echo '<h3>' . $hook . ' <span style="color: #858585;">(' . $desc . ')</span></h3>';

            if (isset($wp_filter[$hook])) {
                echo '<div class="warning">⚠️ Найдены зарегистрированные хуки:</div>';
                echo '<pre>';

                foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                    echo "Приоритет $priority:\n";
                    foreach ($callbacks as $callback) {
                        $callback_name = '(unknown)';

                        if (is_array($callback['function'])) {
                            $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                            $method = $callback['function'][1];
                            $callback_name = $class . '::' . $method;
                        } elseif (is_string($callback['function'])) {
                            $callback_name = $callback['function'];
                        }

                        echo "  - $callback_name\n";
                    }
                }

                echo '</pre>';
            } else {
                echo '<div class="success">✅ Хуки не зарегистрированы</div>';
            }
        }
        ?>

        <h2>Обнаруженные редиректы</h2>
        <?php
        if (empty($redirects_log)) {
            echo '<div class="success">✅ Редиректы не обнаружены!</div>';
        } else {
            echo '<div class="error">❌ Обнаружено редиректов: ' . count($redirects_log) . '</div>';

            foreach ($redirects_log as $idx => $redirect) {
                echo '<div class="box">';
                echo '<h3>Редирект #' . ($idx + 1) . '</h3>';

                if (isset($redirect['location'])) {
                    echo '<div class="info">';
                    echo '<strong>URL:</strong> ' . esc_html($redirect['location']) . '<br>';
                    echo '<strong>HTTP Status:</strong> ' . $redirect['status'];
                    echo '</div>';

                    if (!empty($redirect['backtrace'])) {
                        echo '<h4>Stack Trace (откуда вызвано):</h4>';
                        echo '<pre class="backtrace">';

                        foreach (array_slice($redirect['backtrace'], 0, 10) as $trace) {
                            if (isset($trace['file'])) {
                                $file = str_replace(ABSPATH, '', $trace['file']);
                                echo '<span class="file-line">' . $file . ':' . $trace['line'] . '</span>';

                                if (isset($trace['function'])) {
                                    if (isset($trace['class'])) {
                                        echo ' → ' . $trace['class'] . $trace['type'] . $trace['function'] . '()';
                                    } else {
                                        echo ' → ' . $trace['function'] . '()';
                                    }
                                }
                                echo "\n";
                            }
                        }

                        echo '</pre>';
                    }
                } elseif (isset($redirect['hook'])) {
                    echo '<div class="info">';
                    echo '<strong>Hook:</strong> ' . $redirect['hook'] . '<br>';
                    echo '<strong>URL:</strong> ' . $redirect['url'];
                    echo '</div>';
                }

                echo '</div>';
            }
        }
        ?>

        <h2>Рекомендации</h2>
        <?php
        if (empty($redirects_log)): ?>
            <div class="success">
                <strong>✅ Всё чисто!</strong><br>
                Редиректы не обнаружены. Проблема может быть в:
                <ul>
                    <li>Кэше WordPress (попробуй очистить кэш)</li>
                    <li>Кэше браузера (открой в режиме инкогнито)</li>
                    <li>Редиректе на уровне .htaccess</li>
                    <li>Редиректе из другого плагина</li>
                    <li>Редиректе из темы WordPress</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>❌ Найдены редиректы!</strong><br>
                Смотри Stack Trace выше чтобы понять откуда они вызываются.
            </div>
        <?php endif; ?>

        <h2>Попробуй Kill Switch</h2>
        <div class="info">
            <p><strong>Добавь в wp-config.php (ПЕРЕД строкой "That's all, stop editing!"):</strong></p>
            <pre><code>// 🔴 ПОЛНОЕ ОТКЛЮЧЕНИЕ ПЛАГИНА
define('METODA_DISABLE_PLUGIN', true);</code></pre>

            <p>После этого плагин вообще не будет загружаться и все редиректы прекратятся.</p>
        </div>
    </div>
</body>
</html>
