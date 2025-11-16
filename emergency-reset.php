<?php
/**
 * EMERGENCY: Reset Onboarding - NO AUTH REQUIRED
 *
 * ⚠️ ОПАСНО! Работает БЕЗ авторизации!
 * СРАЗУ УДАЛИ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

// БЕЗОПАСНОСТЬ: Только для localhost или с подтверждением
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1', 'localhost'));
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if (!$is_localhost && !$confirmed) {
    die('<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Подтверждение требуется</title>
    <style>
        body { font-family: sans-serif; padding: 50px; text-align: center; background: #f5f5f5; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        h1 { color: #856404; }
        .btn { display: inline-block; padding: 15px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; font-weight: bold; }
        .btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="warning">
        <h1>⚠️ ВНИМАНИЕ!</h1>
        <p>Этот скрипт сбросит онбординг для ВСЕХ администраторов БЕЗ проверки прав.</p>
        <p>Используйте только если заблокированы и не можете войти в админку!</p>
        <a href="?confirm=yes" class="btn">Да, сбросить онбординг</a>
    </div>
</body>
</html>');
}

// Выполняем сброс
global $wpdb;

// Получаем всех администраторов
$admins = get_users(array('role' => 'administrator'));
$reset_count = 0;

foreach ($admins as $admin) {
    delete_user_meta($admin->ID, '_member_needs_onboarding');
    delete_user_meta($admin->ID, '_member_first_login');
    update_user_meta($admin->ID, '_member_password_changed', '1');
    update_user_meta($admin->ID, '_member_onboarding_completed', current_time('mysql'));
    $reset_count++;
}

// Также сбрасываем для менеджеров
$managers = get_users(array('role' => 'manager'));
foreach ($managers as $manager) {
    delete_user_meta($manager->ID, '_member_needs_onboarding');
    delete_user_meta($manager->ID, '_member_first_login');
    update_user_meta($manager->ID, '_member_password_changed', '1');
    update_user_meta($manager->ID, '_member_onboarding_completed', current_time('mysql'));
    $reset_count++;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Онбординг сброшен</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f5f5f5; }
        .success { background: white; border-left: 4px solid #28a745; padding: 30px; border-radius: 8px; max-width: 700px; margin: 0 auto; }
        h1 { color: #28a745; }
        .danger { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 6px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #0066cc; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="success">
        <h1>✅ Онбординг сброшен!</h1>
        <p><strong>Обработано пользователей:</strong> <?php echo $reset_count; ?></p>
        <p>Все администраторы и менеджеры теперь могут войти в админку.</p>

        <div class="danger">
            <strong>🔥 КРИТИЧЕСКИ ВАЖНО!</strong><br>
            НЕМЕДЛЕННО УДАЛИ ФАЙЛ <code>emergency-reset.php</code> С СЕРВЕРА!<br>
            Этот файл работает БЕЗ авторизации и представляет угрозу безопасности!
        </div>

        <a href="<?php echo admin_url(); ?>" class="btn">Перейти в админку</a>
    </div>
</body>
</html>
