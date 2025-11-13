<?php
/**
 * Template Name: Custom Login Page
 * Кастомная страница входа в стиле "Метода"
 */

// Если пользователь уже авторизован, редиректим
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('member', $user->roles) || in_array('expert', $user->roles)) {
        wp_redirect(home_url('/member-dashboard/'));
    } elseif (in_array('manager', $user->roles) || in_array('administrator', $user->roles)) {
        wp_redirect(home_url('/manager-panel/'));
    } else {
        wp_redirect(home_url());
    }
    exit;
}

// Цвета Метода
$primary_color = '#0066cc';
$accent_color = '#ff6600';
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $accent_color; ?> 100%);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $primary_color; ?>',
                        accent: '<?php echo $accent_color; ?>',
                    }
                }
            }
        }
    </script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center gradient-bg mb-4">
                <i class="fas fa-users text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Добро пожаловать</h1>
            <p class="text-gray-600">Войдите в личный кабинет ассоциации Метода</p>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">

            <?php
            // Показываем сообщения об ошибках
            if (isset($_GET['login']) && $_GET['login'] == 'failed'):
            ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3"></i>
                <span class="text-sm">Неверный email или пароль. Попробуйте еще раз.</span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered']) && $_GET['registered'] == 'true'): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-start">
                <i class="fas fa-check-circle mt-0.5 mr-3"></i>
                <span class="text-sm">Регистрация успешна! Войдите с вашими данными.</span>
            </div>
            <?php endif; ?>

            <form id="login-form" method="post" action="<?php echo wp_login_url(); ?>" class="space-y-6">

                <!-- Email Field -->
                <div>
                    <label for="user_login" class="block text-sm font-semibold text-gray-700 mb-2">
                        Email
                    </label>
                    <div class="relative">
                        <input
                            type="email"
                            id="user_login"
                            name="log"
                            required
                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-base"
                            placeholder="your.email@example.com"
                            style="outline: none; --tw-ring-color: <?php echo $primary_color; ?>;"
                        >
                        <i class="fas fa-envelope absolute left-4 top-4 text-gray-400"></i>
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="user_pass" class="block text-sm font-semibold text-gray-700 mb-2">
                        Пароль
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="user_pass"
                            name="pwd"
                            required
                            class="w-full px-4 py-3 pl-12 pr-12 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-base"
                            placeholder="Введите пароль"
                            style="outline: none; --tw-ring-color: <?php echo $primary_color; ?>;"
                        >
                        <i class="fas fa-lock absolute left-4 top-4 text-gray-400"></i>
                        <i class="fas fa-eye cursor-pointer absolute right-4 top-4 text-gray-400 hover:text-gray-600" id="toggle-password"></i>
                    </div>
                </div>

                <!-- Remember Me and Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="rememberme" value="forever" class="w-4 h-4 rounded focus:ring-2 focus:ring-offset-0" style="color: <?php echo $primary_color; ?>;">
                        <span class="ml-2 text-sm text-gray-700">Запомнить меня</span>
                    </label>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="text-sm font-medium hover:underline" style="color: <?php echo $primary_color; ?>;">
                        Забыли пароль?
                    </a>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    name="wp-submit"
                    class="w-full text-white py-4 px-6 rounded-xl font-semibold text-base hover:opacity-90 transition-all shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $accent_color; ?> 100%);"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Войти
                </button>

                <input type="hidden" name="redirect_to" value="<?php echo home_url('/member-dashboard/'); ?>">
            </form>
        </div>

        <!-- Additional Links -->
        <div class="mt-6 text-center">
            <p class="text-gray-600 text-sm">
                Еще не зарегистрированы?
                <a href="<?php echo home_url('/member-registration/'); ?>" class="font-semibold hover:underline" style="color: <?php echo $primary_color; ?>;">
                    Создать аккаунт
                </a>
            </p>
        </div>

        <div class="mt-6 text-center">
            <a href="<?php echo home_url(); ?>" class="text-gray-500 text-sm hover:text-gray-700 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                На главную страницу
            </a>
        </div>

    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('toggle-password').addEventListener('click', function() {
    const passwordField = document.getElementById('user_pass');
    const icon = this;

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Handle form submission
document.getElementById('login-form').addEventListener('submit', function(e) {
    const button = this.querySelector('button[type="submit"]');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Вход...';
});
</script>

<?php wp_footer(); ?>
</body>
</html>
