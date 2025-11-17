<?php
/**
 * Template: Member Dashboard
 * Личный кабинет участника с современным дизайном
 */

if (!defined('ABSPATH')) {
    exit;
}

$member_id = Member_User_Link::get_current_user_member_id();
$member_data = Member_Dashboard::get_member_data($member_id);
$member_stats = Member_Dashboard::get_member_stats($member_id);
$current_user = wp_get_current_user();

// Цвета Метода
$primary_color = '#0066cc';
$accent_color = '#ff6600';
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        * { font-family: 'Montserrat', sans-serif; }
        .wysiwyg-toolbar { display: flex; gap: 0.5rem; padding: 0.75rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 0.5rem 0.5rem 0 0; }
        .wysiwyg-toolbar button { padding: 0.5rem 0.75rem; background: white; border: 1px solid #e2e8f0; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s; }
        .wysiwyg-toolbar button:hover { background: #f1f5f9; }
        .wysiwyg-content { min-height: 150px; padding: 1rem; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 0.5rem 0.5rem; background: white; }
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

<div id="member-dashboard" class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: <?php echo $primary_color; ?>">
                    <i class="fas fa-user text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Личный кабинет</h1>
                    <p class="text-xs text-gray-500">Метода</p>
                </div>
            </div>
        </div>

        <nav id="main-nav" class="flex-1 p-4">
            <ul class="space-y-1">
                <li>
                    <button onclick="showSection('profile')" id="nav-profile" class="nav-item active w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg font-medium transition-all" style="background-color: rgba(0, 102, 204, 0.1); color: <?php echo $primary_color; ?>">
                        <i class="fas fa-user-circle text-lg"></i>
                        <span>Мой профиль</span>
                    </button>
                </li>
                <li>
                    <button onclick="showSection('gallery')" id="nav-gallery" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg text-gray-600 hover:bg-gray-50 font-medium transition-all">
                        <i class="fas fa-images text-lg"></i>
                        <span>Галерея</span>
                    </button>
                </li>
                <li>
                    <button onclick="showSection('materials')" id="nav-materials" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg text-gray-600 hover:bg-gray-50 font-medium transition-all">
                        <i class="fas fa-folder-open text-lg"></i>
                        <span>Портфолио</span>
                    </button>
                </li>
                <li>
                    <button onclick="showSection('forum')" id="nav-forum" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg text-gray-600 hover:bg-gray-50 font-medium transition-all">
                        <i class="fas fa-comments text-lg"></i>
                        <span>Форум</span>
                    </button>
                </li>
                <li>
                    <button onclick="showSection('messages')" id="nav-messages" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg text-gray-600 hover:bg-gray-50 font-medium transition-all">
                        <i class="fas fa-envelope text-lg"></i>
                        <span>Сообщения</span>
                    </button>
                </li>
            </ul>
        </nav>

        <div id="sidebar-footer" class="p-4 border-t border-gray-200">
            <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 mb-3">
                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-sm">
                    <?php echo strtoupper(mb_substr($current_user->display_name, 0, 2)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?php echo esc_html($current_user->display_name); ?></p>
                    <p class="text-xs text-gray-500">Участник</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="<?php echo esc_url($member_data['permalink']); ?>" target="_blank" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-eye"></i>
                    <span>Профиль</span>
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 transition-opacity" style="background-color: <?php echo $accent_color; ?>">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выход</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 overflow-y-auto">

        <!-- Profile Section -->
        <section id="profile-section" class="section-content">
            <div id="profile-header" class="bg-white border-b border-gray-200 px-8 py-6">
                <div class="max-w-5xl mx-auto">
                    <h2 class="text-2xl font-bold text-gray-900">Мой профиль</h2>
                    <p class="text-sm text-gray-500 mt-1">Управляйте информацией о себе</p>
                </div>
            </div>

            <div id="profile-content" class="p-8">
                <div class="max-w-5xl mx-auto">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-3 gap-6 mb-8">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(0, 102, 204, 0.1);">
                                    <i class="fas fa-eye" style="color: <?php echo $primary_color; ?>"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo esc_html($member_stats['profile_views']); ?></p>
                                    <p class="text-sm text-gray-500">Просмотров</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(255, 102, 0, 0.1);">
                                    <i class="fas fa-folder" style="color: <?php echo $accent_color; ?>"></i>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo esc_html($member_stats['materials_count']); ?></p>
                                    <p class="text-sm text-gray-500">Материалов</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-check-circle text-success"></i>
                                </div>
                                <div>
                                    <p class="text-xl font-bold text-gray-900">Активен</p>
                                    <p class="text-sm text-gray-500">Статус</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Form -->
                    <form id="profile-form" class="bg-white rounded-xl shadow-sm border border-gray-200">

                        <!-- Basic Info -->
                        <div id="basic-info" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Основная информация</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="form-group col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ФИО *</label>
                                    <input type="text" id="member_name" name="member_name" value="<?php echo esc_attr($member_data['name']); ?>" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                                    <input type="text" id="member_position" name="member_position" value="<?php echo esc_attr($member_data['member_position']); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Организация</label>
                                    <input type="text" id="member_company" name="member_company" value="<?php echo esc_attr($member_data['member_company']); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" id="member_email" name="member_email" value="<?php echo esc_attr($member_data['member_email']); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                                    <input type="text" id="member_phone" name="member_phone" value="<?php echo esc_attr($member_data['member_phone']); ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Specialization -->
                        <div id="specialization-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Специализация и опыт</h3>
                            <p class="text-sm text-gray-500 mb-4">Расскажите о вашей специализации</p>
                            <textarea id="member_specialization" name="member_specialization" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Опишите вашу специализацию..."><?php echo esc_textarea($member_data['member_specialization']); ?></textarea>
                        </div>

                        <!-- Experience -->
                        <div id="experience-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Опыт работы</h3>
                            <p class="text-sm text-gray-500 mb-4">Кратко опишите ваш опыт</p>
                            <textarea id="member_experience" name="member_experience" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Расскажите о вашем опыте..."><?php echo esc_textarea($member_data['member_experience']); ?></textarea>
                        </div>

                        <!-- Interests -->
                        <div id="interests-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Профессиональные интересы</h3>
                            <p class="text-sm text-gray-500 mb-4">Что вас интересует?</p>
                            <textarea id="member_interests" name="member_interests" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Ваши интересы..."><?php echo esc_textarea($member_data['member_interests']); ?></textarea>
                        </div>

                        <!-- About -->
                        <div id="about-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">О себе</h3>
                            <p class="text-sm text-gray-500 mb-4">Расскажите о себе подробнее</p>
                            <textarea id="member_bio" name="member_bio" rows="6" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Расскажите о себе..."><?php echo esc_textarea($member_data['member_bio']); ?></textarea>
                        </div>

                        <!-- Expectations -->
                        <div id="expectations-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Ожидания от сотрудничества</h3>
                            <p class="text-sm text-gray-500 mb-4">Что вы ожидаете от участия?</p>
                            <textarea id="member_expectations" name="member_expectations" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all" placeholder="Ваши ожидания..."><?php echo esc_textarea($member_data['member_expectations']); ?></textarea>
                        </div>

                        <!-- Links -->
                        <div id="links-section" class="p-8 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Социальные сети и ссылки</h3>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">LinkedIn</label>
                                    <input type="url" id="member_linkedin" name="member_linkedin" value="<?php echo esc_url($member_data['member_linkedin']); ?>" placeholder="https://linkedin.com/in/username" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Веб-сайт</label>
                                    <input type="url" id="member_website" name="member_website" value="<?php echo esc_url($member_data['member_website']); ?>" placeholder="https://example.com" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="p-8">
                            <div class="flex items-center gap-4">
                                <button type="submit" class="px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: <?php echo $primary_color; ?>">
                                    <i class="fas fa-save"></i>
                                    <span class="btn-text">Сохранить изменения</span>
                                    <span class="btn-loader hidden">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                                <div class="form-message hidden"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section id="gallery-section" class="section-content hidden">
            <div class="bg-white border-b border-gray-200 px-8 py-6">
                <div class="max-w-5xl mx-auto">
                    <h2 class="text-2xl font-bold text-gray-900">Галерея фотографий</h2>
                    <p class="text-sm text-gray-500 mt-1">Управляйте фотографиями в вашем профиле</p>
                </div>
            </div>

            <div class="p-8">
                <div class="max-w-5xl mx-auto">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Мои фотографии</h3>
                            <button type="button" class="px-4 py-2 text-white rounded-lg font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: <?php echo $primary_color; ?>" id="add-gallery-images">
                                <i class="fas fa-plus"></i>
                                <span>Добавить фото</span>
                            </button>
                        </div>

                        <div id="gallery-grid" class="grid grid-cols-4 gap-4 mb-6">
                            <?php if (!empty($member_data['gallery_images'])) : ?>
                                <?php foreach ($member_data['gallery_images'] as $image) : ?>
                                    <div class="gallery-item relative group" data-id="<?php echo esc_attr($image['id']); ?>">
                                        <img src="<?php echo esc_url($image['thumb']); ?>" alt="" class="w-full h-40 object-cover rounded-lg">
                                        <button type="button" class="remove-gallery-item absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center hover:bg-red-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="col-span-4 text-center py-12 text-gray-500">
                                    <i class="fas fa-images text-4xl mb-4 opacity-50"></i>
                                    <p>Галерея пуста. Добавьте свои фотографии.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <input type="hidden" id="gallery_ids" value="<?php echo esc_attr($member_data['member_gallery']); ?>">

                        <button type="button" class="px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: <?php echo $accent_color; ?>" id="save-gallery">
                            <i class="fas fa-save"></i>
                            <span class="btn-text">Сохранить галерею</span>
                            <span class="btn-loader hidden">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>

                        <div class="gallery-message hidden mt-4"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Materials/Portfolio Section -->
        <?php include(plugin_dir_path(__FILE__) . 'dashboard-materials-section.php'); ?>

        <!-- Forum Section -->
        <?php include(plugin_dir_path(__FILE__) . 'dashboard-forum-section.php'); ?>

        <!-- Messages Section -->
        <?php include(plugin_dir_path(__FILE__) . 'dashboard-messages-section.php'); ?>

    </main>

</div>

<script>
// Переключение секций
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.add('hidden');
    });

    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        item.style.backgroundColor = '';
        item.style.color = '';
        item.classList.add('text-gray-600', 'hover:bg-gray-50');
    });

    // Show selected section
    document.getElementById(sectionName + '-section').classList.remove('hidden');

    // Add active class to selected nav item
    const activeNav = document.getElementById('nav-' + sectionName);
    activeNav.classList.add('active');
    activeNav.classList.remove('text-gray-600', 'hover:bg-gray-50');
    activeNav.style.backgroundColor = 'rgba(0, 102, 204, 0.1)';
    activeNav.style.color = '<?php echo $primary_color; ?>';
}
</script>

<?php wp_footer(); ?>
</body>
</html>
