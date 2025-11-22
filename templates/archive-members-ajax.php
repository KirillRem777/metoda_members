<?php
/**
 * Archive Template for Members with AJAX
 * Архив участников с AJAX-фильтрацией
 */

get_header();


// Получаем все города для фильтра
global $wpdb;
$cities = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND meta_value != ''
    ORDER BY meta_value ASC
", 'member_city'));

// Получаем все роли для фильтра
$roles = get_terms(array(
    'taxonomy' => 'member_role',
    'hide_empty' => true
));

// Начальный запрос
$args = array(
    'post_type' => 'members',
    'posts_per_page' => 12,
    'orderby' => 'title',
    'order' => 'ASC'
);
$members_query = new WP_Query($args);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Участники - <?php bloginfo('name'); ?></title>
    <?php metoda_enqueue_frontend_styles(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .metoda-primary { color: #0066cc; }
        .metoda-primary-bg { background-color: #0066cc; }
        .metoda-accent-bg { background-color: #ff6600; }

        /* Мобильный фильтр */
        @media (max-width: 1023px) {
            #filter-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                z-index: 60;
                background: white;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            #filter-sidebar.active {
                transform: translateX(0);
            }
        }

        /* Лоадер */
        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0066cc;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

    <div id="members-archive">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 md:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 metoda-primary-bg rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-white text-lg"></i>
                        </div>
                        <span class="text-xl font-bold text-gray-900 hidden md:block">Участники Ассоциации</span>
                        <span class="text-lg font-bold text-gray-900 md:hidden">Участники</span>
                    </div>

                    <!-- Кнопка фильтров для мобильных -->
                    <button id="mobile-filter-toggle" class="lg:hidden metoda-primary-bg text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-filter"></i>
                        <span>Фильтры</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 md:px-8 py-8">
            <div class="mb-6 md:mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Каталог участников и экспертов</h1>
                <p class="text-gray-600">Знакомьтесь с профессионалами сообщества</p>
            </div>

            <div class="flex gap-8 flex-col lg:flex-row">

                <!-- Sidebar Filters -->
                <aside id="filter-sidebar" class="w-full lg:w-80 flex-shrink-0">
                    <div class="bg-white rounded-xl shadow-sm border p-6 lg:sticky lg:top-24">
                        <!-- Закрыть для мобильных -->
                        <div class="flex items-center justify-between mb-6 lg:mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                            <div class="flex items-center gap-2">
                                <button id="reset-filters" class="text-sm metoda-primary hover:underline font-medium">Сбросить</button>
                                <button id="mobile-filter-close" class="lg:hidden text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                            <div class="relative">
                                <input type="text" id="member-search" placeholder="Поиск по имени..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                            </div>
                        </div>

                        <!-- City Filter -->
                        <?php if (!empty($cities)): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                            <select id="city-filter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Все города...</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Role Filter -->
                        <?php if (!empty($roles) && !is_wp_error($roles)): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Роль в ассоциации</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <?php foreach ($roles as $role): ?>
                                <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded">
                                    <input type="checkbox" value="<?php echo esc_attr($role->slug); ?>" class="role-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                    <span class="ml-3 text-sm text-gray-700"><?php echo esc_html($role->name); ?></span>
                                    <span class="ml-auto text-xs text-gray-500">(<?php echo $role->count; ?>)</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Apply button for mobile -->
                        <button id="mobile-apply-filters" class="lg:hidden w-full metoda-primary-bg text-white py-3 rounded-lg hover:opacity-90 font-medium">
                            Применить фильтры
                        </button>
                    </div>
                </aside>

                <!-- Members Grid -->
                <section class="flex-1">
                    <!-- Controls -->
                    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <p class="text-gray-600">
                            Показано <span id="members-count" class="font-semibold text-gray-900"><?php echo $members_query->found_posts; ?></span> участников
                        </p>

                        <div class="flex items-center gap-3">
                            <label class="text-sm text-gray-700 hidden md:block">Сортировка:</label>
                            <select id="sort-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary text-sm">
                                <option value="title-asc">По имени (А-Я)</option>
                                <option value="title-desc">По имени (Я-А)</option>
                                <option value="date-desc">Сначала новые</option>
                                <option value="date-asc">Сначала старые</option>
                            </select>
                        </div>
                    </div>

                    <!-- Loader -->
                    <div id="members-loader" class="hidden flex justify-center items-center py-12">
                        <div class="loader"></div>
                    </div>

                    <!-- Grid -->
                    <div id="members-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 transition-opacity duration-300">
                        <?php if ($members_query->have_posts()): ?>
                            <?php while ($members_query->have_posts()): $members_query->the_post();
                                $member_id = get_the_ID();
                                $position = get_post_meta($member_id, 'member_position', true);
                                $company = get_post_meta($member_id, 'member_company', true);
                                $city = get_post_meta($member_id, 'member_city', true);
                                $roles_terms = wp_get_post_terms($member_id, 'member_role');
                            ?>
                            <article class="bg-white rounded-xl shadow-sm border p-4 md:p-6 hover:shadow-md transition-shadow">
                                <a href="<?php the_permalink(); ?>" class="flex items-start gap-4">
                                    <div class="w-16 h-16 md:w-20 md:h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                        <?php if (has_post_thumbnail()): ?>
                                            <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover')); ?>
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center text-xl md:text-2xl font-bold text-gray-300">
                                                <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base md:text-lg font-semibold text-gray-900 mb-1 truncate"><?php the_title(); ?></h3>

                                        <?php if ($position): ?>
                                        <p class="text-sm text-gray-600 mb-1 line-clamp-1"><?php echo esc_html($position); ?></p>
                                        <?php endif; ?>

                                        <?php if ($company): ?>
                                        <p class="text-sm font-medium text-gray-700 mb-3 line-clamp-1"><?php echo esc_html($company); ?></p>
                                        <?php endif; ?>

                                        <?php if ($city): ?>
                                        <div class="flex items-center text-sm text-gray-500 mb-3">
                                            <i class="fas fa-map-marker-alt mr-2"></i>
                                            <span class="truncate"><?php echo esc_html($city); ?></span>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($roles_terms && !is_wp_error($roles_terms)): ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach (array_slice($roles_terms, 0, 2) as $role): ?>
                                            <span class="px-2 md:px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                                                <?php echo esc_html($role->name); ?>
                                            </span>
                                            <?php endforeach; ?>
                                            <?php if (count($roles_terms) > 2): ?>
                                            <span class="px-2 md:px-3 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">
                                                +<?php echo count($roles_terms) - 2; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </article>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-span-2 bg-white rounded-xl shadow-sm border p-12 text-center">
                                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">Участники не найдены</h3>
                                <p class="text-gray-600">Попробуйте изменить параметры поиска</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <div id="members-pagination">
                        <?php if ($members_query->max_num_pages > 1): ?>
                        <div class="flex justify-center items-center space-x-2 mt-8 flex-wrap gap-2">
                            <a href="#" data-page="1" class="pagination-link px-3 md:px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php for ($i = 1; $i <= min(5, $members_query->max_num_pages); $i++): ?>
                            <a href="#" data-page="<?php echo $i; ?>" class="pagination-link px-3 md:px-4 py-2 <?php echo $i == 1 ? 'bg-blue-600 text-white' : 'border border-gray-300 hover:bg-gray-50'; ?> rounded-lg transition-colors">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($members_query->max_num_pages > 5): ?>
                            <span class="px-2">...</span>
                            <?php endif; ?>

                            <a href="#" data-page="<?php echo $members_query->max_num_pages; ?>" class="pagination-link px-3 md:px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        // Мобильный фильтр
        jQuery(document).ready(function($) {
            $('#mobile-filter-toggle, #mobile-filter-close').on('click', function() {
                $('#filter-sidebar').toggleClass('active');
                $('body').toggleClass('overflow-hidden');
            });

            $('#mobile-apply-filters').on('click', function() {
                $('#filter-sidebar').removeClass('active');
                $('body').removeClass('overflow-hidden');
            });

            // Закрытие по клику вне фильтра
            $('#filter-sidebar').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                    $('body').removeClass('overflow-hidden');
                }
            });
        });
    </script>

    <?php wp_footer(); ?>
</body>
</html>

<?php
wp_reset_postdata();
?>
