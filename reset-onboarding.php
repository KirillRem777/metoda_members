<?php
/**
 * Reset Onboarding Utility
 * Сбрасывает флаги онбординга для всех администраторов
 *
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Загрузите этот файл в корень плагина
 * 2. Откройте в браузере: https://ваш-сайт.ru/wp-content/plugins/members-management-pro/reset-onboarding.php
 * 3. После использования УДАЛИТЕ этот файл!
 */

// Безопасность: только для авторизованных админов
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

// Проверка прав
if (!current_user_can('manage_options')) {
    die('<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Доступ запрещён</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f5f5f5; }
        .error { background: white; border-left: 4px solid #dc3545; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        h1 { color: #dc3545; margin-top: 0; }
    </style>
</head>
<body>
    <div class="error">
        <h1>❌ Доступ запрещён</h1>
        <p>У вас нет прав для выполнения этой операции.</p>
        <p>Войдите как администратор.</p>
    </div>
</body>
</html>');
}

// Выполняем сброс
$reset_count = 0;
$users_reset = array();

// Получаем всех пользователей с флагом онбординга
$users_with_onboarding = get_users(array(
    'meta_key' => '_member_needs_onboarding',
    'meta_value' => '1',
));

foreach ($users_with_onboarding as $user) {
    // Сбрасываем флаг для администраторов и менеджеров
    if (in_array('administrator', (array) $user->roles) ||
        in_array('manager', (array) $user->roles) ||
        $user->ID === 1) {

        delete_user_meta($user->ID, '_member_needs_onboarding');
        delete_user_meta($user->ID, '_member_first_login');
        update_user_meta($user->ID, '_member_password_changed', '1');
        update_user_meta($user->ID, '_member_onboarding_completed', current_time('mysql'));

        $reset_count++;
        $users_reset[] = $user->display_name . ' (' . $user->user_email . ')';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Сброс онбординга выполнен</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .success {
            background: white;
            border-left: 4px solid #28a745;
            padding: 30px;
            border-radius: 8px;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #28a745;
            margin-top: 0;
            font-size: 28px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .user-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .user-list li {
            padding: 5px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 500;
        }
        .btn:hover {
            background: #0052a3;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="success">
        <h1>✅ Сброс онбординга выполнен</h1>

        <?php if ($reset_count > 0): ?>
            <p><strong>Обработано пользователей:</strong> <?php echo $reset_count; ?></p>

            <div class="user-list">
                <strong>Сброшены флаги для:</strong>
                <ul>
                    <?php foreach ($users_reset as $user_name): ?>
                        <li><?php echo esc_html($user_name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <p>Эти пользователи больше не будут перенаправлены на страницу онбординга.</p>
        <?php else: ?>
            <p>Не найдено администраторов с активным флагом онбординга.</p>
            <p>Все администраторы уже могут свободно заходить в админку.</p>
        <?php endif; ?>

        <div class="warning">
            <strong>⚠️ ВАЖНО: Безопасность</strong>
            <p>После использования этого скрипта <strong>обязательно удалите файл</strong> <code>reset-onboarding.php</code> из директории плагина!</p>
            <p>Оставлять этот файл на сервере небезопасно.</p>
        </div>

        <a href="<?php echo admin_url(); ?>" class="btn">Перейти в админку</a>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #dee2e6;">

        <h3>Что было сделано:</h3>
        <ul>
            <li>✅ Удалён флаг <code>_member_needs_onboarding</code></li>
            <li>✅ Удалён флаг <code>_member_first_login</code></li>
            <li>✅ Установлен флаг <code>_member_password_changed</code></li>
            <li>✅ Установлен флаг <code>_member_onboarding_completed</code></li>
        </ul>
    </div>
</body>
</html>
