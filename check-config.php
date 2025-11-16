<?php
/**
 * 🔍 SIMPLE CONFIG CHECKER - Проверка без загрузки WordPress
 *
 * Этот скрипт НЕ загружает WordPress, поэтому работает без ошибок
 */

// Находим wp-config.php
$wp_config_path = __DIR__ . '/../../../wp-config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔍 Config Checker</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 900px;
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
        h2 { color: #569cd6; border-bottom: 2px solid #569cd6; padding-bottom: 10px; }
        .good { color: #4ec9b0; font-weight: bold; }
        .bad { color: #f48771; font-weight: bold; }
        .warning { color: #ce9178; font-weight: bold; }
        pre {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #3e3e42;
            color: #ce9178;
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
        code {
            background: #1e1e1e;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ce9178;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔍 Simple Config Checker</h1>
        <p>Этот скрипт проверяет конфигурацию БЕЗ загрузки WordPress (избегая конфликтов с Elementor)</p>

        <h2>1. Проверка wp-config.php</h2>
        <?php if (file_exists($wp_config_path)): ?>
            <div class="success">✅ Файл найден: <code><?php echo $wp_config_path; ?></code></div>

            <?php
            $config_content = file_get_contents($wp_config_path);

            // Проверяем kill switch
            $has_kill_switch = false;
            if (strpos($config_content, 'METODA_DISABLE_PLUGIN') !== false) {
                // Проверяем не закомментирован ли
                preg_match('/^[^\/\*]*define\s*\(\s*[\'"]METODA_DISABLE_PLUGIN[\'"]\s*,\s*true\s*\)/m', $config_content, $matches);
                if (!empty($matches)) {
                    $has_kill_switch = true;
                }
            }

            if ($has_kill_switch) {
                echo '<div class="success">✅ <strong>Kill switch АКТИВЕН!</strong> Плагин должен быть отключен.</div>';
            } else {
                echo '<div class="error">❌ <strong>Kill switch НЕ найден!</strong></div>';
                echo '<div class="info">';
                echo '<p>Добавь в wp-config.php (ПЕРЕД строкой "That\'s all, stop editing!"):</p>';
                echo '<pre>// 🔴 ПОЛНОЕ ОТКЛЮЧЕНИЕ ПЛАГИНА METODA
define(\'METODA_DISABLE_PLUGIN\', true);</pre>';
                echo '</div>';
            }

            // Проверяем другие константы
            $other_constants = array(
                'METODA_DISABLE_REDIRECTS' => 'Отключение редиректов',
            );

            foreach ($other_constants as $const => $desc) {
                if (strpos($config_content, $const) !== false) {
                    echo '<div class="warning">⚠️ Найдена константа: <code>' . $const . '</code> - ' . $desc . '</div>';
                }
            }
            ?>
        <?php else: ?>
            <div class="error">❌ Файл wp-config.php не найден по пути: <?php echo $wp_config_path; ?></div>
        <?php endif; ?>

        <h2>2. Проверка файлов плагина</h2>
        <?php
        $plugin_file = __DIR__ . '/members-management-pro.php';
        $onboarding_file = __DIR__ . '/includes/class-member-onboarding.php';

        if (file_exists($plugin_file)) {
            $plugin_content = file_get_contents($plugin_file);

            // Проверяем kill switch в плагине
            if (strpos($plugin_content, 'ЯДЕРНАЯ КНОПКА') !== false) {
                echo '<div class="success">✅ Kill switch код найден в members-management-pro.php</div>';
            } else {
                echo '<div class="error">❌ Kill switch НЕ найден в members-management-pro.php</div>';
            }

            // Проверяем отключение admin_init
            if (strpos($plugin_content, '// ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ РАЗРАБОТКИ: add_action(\'admin_init\'') !== false) {
                echo '<div class="success">✅ admin_init хук ОТКЛЮЧЕН (block_admin_access_for_members)</div>';
            } else {
                echo '<div class="error">❌ admin_init хук ВСЁ ЕЩЁ АКТИВЕН!</div>';
            }
        }

        if (file_exists($onboarding_file)) {
            $onboarding_content = file_get_contents($onboarding_file);

            // Проверяем отключение template_redirect
            if (strpos($onboarding_content, '// ВРЕМЕННО ОТКЛЮЧЕНО: add_action(\'template_redirect\'') !== false) {
                echo '<div class="success">✅ template_redirect хук ОТКЛЮЧЕН (onboarding)</div>';
            } else {
                echo '<div class="error">❌ template_redirect хук ВСЁ ЕЩЁ АКТИВЕН!</div>';
            }
        }
        ?>

        <h2>3. Возможные причины блокировки</h2>
        <?php if ($has_kill_switch): ?>
            <div class="success">
                <strong>✅ Kill switch активен - плагин должен быть полностью выключен!</strong>
                <p>Попробуй:</p>
                <ol>
                    <li>Очистить кэш браузера (Ctrl+Shift+Del)</li>
                    <li>Открыть админку в режиме инкогнито</li>
                    <li>Очистить кэш WordPress (если есть плагин кэширования)</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="error">
                <strong>❌ Редирект скорее всего идёт из плагина!</strong>
                <p>Возможные источники:</p>
                <ul>
                    <li><strong>Плагин НЕ отключен</strong> - нужно добавить kill switch в wp-config.php</li>
                    <li>Кэш браузера - открой админку в режиме инкогнито</li>
                    <li>Кэш WordPress - очисти кэш</li>
                    <li>Другой плагин редиректит (например, плагин членства/ограничений доступа)</li>
                    <li>.htaccess редирект</li>
                </ul>
            </div>
        <?php endif; ?>

        <h2>4. РЕШЕНИЕ: Добавь Kill Switch прямо сейчас!</h2>
        <div class="info">
            <p><strong>Открой файл wp-config.php и добавь ЭТИ СТРОКИ:</strong></p>
            <pre>// 🔴 ПОЛНОЕ ОТКЛЮЧЕНИЕ ПЛАГИНА METODA (временно для отладки)
define('METODA_DISABLE_PLUGIN', true);

/* That's all, stop editing! Happy publishing. */</pre>
            <p>Добавь <strong>ПЕРЕД</strong> строкой "That's all, stop editing!"</p>
        </div>

        <h2>5. Альтернатива: Переименуй папку плагина</h2>
        <div class="warning">
            <p>Если kill switch не помогает, просто <strong>переименуй папку плагина</strong>:</p>
            <pre>metoda_members → metoda_members_DISABLED</pre>
            <p>WordPress сразу же перестанет загружать плагин!</p>
        </div>

        <h2>6. После того как зайдёшь в админку</h2>
        <div class="info">
            <p>Когда успешно зайдёшь:</p>
            <ol>
                <li>Убери константу из wp-config.php (или верни название папки)</li>
                <li>В админке деактивируй плагин "Metoda Community MGMT"</li>
                <li>Дай мне знать - мы исправим все редиректы навсегда!</li>
            </ol>
        </div>
    </div>

    <div class="box">
        <h2>Информация о системе</h2>
        <div class="info">
            <strong>Путь к плагину:</strong> <code><?php echo __DIR__; ?></code><br>
            <strong>Путь к wp-config.php:</strong> <code><?php echo $wp_config_path; ?></code><br>
            <strong>Время на сервере:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>PHP версия:</strong> <?php echo PHP_VERSION; ?>
        </div>
    </div>
</body>
</html>
