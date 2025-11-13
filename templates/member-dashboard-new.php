<?php
/**
 * Template: Member Dashboard (New Design)
 * Обновленный личный кабинет участника с фирменным дизайном "Метода"
 */

if (!defined('ABSPATH')) exit;

// Получаем ID участника
$user_id = get_current_user_id();
$member_id = get_user_meta($user_id, 'member_id', true);

if (!$member_id) {
    echo '<div class="bg-white rounded-xl shadow-sm border p-12 text-center">
        <i class="fas fa-user-slash text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Профиль не найден</h3>
        <p class="text-gray-600">Ваш профиль участника не создан.</p>
    </div>';
    return;
}

// Получаем данные участника
$member_data = Member_Dashboard::get_member_data($member_id);

if (!$member_data) {
    return;
}

// Цвета Метода
$primary_color = '#0066cc';
$accent_color = '#ff6600';

$current_user = wp_get_current_user();
$member_roles = wp_get_post_terms($member_id, 'member_role');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo esc_html($member_data['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { display: none; }
        .metoda-primary { color: <?php echo $primary_color; ?>; }
        .metoda-primary-bg { background-color: <?php echo $primary_color; ?>; }
        .metoda-accent-bg { background-color: <?php echo $accent_color; ?>; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $primary_color; ?>',
                        accent: '<?php echo $accent_color; ?>',
                        secondary: '#64748b',
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444',
                    }
                }
            }
        }
    </script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

