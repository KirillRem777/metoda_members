<?php
/**
 * REDIRECT TRACER - Диагностика всех редиректов
 *
 * Положи этот файл в КОРЕНЬ WordPress и открой в браузере
 * https://ваш-сайт.ru/redirect-tracer.php
 *
 * Он перехватит ВСЕ редиректы и покажет откуда они идут
 *
 * ВАЖНО: УДАЛИ файл после диагностики!
 */

require_once('wp-load.php');

if (!is_user_logged_in()) {
    die('Залогинься сначала!');
}

// Включаем перехват редиректов
add_filter('wp_redirect', 'trace_redirect', 1, 2);
add_action('admin_init', 'trace_admin_init', 1);
add_action('template_redirect', 'trace_template_redirect', 1);
add_action('login_redirect', 'trace_login_redirect', 1, 3);

$GLOBALS['redirect_log'] = array();

function trace_redirect($location, $status) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

    $trace_info = array(
        'type' => 'wp_redirect',
        'location' => $location,
        'status' => $status,
        'time' => microtime(true),
        'backtrace' => array()
    );

    foreach ($backtrace as $trace) {
        if (isset($trace['file'])) {
            $trace_info['backtrace'][] = array(
                'file' => str_replace(ABSPATH, '', $trace['file']),
                'line' => $trace['line'] ?? '',
                'function' => $trace['function'] ?? '',
                'class' => $trace['class'] ?? ''
            );
        }
    }

    $GLOBALS['redirect_log'][] = $trace_info;

    // НЕ выполняем редирект, просто логируем
    return false;
}

function trace_admin_init() {
    $GLOBALS['redirect_log'][] = array(
        'type' => 'admin_init fired',
        'time' => microtime(true),
        'user_id' => get_current_user_id(),
        'current_screen' => function_exists('get_current_screen') ? get_current_screen() : 'N/A'
    );
}

function trace_template_redirect() {
    $GLOBALS['redirect_log'][] = array(
        'type' => 'template_redirect fired',
        'time' => microtime(true),
        'user_id' => get_current_user_id(),
        'is_admin' => is_admin(),
        'current_url' => $_SERVER['REQUEST_URI'] ?? ''
    );
}

function trace_login_redirect($redirect_to, $request, $user) {
    $GLOBALS['redirect_log'][] = array(
        'type' => 'login_redirect filter',
        'redirect_to' => $redirect_to,
        'request' => $request,
        'user_roles' => isset($user->roles) ? $user->roles : 'N/A',
        'time' => microtime(true)
    );

    return $redirect_to;
}

// Симулируем переход в админку
do_action('admin_init');

// Симулируем обычную страницу
do_action('template_redirect');

