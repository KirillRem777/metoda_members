<?php
/**
 * Reset Onboarding Flag
 *
 * Этот файл сбрасывает флаг онбординга для текущего пользователя
 *
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Скопируй файл в корень WordPress
 * 2. Открой в браузере: https://ваш-сайт.ru/reset-onboarding.php
 * 3. УДАЛИ файл после использования!
 */

// Загружаем WordPress
require_once('wp-load.php');

// Проверяем авторизацию
if (!is_user_logged_in()) {
    die('Необходимо авторизоваться');
}

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Удаляем флаг онбординга
delete_user_meta($user_id, '_member_needs_onboarding');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Сброс онбординга</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f0f1;
            padding: 50px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #1d2327; margin-top: 0; }
        .success {
            background: #00a32a;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover { background: #135e96; }
        .info {
            background: #f0f6fc;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Флаг онбординга сброшен!</h1>

        <div class="success">
            <strong>Пользователь:</strong> <?php echo esc_html($user->display_name); ?> (ID: <?php echo $user_id; ?>)<br>
            <strong>Статус:</strong> Флаг "_member_needs_onboarding" удален
        </div>

        <div class="info">
            <strong>Что дальше?</strong><br>
            1. Теперь можешь вернуться в админку<br>
            2. ОБЯЗАТЕЛЬНО удали этот файл (reset-onboarding.php) из корня сайта!
        </div>

        <a href="<?php echo admin_url(); ?>" class="btn">→ Вернуться в админку</a>
    </div>
</body>
</html>
