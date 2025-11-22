<?php
/**
 * Template Name: Онбординг участника
 * Пошаговая регистрация после ввода кода доступа
 * Стиль: Apple minimalism — эмоционально тёплый, лаконичный
 */

// Получаем данные участника из URL
$member_id = intval($_GET['member_id'] ?? 0);
$member_name = sanitize_text_field($_GET['member_name'] ?? '');
$member_photo = esc_url($_GET['member_photo'] ?? '');
$member_company = sanitize_text_field($_GET['member_company'] ?? '');
$member_position = sanitize_text_field($_GET['member_position'] ?? '');

// Если нет member_id — редирект на логин
if (!$member_id) {
    wp_redirect(home_url('/member-login/'));
    exit;
}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добро пожаловать — <?php bloginfo('name'); ?></title>
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
            overflow-x: hidden;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #0066cc 0%, #ff6600 100%);
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #e5e7eb;
            z-index: 1000;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0066cc 0%, #ff6600 100%);
            transition: width 0.4s ease;
        }

        /* Steps */
        .onboarding-step {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .onboarding-step.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Photo */
        .member-photo {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Input focus glow */
        input:focus {
            outline: none;
            ring: 2px;
            ring-color: #0066cc;
            border-color: transparent;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

<!-- Progress Bar -->
<div class="progress-bar">
    <div class="progress-fill" id="progress-fill" style="width: 0%;"></div>
</div>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <!-- ШАГ 0: Приветствие -->
        <div class="onboarding-step active" id="step-welcome" data-step="0">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 p-8 text-center">
                <?php if ($member_photo): ?>
                    <img src="<?php echo $member_photo; ?>" alt="<?php echo esc_attr($member_name); ?>" class="member-photo mx-auto mb-6">
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Здравствуйте, <?php echo esc_html($member_name); ?>
                </h1>

                <?php if ($member_position && $member_company): ?>
                    <p class="text-gray-600 mb-6">
                        <?php echo esc_html($member_position); ?>, <?php echo esc_html($member_company); ?>
                    </p>
                <?php endif; ?>

                <div class="my-8 p-6 bg-blue-50 rounded-2xl">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        Добро пожаловать в ассоциацию МЕТОДА
                    </h2>
                    <p class="text-gray-600">
                        Осталось пара шагов, чтобы всё заработало
                    </p>
                </div>

                <button
                    type="button"
                    class="w-full gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                    onclick="nextStep()"
                >
                    Начать
                </button>
            </div>
        </div>

        <!-- ШАГ 1: Email -->
        <div class="onboarding-step" id="step-email" data-step="1">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Как с вами связаться?
                    </h2>
                    <p class="text-gray-600">
                        Укажите почту — будем присылать только важное
                    </p>
                </div>

                <div class="space-y-4">
                    <input
                        type="email"
                        id="email-input"
                        required
                        class="w-full px-4 py-4 text-lg border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="your.email@example.com"
                    >
                    <p id="email-error" class="text-sm text-red-600 hidden"></p>
                </div>

                <div class="flex gap-3 mt-8">
                    <button
                        type="button"
                        class="flex-1 bg-gray-100 text-gray-700 py-4 px-6 rounded-xl font-medium hover:bg-gray-200 transition-all"
                        onclick="prevStep()"
                    >
                        Назад
                    </button>
                    <button
                        type="button"
                        class="flex-1 gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        onclick="validateEmail()"
                    >
                        Продолжить
                    </button>
                </div>
            </div>
        </div>

        <!-- ШАГ 2: Пароль -->
        <div class="onboarding-step" id="step-password" data-step="2">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Придумайте пароль
                    </h2>
                    <p class="text-gray-600">
                        Минимум 8 символов. Надёжный — значит спокойный
                    </p>
                </div>

                <div class="space-y-4">
                    <input
                        type="password"
                        id="password-input"
                        required
                        class="w-full px-4 py-4 text-lg border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Введите пароль"
                    >

                    <input
                        type="password"
                        id="password-confirm"
                        required
                        class="w-full px-4 py-4 text-lg border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                        placeholder="Повторите пароль"
                    >

                    <p id="password-error" class="text-sm text-red-600 hidden"></p>
                </div>

                <div class="flex gap-3 mt-8">
                    <button
                        type="button"
                        class="flex-1 bg-gray-100 text-gray-700 py-4 px-6 rounded-xl font-medium hover:bg-gray-200 transition-all"
                        onclick="prevStep()"
                    >
                        Назад
                    </button>
                    <button
                        type="button"
                        class="flex-1 gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        onclick="validatePassword()"
                    >
                        Продолжить
                    </button>
                </div>
            </div>
        </div>

        <!-- ШАГ 3: Способ входа -->
        <div class="onboarding-step" id="step-method" data-step="3">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 p-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        Как будете заходить?
                    </h2>
                    <p class="text-gray-600">
                        Можно изменить потом в настройках
                    </p>
                </div>

                <div class="space-y-4">
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary transition-all">
                        <input type="radio" name="login_method" value="password" checked class="mt-1 w-5 h-5 text-primary">
                        <div class="ml-4">
                            <div class="font-semibold text-gray-900">По паролю</div>
                            <div class="text-sm text-gray-600">Классика, всегда работает</div>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary transition-all">
                        <input type="radio" name="login_method" value="otp" class="mt-1 w-5 h-5 text-primary">
                        <div class="ml-4">
                            <div class="font-semibold text-gray-900">Код на почту</div>
                            <div class="text-sm text-gray-600">Каждый раз новый, не надо запоминать</div>
                        </div>
                    </label>
                </div>

                <div class="flex gap-3 mt-8">
                    <button
                        type="button"
                        class="flex-1 bg-gray-100 text-gray-700 py-4 px-6 rounded-xl font-medium hover:bg-gray-200 transition-all"
                        onclick="prevStep()"
                    >
                        Назад
                    </button>
                    <button
                        type="button"
                        id="complete-btn"
                        class="flex-1 gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                        onclick="completeOnboarding()"
                    >
                        Завершить
                    </button>
                </div>
            </div>
        </div>

        <!-- ШАГ 4: Финал -->
        <div class="onboarding-step" id="step-complete" data-step="4">
            <div class="bg-white rounded-3xl shadow-2xl border border-gray-200 p-8 text-center">
                <div class="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-green-600 text-3xl"></i>
                </div>

                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    Готово!
                </h2>
                <p class="text-gray-600 mb-8">
                    Ваш кабинет ждёт
                </p>

                <button
                    type="button"
                    class="w-full gradient-bg text-white py-4 px-6 rounded-xl font-semibold hover:opacity-90 transition-all shadow-lg"
                    onclick="window.location.href='<?php echo home_url('/member-dashboard/'); ?>'"
                >
                    Перейти в кабинет
                </button>
            </div>
        </div>

    </div>
</div>

<script>
// Onboarding state
let currentStep = 0;
const totalSteps = 4;
const onboardingData = {
    member_id: <?php echo $member_id; ?>,
    email: '',
    password: '',
    password_confirm: '',
    login_method: 'password'
};

// Update progress bar
function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.getElementById('progress-fill').style.width = progress + '%';
}

// Next step
function nextStep() {
    const steps = document.querySelectorAll('.onboarding-step');
    if (currentStep < totalSteps) {
        steps[currentStep].classList.remove('active');
        currentStep++;
        steps[currentStep].classList.add('active');
        updateProgress();
        window.scrollTo(0, 0);
    }
}

// Previous step
function prevStep() {
    const steps = document.querySelectorAll('.onboarding-step');
    if (currentStep > 0) {
        steps[currentStep].classList.remove('active');
        currentStep--;
        steps[currentStep].classList.add('active');
        updateProgress();
        window.scrollTo(0, 0);
    }
}

// Validate email
function validateEmail() {
    const email = document.getElementById('email-input').value.trim();
    const errorEl = document.getElementById('email-error');

    if (!email) {
        errorEl.textContent = 'Введите email';
        errorEl.classList.remove('hidden');
        return;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        errorEl.textContent = 'Некорректный email адрес';
        errorEl.classList.remove('hidden');
        return;
    }

    errorEl.classList.add('hidden');
    onboardingData.email = email;
    nextStep();
}

// Validate password
function validatePassword() {
    const password = document.getElementById('password-input').value;
    const confirm = document.getElementById('password-confirm').value;
    const errorEl = document.getElementById('password-error');

    if (password.length < 8) {
        errorEl.textContent = 'Пароль должен содержать минимум 8 символов';
        errorEl.classList.remove('hidden');
        return;
    }

    if (password !== confirm) {
        errorEl.textContent = 'Пароли не совпадают';
        errorEl.classList.remove('hidden');
        return;
    }

    errorEl.classList.add('hidden');
    onboardingData.password = password;
    onboardingData.password_confirm = confirm;
    nextStep();
}

// Complete onboarding
function completeOnboarding() {
    const loginMethod = document.querySelector('input[name="login_method"]:checked').value;
    onboardingData.login_method = loginMethod;

    const btn = document.getElementById('complete-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Завершение...';

    // Send data to server
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'member_complete_onboarding',
            nonce: '<?php echo wp_create_nonce('member_login_nonce'); ?>',
            member_id: onboardingData.member_id,
            email: onboardingData.email,
            password: onboardingData.password,
            password_confirm: onboardingData.password_confirm,
            login_method: onboardingData.login_method
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            nextStep(); // Показываем финальный экран
            // Через 2 секунды редирект
            setTimeout(() => {
                window.location.href = data.data.redirect;
            }, 2000);
        } else {
            alert('Ошибка: ' + data.data.message);
            btn.disabled = false;
            btn.textContent = 'Завершить';
        }
    })
    .catch(err => {
        alert('Ошибка соединения');
        btn.disabled = false;
        btn.textContent = 'Завершить';
    });
}

// Initialize
updateProgress();
</script>

<?php wp_footer(); ?>
</body>
</html>
