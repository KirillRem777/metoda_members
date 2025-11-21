<?php
/**
 * Template: Member Manager Panel
 * Панель управления участниками (дизайн от дизайнера)
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
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - <?php bloginfo('name'); ?></title>
    <?php metoda_enqueue_frontend_styles(); ?>
    <script>window.FontAwesomeConfig = { autoReplaceSvg: 'nest'};</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>::-webkit-scrollbar { display: none;}</style>
    <script>
    <?php
    // Загружаем скрипты менеджера
    wp_enqueue_style('member-manager', plugin_dir_url(dirname(__FILE__)) . '../assets/css/member-manager.css', array(), '1.0.0');
    wp_enqueue_script('member-manager', plugin_dir_url(dirname(__FILE__)) . '../assets/js/member-manager.js', array('jquery'), '1.0.0', true);
    wp_localize_script('member-manager', 'memberManager', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('member_manager_nonce'),
    ));
    wp_enqueue_media();
    wp_head();
    ?>
</head>
<body class="bg-gray-50">

    <!-- Sidebar -->
    <div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 z-50">
        <div class="p-6 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-800">Метода</h1>
            <p class="text-xs text-gray-500 mt-1">Панель управления</p>
        </div>

        <nav class="mt-6">
            <a href="<?php echo admin_url(); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-admin-gray hover:border-r-4 hover:border-admin-blue transition-all">
                <i class="fa-solid fa-chart-line w-5 h-5 mr-3"></i>
                Dashboard
            </a>
            <a href="#" id="nav-members" class="flex items-center px-6 py-3 bg-admin-gray border-r-4 border-admin-blue text-admin-blue font-medium">
                <i class="fa-solid fa-users w-5 h-5 mr-3"></i>
                Управление участниками
            </a>
            <a href="<?php echo get_post_type_archive_link('members'); ?>" target="_blank" class="flex items-center px-6 py-3 text-gray-700 hover:bg-admin-gray hover:border-r-4 hover:border-admin-blue transition-all">
                <i class="fa-solid fa-eye w-5 h-5 mr-3"></i>
                Просмотр архива
            </a>
        </nav>

        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200">
            <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50">
                <div class="w-10 h-10 rounded-full bg-admin-blue flex items-center justify-center text-white font-bold">
                    <?php echo mb_substr($current_user->display_name, 0, 1); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?php echo esc_html($current_user->display_name); ?></p>
                    <p class="text-xs text-gray-500">Менеджер</p>
                </div>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="text-gray-400 hover:text-gray-600" title="Выход">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64">
        <!-- Header -->
        <header id="header" class="bg-white shadow-sm border-b border-gray-200 px-8 py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-semibold text-gray-800">Управление участниками</h2>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-600">
                        Всего: <span id="total-count" class="font-semibold">0</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Member Management Section -->
        <div id="member-management" class="p-8">
            <!-- Controls Bar -->
            <div id="controls-bar" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <button id="add-member-btn" class="bg-admin-blue text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                        <i class="fa-solid fa-plus mr-2"></i>
                        Добавить участника
                    </button>
                </div>

                <!-- Search and Filters -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" id="member-search" placeholder="Поиск по имени или компании..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue focus:border-transparent">
                    </div>
                    <select id="filter-type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                        <option value="">Все типы</option>
                        <?php if ($member_types): foreach ($member_types as $type): ?>
                            <option value="<?php echo esc_attr($type->slug); ?>"><?php echo esc_html($type->name); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <select id="filter-city" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                        <option value="">Все города</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filter-status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                        <option value="">Все статусы</option>
                        <option value="publish">Активен</option>
                        <option value="pending">На модерации</option>
                        <option value="draft">Черновик</option>
                    </select>
                </div>
            </div>

            <!-- Members Table -->
            <div id="members-table-container" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden pb-4">
                <div class="overflow-x-auto">
                    <table class="w-full" id="members-table">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 200px;">Участник</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 100px;">Тип</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 250px; max-width: 350px;">Компания</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">Город</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 120px;">Регистрация</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 100px;">Статус</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="min-width: 180px;">Действия</th>
                            </tr>
                        </thead>
                    <tbody id="members-tbody" class="divide-y divide-gray-200">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
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
            <div id="pagination" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Показано <span id="page-from" class="font-medium">0</span> - <span id="page-to" class="font-medium">0</span> из <span id="page-total" class="font-medium">0</span>
                </div>
                <div id="pagination-buttons" class="flex space-x-2">
                    <!-- Pagination buttons will be inserted here -->
                </div>
            </div>
        </div>

        <!-- Member Detail Modal (Hidden by default) -->
        <div id="member-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 id="modal-title" class="text-lg font-semibold text-gray-900">Редактирование участника</h3>
                            <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                                <i class="fa-solid fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <form id="member-form" class="p-6 space-y-6">
                        <input type="hidden" id="member-id" name="member_id">

                        <!-- Profile Form -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ФИО *</label>
                                <input type="text" id="member-title" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" id="member-email" name="member_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Компания</label>
                                <input type="text" id="member-company" name="member_company" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Должность</label>
                                <input type="text" id="member-position" name="member_position" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                                <input type="text" id="member-city" name="member_city" list="cities-list" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                                <datalist id="cities-list">
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?php echo esc_attr($city); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
                                <input type="tel" id="member-phone" name="member_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                            </div>
                        </div>

                        <!-- Status Management -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 mb-3">Статус</h4>
                            <div class="flex space-x-4">
                                <button type="button" class="status-btn px-4 py-2 bg-gray-300 text-gray-700 rounded-lg text-sm" data-status="publish">
                                    <i class="fas fa-check-circle mr-1"></i> Активен
                                </button>
                                <button type="button" class="status-btn px-4 py-2 bg-gray-300 text-gray-700 rounded-lg text-sm" data-status="pending">
                                    <i class="fas fa-clock mr-1"></i> На модерации
                                </button>
                                <button type="button" class="status-btn px-4 py-2 bg-gray-300 text-gray-700 rounded-lg text-sm" data-status="draft">
                                    <i class="fas fa-file mr-1"></i> Черновик
                                </button>
                            </div>
                            <input type="hidden" id="member-status" name="post_status" value="publish">
                        </div>

                        <!-- Type and Categories -->
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Категории</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php if (!empty($member_types)): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Тип участника</label>
                                    <div class="space-y-2" id="member-types-group">
                                        <?php foreach ($member_types as $term): ?>
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" name="member_types[]" value="<?php echo $term->term_id; ?>" class="mr-2 text-admin-blue focus:ring-admin-blue">
                                                <span class="text-sm"><?php echo esc_html($term->name); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($member_roles)): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Роль</label>
                                    <select id="member-role" name="member_role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                                        <option value="">Выберите роль</option>
                                        <?php foreach ($member_roles as $term): ?>
                                            <option value="<?php echo $term->term_id; ?>"><?php echo esc_html($term->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" id="cancel-btn" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                Отмена
                            </button>
                            <button type="submit" class="px-6 py-2 bg-admin-blue text-white rounded-lg hover:bg-blue-700">
                                <span class="btn-text">Сохранить</span>
                                <i class="btn-loader fas fa-circle-notch fa-spin hidden ml-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="delete-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Подтверждение удаления</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-gray-800 mb-2">
                                    Вы уверены, что хотите удалить участника <strong id="delete-member-name"></strong>?
                                </p>
                                <p class="text-sm text-red-600">Это действие нельзя отменить.</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
                        <button type="button" id="delete-cancel-btn" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Отмена
                        </button>
                        <button type="button" id="delete-confirm-btn" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            <span class="btn-text">Удалить</span>
                            <i class="btn-loader fas fa-circle-notch fa-spin hidden ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
