<?php
/**
 * Template Name: Member Registration
 * Многошаговая форма регистрации участника
 */

if (!defined('ABSPATH')) exit;

// KILL SWITCH: Не редиректим если отключены редиректы
if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
    // Просто не показываем форму
    echo '<div style="padding: 20px; background: #ffeb3b; border: 2px solid #ff9800;">';
    echo '<h3>⚠️ Редиректы отключены (METODA_DISABLE_REDIRECTS)</h3>';
    if (is_user_logged_in()) {
        echo '<p>Вы уже авторизованы. <a href="' . admin_url() . '">Перейти в админку →</a></p>';
    }
    echo '</div>';
    return;
}

// Не показываем в админке
if (is_admin()) {
    return;
}

// Если пользователь уже авторизован, редирект в личный кабинет
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

// Цвета Метода
$primary_color = '#0066cc';
$accent_color = '#ff6600';

// Получаем города для выпадающего списка
global $wpdb;
$cities = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND meta_value != ''
    ORDER BY meta_value ASC
", 'member_city'));

// Получаем роли
$roles = get_terms(array(
    'taxonomy' => 'member_role',
    'hide_empty' => false
));
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация участника - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none;}
        body { font-family: 'Inter', sans-serif; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; }
        .password-strength { height: 4px; transition: all 0.3s ease; }
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-medium { background: #f59e0b; width: 50%; }
        .strength-strong { background: #10b981; width: 75%; }
        .strength-very-strong { background: #059669; width: 100%; }
        .step-indicator { position: relative; }
        .step-indicator::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: #e5e7eb; z-index: 1; }
        .step-indicator .step-item { position: relative; z-index: 2; background: white; }
        .step-item.completed { color: #059669; }
        .step-item.active { color: <?php echo $primary_color; ?>; }
        .step-item.pending { color: #9ca3af; }
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
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">

<!-- Header -->
<header id="header" class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: <?php echo $primary_color; ?>">
                    <i class="fas fa-users text-white text-sm"></i>
                </div>
                <span class="ml-3 text-xl font-bold text-gray-900">Ассоциация Метода</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="<?php echo wp_login_url(); ?>" class="text-gray-600 hover:text-gray-900 font-medium">Войти</a>
                <a href="<?php echo home_url(); ?>" class="font-medium" style="color: <?php echo $primary_color; ?>">Помощь</a>
            </div>
        </div>
    </div>
</header>

<div id="registration-container" class="min-h-screen py-12 px-4">
    <div class="max-w-4xl mx-auto">

        <!-- Step Indicator -->
        <div id="step-indicator" class="mb-12">
            <div class="step-indicator flex justify-between items-center max-w-3xl mx-auto">
                <div class="step-item active flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center mb-2" style="border-color: <?php echo $primary_color; ?>; background-color: <?php echo $primary_color; ?>">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <span class="text-sm font-medium">Данные</span>
                </div>
                <div class="step-item pending flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center mb-2">
                        <i class="fas fa-briefcase text-gray-400 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium">Работа</span>
                </div>
                <div class="step-item pending flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center mb-2">
                        <i class="fas fa-star text-gray-400 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium">Экспертиза</span>
                </div>
                <div class="step-item pending flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center mb-2">
                        <i class="fas fa-check text-gray-400 text-sm"></i>
                    </div>
                    <span class="text-sm font-medium">Готово</span>
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

            <!-- Progress Header -->
            <div id="progress-header" class="px-8 py-8" style="background: linear-gradient(to right, <?php echo $primary_color; ?>, <?php echo $accent_color; ?>)">
                <div class="flex items-center justify-between text-white mb-6 flex-col md:flex-row gap-4">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Присоединяйтесь к нам</h1>
                        <p id="step-subtitle" class="text-blue-100">Начнем с основной информации</p>
                    </div>
                    <div class="text-right">
                        <span id="step-counter" class="text-blue-100 text-sm">Шаг 1 из 4</span>
                        <div id="step-title" class="text-xl font-semibold">Аутентификация</div>
                    </div>
                </div>
                <div class="w-full bg-blue-800 rounded-full h-3">
                    <div id="progress-bar" class="bg-white h-3 rounded-full transition-all duration-700 ease-out shadow-sm" style="width: 25%"></div>
                </div>
            </div>

            <!-- Form Container -->
            <div class="p-8 lg:p-12">
                <form id="registration-wizard">

                    <!-- Step 1: Аутентификация и Базовые Данные -->
                    <div id="step-1" class="wizard-step active">
                        <div class="max-w-2xl mx-auto space-y-8">
                            <div class="text-center mb-8">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Создайте аккаунт</h2>
                                <p class="text-gray-600">Безопасная аутентификация и базовая информация профиля</p>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Email адрес</label>
                                    <div class="relative">
                                        <input type="email" id="email" name="email" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-lg" placeholder="your.email@example.com" required style="focus:ring-color: <?php echo $primary_color; ?>">
                                        <i class="fas fa-envelope absolute right-4 top-5 text-gray-400"></i>
                                    </div>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Код доступа <span class="text-gray-500 font-normal">(опционально)</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="access_code" name="access_code" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-lg uppercase" placeholder="METODA-2024-XXXXXX" style="focus:ring-color: <?php echo $primary_color; ?>">
                                        <i class="fas fa-key absolute right-4 top-5 text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-2">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Если вы уже являетесь участником ассоциации, введите ваш код доступа для активации профиля. Новым участникам оставьте поле пустым.
                                    </p>
                                    <div id="access-code-feedback" class="mt-2 text-sm hidden"></div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Пароль</label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all" placeholder="Создайте надежный пароль" required>
                                        <i class="fas fa-eye cursor-pointer absolute right-4 top-5 text-gray-400 hover:text-gray-600" onclick="togglePassword('password')"></i>
                                    </div>
                                    <div class="mt-3">
                                        <div class="password-strength bg-gray-200 rounded-full"></div>
                                        <p id="password-feedback" class="text-sm text-gray-500 mt-2">Используйте 8+ символов, буквы, цифры и символы</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Повторите пароль</label>
                                    <div class="relative">
                                        <input type="password" id="password-repeat" name="password_repeat" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all" placeholder="Повторите пароль" required>
                                        <i class="fas fa-eye cursor-pointer absolute right-4 top-5 text-gray-400 hover:text-gray-600" onclick="togglePassword('password-repeat')"></i>
                                    </div>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-4">Тип участия</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <label class="relative flex items-center p-6 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 transition-all hover:shadow-md">
                                            <input type="radio" name="account_type" value="member" class="focus:ring-blue-500" checked style="color: <?php echo $primary_color; ?>">
                                            <div class="ml-4">
                                                <div class="flex items-center mb-2">
                                                    <i class="fas fa-user-friends mr-2" style="color: <?php echo $primary_color; ?>"></i>
                                                    <div class="text-lg font-semibold text-gray-900">Участник</div>
                                                </div>
                                                <div class="text-sm text-gray-600">Присоединяйтесь как участник с доступом к ресурсам и нетворкингу</div>
                                            </div>
                                        </label>
                                        <label class="relative flex items-center p-6 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-300 transition-all hover:shadow-md">
                                            <input type="radio" name="account_type" value="expert" class="focus:ring-blue-500" style="color: <?php echo $primary_color; ?>">
                                            <div class="ml-4">
                                                <div class="flex items-center mb-2">
                                                    <i class="fas fa-graduation-cap mr-2" style="color: <?php echo $primary_color; ?>"></i>
                                                    <div class="text-lg font-semibold text-gray-900">Эксперт</div>
                                                </div>
                                                <div class="text-sm text-gray-600">Делитесь знаниями и наставляйте других участников</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">ФИО</label>
                                    <input type="text" id="fullname" name="fullname" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all text-lg" placeholder="Введите ваше полное имя" required>
                                </div>

                                <div class="lg:col-span-2">
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <div class="flex items-start">
                                            <input type="checkbox" id="terms" name="terms" class="mt-1 rounded" required style="color: <?php echo $primary_color; ?>">
                                            <label for="terms" class="ml-3 text-sm text-gray-700">
                                                Я согласен с <a href="#" style="color: <?php echo $primary_color; ?>" class="hover:underline font-medium">Условиями использования</a> и <a href="#" style="color: <?php echo $primary_color; ?>" class="hover:underline font-medium">Политикой конфиденциальности</a>. Я понимаю, что моя информация будет использована для создания профиля участника.
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Профессиональная и Контактная Информация -->
                    <div id="step-2" class="wizard-step">
                        <div class="max-w-2xl mx-auto space-y-8">
                            <div class="text-center mb-8">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Профессиональная информация</h2>
                                <p class="text-gray-600">Расскажите о вашем текущем статусе</p>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Компания</label>
                                    <div class="relative">
                                        <input type="text" id="company" name="company" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all" placeholder="Ваша текущая компания или организация">
                                        <i class="fas fa-building absolute right-4 top-5 text-gray-400"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Должность</label>
                                    <div class="relative">
                                        <input type="text" id="job-title" name="position" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all" placeholder="Ваша текущая позиция или роль">
                                        <i class="fas fa-briefcase absolute right-4 top-5 text-gray-400"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Город</label>
                                    <div class="relative">
                                        <select id="city" name="city" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all appearance-none bg-white">
                                            <option value="">Выберите город</option>
                                            <?php if (!empty($cities)): ?>
                                                <?php foreach ($cities as $city): ?>
                                                    <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <option value="Москва">Москва</option>
                                            <option value="Санкт-Петербург">Санкт-Петербург</option>
                                            <option value="Новосибирск">Новосибирск</option>
                                            <option value="Екатеринбург">Екатеринбург</option>
                                            <option value="Другой">Другой</option>
                                        </select>
                                        <i class="fas fa-map-marker-alt absolute right-4 top-5 text-gray-400"></i>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Роль в ассоциации</label>
                                    <div id="role-tags" class="space-y-3">
                                        <div class="flex flex-wrap gap-2 p-4 border border-gray-300 rounded-xl min-h-[4rem] bg-white" id="role-tags-container">
                                            <input type="text" id="role-input" class="flex-1 border-none outline-none min-w-[250px] text-gray-700" placeholder="Введите роль и нажмите Enter (например, Лидерство, Менторинг)">
                                        </div>
                                        <div class="bg-blue-50 rounded-lg p-4">
                                            <div class="text-xs font-medium text-blue-800 mb-2">Популярные роли:</div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" onclick="addRoleTag('Лидерство')" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">Лидерство</button>
                                                <button type="button" onclick="addRoleTag('Менторинг')" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">Менторинг</button>
                                                <button type="button" onclick="addRoleTag('Инновации')" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">Инновации</button>
                                                <button type="button" onclick="addRoleTag('Исследования')" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">Исследования</button>
                                                <button type="button" onclick="addRoleTag('Нетворкинг')" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm hover:bg-blue-200 transition-colors">Нетворкинг</button>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="roles-hidden" name="roles" value="">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Специализация и Интересы -->
                    <div id="step-3" class="wizard-step">
                        <div class="max-w-3xl mx-auto space-y-8">
                            <div class="text-center mb-8">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Экспертиза и интересы</h2>
                                <p class="text-gray-600">Поделитесь вашими профессиональными специализациями и интересами</p>
                            </div>

                            <div class="space-y-8">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-4">Специализация и стаж</label>
                                    <div id="specialization-list" class="space-y-4">
                                        <div class="specialization-item bg-gray-50 rounded-xl p-6">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-2">Специализация</label>
                                                    <input type="text" class="specialization-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent" placeholder="например, Frontend разработка">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-2">Стаж</label>
                                                    <input type="text" class="experience-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent" placeholder="например, 5 лет">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addSpecialization()" class="mt-4 flex items-center hover:underline font-medium transition-colors" style="color: <?php echo $primary_color; ?>">
                                        <i class="fas fa-plus-circle mr-2"></i>Добавить еще специализацию
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-4">Профессиональные интересы</label>
                                    <div id="interests-list" class="space-y-4">
                                        <div class="interest-item bg-gray-50 rounded-xl p-6">
                                            <label class="block text-xs font-medium text-gray-600 mb-2">Сфера интересов</label>
                                            <input type="text" class="interest-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent" placeholder="например, Искусственный интеллект, Устойчивые технологии">
                                        </div>
                                    </div>
                                    <button type="button" onclick="addInterest()" class="mt-4 flex items-center hover:underline font-medium transition-colors" style="color: <?php echo $primary_color; ?>">
                                        <i class="fas fa-plus-circle mr-2"></i>Добавить еще интерес
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: О себе и Ожидания -->
                    <div id="step-4" class="wizard-step">
                        <div class="max-w-3xl mx-auto space-y-8">
                            <div class="text-center mb-8">
                                <h2 class="text-2xl font-bold text-gray-900 mb-2">Расскажите о себе</h2>
                                <p class="text-gray-600">Поделитесь своей историей и целями</p>
                            </div>

                            <div class="space-y-8">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">О себе</label>
                                    <div class="relative">
                                        <textarea id="about-me" name="bio" rows="8" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all resize-none" placeholder="Расскажите о себе, вашем опыте, достижениях и том, что вы привносите в ассоциацию. Поделитесь вашим профессиональным путем, ключевыми достижениями и тем, что делает вас уникальным..."></textarea>
                                        <div class="absolute bottom-4 right-4 text-xs text-gray-400">
                                            <span id="about-count">0</span>/500 символов
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-2">
                                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                        Совет: Укажите ваш опыт, ключевые навыки и что вас мотивирует профессионально
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Ожидания от сотрудничества</label>
                                    <div class="relative">
                                        <textarea id="expectations" name="expectations" rows="8" class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:border-transparent transition-all resize-none" placeholder="Что вы надеетесь достичь через эту ассоциацию? Какие у вас цели, какое сотрудничество вы ищете, и как вы планируете внести вклад в сообщество..."></textarea>
                                        <div class="absolute bottom-4 right-4 text-xs text-gray-400">
                                            <span id="expectations-count">0</span>/500 символов
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="text-sm text-gray-600">
                                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                                            Совет: Укажите конкретные цели, типы сотрудничества и как вы внесете вклад
                                        </div>
                                        <button type="button" class="hover:underline font-medium transition-colors" style="color: <?php echo $primary_color; ?>">
                                            <i class="fas fa-clock mr-2"></i>Заполнить позже
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Success Screen -->
                    <div id="success-screen" class="wizard-step text-center py-16">
                        <div class="max-w-lg mx-auto">
                            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-8">
                                <i class="fas fa-check text-4xl text-green-600"></i>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">Регистрация завершена!</h2>
                            <p class="text-lg text-gray-600 mb-8">Ваш профиль успешно создан и отправлен на модерацию. Вы получите письмо с подтверждением и дальнейшими инструкциями.</p>
                            <div class="space-y-4">
                                <a href="<?php echo home_url('/member-dashboard/'); ?>" class="block w-full text-white px-8 py-4 rounded-xl font-semibold hover:opacity-90 transition-colors" style="background-color: <?php echo $primary_color; ?>">
                                    <i class="fas fa-user-circle mr-2"></i>Перейти в личный кабинет
                                </a>
                                <a href="<?php echo home_url(); ?>" class="block w-full bg-gray-100 text-gray-700 px-8 py-4 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-home mr-2"></i>На главную страницу
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div id="wizard-navigation" class="flex justify-between items-center mt-12 pt-8 border-t border-gray-200">
                        <button type="button" id="prev-btn" class="flex items-center px-6 py-3 text-gray-600 font-semibold hover:text-gray-800 transition-colors" style="display: none;" onclick="prevStep()">
                            <i class="fas fa-arrow-left mr-2"></i>Назад
                        </button>
                        <div class="flex-1"></div>
                        <button type="button" id="next-btn" class="text-white px-8 py-4 rounded-xl font-semibold hover:opacity-90 transition-colors shadow-lg" onclick="nextStep()" style="background-color: <?php echo $primary_color; ?>">
                            Продолжить<i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentStep = 1;
const totalSteps = 4;
const roleTags = [];

const stepInfo = [
    { title: 'Аутентификация', subtitle: 'Начнем с основной информации' },
    { title: 'Профессия', subtitle: 'Расскажите о вашей текущей роли и локации' },
    { title: 'Экспертиза', subtitle: 'Поделитесь специализациями и интересами' },
    { title: 'О вас', subtitle: 'Расскажите вашу историю и ожидания' }
];

function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.getElementById('progress-bar').style.width = progress + '%';
    document.getElementById('step-counter').textContent = `Шаг ${currentStep} из ${totalSteps}`;
    document.getElementById('step-title').textContent = stepInfo[currentStep - 1].title;
    document.getElementById('step-subtitle').textContent = stepInfo[currentStep - 1].subtitle;

    document.querySelectorAll('.step-item').forEach((item, index) => {
        item.classList.remove('active', 'completed', 'pending');
        const circle = item.querySelector('div');
        if (index < currentStep - 1) {
            item.classList.add('completed');
            circle.className = 'w-10 h-10 rounded-full border-2 border-green-600 bg-green-600 flex items-center justify-center mb-2';
        } else if (index === currentStep - 1) {
            item.classList.add('active');
            circle.className = 'w-10 h-10 rounded-full border-2 border-blue-600 bg-blue-600 flex items-center justify-center mb-2';
        } else {
            item.classList.add('pending');
            circle.className = 'w-10 h-10 rounded-full border-2 border-gray-300 bg-white flex items-center justify-center mb-2';
        }
    });
}

function showStep(step) {
    document.querySelectorAll('.wizard-step').forEach(s => s.classList.remove('active'));
    document.getElementById(`step-${step}`).classList.add('active');

    document.getElementById('prev-btn').style.display = step > 1 ? 'flex' : 'none';

    const nextBtn = document.getElementById('next-btn');
    if (step === totalSteps) {
        nextBtn.innerHTML = 'Завершить регистрацию<i class="fas fa-check ml-2"></i>';
    } else {
        nextBtn.innerHTML = 'Продолжить<i class="fas fa-arrow-right ml-2"></i>';
    }
}

function nextStep() {
    if (currentStep < totalSteps) {
        // Валидация текущего шага
        if (!validateStep(currentStep)) {
            return;
        }
        currentStep++;
        showStep(currentStep);
        updateProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        // Отправка формы
        submitRegistration();
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateProgress();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function validateStep(step) {
    switch(step) {
        case 1:
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const passwordRepeat = document.getElementById('password-repeat').value;
            const fullname = document.getElementById('fullname').value;
            const terms = document.getElementById('terms').checked;

            if (!email || !password || !fullname) {
                alert('Пожалуйста, заполните все обязательные поля');
                return false;
            }
            if (password !== passwordRepeat) {
                alert('Пароли не совпадают');
                return false;
            }
            if (password.length < 8) {
                alert('Пароль должен содержать минимум 8 символов');
                return false;
            }
            if (!terms) {
                alert('Необходимо согласиться с условиями использования');
                return false;
            }
            break;
    }
    return true;
}

function submitRegistration() {
    const btn = document.getElementById('next-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Регистрация...';

    // Собираем специализации
    const specializations = [];
    document.querySelectorAll('.specialization-item').forEach(item => {
        const spec = item.querySelector('.specialization-field').value;
        const exp = item.querySelector('.experience-field').value;
        if (spec) {
            specializations.push(`${spec} - ${exp}`);
        }
    });

    // Собираем интересы
    const interests = [];
    document.querySelectorAll('.interest-item').forEach(item => {
        const interest = item.querySelector('.interest-field').value;
        if (interest) {
            interests.push(interest);
        }
    });

    const formData = new FormData();
    formData.append('action', 'member_register');
    formData.append('nonce', '<?php echo wp_create_nonce("member_registration"); ?>');
    formData.append('email', document.getElementById('email').value);
    formData.append('password', document.getElementById('password').value);
    formData.append('fullname', document.getElementById('fullname').value);
    formData.append('account_type', document.querySelector('input[name="account_type"]:checked').value);
    formData.append('company', document.getElementById('company').value);
    formData.append('position', document.getElementById('job-title').value);
    formData.append('city', document.getElementById('city').value);
    formData.append('roles', roleTags.join(','));
    formData.append('specializations', specializations.join('\n'));
    formData.append('interests', interests.join('\n'));
    formData.append('bio', document.getElementById('about-me').value);
    formData.append('expectations', document.getElementById('expectations').value);
    formData.append('access_code', document.getElementById('access_code').value);

    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('wizard-navigation').style.display = 'none';
            document.getElementById('progress-header').style.display = 'none';
            document.getElementById('step-indicator').style.display = 'none';
            document.getElementById('success-screen').classList.add('active');
        } else {
            alert(data.data.message || 'Ошибка регистрации. Попробуйте позже.');
            btn.disabled = false;
            btn.innerHTML = 'Завершить регистрацию<i class="fas fa-check ml-2"></i>';
        }
    })
    .catch(error => {
        alert('Произошла ошибка. Попробуйте позже.');
        btn.disabled = false;
        btn.innerHTML = 'Завершить регистрацию<i class="fas fa-check ml-2"></i>';
    });
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling;
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthBar = document.querySelector('.password-strength');
    const feedback = document.getElementById('password-feedback');

    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    strengthBar.className = 'password-strength';
    if (strength === 1) {
        strengthBar.classList.add('strength-weak');
        feedback.textContent = 'Слабый пароль';
        feedback.className = 'text-sm text-red-500 mt-2';
    } else if (strength === 2) {
        strengthBar.classList.add('strength-medium');
        feedback.textContent = 'Средняя надежность';
        feedback.className = 'text-sm text-orange-500 mt-2';
    } else if (strength === 3) {
        strengthBar.classList.add('strength-strong');
        feedback.textContent = 'Надежный пароль';
        feedback.className = 'text-sm text-green-600 mt-2';
    } else if (strength === 4) {
        strengthBar.classList.add('strength-very-strong');
        feedback.textContent = 'Очень надежный пароль';
        feedback.className = 'text-sm text-green-700 mt-2';
    }
});

// Role tags
document.getElementById('role-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const value = this.value.trim();
        if (value && !roleTags.includes(value)) {
            addRoleTag(value);
            this.value = '';
        }
    }
});

function addRoleTag(tagName) {
    if (!roleTags.includes(tagName)) {
        roleTags.push(tagName);
        const container = document.getElementById('role-tags-container');
        const input = document.getElementById('role-input');
        const tag = document.createElement('span');
        tag.className = 'inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-full text-sm';
        tag.innerHTML = `${tagName}<i class="fas fa-times ml-2 cursor-pointer" onclick="removeRoleTag('${tagName}', this)"></i>`;
        container.insertBefore(tag, input);
        document.getElementById('roles-hidden').value = roleTags.join(',');
    }
}

function removeRoleTag(tagName, element) {
    const index = roleTags.indexOf(tagName);
    if (index > -1) {
        roleTags.splice(index, 1);
    }
    element.parentElement.remove();
    document.getElementById('roles-hidden').value = roleTags.join(',');
}

function addSpecialization() {
    const list = document.getElementById('specialization-list');
    const item = document.createElement('div');
    item.className = 'specialization-item bg-gray-50 rounded-xl p-6 relative';
    item.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors">
            <i class="fas fa-times"></i>
        </button>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Специализация</label>
                <input type="text" class="specialization-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent" placeholder="например, Frontend разработка">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Стаж</label>
                <input type="text" class="experience-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent" placeholder="например, 5 лет">
            </div>
        </div>
    `;
    list.appendChild(item);
}

function addInterest() {
    const list = document.getElementById('interests-list');
    const item = document.createElement('div');
    item.className = 'interest-item bg-gray-50 rounded-xl p-6 relative';
    item.innerHTML = `
        <button type="button" onclick="this.parentElement.remove()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors">
            <i class="fas fa-times"></i>
        </button>
        <label class="block text-xs font-medium text-gray-600 mb-2">Сфера интересов</label>
        <input type="text" class="interest-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent" placeholder="например, Искусственный интеллект, Устойчивые технологии">
    `;
    list.appendChild(item);
}

// Character counters
document.getElementById('about-me').addEventListener('input', function(e) {
    document.getElementById('about-count').textContent = e.target.value.length;
});

document.getElementById('expectations').addEventListener('input', function(e) {
    document.getElementById('expectations-count').textContent = e.target.value.length;
});

window.addEventListener('load', function() {
    updateProgress();
});

// Access code validation
let accessCodeTimeout;
document.getElementById('access_code').addEventListener('input', function(e) {
    const code = e.target.value.trim().toUpperCase();
    const feedback = document.getElementById('access-code-feedback');

    // Update input to uppercase
    this.value = code;

    // Clear previous timeout
    clearTimeout(accessCodeTimeout);

    // Hide feedback if empty
    if (!code) {
        feedback.classList.add('hidden');
        return;
    }

    // Show loading
    feedback.classList.remove('hidden');
    feedback.className = 'mt-2 text-sm text-gray-500';
    feedback.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Проверка кода...';

    // Debounce validation
    accessCodeTimeout = setTimeout(function() {
        const formData = new FormData();
        formData.append('action', 'validate_access_code');
        formData.append('code', code);

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                feedback.className = 'mt-2 text-sm text-green-600';
                feedback.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.data.message;
            } else {
                feedback.className = 'mt-2 text-sm text-red-600';
                feedback.innerHTML = '<i class="fas fa-times-circle mr-2"></i>' + data.data.message;
            }
        })
        .catch(error => {
            feedback.className = 'mt-2 text-sm text-red-600';
            feedback.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Ошибка проверки кода';
        });
    }, 500);
});
</script>

<?php wp_footer(); ?>
</body>
</html>
