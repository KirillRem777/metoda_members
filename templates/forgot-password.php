<?php
/**
 * Template Name: Forgot Password
 * Кастомная страница восстановления пароля в стиле "Метода"
 */

// KILL SWITCH: Не редиректим если отключены редиректы
if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
    if (is_user_logged_in()) {
        echo '<div style="padding: 20px; background: #ffeb3b; border: 2px solid #ff9800;">';
        echo '<h3>⚠️ Редиректы отключены (METODA_DISABLE_REDIRECTS)</h3>';
        echo '<p>Вы уже авторизованы. <a href="' . admin_url() . '">Перейти в админку →</a></p>';
        echo '</div>';
        return;
    }
}

// Не показываем в админке
if (is_admin()) {
    return;
}

// Если пользователь уже авторизован, редиректим
if (is_user_logged_in()) {
    $user = wp_get_current_user();

    // Администраторы не должны редиректиться
    if (current_user_can('administrator') || current_user_can('manage_options')) {
        echo '<div style="padding: 20px; text-align: center;">';
        echo '<h3>Вы уже авторизованы как администратор</h3>';
        echo '<p><a href="' . admin_url() . '">Перейти в админку →</a></p>';
        echo '</div>';
        return;
    }

    wp_redirect(home_url('/member-dashboard/'));
    exit;
}


// Обработка формы
$message = '';
$message_type = '';

if (isset($_POST['submit_forgot_password'])) {
    $user_email = sanitize_email($_POST['user_email']);

    if (empty($user_email)) {
        $message = 'Пожалуйста, введите email адрес.';
        $message_type = 'error';
    } elseif (!is_email($user_email)) {
        $message = 'Пожалуйста, введите корректный email адрес.';
        $message_type = 'error';
    } else {
        $user = get_user_by('email', $user_email);

        if (!$user) {
            $message = 'Пользователь с таким email не найден.';
            $message_type = 'error';
        } else {
            // Генерируем токен для сброса пароля
            $reset_key = get_password_reset_key($user);

            if (is_wp_error($reset_key)) {
                $message = 'Произошла ошибка. Попробуйте позже.';
                $message_type = 'error';
            } else {
                // Отправляем email с ссылкой для сброса
                $reset_url = add_query_arg(array(
                    'action' => 'rp',
                    'key' => $reset_key,
                    'login' => rawurlencode($user->user_login)
                ), home_url('/reset-password/'));

                $email_subject = 'Восстановление пароля - ' . get_bloginfo('name');
                $email_message = "Здравствуйте, {$user->display_name}!\n\n";
                $email_message .= "Вы запросили восстановление пароля для вашего аккаунта.\n\n";
                $email_message .= "Для установки нового пароля перейдите по ссылке:\n";
                $email_message .= $reset_url . "\n\n";
                $email_message .= "Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.\n\n";
                $email_message .= "С уважением,\n";
                $email_message .= "Команда " . get_bloginfo('name');

                $sent = wp_mail($user_email, $email_subject, $email_message);

                if ($sent) {
                    $message = 'Инструкции по восстановлению пароля отправлены на ваш email.';
                    $message_type = 'success';
                } else {
                    $message = 'Не удалось отправить email. Попробуйте позже.';
                    $message_type = 'error';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля - <?php bloginfo('name'); ?></title>
    <?php metoda_enqueue_frontend_styles(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #0066cc 0%, #ff6600 100%);
        }
    </style>
    <script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center gradient-bg mb-4">
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Забыли пароль?</h1>
            <p class="text-gray-600">Введите ваш email и мы отправим инструкции по восстановлению</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">

            <?php if (!empty($message)): ?>
            <div class="mb-6 <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border px-4 py-3 rounded-lg flex items-start">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5 mr-3"></i>
                <span class="text-sm"><?php echo esc_html($message); ?></span>
            </div>
            <?php endif; ?>

            <form method="post" action="" class="space-y-6">

                <!-- Email Field -->
                <div>
                    <label for="user_email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email адрес
                    </label>
                    <div class="relative">
                        <input
                            type="email"
                            id="user_email"
                            name="user_email"
                            required
                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-base"
                            placeholder="your.email@example.com"
                            style="outline: none; --tw-ring-color: #0066cc;"
                        >
                        <i class="fas fa-envelope absolute left-4 top-4 text-gray-400"></i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    name="submit_forgot_password"
                    class="w-full text-white py-4 px-6 rounded-xl font-semibold text-base hover:opacity-90 transition-all shadow-lg"
                    style="background: linear-gradient(135deg, #0066cc 0%, #ff6600 100%);"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Отправить инструкции
                </button>

            </form>
        </div>

        <!-- Additional Links -->
        <div class="mt-6 text-center">
            <p class="text-gray-600 text-sm">
                Вспомнили пароль?
                <a href="<?php echo home_url('/custom-login/'); ?>" class="font-semibold hover:underline" style="color: #0066cc;">
                    Войти
                </a>
            </p>
        </div>

        <div class="mt-4 text-center">
            <a href="<?php echo home_url(); ?>" class="text-gray-500 text-sm hover:text-gray-700 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                На главную страницу
            </a>
        </div>

    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
