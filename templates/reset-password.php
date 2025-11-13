<?php
/**
 * Template Name: Reset Password
 * Кастомная страница установки нового пароля в стиле "Метода"
 */

// Если пользователь уже авторизован, редиректим
if (is_user_logged_in()) {
    wp_redirect(home_url('/member-dashboard/'));
    exit;
}

// Цвета Метода
$primary_color = '#0066cc';
$accent_color = '#ff6600';

// Обработка формы
$message = '';
$message_type = '';
$show_form = false;

// Проверяем параметры
$reset_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
$user_login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';

if (empty($reset_key) || empty($user_login)) {
    $message = 'Недействительная ссылка для сброса пароля.';
    $message_type = 'error';
} else {
    $user = check_password_reset_key($reset_key, $user_login);

    if (is_wp_error($user)) {
        if ($user->get_error_code() === 'expired_key') {
            $message = 'Ссылка для сброса пароля устарела. Запросите новую.';
        } else {
            $message = 'Недействительная ссылка для сброса пароля.';
        }
        $message_type = 'error';
    } else {
        $show_form = true;

        // Обработка отправки формы
        if (isset($_POST['submit_reset_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($new_password) || empty($confirm_password)) {
                $message = 'Пожалуйста, заполните все поля.';
                $message_type = 'error';
            } elseif ($new_password !== $confirm_password) {
                $message = 'Пароли не совпадают.';
                $message_type = 'error';
            } elseif (strlen($new_password) < 8) {
                $message = 'Пароль должен содержать минимум 8 символов.';
                $message_type = 'error';
            } else {
                // Устанавливаем новый пароль
                reset_password($user, $new_password);

                // Авторизуем пользователя
                wp_set_auth_cookie($user->ID);

                // Редиректим в личный кабинет
                wp_redirect(add_query_arg('password_reset', 'success', home_url('/member-dashboard/')));
                exit;
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
    <title>Установка нового пароля - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $accent_color; ?> 100%);
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            margin-top: 8px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            transition: all 0.3s;
            width: 0;
        }
        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }
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
                <i class="fas fa-lock text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Новый пароль</h1>
            <p class="text-gray-600">Введите новый пароль для вашего аккаунта</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">

            <?php if (!empty($message)): ?>
            <div class="mb-6 <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border px-4 py-3 rounded-lg flex items-start">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5 mr-3"></i>
                <span class="text-sm"><?php echo esc_html($message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
            <form id="reset-password-form" method="post" action="" class="space-y-6">

                <!-- New Password Field -->
                <div>
                    <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Новый пароль
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            required
                            minlength="8"
                            class="w-full px-4 py-3 pl-12 pr-12 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-base"
                            placeholder="Минимум 8 символов"
                            style="outline: none; --tw-ring-color: <?php echo $primary_color; ?>;"
                        >
                        <i class="fas fa-lock absolute left-4 top-4 text-gray-400"></i>
                        <i class="fas fa-eye cursor-pointer absolute right-4 top-4 text-gray-400 hover:text-gray-600" id="toggle-new-password"></i>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="password-strength-bar"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2" id="password-strength-text">Минимум 8 символов</p>
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Подтвердите пароль
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            minlength="8"
                            class="w-full px-4 py-3 pl-12 pr-12 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-base"
                            placeholder="Повторите пароль"
                            style="outline: none; --tw-ring-color: <?php echo $primary_color; ?>;"
                        >
                        <i class="fas fa-lock absolute left-4 top-4 text-gray-400"></i>
                        <i class="fas fa-eye cursor-pointer absolute right-4 top-4 text-gray-400 hover:text-gray-600" id="toggle-confirm-password"></i>
                    </div>
                    <p class="text-xs text-red-500 mt-2 hidden" id="password-match-error">Пароли не совпадают</p>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    name="submit_reset_password"
                    id="submit-button"
                    class="w-full text-white py-4 px-6 rounded-xl font-semibold text-base hover:opacity-90 transition-all shadow-lg"
                    style="background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $accent_color; ?> 100%);"
                >
                    <i class="fas fa-check mr-2"></i>
                    Установить новый пароль
                </button>

            </form>
            <?php else: ?>
                <div class="text-center py-6">
                    <a href="<?php echo home_url('/forgot-password/'); ?>" class="inline-block text-white py-3 px-6 rounded-xl font-semibold hover:opacity-90 transition-all" style="background: linear-gradient(135deg, <?php echo $primary_color; ?> 0%, <?php echo $accent_color; ?> 100%);">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Запросить новую ссылку
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Additional Links -->
        <div class="mt-6 text-center">
            <a href="<?php echo home_url('/custom-login/'); ?>" class="text-gray-600 text-sm hover:text-gray-800 inline-flex items-center font-medium">
                <i class="fas fa-arrow-left mr-2"></i>
                Назад к входу
            </a>
        </div>

    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('toggle-new-password')?.addEventListener('click', function() {
    const passwordField = document.getElementById('new_password');
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

document.getElementById('toggle-confirm-password')?.addEventListener('click', function() {
    const passwordField = document.getElementById('confirm_password');
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

// Password strength indicator
const newPasswordField = document.getElementById('new_password');
const strengthBar = document.getElementById('password-strength-bar');
const strengthText = document.getElementById('password-strength-text');

newPasswordField?.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;

    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    strengthBar.className = 'password-strength-bar';

    if (strength <= 2) {
        strengthBar.classList.add('strength-weak');
        strengthText.textContent = 'Слабый пароль';
        strengthText.className = 'text-xs text-red-500 mt-2';
    } else if (strength <= 4) {
        strengthBar.classList.add('strength-medium');
        strengthText.textContent = 'Средний пароль';
        strengthText.className = 'text-xs text-yellow-600 mt-2';
    } else {
        strengthBar.classList.add('strength-strong');
        strengthText.textContent = 'Надежный пароль';
        strengthText.className = 'text-xs text-green-600 mt-2';
    }
});

// Check password match
const confirmPasswordField = document.getElementById('confirm_password');
const matchError = document.getElementById('password-match-error');
const submitButton = document.getElementById('submit-button');

function checkPasswordMatch() {
    if (confirmPasswordField.value && newPasswordField.value !== confirmPasswordField.value) {
        matchError.classList.remove('hidden');
        submitButton.disabled = true;
        submitButton.style.opacity = '0.5';
    } else {
        matchError.classList.add('hidden');
        submitButton.disabled = false;
        submitButton.style.opacity = '1';
    }
}

newPasswordField?.addEventListener('input', checkPasswordMatch);
confirmPasswordField?.addEventListener('input', checkPasswordMatch);

// Handle form submission
document.getElementById('reset-password-form')?.addEventListener('submit', function(e) {
    const button = submitButton;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение...';
});
</script>

<?php wp_footer(); ?>
</body>
</html>
