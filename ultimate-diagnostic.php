<?php
/**
 * ULTIMATE DIAGNOSTIC TOOL
 * Проверяет ВСЕ возможные источники редиректа
 *
 * УДАЛИ после диагностики!
 */

require_once('wp-load.php');

if (!is_user_logged_in()) {
    die('Залогинься сначала!');
}

$results = array();
$user = wp_get_current_user();
$user_id = get_current_user_id();

// 1. Проверка User ID и ролей
$results['user_info'] = array(
    'id' => $user_id,
    'login' => $user->user_login,
    'roles' => $user->roles,
    'capabilities' => array(
        'manage_options' => current_user_can('manage_options'),
        'administrator' => current_user_can('administrator'),
        'member' => current_user_can('member'),
        'expert' => current_user_can('expert'),
    )
);

// 2. Проверка user meta флагов
$results['user_meta'] = array(
    '_member_needs_onboarding' => get_user_meta($user_id, '_member_needs_onboarding', true),
    '_member_first_login' => get_user_meta($user_id, '_member_first_login', true),
    '_member_onboarding_completed' => get_user_meta($user_id, '_member_onboarding_completed', true),
    '_member_password_changed' => get_user_meta($user_id, '_member_password_changed', true),
);

// 3. Проверка активных плагинов
$active_plugins = get_option('active_plugins');
$results['active_plugins'] = array();
foreach ($active_plugins as $plugin) {
    $results['active_plugins'][] = $plugin;
}

// 4. Проверка активной темы
$theme = wp_get_theme();
$results['theme'] = array(
    'name' => $theme->get('Name'),
    'version' => $theme->get('Version'),
    'template' => $theme->get_template(),
);

// 5. Проверка WordPress константы METODA_DISABLE_REDIRECTS
$results['kill_switch'] = array(
    'defined' => defined('METODA_DISABLE_REDIRECTS'),
    'value' => defined('METODA_DISABLE_REDIRECTS') ? METODA_DISABLE_REDIRECTS : null,
);

// 6. Проверка transients
$results['transients'] = array(
    'metoda_members_activating' => get_transient('metoda_members_activating'),
    'metoda_needs_page_creation' => get_option('metoda_needs_page_creation'),
);

// 7. Проверка всех зарегистрированных login_redirect фильтров
global $wp_filter;
$results['login_redirect_filters'] = array();
if (isset($wp_filter['login_redirect'])) {
    foreach ($wp_filter['login_redirect']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $function_name = 'unknown';
            if (is_string($callback['function'])) {
                $function_name = $callback['function'];
            } elseif (is_array($callback['function'])) {
                if (is_object($callback['function'][0])) {
                    $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                } else {
                    $function_name = $callback['function'][0] . '::' . $callback['function'][1];
                }
            }
            $results['login_redirect_filters'][] = array(
                'priority' => $priority,
                'function' => $function_name,
            );
        }
    }
}

// 8. Проверка всех зарегистрированных admin_init хуков
$results['admin_init_hooks'] = array();
if (isset($wp_filter['admin_init'])) {
    foreach ($wp_filter['admin_init']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $function_name = 'unknown';
            if (is_string($callback['function'])) {
                $function_name = $callback['function'];
            } elseif (is_array($callback['function'])) {
                if (is_object($callback['function'][0])) {
                    $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                } else {
                    $function_name = $callback['function'][0] . '::' . $callback['function'][1];
                }
            }

            // Показываем только потенциально проблемные
            if (stripos($function_name, 'redirect') !== false ||
                stripos($function_name, 'block') !== false ||
                stripos($function_name, 'member') !== false ||
                stripos($function_name, 'onboard') !== false) {
                $results['admin_init_hooks'][] = array(
                    'priority' => $priority,
                    'function' => $function_name,
                );
            }
        }
    }
}

