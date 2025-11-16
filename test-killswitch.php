<?php
/**
 * 🧪 TEST KILL SWITCH
 *
 * Проверяет работает ли kill switch после загрузки WordPress
 */

define('WP_USE_THEMES', false);

// Загружаем WordPress
require_once(__DIR__ . '/../../../wp-load.php');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🧪 Kill Switch Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
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
        h1 { color: #4ec9b0; }
        .good { color: #4ec9b0; font-weight: bold; }
        .bad { color: #f48771; font-weight: bold; }
        .success {
            background: #1e3a1e;
            padding: 15px;
            border-left: 4px solid #4ec9b0;
            margin: 10px 0;
        }
        .error {
            background: #3a1f1f;
            padding: 15px;
            border-left: 4px solid #f48771;
            margin: 10px 0;
        }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #3e3e42;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🧪 Kill Switch Test</h1>

        <h2>1. Проверка константы METODA_DISABLE_PLUGIN</h2>
        <?php if (defined('METODA_DISABLE_PLUGIN')): ?>
            <div class="<?php echo METODA_DISABLE_PLUGIN ? 'success' : 'error'; ?>">
                <strong>Константа определена:</strong>
                <?php echo METODA_DISABLE_PLUGIN ? '<span class="good">✅ TRUE (плагин должен быть отключен)</span>' : '<span class="bad">❌ FALSE</span>'; ?>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>❌ Константа НЕ определена!</strong><br>
                Kill switch не работает! Нужно добавить в wp-config.php
            </div>
        <?php endif; ?>

        <h2>2. Проверка класса Member_Onboarding</h2>
        <?php if (class_exists('Member_Onboarding')): ?>
            <div class="error">
                <strong>❌ Класс Member_Onboarding ЗАГРУЖЕН!</strong><br>
                Это значит что плагин загрузился несмотря на kill switch!
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ Класс Member_Onboarding НЕ загружен</strong><br>
                Kill switch работает корректно!
            </div>
        <?php endif; ?>

        <h2>3. Проверка активных плагинов</h2>
        <?php
        $active_plugins = get_option('active_plugins', array());
        $metoda_active = array_filter($active_plugins, function($plugin) {
            return stripos($plugin, 'metoda') !== false || stripos($plugin, 'member') !== false;
        });

        if (empty($metoda_active)) {
            echo '<div class="success">✅ Плагин Metoda НЕ активен в списке WordPress</div>';
        } else {
            echo '<div class="error">❌ Плагин Metoda АКТИВЕН в WordPress:</div>';
            echo '<pre>';
            foreach ($metoda_active as $plugin) {
                echo htmlspecialchars($plugin) . "\n";
            }
            echo '</pre>';
        }
        ?>

        <h2>4. Проверка зарегистрированных хуков</h2>
        <?php
        global $wp_filter;

        $hooks_to_check = array(
            'template_redirect',
            'admin_init',
            'wp_login'
        );

        foreach ($hooks_to_check as $hook) {
            echo '<h3>' . $hook . '</h3>';

            if (isset($wp_filter[$hook]) && !empty($wp_filter[$hook]->callbacks)) {
                echo '<div class="error">⚠️ Найдены зарегистрированные callback\'и:</div>';
                echo '<pre>';

                foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback) {
                        $callback_name = 'unknown';

                        if (is_array($callback['function'])) {
                            $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                            $method = $callback['function'][1];
                            $callback_name = $class . '::' . $method;
                        } elseif (is_string($callback['function'])) {
                            $callback_name = $callback['function'];
                        }

                        // Проверяем относится ли к нашему плагину
                        if (stripos($callback_name, 'member') !== false || stripos($callback_name, 'metoda') !== false) {
                            echo '<span class="bad">❌ [' . $priority . '] ' . htmlspecialchars($callback_name) . "</span>\n";
                        } else {
                            echo '[' . $priority . '] ' . htmlspecialchars($callback_name) . "\n";
                        }
                    }
                }

                echo '</pre>';
            } else {
                echo '<div class="success">✅ Хуки не зарегистрированы</div>';
            }
        }
        ?>

        <h2>5. Текущий пользователь</h2>
        <?php if (is_user_logged_in()):
            $user = wp_get_current_user();
        ?>
            <div class="success">
                <strong>✅ Вы авторизованы</strong><br>
                User ID: <?php echo $user->ID; ?><br>
                Логин: <?php echo $user->user_login; ?><br>
                Роли: <?php echo implode(', ', $user->roles); ?><br>
                <br>
                <strong>Мета онбординга:</strong><br>
                <?php
                $needs_onboarding = get_user_meta($user->ID, '_member_needs_onboarding', true);
                echo '_member_needs_onboarding: ' . ($needs_onboarding === '1' ? '<span class="bad">1 (требуется онбординг!)</span>' : '<span class="good">не установлен</span>');
                ?>
            </div>
        <?php else: ?>
            <div class="error">❌ Вы НЕ авторизованы</div>
        <?php endif; ?>

        <h2>6. SQL для деактивации ВСЕХ плагинов</h2>
        <div class="error">
            <p><strong>Если ничего не помогает - выполни в phpMyAdmin:</strong></p>
            <pre>UPDATE <?php echo $GLOBALS['wpdb']->prefix; ?>options
SET option_value = 'a:0:{}'
WHERE option_name = 'active_plugins';</pre>
            <p>Это деактивирует ВСЕ плагины!</p>
        </div>

        <h2>7. Попробуй зайти в админку</h2>
        <div class="success">
            <a href="<?php echo admin_url(); ?>" style="color: #4ec9b0; font-weight: bold; font-size: 18px;">→ Попробовать зайти в /wp-admin/</a>
        </div>
    </div>
</body>
</html>