<div id="admin-panel" class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 metoda-primary-bg rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Метода</h1>
                    <p class="text-xs text-gray-500">Личный кабинет</p>
                </div>
            </div>
        </div>

        <nav id="main-nav" class="flex-1 p-4">
            <ul class="space-y-1">
                <li>
                    <button onclick="showSection('profile')" id="nav-profile" class="nav-item active w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg bg-blue-50 metoda-primary font-medium transition-all">
                        <i class="fas fa-user-circle text-lg"></i>
                        <span>Мой профиль</span>
                    </button>
                </li>
                <li>
                    <button onclick="showSection('materials')" id="nav-materials" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg text-gray-600 hover:bg-gray-50 font-medium transition-all">
                        <i class="fas fa-folder-open text-lg"></i>
                        <span>Мои материалы</span>
                    </button>
                </li>
                <li>
                    <a href="<?php echo esc_url($member_data['permalink']); ?>" target="_blank" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg text-gray-600 hover:bg-gray-50 font-medium transition-all">
                        <i class="fas fa-eye text-lg"></i>
                        <span>Просмотр профиля</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div id="sidebar-footer" class="p-4 border-t border-gray-200">
            <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                <?php if ($member_data['thumbnail_url']): ?>
                <img src="<?php echo esc_url($member_data['thumbnail_url']); ?>" alt="<?php echo esc_attr($member_data['name']); ?>" class="w-10 h-10 rounded-full object-cover">
                <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold">
                    <?php echo mb_substr($member_data['name'], 0, 1); ?>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?php echo esc_html($member_data['name']); ?></p>
                    <p class="text-xs text-gray-500">Участник</p>
                </div>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="text-gray-400 hover:text-gray-600" title="Выход">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>

    <main id="main-content" class="flex-1 overflow-y-auto">

        <!-- Profile Section -->
        <section id="profile-section" class="section-content">
            <div id="profile-header" class="bg-white border-b border-gray-200 px-8 py-6">
                <div class="max-w-5xl mx-auto">
                    <h2 class="text-2xl font-bold text-gray-900">Мой профиль</h2>
                    <p class="text-sm text-gray-500 mt-1">Управляйте информацией вашего публичного профиля</p>
                </div>
            </div>

            <div id="profile-content" class="p-8">
                <div class="max-w-5xl mx-auto">
                    <form id="profile-form" class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('member_dashboard_nonce'); ?>">

                        <!-- Basic Info -->
                        <div id="basic-info" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Основная информация</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="form-group col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ФИО</label>
                                    <input type="text" name="member_name" value="<?php echo esc_attr($member_data['name']); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Компания</label>
                                    <input type="text" name="member_company" value="<?php echo esc_attr($member_data['member_company'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                                    <input type="text" name="member_position" value="<?php echo esc_attr($member_data['member_position'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                                    <input type="text" name="member_city" value="<?php echo esc_attr($member_data['member_city'] ?? ''); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Role Section -->
                        <div id="role-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Роль в ассоциации</h3>
                            <p class="text-sm text-gray-500 mb-4">Ваши роли в сообществе</p>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php if (!empty($member_roles) && !is_wp_error($member_roles)): ?>
                                    <?php foreach ($member_roles as $role): ?>
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 metoda-primary rounded-full text-sm font-medium">
                                            <?php echo esc_html($role->name); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-sm">Роли не указаны</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Specialization Section -->
                        <div id="specialization-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Специализация и стаж</h3>
                            <p class="text-sm text-gray-500 mb-4">Области вашей экспертизы</p>
                            <div id="specialization-list" class="space-y-3 mb-4">
                                <?php
                                $specialization = $member_data['member_specialization_experience'] ?? '';
                                $spec_items = array_filter(explode("\n", $specialization));
                                if (!empty($spec_items)):
                                    foreach ($spec_items as $spec):
                                        $spec = trim($spec);
                                        if (empty($spec)) continue;
                                ?>
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                                    <i class="fas fa-grip-vertical text-gray-400"></i>
                                    <span class="flex-1 text-gray-900"><?php echo esc_html($spec); ?></span>
                                </div>
                                <?php endforeach; else: ?>
                                    <p class="text-gray-500 text-sm">Специализация не указана</p>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium metoda-primary border-2 border-primary rounded-lg hover:bg-blue-50 transition-all">
                                <i class="fas fa-edit"></i>
                                Редактировать
                            </button>
                        </div>

                        <!-- Interests Section -->
                        <div id="interests-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Сфера профессиональных интересов</h3>
                            <p class="text-sm text-gray-500 mb-4">Что вас интересует больше всего?</p>
                            <div id="interests-list" class="space-y-3 mb-4">
                                <?php
                                $interests = $member_data['member_professional_interests'] ?? '';
                                $interest_items = array_filter(explode("\n", $interests));
                                if (!empty($interest_items)):
                                    foreach ($interest_items as $interest):
                                        $interest = trim($interest);
                                        if (empty($interest)) continue;
                                ?>
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                                    <i class="fas fa-grip-vertical text-gray-400"></i>
                                    <span class="flex-1 text-gray-900"><?php echo esc_html($interest); ?></span>
                                </div>
                                <?php endforeach; else: ?>
                                    <p class="text-gray-500 text-sm">Интересы не указаны</p>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium metoda-primary border-2 border-primary rounded-lg hover:bg-blue-50 transition-all">
                                <i class="fas fa-edit"></i>
                                Редактировать
                            </button>
                        </div>

                        <!-- About Section -->
                        <div id="about-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">О себе</h3>
                            <p class="text-sm text-gray-500 mb-4">Расскажите о себе другим</p>
                            <textarea name="member_bio" rows="8" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none"><?php echo esc_textarea($member_data['member_bio'] ?? ''); ?></textarea>
                        </div>

                        <!-- Expectations Section -->
                        <div id="expectations-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Ожидания от сотрудничества</h3>
                            <p class="text-sm text-gray-500 mb-4">Что вы надеетесь достичь?</p>
                            <textarea name="member_expectations" rows="8" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none"><?php echo esc_textarea($member_data['member_expectations'] ?? ''); ?></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div id="form-actions" class="p-8">
                            <div class="flex items-center justify-end gap-4">
                                <button type="button" onclick="location.reload()" class="px-6 py-3 text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-all">
                                    Отмена
                                </button>
                                <button type="submit" class="px-8 py-3 metoda-primary-bg text-white font-semibold rounded-lg hover:opacity-90 shadow-lg transition-all">
                                    <i class="fas fa-save mr-2"></i>
                                    Сохранить изменения
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Materials Section -->
        <section id="materials-section" class="section-content hidden">
            <div id="materials-header" class="bg-white border-b border-gray-200 px-8 py-6">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Мои материалы</h2>
                        <p class="text-sm text-gray-500 mt-1">Управление вашим контентом</p>
                    </div>
                </div>
            </div>

            <div id="materials-content" class="p-8">
                <div class="max-w-7xl mx-auto">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Материалы будут скоро</h3>
                        <p class="text-gray-600">Функционал управления материалами в разработке</p>
                    </div>
                </div>
            </div>
        </section>

    </main>
</div>

<script>
jQuery(document).ready(function($) {
    // Section navigation
    window.showSection = function(section) {
        $('.section-content').addClass('hidden');
        $('.nav-item').removeClass('active bg-blue-50 metoda-primary').addClass('text-gray-600');

        if(section === 'profile') {
            $('#profile-section').removeClass('hidden');
            $('#nav-profile').addClass('active bg-blue-50 metoda-primary').removeClass('text-gray-600');
        } else if(section === 'materials') {
            $('#materials-section').removeClass('hidden');
            $('#nav-materials').addClass('active bg-blue-50 metoda-primary').removeClass('text-gray-600');
        }
    };

    // Form submission via AJAX
    $('#profile-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const buttonText = $button.html();

        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение...');

        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: $form.serialize() + '&action=member_update_profile',
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Профиль успешно обновлен!');
                    location.reload();
                } else {
                    alert(response.data.message || 'Ошибка при сохранении');
                }
            },
            error: function() {
                alert('Произошла ошибка при сохранении. Попробуйте позже.');
            },
            complete: function() {
                $button.prop('disabled', false).html(buttonText);
            }
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
