<?php
/**
 * Debug Activation Status
 *
 * Положите этот файл в корень WordPress и откройте в браузере:
 * https://ваш-сайт.ru/debug-activation.php
 *
 * ВАЖНО: Удалите файл после проверки!
 */

// Загружаем WordPress
require_once('wp-load.php');

// Только для администраторов
if (!current_user_can('manage_options')) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Metoda Community MGMT - Debug</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0066cc; }
        .status { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        .code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; }
        .btn:hover { background: #0052a3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Metoda Community MGMT - Диагностика активации</h1>

        <?php
        // Проверка активности плагина
        $is_active = is_plugin_active('metoda_members/members-management-pro.php');
        ?>

        <div class="status <?php echo $is_active ? 'success' : 'error'; ?>">
            <strong>Статус плагина:</strong>
            <?php echo $is_active ? '✅ Активен' : '❌ НЕ активен'; ?>
        </div>

        <h2>📊 Debug информация</h2>
        <table>
            <tr>
                <th>Параметр</th>
                <th>Значение</th>
                <th>Статус</th>
            </tr>
            <?php
            $debug_options = [
                'metoda_activation_started' => 'Начало активации',
                'metoda_activation_completed' => 'Завершение активации',
                'metoda_activation_error' => 'Ошибка активации',
                'metoda_activation_terms_created' => 'Создано терминов',
                'metoda_needs_page_creation' => 'Нужно создать страницы',
                'metoda_forum_pages_created' => 'Страницы форума созданы',
                'metoda_pages_created_at' => 'Страницы созданы',
            ];

            foreach ($debug_options as $key => $label) {
                $value = get_option($key);
                $has_value = $value !== false && $value !== '';
                ?>
                <tr>
                    <td><strong><?php echo esc_html($label); ?></strong><br><code class="code"><?php echo esc_html($key); ?></code></td>
                    <td><?php echo $value ? esc_html($value) : '<em>не установлено</em>'; ?></td>
                    <td><?php echo $has_value ? '✅' : '⚠️'; ?></td>
                </tr>
                <?php
            }

            // Проверка transient
            $transient = get_transient('metoda_members_activating');
            ?>
            <tr>
                <td><strong>Transient активации</strong><br><code class="code">metoda_members_activating</code></td>
                <td><?php echo $transient ? 'Активен (блокирует редиректы)' : 'Отсутствует'; ?></td>
                <td><?php echo $transient ? '✅' : '⚠️'; ?></td>
            </tr>
        </table>

        <h2>👥 Роли пользователей</h2>
        <?php
        global $wp_roles;
        $custom_roles = ['member', 'expert', 'manager'];
        ?>
        <table>
            <tr>
                <th>Роль</th>
                <th>Существует</th>
                <th>Capabilities</th>
            </tr>
            <?php
            foreach ($custom_roles as $role_name) {
                $role = $wp_roles->get_role($role_name);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($role_name); ?></strong></td>
                    <td><?php echo $role ? '✅ Да' : '❌ Нет'; ?></td>
                    <td><?php echo $role ? count($role->capabilities) . ' прав' : 'N/A'; ?></td>
                </tr>
                <?php
            }
            ?>
        </table>

        <h2>📄 Страницы плагина</h2>
        <?php
        $pages_to_check = [
            'metoda_members_page_id' => 'Участники',
            'metoda_registration_page_id' => 'Регистрация',
            'metoda_dashboard_page_id' => 'Личный кабинет',
            'metoda_onboarding_page_id' => 'Онбординг',
            'metoda_forum_page_id' => 'Форум',
            'metoda_manager_page_id' => 'Панель менеджера',
            'metoda_login_page_id' => 'Вход',
        ];
        ?>
        <table>
            <tr>
                <th>Страница</th>
                <th>ID</th>
                <th>Существует</th>
                <th>Ссылка</th>
            </tr>
            <?php
            foreach ($pages_to_check as $option => $title) {
                $page_id = get_option($option);
                $page_exists = $page_id && get_post($page_id);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($title); ?></strong></td>
                    <td><?php echo $page_id ? $page_id : '<em>не создана</em>'; ?></td>
                    <td><?php echo $page_exists ? '✅ Да' : '❌ Нет'; ?></td>
                    <td>
                        <?php if ($page_exists): ?>
                            <a href="<?php echo get_permalink($page_id); ?>" target="_blank">Открыть →</a>
                        <?php else: ?>
                            <em>N/A</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>

        <h2>⚙️ Действия</h2>

        <?php if ($is_active && get_option('metoda_needs_page_creation') === '1'): ?>
            <div class="status warning">
                <strong>⚠️ Внимание:</strong> Флаг создания страниц активен. Страницы будут созданы при следующей загрузке админки.
                <br><a href="<?php echo admin_url(); ?>" class="btn">Перейти в админку</a>
            </div>
        <?php endif; ?>

        <a href="<?php echo admin_url('plugins.php'); ?>" class="btn">Управление плагинами</a>

        <?php if (isset($_GET['clear_debug'])): ?>
            <?php
            delete_option('metoda_activation_started');
            delete_option('metoda_activation_completed');
            delete_option('metoda_activation_error');
            delete_option('metoda_activation_terms_created');
            delete_option('metoda_needs_page_creation');
            delete_option('metoda_pages_created_at');
            delete_transient('metoda_members_activating');
            ?>
            <div class="status success">✅ Debug данные очищены!</div>
            <meta http-equiv="refresh" content="2;url=debug-activation.php">
        <?php else: ?>
            <a href="?clear_debug=1" class="btn btn-danger">Очистить debug данные</a>
        <?php endif; ?>

        <div class="status info" style="margin-top: 30px;">
            <strong>ℹ️ Важно:</strong> Удалите этот файл (debug-activation.php) после завершения диагностики!
        </div>
    </div>
</body>
</html>