$user = wp_get_current_user();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirect Tracer - Metoda Community MGMT</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
            background: #0d1117;
            color: #c9d1d9;
            padding: 20px;
            margin: 0;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #58a6ff; margin-bottom: 10px; }
        .section {
            background: #161b22;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border: 1px solid #30363d;
        }
        .user-info {
            background: #1c2128;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #58a6ff;
        }
        .redirect-entry {
            background: #0d1117;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #f85149;
        }
        .hook-entry {
            background: #0d1117;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #56d364;
        }
        .backtrace {
            background: #0d1117;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-size: 12px;
            overflow-x: auto;
        }
        .backtrace-item {
            padding: 5px 0;
            border-bottom: 1px solid #21262d;
        }
        .backtrace-item:last-child { border-bottom: none; }
        .file { color: #79c0ff; }
        .function { color: #d2a8ff; }
        .line { color: #ffa657; }
        .good { color: #56d364; }
        .bad { color: #f85149; }
        .warning { color: #e3b341; }
        pre {
            background: #0d1117;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #30363d;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .badge-redirect { background: #da3633; color: white; }
        .badge-hook { background: #1f6feb; color: white; }
        .badge-filter { background: #8957e5; color: white; }
        .count {
            font-size: 24px;
            font-weight: bold;
            color: #58a6ff;
        }
        a { color: #58a6ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .kill-switch {
            background: #da3633;
            color: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .kill-switch code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Redirect Tracer</h1>
        <p style="color: #8b949e;">Перехват всех редиректов в реальном времени</p>

        <div class="section user-info">
            <h2 style="margin-top: 0; color: #58a6ff;">👤 Текущий пользователь</h2>
            <p><strong>ID:</strong> <?php echo $user->ID; ?></p>
            <p><strong>Логин:</strong> <?php echo $user->user_login; ?></p>
            <p><strong>Роли:</strong>
                <?php
                foreach ($user->roles as $role) {
                    $color = ($role === 'administrator') ? 'good' : 'warning';
                    echo '<span class="' . $color . '">' . $role . '</span> ';
                }
                ?>
            </p>
            <p><strong>Capabilities:</strong>
                <?php
                $caps = array('manage_options', 'administrator', 'member', 'expert');
                foreach ($caps as $cap) {
                    $has = current_user_can($cap);
                    $color = $has ? 'good' : 'bad';
                    echo '<span class="' . $color . '">' . $cap . ': ' . ($has ? '✅' : '❌') . '</span> ';
                }
                ?>
            </p>
        </div>

        <div class="section">
            <h2 style="color: #58a6ff;">📊 Статистика</h2>
            <p>
                <span class="count"><?php echo count($GLOBALS['redirect_log']); ?></span>
                событий перехвачено
            </p>
        </div>

        <?php if (empty($GLOBALS['redirect_log'])): ?>
            <div class="section" style="border-left: 3px solid #56d364;">
                <h2 style="color: #56d364;">✅ Редиректов не обнаружено!</h2>
                <p>Во время загрузки этой страницы не было ни одного вызова wp_redirect().</p>
                <p><strong>Что это значит?</strong></p>
                <ul>
                    <li>Плагин Metoda Community MGMT НЕ вызывает редиректы для твоего аккаунта</li>
                    <li>Проблема может быть в другом плагине или теме</li>
                    <li>Или проблема проявляется только при определенных условиях</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="section">
                <h2 style="color: #f85149;">🚨 Обнаруженные события</h2>

                <?php foreach ($GLOBALS['redirect_log'] as $index => $log): ?>

                    <?php if ($log['type'] === 'wp_redirect'): ?>
                        <div class="redirect-entry">
                            <strong style="font-size: 16px;">
                                🔴 Редирект #<?php echo $index + 1; ?>
                                <span class="badge badge-redirect">wp_redirect()</span>
                            </strong>
                            <p><strong>Куда:</strong> <span class="warning"><?php echo esc_html($log['location']); ?></span></p>
                            <p><strong>HTTP код:</strong> <?php echo $log['status']; ?></p>

                            <?php if (!empty($log['backtrace'])): ?>
                                <div class="backtrace">
                                    <strong>📍 Откуда вызвано (backtrace):</strong>
                                    <?php foreach ($log['backtrace'] as $trace): ?>
                                        <div class="backtrace-item">
                                            <span class="file"><?php echo esc_html($trace['file']); ?></span>
                                            <span class="line">:<?php echo $trace['line']; ?></span>
                                            <?php if ($trace['class']): ?>
                                                <br>→ <span class="function"><?php echo $trace['class']; ?>::<?php echo $trace['function']; ?>()</span>
                                            <?php elseif ($trace['function']): ?>
                                                <br>→ <span class="function"><?php echo $trace['function']; ?>()</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($log['type'] === 'admin_init fired'): ?>
                        <div class="hook-entry">
                            <strong>⚡ admin_init hook</strong>
                            <span class="badge badge-hook">ACTION</span>
                            <p>User ID: <?php echo $log['user_id']; ?></p>
                        </div>

                    <?php elseif ($log['type'] === 'template_redirect fired'): ?>
                        <div class="hook-entry">
                            <strong>⚡ template_redirect hook</strong>
                            <span class="badge badge-hook">ACTION</span>
                            <p>
                                URL: <?php echo esc_html($log['current_url']); ?><br>
                                is_admin(): <?php echo $log['is_admin'] ? 'true' : 'false'; ?>
                            </p>
                        </div>

                    <?php elseif ($log['type'] === 'login_redirect filter'): ?>
                        <div class="hook-entry">
                            <strong>⚡ login_redirect filter</strong>
                            <span class="badge badge-filter">FILTER</span>
                            <p>
                                <strong>Redirect to:</strong> <?php echo esc_html($log['redirect_to']); ?><br>
                                <strong>User roles:</strong> <?php echo is_array($log['user_roles']) ? implode(', ', $log['user_roles']) : $log['user_roles']; ?>
                            </p>
                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section kill-switch">
            <h2 style="margin-top: 0; color: white;">🛑 KILL SWITCH - Отключение всех редиректов</h2>
            <p>Если нужно временно отключить ВСЕ редиректы плагина для тестирования, добавь в <code>wp-config.php</code>:</p>
            <pre style="background: rgba(0,0,0,0.3); border: none; color: white;">define('METODA_DISABLE_REDIRECTS', true);</pre>
            <p>Это отключит все redirect-функции плагина и позволит зайти в админку.</p>
        </div>

        <div class="section">
            <h2 style="color: #58a6ff;">🔧 Быстрые действия</h2>
            <p><a href="<?php echo admin_url(); ?>">→ Попробовать зайти в админку</a> (может редиректнуть)</p>
            <p><a href="<?php echo home_url('/member-dashboard/'); ?>">→ Перейти в member-dashboard</a></p>
            <p><a href="<?php echo home_url(); ?>">→ На главную сайта</a></p>
            <p><a href="?refresh=1" style="color: #56d364;">🔄 Обновить трассировку</a></p>
        </div>

        <div class="section" style="background: #1c2128; border: 1px solid #f85149;">
            <h2 style="color: #f85149;">⚠️ ВАЖНО</h2>
            <p>После диагностики <strong>ОБЯЗАТЕЛЬНО УДАЛИ</strong> этот файл (redirect-tracer.php) из корня сайта!</p>
            <p>Этот файл содержит отладочную информацию и не должен быть доступен публично.</p>
        </div>

    </div>
</body>
</html>
