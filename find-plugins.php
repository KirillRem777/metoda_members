<?php
/**
 * 🔍 FIND ALL PLUGIN COPIES
 *
 * Этот скрипт ищет все копии плагина и показывает какая активна
 */

// Путь к plugins
$plugins_dir = __DIR__ . '/../../';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔍 Find Plugin Copies</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1000px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #3e3e42;
        }
        th {
            background: #1e1e1e;
            color: #569cd6;
            font-weight: bold;
        }
        tr:hover {
            background: #2d2d30;
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
        <h1>🔍 Find All Plugin Copies</h1>

        <h2>Поиск копий плагина</h2>
        <?php
        if (is_dir($plugins_dir)) {
            $folders = scandir($plugins_dir);
            $metoda_folders = array();

            foreach ($folders as $folder) {
                if ($folder === '.' || $folder === '..') continue;

                // Ищем папки которые начинаются с metoda
                if (stripos($folder, 'metoda') !== false || stripos($folder, 'member') !== false) {
                    $full_path = $plugins_dir . '/' . $folder;
                    if (is_dir($full_path)) {
                        $metoda_folders[] = array(
                            'name' => $folder,
                            'path' => $full_path,
                            'has_main_file' => file_exists($full_path . '/members-management-pro.php'),
                            'size' => 0,
                            'modified' => filemtime($full_path)
                        );
                    }
                }
            }

            if (empty($metoda_folders)) {
                echo '<div class="warning">⚠️ Папки с плагином не найдены!</div>';
            } else {
                echo '<div class="success">✅ Найдено копий: ' . count($metoda_folders) . '</div>';

                echo '<table>';
                echo '<tr><th>Название папки</th><th>Путь</th><th>Главный файл</th><th>Изменено</th></tr>';

                foreach ($metoda_folders as $folder) {
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($folder['name']) . '</code></td>';
                    echo '<td style="font-size: 11px;">' . htmlspecialchars($folder['path']) . '</td>';

                    if ($folder['has_main_file']) {
                        echo '<td><span class="good">✅ Есть</span></td>';
                    } else {
                        echo '<td><span class="bad">❌ Нет</span></td>';
                    }

                    echo '<td>' . date('Y-m-d H:i:s', $folder['modified']) . '</td>';
                    echo '</tr>';
                }

                echo '</table>';
            }
        } else {
            echo '<div class="error">❌ Папка plugins не найдена: ' . $plugins_dir . '</div>';
        }
        ?>

        <h2>Проверка активных плагинов WordPress</h2>
        <?php
        // Загружаем список активных плагинов из опций
        $wp_config_path = __DIR__ . '/../../../wp-config.php';

        if (file_exists($wp_config_path)) {
            // Простой способ - читаем таблицу options
            // Но нам нужно подключиться к БД

            // Парсим wp-config.php чтобы получить настройки БД
            $config_content = file_get_contents($wp_config_path);

            preg_match("/define\s*\(\s*'DB_NAME'\s*,\s*'([^']+)'/", $config_content, $db_name_match);
            preg_match("/define\s*\(\s*'DB_USER'\s*,\s*'([^']+)'/", $config_content, $db_user_match);
            preg_match("/define\s*\(\s*'DB_PASSWORD'\s*,\s*'([^']+)'/", $config_content, $db_pass_match);
            preg_match("/define\s*\(\s*'DB_HOST'\s*,\s*'([^']+)'/", $config_content, $db_host_match);
            preg_match("/\\\$table_prefix\s*=\s*'([^']+)'/", $config_content, $table_prefix_match);

            if (!empty($db_name_match[1]) && !empty($db_user_match[1])) {
                $db_name = $db_name_match[1];
                $db_user = $db_user_match[1];
                $db_pass = isset($db_pass_match[1]) ? $db_pass_match[1] : '';
                $db_host = isset($db_host_match[1]) ? $db_host_match[1] : 'localhost';
                $table_prefix = isset($table_prefix_match[1]) ? $table_prefix_match[1] : 'wp_';

                try {
                    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Получаем активные плагины
                    $stmt = $pdo->prepare("SELECT option_value FROM {$table_prefix}options WHERE option_name = 'active_plugins'");
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($result) {
                        $active_plugins = unserialize($result['option_value']);

                        echo '<div class="success">✅ Подключение к базе данных успешно!</div>';

                        // Фильтруем только metoda плагины
                        $metoda_active = array_filter($active_plugins, function($plugin) {
                            return stripos($plugin, 'metoda') !== false || stripos($plugin, 'member') !== false;
                        });

                        if (empty($metoda_active)) {
                            echo '<div class="warning">⚠️ Плагин Metoda НЕ активен!</div>';
                        } else {
                            echo '<div class="error">❌ Найдены АКТИВНЫЕ плагины Metoda:</div>';
                            echo '<pre>';
                            foreach ($metoda_active as $plugin) {
                                echo htmlspecialchars($plugin) . "\n";

                                // Парсим путь
                                $parts = explode('/', $plugin);
                                if (count($parts) > 0) {
                                    $folder_name = $parts[0];
                                    echo "  → Папка: <strong>$folder_name</strong>\n";
                                }
                            }
                            echo '</pre>';
                        }

                        // Показываем ВСЕ активные плагины
                        echo '<h3>Все активные плагины (' . count($active_plugins) . '):</h3>';
                        echo '<pre>';
                        foreach ($active_plugins as $plugin) {
                            echo htmlspecialchars($plugin) . "\n";
                        }
                        echo '</pre>';
                    }

                } catch (PDOException $e) {
                    echo '<div class="error">❌ Ошибка подключения к БД: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                echo '<div class="warning">⚠️ Не удалось распарсить wp-config.php</div>';
            }
        }
        ?>

        <h2>РЕШЕНИЕ</h2>
        <div class="error">
            <h3>Если найдена АКТИВНАЯ копия плагина:</h3>
            <ol>
                <li><strong>Переименуй активную папку плагина</strong> (добавь _DISABLED в конец)</li>
                <li>Или добавь kill switch в ГЛАВНЫЙ файл активной копии</li>
                <li>Или деактивируй плагин через базу данных (SQL запрос ниже)</li>
            </ol>
        </div>

        <h2>SQL Запрос для деактивации плагина</h2>
        <div class="info">
            <p>Выполни этот SQL запрос в phpMyAdmin чтобы деактивировать ВСЕ плагины:</p>
            <pre>UPDATE <?php echo isset($table_prefix) ? $table_prefix : 'wp_'; ?>options
SET option_value = 'a:0:{}'
WHERE option_name = 'active_plugins';</pre>
            <p><strong>⚠️ ВНИМАНИЕ:</strong> Это деактивирует ВСЕ плагины! После этого зайди в админку и активируй нужные плагины заново.</p>
        </div>

        <h2>Самый простой способ</h2>
        <div class="success">
            <h3>Переименуй папку активного плагина:</h3>
            <p>Через FTP/файловый менеджер переименуй папку:</p>
            <pre>metoda_members → metoda_members_OFF</pre>
            <p>WordPress сразу же перестанет загружать плагин!</p>
        </div>
    </div>

    <div class="box">
        <h2>Информация</h2>
        <div class="info">
            <strong>Текущая папка:</strong> <code><?php echo __DIR__; ?></code><br>
            <strong>Путь к plugins:</strong> <code><?php echo realpath($plugins_dir); ?></code><br>
            <strong>Время:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