// 9. Проверка всех зарегистрированных template_redirect хуков
$results['template_redirect_hooks'] = array();
if (isset($wp_filter['template_redirect'])) {
    foreach ($wp_filter['template_redirect']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $function_name = 'unknown';
            if (is_string($callback['function'])) {
                $function_name = $callback['function'];
            } elseif (is_array($callback['function'])) {
                if (is_object($callback['function'][0])) {
                    $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                } else {
                    $function_name = $callback['function'][0] . '::' . $callback['function'][1];
                }
            }

            // Показываем только потенциально проблемные
            if (stripos($function_name, 'redirect') !== false ||
                stripos($function_name, 'member') !== false ||
                stripos($function_name, 'onboard') !== false) {
                $results['template_redirect_hooks'][] = array(
                    'priority' => $priority,
                    'function' => $function_name,
                );
            }
        }
    }
}

// 10. Проверка wp_login хуков
$results['wp_login_hooks'] = array();
if (isset($wp_filter['wp_login'])) {
    foreach ($wp_filter['wp_login']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $callback) {
            $function_name = 'unknown';
            if (is_string($callback['function'])) {
                $function_name = $callback['function'];
            } elseif (is_array($callback['function'])) {
                if (is_object($callback['function'][0])) {
                    $function_name = get_class($callback['function'][0]) . '::' . $callback['function'][1];
                } else {
                    $function_name = $callback['function'][0] . '::' . $callback['function'][1];
                }
            }
            $results['wp_login_hooks'][] = array(
                'priority' => $priority,
                'function' => $function_name,
            );
        }
    }
}

