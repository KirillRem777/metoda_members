<?php
/**
 * ДИАГНОСТИКА РЕДИРЕКТОВ
 *
 * Положи этот файл в КОРЕНЬ WordPress и открой в браузере
 * https://ваш-сайт.ru/diagnose.php
 */

require_once('wp-load.php');

if (!is_user_logged_in()) {
    die('Залогинься сначала!');
}

$user = wp_get_current_user();
$user_id = $user->ID;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Диагностика - Metoda Community MGMT</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .section { background: #252526; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #007acc; }
        .good { color: #4ec9b0; }
        .bad { color: #f48771; }
        .warning { color: #dcdcaa; }
        h1 { color: #569cd6; }
        h2 { color: #4fc1ff; margin-top: 0; }
        pre { background: #1e1e1e; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px; }
        .badge-success { background: #0e639c; color: white; }
        .badge-danger { background: #a31515; color: white; }
        .badge-warning { background: #8b6f00; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Диагностика редиректов</h1>

    <div class="section">
        <h2>👤 Информация о пользователе</h2>
        <p><strong>ID:</strong> <?php echo $user_id; ?></p>
        <p><strong>Логин:</strong> <?php echo $user->user_login; ?></p>
        <p><strong>Email:</strong> <?php echo $user->user_email; ?></p>
        <p><strong>Роли:</strong></p>
        <pre><?php print_r($user->roles); ?></pre>

        <?php
        $has_admin = in_array('administrator', $user->roles);
        $has_manager = in_array('manager', $user->roles);
        $has_member = in_array('member', $user->roles);
        $has_expert = in_array('expert', $user->roles);
        ?>

        <p>
            <span class="<?php echo $has_admin ? 'good' : 'bad'; ?>">
                <?php echo $has_admin ? '✅' : '❌'; ?> Administrator
            </span>
            <?php if ($has_admin && ($has_member || $has_expert)): ?>
                <span class="badge badge-warning">СМЕШАННЫЕ РОЛИ!</span>
            <?php endif; ?>
        </p>
        <p>
            <span class="<?php echo $has_manager ? 'good' : 'bad'; ?>">
                <?php echo $has_manager ? '✅' : '❌'; ?> Manager
            </span>
        </p>
        <p>
            <span class="<?php echo $has_member ? 'warning' : 'good'; ?>">
                <?php echo $has_member ? '⚠️' : '✅'; ?> Member <?php echo $has_member ? '(ПРОБЛЕМА!)' : ''; ?>
            </span>
        </p>
        <p>
            <span class="<?php echo $has_expert ? 'warning' : 'good'; ?>">
                <?php echo $has_expert ? '⚠️' : '✅'; ?> Expert <?php echo $has_expert ? '(ПРОБЛЕМА!)' : ''; ?>
            </span>
        </p>
    </div>

    <div class="section">
        <h2>🚦 Флаги онбординга</h2>
        <?php
        $needs_onboarding = get_user_meta($user_id, '_member_needs_onboarding', true);
        $first_login = get_user_meta($user_id, '_member_first_login', true);
        $onboarding_seen = get_user_meta($user_id, 'metoda_onboarding_seen', true);
        ?>
        <p><strong>_member_needs_onboarding:</strong>
            <span class="<?php echo $needs_onboarding === '1' ? 'bad' : 'good'; ?>">
                <?php echo $needs_onboarding ? $needs_onboarding . ' ❌ УДАЛИ!' : 'не установлен ✅'; ?>
            </span>
        </p>
        <p><strong>_member_first_login:</strong> <?php echo $first_login ? $first_login : 'не установлен'; ?></p>
        <p><strong>metoda_onboarding_seen:</strong> <?php echo $onboarding_seen ? $onboarding_seen : 'не установлен'; ?></p>
    </div>

    <div class="section">
        <h2>🔗 Привязка к профилю участника</h2>
        <?php
        $member_id = Member_User_Link::get_current_user_member_id();
        ?>
        <p><strong>ID профиля участника:</strong>
            <?php if ($member_id): ?>
                <span class="warning"><?php echo $member_id; ?> (есть привязка - может вызывать редирект)</span>
            <?php else: ?>
                <span class="good">Нет привязки ✅</span>
            <?php endif; ?>
        </p>
    </div>

    <div class="section">
        <h2>⚙️ Capabilities</h2>
        <?php
        $caps_to_check = ['manage_options', 'administrator', 'member', 'expert', 'manage_members'];
        ?>
        <?php foreach ($caps_to_check as $cap): ?>
            <p>
                <strong><?php echo $cap; ?>:</strong>
                <span class="<?php echo current_user_can($cap) ? 'good' : 'bad'; ?>">
                    <?php echo current_user_can($cap) ? 'Да ✅' : 'Нет ❌'; ?>
                </span>
            </p>
        <?php endforeach; ?>
    </div>

    <div class="section">
        <h2>🎯 ПРОБЛЕМЫ И РЕШЕНИЯ</h2>

        <?php if ($has_member || $has_expert): ?>
            <div style="background: #a31515; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <p><strong>❌ ПРОБЛЕМА #1: Есть роль member/expert</strong></p>
                <p>Твой админский аккаунт имеет роль member или expert. Это вызывает редиректы!</p>
                <p><strong>РЕШЕНИЕ:</strong> Выполни в phpMyAdmin:</p>
                <pre>UPDATE wp_usermeta
SET meta_value = 'a:1:{s:13:"administrator";b:1;}'
WHERE user_id = <?php echo $user_id; ?>
AND meta_key = 'wp_capabilities';</pre>
            </div>
        <?php endif; ?>

        <?php if ($needs_onboarding === '1'): ?>
            <div style="background: #a31515; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <p><strong>❌ ПРОБЛЕМА #2: Флаг онбординга установлен</strong></p>
                <p>Флаг _member_needs_onboarding вызывает редирект на страницу онбординга!</p>
                <p><strong>РЕШЕНИЕ:</strong> Выполни в phpMyAdmin:</p>
                <pre>DELETE FROM wp_usermeta
WHERE user_id = <?php echo $user_id; ?>
AND meta_key = '_member_needs_onboarding';</pre>
            </div>
        <?php endif; ?>

        <?php if ($member_id && $has_admin): ?>
            <div style="background: #8b6f00; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <p><strong>⚠️ ПРОБЛЕМА #3: Админ привязан к профилю участника</strong></p>
                <p>Твой админский аккаунт привязан к профилю участника (ID: <?php echo $member_id; ?>). Это может вызывать редиректы!</p>
                <p><strong>РЕШЕНИЕ:</strong> Выполни в phpMyAdmin:</p>
                <pre>DELETE FROM wp_postmeta
WHERE post_id = <?php echo $member_id; ?>
AND meta_key = 'member_user_id'
AND meta_value = '<?php echo $user_id; ?>';</pre>
            </div>
        <?php endif; ?>

        <?php if (!$has_member && !$has_expert && $needs_onboarding !== '1'): ?>
            <div style="background: #0e639c; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <p><strong>✅ Все хорошо!</strong></p>
                <p>Очевидных проблем не найдено. Если редирект все еще происходит, проверь:</p>
                <ul>
                    <li>Может быть другой плагин вызывает редирект?</li>
                    <li>Может быть тема имеет свои хуки?</li>
                    <li>Попробуй деактивировать все плагины кроме Metoda Community MGMT</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>🔧 Быстрые действия</h2>
        <p><a href="<?php echo admin_url(); ?>" style="color: #4ec9b0;">→ Перейти в админку</a></p>
        <p><a href="<?php echo admin_url('plugins.php'); ?>" style="color: #4ec9b0;">→ Управление плагинами</a></p>
        <p><a href="<?php echo home_url(); ?>" style="color: #4ec9b0;">→ На главную сайта</a></p>
    </div>

    <div class="section" style="background: #3b1f1f; border-left-color: #f48771;">
        <h2>⚠️ ВАЖНО</h2>
        <p>После диагностики <strong>УДАЛИ</strong> этот файл (diagnose.php) из корня сайта!</p>
    </div>

</body>
</html>
