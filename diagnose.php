<?php
/**
 * Diagnostic Script - Shows user roles and onboarding meta
 *
 * Access: /wp-content/plugins/metoda_members/diagnose.php
 * БЕЗОПАСНО: Работает только для залогиненных пользователей
 */

define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../wp-load.php');

// Security: Must be logged in
if (!is_user_logged_in()) {
    wp_die('Пожалуйста, войдите в систему чтобы использовать этот скрипт.');
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Диагностика пользователя</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .diagnostic-box {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-top: 0;
        }
        h2 {
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .info-row {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #2c3e50;
            font-family: monospace;
        }
        .good {
            color: #27ae60;
            font-weight: bold;
        }
        .bad {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <div class="diagnostic-box">
        <h1>🔍 Диагностика пользователя</h1>

        <h2>Основная информация</h2>
        <div class="info-row">
            <span class="label">User ID:</span>
            <span class="value"><?php echo $user_id; ?></span>
        </div>
        <div class="info-row">
            <span class="label">Логин:</span>
            <span class="value"><?php echo $current_user->user_login; ?></span>
        </div>
        <div class="info-row">
            <span class="label">Email:</span>
            <span class="value"><?php echo $current_user->user_email; ?></span>
        </div>

        <h2>Роли пользователя</h2>
        <?php
        $roles = (array) $current_user->roles;
        if (empty($roles)) {
            echo '<div class="info-row"><span class="bad">⚠️ У пользователя НЕТ ролей!</span></div>';
        } else {
            foreach ($roles as $role) {
                $class = in_array($role, ['administrator', 'manager']) ? 'good' : 'warning';
                echo '<div class="info-row">';
                echo '<span class="' . $class . '">🎭 ' . esc_html($role) . '</span>';
                echo '</div>';
            }
        }

        // Check capabilities
        echo '<div class="info-row">';
        echo '<span class="label">manage_options:</span> ';
        echo current_user_can('manage_options') ? '<span class="good">✅ ДА</span>' : '<span class="bad">❌ НЕТ</span>';
        echo '</div>';

        echo '<div class="info-row">';
        echo '<span class="label">administrator:</span> ';
        echo current_user_can('administrator') ? '<span class="good">✅ ДА</span>' : '<span class="bad">❌ НЕТ</span>';
        echo '</div>';
        ?>

        <h2>Мета-флаги онбординга</h2>
        <?php
        $meta_fields = array(
            '_member_needs_onboarding' => 'Требуется онбординг',
            '_member_first_login' => 'Первый логин',
            '_member_password_changed' => 'Пароль изменен',
            '_member_onboarding_completed' => 'Онбординг завершен',
        );

        foreach ($meta_fields as $key => $label) {
            $value = get_user_meta($user_id, $key, true);
            echo '<div class="info-row">';
            echo '<span class="label">' . esc_html($label) . ' (' . $key . '):</span> ';

            if (empty($value)) {
                echo '<span class="value">не установлен</span>';
            } else {
                $class = ($key === '_member_needs_onboarding' && $value === '1') ? 'bad' : 'good';
                echo '<span class="' . $class . '">' . esc_html($value) . '</span>';
            }
            echo '</div>';
        }
        ?>

        <h2>Проблемы и рекомендации</h2>
        <?php
        $has_admin_role = in_array('administrator', $roles) || in_array('manager', $roles);
        $has_member_role = in_array('member', $roles) || in_array('expert', $roles);
        $needs_onboarding = get_user_meta($user_id, '_member_needs_onboarding', true);

        if (!$has_admin_role && $has_member_role) {
            echo '<div class="info-row"><span class="bad">⚠️ ПРОБЛЕМА: У вас нет роли администратора! Только роли member/expert.</span></div>';
            echo '<div class="info-row"><span class="warning">💡 РЕШЕНИЕ: Нужно добавить роль "administrator" через базу данных</span></div>';
        }

        if ($needs_onboarding === '1') {
            echo '<div class="info-row"><span class="bad">⚠️ ПРОБЛЕМА: Флаг _member_needs_onboarding = 1 (активен)</span></div>';
            echo '<div class="info-row"><span class="warning">💡 РЕШЕНИЕ: Нужно удалить этот флаг</span></div>';
        }

        if ($has_admin_role && !$needs_onboarding) {
            echo '<div class="info-row"><span class="good">✅ ВСЁ В ПОРЯДКЕ: У вас есть админские права и онбординг не требуется</span></div>';
        }
        ?>

        <div class="action-buttons">
            <a href="<?php echo admin_url(); ?>" class="btn btn-primary">Попробовать зайти в админку</a>
            <a href="<?php echo home_url(); ?>" class="btn btn-success">На главную</a>
            <?php if ($needs_onboarding === '1'): ?>
            <a href="emergency-reset.php" class="btn btn-danger">Сбросить онбординг</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="diagnostic-box">
        <h2>SQL команды для ручного исправления</h2>
        <p>Если нужно исправить через phpMyAdmin:</p>

        <h3>1. Удалить флаг онбординга:</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto;">DELETE FROM wp_usermeta WHERE user_id = <?php echo $user_id; ?> AND meta_key = '_member_needs_onboarding';</pre>

        <h3>2. Добавить роль administrator (если её нет):</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto;">UPDATE wp_usermeta
SET meta_value = 'a:1:{s:13:"administrator";b:1;}'
WHERE user_id = <?php echo $user_id; ?> AND meta_key = 'wp_capabilities';</pre>

        <h3>3. Проверить роли пользователя:</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto;">SELECT * FROM wp_usermeta WHERE user_id = <?php echo $user_id; ?> AND meta_key = 'wp_capabilities';</pre>
    </div>
</body>
</html>