// 11. Проверяем есть ли JavaScript редиректы в теме
$theme_path = get_template_directory();
$js_files = array();
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_path));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'js') {
        $content = file_get_contents($file->getPathname());
        if (stripos($content, 'window.location') !== false ||
            stripos($content, 'location.href') !== false ||
            stripos($content, 'location.replace') !== false) {
            $js_files[] = str_replace(ABSPATH, '', $file->getPathname());
        }
    }
}
$results['theme_js_redirects'] = $js_files;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ultimate Diagnostic - Metoda Community MGMT</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
            background: #0d1117;
            color: #c9d1d9;
            padding: 20px;
            margin: 0;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #58a6ff; }
        h2 { color: #79c0ff; margin-top: 30px; }
        .section {
            background: #161b22;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border: 1px solid #30363d;
        }
        .good { color: #56d364; }
        .bad { color: #f85149; }
        .warning { color: #e3b341; }
        .info { color: #58a6ff; }
        pre {
            background: #0d1117;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #30363d;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #30363d;
        }
        th {
            background: #0d1117;
            color: #58a6ff;
            font-weight: 600;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid;
        }
        .alert-danger {
            background: rgba(248, 81, 73, 0.1);
            border-color: #f85149;
        }
        .alert-warning {
            background: rgba(227, 179, 65, 0.1);
            border-color: #e3b341;
        }
        .alert-success {
            background: rgba(86, 211, 100, 0.1);
            border-color: #56d364;
        }
        .alert-info {
            background: rgba(88, 166, 255, 0.1);
            border-color: #58a6ff;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
        }
        .badge-red { background: #da3633; color: white; }
        .badge-yellow { background: #9e6a03; color: white; }
        .badge-green { background: #1a7f37; color: white; }
        .badge-blue { background: #0969da; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 Ultimate Diagnostic Tool</h1>
        <p style="color: #8b949e;">Полная диагностика всех возможных источников редиректа</p>

        <!-- USER INFO -->
        <div class="section">
            <h2>👤 Информация о пользователе</h2>
            <table>
                <tr>
                    <th>Параметр</th>
                    <th>Значение</th>
                    <th>Статус</th>
                </tr>
                <tr>
                    <td>User ID</td>
                    <td><strong><?php echo $results['user_info']['id']; ?></strong></td>
                    <td><?php echo $results['user_info']['id'] === 1 ? '<span class="good">✅ Супер-админ</span>' : '<span class="info">Обычный пользователь</span>'; ?></td>
                </tr>
                <tr>
                    <td>Login</td>
                    <td><?php echo $results['user_info']['login']; ?></td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>Роли</td>
                    <td><?php echo implode(', ', $results['user_info']['roles']); ?></td>
                    <td><?php echo in_array('administrator', $results['user_info']['roles']) ? '<span class="good">✅ Администратор</span>' : '<span class="bad">⚠️ Не админ</span>'; ?></td>
                </tr>
                <tr>
                    <td>manage_options</td>
                    <td><?php echo $results['user_info']['capabilities']['manage_options'] ? 'true' : 'false'; ?></td>
                    <td><?php echo $results['user_info']['capabilities']['manage_options'] ? '<span class="good">✅</span>' : '<span class="bad">❌</span>'; ?></td>
                </tr>
            </table>
        </div>

        <!-- USER META FLAGS -->
        <div class="section">
            <h2>🏴 User Meta Флаги</h2>
            <table>
                <tr>
                    <th>Флаг</th>
                    <th>Значение</th>
                    <th>Статус</th>
                </tr>
                <?php foreach ($results['user_meta'] as $key => $value): ?>
                <tr>
                    <td><code><?php echo $key; ?></code></td>
                    <td><?php echo empty($value) ? '<span class="good">не установлен</span>' : '<span class="warning">' . esc_html($value) . '</span>'; ?></td>
                    <td>
                        <?php
                        if ($key === '_member_needs_onboarding' && $value === '1') {
                            echo '<span class="bad">⚠️ МОЖЕТ ВЫЗЫВАТЬ РЕДИРЕКТ</span>';
                        } elseif (empty($value)) {
                            echo '<span class="good">✅ OK</span>';
                        } else {
                            echo '<span class="info">ℹ️ Установлен</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <?php if ($results['user_meta']['_member_needs_onboarding'] === '1'): ?>
            <div class="alert alert-danger">
                <strong>🚨 НАЙДЕНА ПРОБЛЕМА!</strong><br>
                Флаг <code>_member_needs_onboarding</code> установлен в "1"!<br>
                Это вызывает редирект на /member-onboarding/ через функцию <code>force_onboarding_redirect()</code>
                <br><br>
                <strong>РЕШЕНИЕ:</strong> Удали этот флаг через SQL:
                <pre>DELETE FROM wp_usermeta WHERE user_id = <?php echo $user_id; ?> AND meta_key = '_member_needs_onboarding';</pre>
            </div>
            <?php endif; ?>
        </div>

        <!-- KILL SWITCH -->
        <div class="section">
            <h2>🛑 KILL SWITCH Status</h2>
            <table>
                <tr>
                    <th>Константа</th>
                    <th>Определена?</th>
                    <th>Значение</th>
                    <th>Статус</th>
                </tr>
                <tr>
                    <td><code>METODA_DISABLE_REDIRECTS</code></td>
                    <td><?php echo $results['kill_switch']['defined'] ? '<span class="good">Да</span>' : '<span class="bad">Нет</span>'; ?></td>
                    <td><?php echo $results['kill_switch']['value'] ? '<span class="good">true</span>' : '<span class="bad">false/null</span>'; ?></td>
                    <td>
                        <?php
                        if ($results['kill_switch']['defined'] && $results['kill_switch']['value']) {
                            echo '<span class="good">✅ Редиректы ОТКЛЮЧЕНЫ</span>';
                        } else {
                            echo '<span class="bad">⚠️ Редиректы АКТИВНЫ</span>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- LOGIN REDIRECT FILTERS -->
        <div class="section">
            <h2>🔀 login_redirect Фильтры</h2>
            <?php if (empty($results['login_redirect_filters'])): ?>
                <p class="good">✅ Нет зарегистрированных фильтров</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Приоритет</th>
                        <th>Функция</th>
                    </tr>
                    <?php foreach ($results['login_redirect_filters'] as $filter): ?>
                    <tr>
                        <td><?php echo $filter['priority']; ?></td>
                        <td>
                            <code><?php echo $filter['function']; ?></code>
                            <?php if (stripos($filter['function'], 'member_login_redirect') !== false): ?>
                                <span class="badge badge-blue">НАШ ПЛАГИН</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- ADMIN_INIT HOOKS -->
        <div class="section">
            <h2>⚙️ admin_init Хуки (подозрительные)</h2>
            <?php if (empty($results['admin_init_hooks'])): ?>
                <p class="good">✅ Нет подозрительных хуков</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Приоритет</th>
                        <th>Функция</th>
                    </tr>
                    <?php foreach ($results['admin_init_hooks'] as $hook): ?>
                    <tr>
                        <td><?php echo $hook['priority']; ?></td>
                        <td>
                            <code><?php echo $hook['function']; ?></code>
                            <?php if (stripos($hook['function'], 'block_admin_access') !== false): ?>
                                <span class="badge badge-blue">НАШ ПЛАГИН</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- TEMPLATE_REDIRECT HOOKS -->
        <div class="section">
            <h2>📄 template_redirect Хуки (подозрительные)</h2>
            <?php if (empty($results['template_redirect_hooks'])): ?>
                <p class="good">✅ Нет подозрительных хуков</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Приоритет</th>
                        <th>Функция</th>
                    </tr>
                    <?php foreach ($results['template_redirect_hooks'] as $hook): ?>
                    <tr>
                        <td><?php echo $hook['priority']; ?></td>
                        <td>
                            <code><?php echo $hook['function']; ?></code>
                            <?php if (stripos($hook['function'], 'force_onboarding') !== false): ?>
                                <span class="badge badge-blue">НАШ ПЛАГИН</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- WP_LOGIN HOOKS -->
        <div class="section">
            <h2>🔐 wp_login Хуки</h2>
            <?php if (empty($results['wp_login_hooks'])): ?>
                <p class="good">✅ Нет зарегистрированных хуков</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Приоритет</th>
                        <th>Функция</th>
                    </tr>
                    <?php foreach ($results['wp_login_hooks'] as $hook): ?>
                    <tr>
                        <td><?php echo $hook['priority']; ?></td>
                        <td>
                            <code><?php echo $hook['function']; ?></code>
                            <?php if (stripos($hook['function'], 'check_first_login') !== false): ?>
                                <span class="badge badge-blue">НАШ ПЛАГИН</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- ACTIVE PLUGINS -->
        <div class="section">
            <h2>🔌 Активные плагины (<?php echo count($results['active_plugins']); ?>)</h2>
            <pre><?php echo implode("\n", $results['active_plugins']); ?></pre>
        </div>

        <!-- THEME -->
        <div class="section">
            <h2>🎨 Активная тема</h2>
            <table>
                <tr>
                    <th>Параметр</th>
                    <th>Значение</th>
                </tr>
                <tr>
                    <td>Название</td>
                    <td><?php echo $results['theme']['name']; ?></td>
                </tr>
                <tr>
                    <td>Версия</td>
                    <td><?php echo $results['theme']['version']; ?></td>
                </tr>
                <tr>
                    <td>Template</td>
                    <td><?php echo $results['theme']['template']; ?></td>
                </tr>
            </table>
        </div>

        <!-- THEME JS REDIRECTS -->
        <div class="section">
            <h2>📜 JavaScript редиректы в теме</h2>
            <?php if (empty($results['theme_js_redirects'])): ?>
                <p class="good">✅ JS редиректы не найдены</p>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>⚠️ Найдены JS файлы с редиректами:</strong>
                </div>
                <pre><?php echo implode("\n", $results['theme_js_redirects']); ?></pre>
            <?php endif; ?>
        </div>

        <!-- RAW DATA -->
        <div class="section">
            <h2>📊 Raw Data (JSON)</h2>
            <pre><?php echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></pre>
        </div>

        <div class="section" style="background: #1c2128; border: 1px solid #f85149;">
            <h2 style="color: #f85149;">⚠️ ВАЖНО</h2>
            <p><strong>УДАЛИ этот файл после диагностики!</strong></p>
            <p>Файл содержит конфиденциальную информацию о конфигурации сайта.</p>
        </div>

    </div>
</body>
</html>
