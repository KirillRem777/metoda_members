<?php
/**
 * Template: Member Manager Panel
 * Современная панель управления участниками
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();

// Get taxonomies for filters
$member_types = get_terms(array('taxonomy' => 'member_type', 'hide_empty' => false));
$member_roles = get_terms(array('taxonomy' => 'member_role', 'hide_empty' => false));
$member_locations = get_terms(array('taxonomy' => 'member_location', 'hide_empty' => false));

// Получаем города из postmeta
global $wpdb;
$cities = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND meta_value != ''
    ORDER BY meta_value ASC
", 'member_city'));

// Цвета
$primary_color = '#0066cc';
$accent_color = '#ff6600';
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Montserrat', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }

        /* Modal Animations */
        .modal-backdrop {
            transition: opacity 0.3s ease;
        }
        .modal-content-wrapper {
            transition: transform 0.3s ease, opacity 0.3s ease;
            transform: scale(0.9);
            opacity: 0;
        }
        .modal-backdrop.active .modal-content-wrapper {
            transform: scale(1);
            opacity: 1;
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

<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-primary">
                        <i class="fas fa-users text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Панель управления</h1>
                        <p class="text-xs text-gray-500">Привет, <?php echo esc_html($current_user->display_name); ?>!</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="<?php echo get_post_type_archive_link('members'); ?>" target="_blank" class="text-gray-600 hover:text-primary transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        <span class="hidden sm:inline">Просмотр архива</span>
                    </a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="text-gray-600 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span class="hidden sm:inline">Выход</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Toolbar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex-1 w-full sm:w-auto">
                    <div class="relative">
                        <input
                            type="text"
                            id="member-search"
                            placeholder="Поиск по имени, email, телефону..."
                            class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                        >
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    </div>
                </div>
                <button
                    id="add-member-btn"
                    class="w-full sm:w-auto px-6 py-2.5 bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-all inline-flex items-center justify-center gap-2 shadow-sm"
                >
                    <i class="fas fa-plus"></i>
                    <span>Добавить участника</span>
                </button>
            </div>

            <div class="mt-4 flex items-center justify-between text-sm">
                <span class="text-gray-600">
                    Найдено: <strong id="total-count" class="text-gray-900">0</strong> участников
                </span>
            </div>
        </div>

        <!-- Members Table/Grid -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full" id="members-table">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Участник
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">
                                Контакты
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden lg:table-cell">
                                Локация
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                Действия
                            </th>
                        </tr>
                    </thead>
                    <tbody id="members-tbody" class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center gap-3 text-gray-500">
                                    <i class="fas fa-circle-notch fa-spin text-2xl"></i>
                                    <span class="text-lg">Загрузка участников...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="mt-6"></div>

    </main>
</div>

<!-- Add/Edit Member Modal -->
<div id="member-modal" class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="modal-content-wrapper bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 id="modal-title" class="text-xl font-bold text-gray-900">Добавить участника</h2>
            <button id="modal-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto px-6 py-6">
            <form id="member-form">
                <input type="hidden" id="member-id" name="member_id">

                <!-- Основная информация -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-user text-primary"></i>
                        Основная информация
                    </h3>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ФИО <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="title"
                                id="member-title"
                                required
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Иванов Иван Иванович"
                            >
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                                <input
                                    type="text"
                                    name="member_position"
                                    id="member-position"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="Методист"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Организация</label>
                                <input
                                    type="text"
                                    name="member_company"
                                    id="member-company"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="Школа №1"
                                >
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input
                                    type="email"
                                    name="member_email"
                                    id="member-email"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="ivan@example.com"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                                <input
                                    type="tel"
                                    name="member_phone"
                                    id="member-phone"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="+7 (999) 123-45-67"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                            <input
                                type="text"
                                name="member_city"
                                id="member-city"
                                list="cities-list"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Москва"
                            >
                            <datalist id="cities-list">
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Фото профиля</label>
                            <div class="flex items-center gap-4">
                                <div id="photo-preview" class="w-24 h-24 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                                </div>
                                <input type="hidden" name="thumbnail_id" id="thumbnail-id">
                                <button
                                    type="button"
                                    id="upload-photo-btn"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    <i class="fas fa-upload mr-2"></i>
                                    Загрузить фото
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- О себе и опыт -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i>
                        Детальная информация
                    </h3>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">О себе</label>
                            <textarea
                                name="member_bio"
                                id="member-bio"
                                rows="3"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Краткая информация об участнике..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Специализация</label>
                            <textarea
                                name="member_specialization"
                                id="member-specialization"
                                rows="2"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Основные области специализации..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Опыт работы</label>
                            <textarea
                                name="member_experience"
                                id="member-experience"
                                rows="3"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Опыт работы и достижения..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Интересы</label>
                            <textarea
                                name="member_interests"
                                id="member-interests"
                                rows="2"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Профессиональные интересы..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ожидания от участия</label>
                            <textarea
                                name="member_expectations"
                                id="member-expectations"
                                rows="3"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                placeholder="Что ожидает получить от участия в ассоциации..."
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fab fa-linkedin text-blue-600 mr-1"></i>
                                    LinkedIn
                                </label>
                                <input
                                    type="url"
                                    name="member_linkedin"
                                    id="member-linkedin"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="https://linkedin.com/in/..."
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-globe text-green-600 mr-1"></i>
                                    Веб-сайт
                                </label>
                                <input
                                    type="url"
                                    name="member_website"
                                    id="member-website"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                    placeholder="https://example.com"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Категории -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-tags text-primary"></i>
                        Категории участника
                    </h3>

                    <div class="grid grid-cols-1 gap-4">
                        <?php if (!empty($member_types)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Тип участника</label>
                            <div class="flex flex-wrap gap-3" id="member-types-group">
                                <?php foreach ($member_types as $term): ?>
                                    <label class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input type="checkbox" name="member_types[]" value="<?php echo $term->term_id; ?>" class="mr-2 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700"><?php echo esc_html($term->name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($member_roles)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Роль участника</label>
                            <div class="flex flex-wrap gap-3" id="member-roles-group">
                                <?php foreach ($member_roles as $term): ?>
                                    <label class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input type="checkbox" name="member_roles[]" value="<?php echo $term->term_id; ?>" class="mr-2 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700"><?php echo esc_html($term->name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($member_locations)): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Локация</label>
                            <div class="flex flex-wrap gap-3" id="member-locations-group">
                                <?php foreach ($member_locations as $term): ?>
                                    <label class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input type="checkbox" name="member_locations[]" value="<?php echo $term->term_id; ?>" class="mr-2 text-primary focus:ring-primary">
                                        <span class="text-sm font-medium text-gray-700"><?php echo esc_html($term->name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Галерея -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-images text-primary"></i>
                        Галерея фотографий
                    </h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Дополнительные фотографии</label>
                        <div id="gallery-preview" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-3">
                            <!-- Gallery images will be displayed here -->
                        </div>
                        <input type="hidden" id="gallery-ids" name="gallery_ids">
                        <button
                            type="button"
                            id="upload-gallery-btn"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            <i class="fas fa-camera mr-2"></i>
                            Добавить фотографии
                        </button>
                    </div>
                </div>

            </form>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button
                type="button"
                id="cancel-btn"
                class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
            >
                Отмена
            </button>
            <button
                type="submit"
                form="member-form"
                class="px-6 py-2.5 bg-primary text-white rounded-lg font-medium hover:opacity-90 transition-all inline-flex items-center gap-2"
            >
                <span class="btn-text">Сохранить</span>
                <i class="btn-loader fas fa-circle-notch fa-spin hidden"></i>
            </button>
        </div>

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-backdrop fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="modal-content-wrapper bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Подтверждение удаления</h2>
        </div>
        <div class="px-6 py-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-800 mb-2">
                        Вы уверены, что хотите удалить участника <strong id="delete-member-name" class="text-gray-900"></strong>?
                    </p>
                    <p class="text-sm text-red-600">Это действие нельзя отменить.</p>
                </div>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button
                type="button"
                id="delete-cancel-btn"
                class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
            >
                Отмена
            </button>
            <button
                type="button"
                id="delete-confirm-btn"
                class="px-6 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors inline-flex items-center gap-2"
            >
                <span class="btn-text">Удалить</span>
                <i class="btn-loader fas fa-circle-notch fa-spin hidden"></i>
            </button>
        </div>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
