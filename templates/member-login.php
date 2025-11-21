<?php
/**
 * Template Name: Вход для участников
 * Страница входа с тремя вариантами: пароль, access code, OTP
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

// Если пользователь уже авторизован
$is_logged_in = is_user_logged_in();
$current_user = $is_logged_in ? wp_get_current_user() : null;

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — <?php bloginfo('name'); ?></title>
    <?php metoda_enqueue_frontend_styles(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <style>
        ::-webkit-scrollbar { display: none; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #0066cc 0%, #ff6600 100%);
        }
    </style>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Вход для участников</h1>
            <p class="text-gray-600">Выберите способ входа в личный кабинет</p>
        </div>

        <?php if ($is_logged_in): ?>
            <!-- Сообщение для авторизованных пользователей -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-start mb-4">
                        <i class="fas fa-info-circle text-blue-500 text-xl mr-3 mt-1"></i>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Вы уже авторизованы</h3>
                            <p class="text-sm text-gray-700 mb-1">Здравствуйте, <strong><?php echo esc_html($current_user->display_name); ?></strong>!</p>
                            <p class="text-sm text-gray-600">Вы можете перейти в свой профиль или выйти из системы.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 mt-4">
                        <?php if (current_user_can('administrator') || current_user_can('manage_options')): ?>
                            <a href="<?php echo admin_url(); ?>" class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:opacity-90 transition-all">
                                <i class="fas fa-cog mr-2"></i>
                                Перейти в админку
                            </a>
                        <?php elseif (in_array('manager', $current_user->roles)): ?>
                            <a href="<?php echo home_url('/manager-panel/'); ?>" class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:opacity-90 transition-all">
                                <i class="fas fa-users-cog mr-2"></i>
                                Панель менеджера
                            </a>
                        <?php else: ?>
                            <a href="<?php echo home_url('/member-dashboard/'); ?>" class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:opacity-90 transition-all">
                                <i class="fas fa-user mr-2"></i>
                                Личный кабинет
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo wp_logout_url(home_url('/member-login/')); ?>" class="inline-flex items-center justify-center px-4 py-3 border-2 border-red-500 text-red-600 rounded-xl hover:bg-red-50 transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Выйти из системы
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Login Methods Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">

                <!-- Method Tabs -->
                <div class="flex gap-2 mb-6 p-1 bg-gray-100 rounded-xl">
                    <button class="login-method-tab flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all active" data-method="password">
                        Пароль
                    </button>
                    <button class="login-method-tab flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all" data-method="access-code">
                        Код доступа
                    </button>
                    <button class="login-method-tab flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all" data-method="otp">
                        Код на почту
                    </button>
                </div>

                <!-- Вариант 1: Обычный вход по паролю -->
                <form id="login-password" class="login-method-form active" method="post" action="<?php echo wp_login_url(); ?>">
                    <div class="space-y-4">
                        <div>
                            <label for="password-email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                type="email"
                                id="password-email"
                                name="log"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="your.email@example.com"
                            >
                        </div>

                        <div>
                            <label for="password-pass" class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                            <input
                                type="password"
                                id="password-pass"
                                name="pwd"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="Введите пароль"
                            >
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input type="checkbox" name="rememberme" value="forever" class="w-4 h-4 rounded text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Запомнить меня</span>
                            </label>
                            <a href="<?php echo home_url('/forgot-password/'); ?>" class="text-sm text-primary hover:underline">
                                Забыли пароль?
                            </a>
                        </div>

                        <button
                            type="submit"
                            name="wp-submit"
                            class="w-full gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        >
                            Войти
                        </button>

                        <input type="hidden" name="redirect_to" value="<?php echo home_url('/member-dashboard/'); ?>">
                    </div>
                </form>

                <!-- Вариант 2: Вход по коду доступа -->
                <form id="login-access-code" class="login-method-form hidden">
                    <div class="space-y-4">
                        <div>
                            <label for="access-code-input" class="block text-sm font-medium text-gray-700 mb-2">Код доступа</label>
                            <input
                                type="text"
                                id="access-code-input"
                                name="access_code"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all uppercase text-center text-lg tracking-widest"
                                placeholder="ABC123XYZ"
                                maxlength="20"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Введите код доступа, который вы получили от администратора
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="w-full gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        >
                            Продолжить
                        </button>
                    </div>
                </form>

                <!-- Вариант 3: Вход по OTP (код на почту) -->
                <form id="login-otp" class="login-method-form hidden">
                    <div class="space-y-4" id="otp-email-step">
                        <div>
                            <label for="otp-email-input" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                type="email"
                                id="otp-email-input"
                                name="otp_email"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                                placeholder="your.email@example.com"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Мы отправим одноразовый код на вашу почту
                            </p>
                        </div>

                        <button
                            type="button"
                            id="send-otp-btn"
                            class="w-full gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        >
                            Получить код
                        </button>
                    </div>

                    <div class="space-y-4 hidden" id="otp-code-step">
                        <div>
                            <label for="otp-code-input" class="block text-sm font-medium text-gray-700 mb-2">Код из письма</label>
                            <input
                                type="text"
                                id="otp-code-input"
                                name="otp_code"
                                maxlength="6"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all text-center text-2xl tracking-widest"
                                placeholder="000000"
                                pattern="\d{6}"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-clock mr-1"></i>
                                Код действителен 10 минут
                            </p>
                        </div>

                        <button
                            type="submit"
                            id="verify-otp-btn"
                            class="w-full gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        >
                            Войти
                        </button>

                        <button
                            type="button"
                            id="resend-otp-btn"
                            class="w-full bg-gray-100 text-gray-700 py-3 px-6 rounded-xl font-medium hover:bg-gray-200 transition-all"
                        >
                            Отправить код ещё раз
                        </button>
                    </div>
                </form>

                <!-- Error/Success Messages -->
                <div id="login-message" class="hidden mt-4"></div>

            </div>
        <?php endif; ?>

        <!-- Back to Home -->
        <div class="mt-6 text-center">
            <a href="<?php echo home_url(); ?>" class="text-gray-500 text-sm hover:text-gray-700 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                На главную страницу
            </a>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Method switching
    const tabs = document.querySelectorAll('.login-method-tab');
    const forms = document.querySelectorAll('.login-method-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const method = this.dataset.method;

            // Update tabs
            tabs.forEach(t => t.classList.remove('active', 'bg-white', 'text-primary', 'shadow-sm'));
            tabs.forEach(t => t.classList.add('text-gray-600'));
            this.classList.add('active', 'bg-white', 'text-primary', 'shadow-sm');
            this.classList.remove('text-gray-600');

            // Update forms
            forms.forEach(f => {
                f.classList.add('hidden');
                f.classList.remove('active');
            });

            const targetForm = document.getElementById('login-' + method);
            if (targetForm) {
                targetForm.classList.remove('hidden');
                targetForm.classList.add('active');
            }
        });
    });

    // Set initial active state
    tabs[0].classList.add('bg-white', 'text-primary', 'shadow-sm');
    tabs[0].classList.remove('text-gray-600');

    // Access Code form submission
    const accessCodeForm = document.getElementById('login-access-code');
    if (accessCodeForm) {
        accessCodeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const code = document.getElementById('access-code-input').value.trim();

            if (!code) {
                showMessage('error', 'Введите код доступа');
                return;
            }

            // Validate code via AJAX
            validateAccessCode(code);
        });
    }

    // OTP: Send code
    const sendOtpBtn = document.getElementById('send-otp-btn');
    if (sendOtpBtn) {
        sendOtpBtn.addEventListener('click', function() {
            const email = document.getElementById('otp-email-input').value.trim();

            if (!email) {
                showMessage('error', 'Введите email');
                return;
            }

            sendOTP(email);
        });
    }

    // OTP: Verify code
    const otpForm = document.getElementById('login-otp');
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('otp-email-input').value.trim();
            const code = document.getElementById('otp-code-input').value.trim();

            if (!email || !code) {
                showMessage('error', 'Заполните все поля');
                return;
            }

            verifyOTP(email, code);
        });
    }

    // OTP: Resend code
    const resendOtpBtn = document.getElementById('resend-otp-btn');
    if (resendOtpBtn) {
        resendOtpBtn.addEventListener('click', function() {
            const email = document.getElementById('otp-email-input').value.trim();
            sendOTP(email);
        });
    }

    // Functions
    function validateAccessCode(code) {
        const btn = accessCodeForm.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Проверка...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'validate_access_code',
                nonce: '<?php echo wp_create_nonce('member_login_nonce'); ?>',
                code: code
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Redirect to onboarding with member data
                const params = new URLSearchParams({
                    member_id: data.data.member_id,
                    member_name: data.data.member_name,
                    member_photo: data.data.member_photo || '',
                    member_company: data.data.member_company || '',
                    member_position: data.data.member_position || ''
                });
                window.location.href = '<?php echo home_url('/member-onboarding/'); ?>?' + params.toString();
            } else {
                showMessage('error', data.data.message);
                btn.disabled = false;
                btn.textContent = 'Продолжить';
            }
        })
        .catch(err => {
            showMessage('error', 'Ошибка соединения');
            btn.disabled = false;
            btn.textContent = 'Продолжить';
        });
    }

    function sendOTP(email) {
        const btn = sendOtpBtn;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Отправка...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'send_otp_code',
                nonce: '<?php echo wp_create_nonce('member_login_nonce'); ?>',
                email: email
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.data.message);
                document.getElementById('otp-email-step').classList.add('hidden');
                document.getElementById('otp-code-step').classList.remove('hidden');
            } else {
                showMessage('error', data.data.message);
                btn.disabled = false;
                btn.textContent = 'Получить код';
            }
        })
        .catch(err => {
            showMessage('error', 'Ошибка соединения');
            btn.disabled = false;
            btn.textContent = 'Получить код';
        });
    }

    function verifyOTP(email, code) {
        const btn = document.getElementById('verify-otp-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Вход...';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'verify_otp_login',
                nonce: '<?php echo wp_create_nonce('member_login_nonce'); ?>',
                email: email,
                otp: code
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('success', data.data.message);
                setTimeout(() => {
                    window.location.href = data.data.redirect;
                }, 1000);
            } else {
                showMessage('error', data.data.message);
                btn.disabled = false;
                btn.textContent = 'Войти';
            }
        })
        .catch(err => {
            showMessage('error', 'Ошибка соединения');
            btn.disabled = false;
            btn.textContent = 'Войти';
        });
    }

    function showMessage(type, message) {
        const msgEl = document.getElementById('login-message');
        msgEl.className = 'mt-4 px-4 py-3 rounded-lg flex items-start';

        if (type === 'error') {
            msgEl.classList.add('bg-red-50', 'border', 'border-red-200', 'text-red-700');
            msgEl.innerHTML = '<i class="fas fa-exclamation-circle mt-0.5 mr-3"></i><span class="text-sm">' + message + '</span>';
        } else {
            msgEl.classList.add('bg-green-50', 'border', 'border-green-200', 'text-green-700');
            msgEl.innerHTML = '<i class="fas fa-check-circle mt-0.5 mr-3"></i><span class="text-sm">' + message + '</span>';
        }

        msgEl.classList.remove('hidden');

        if (type === 'success') {
            setTimeout(() => msgEl.classList.add('hidden'), 5000);
        }
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
