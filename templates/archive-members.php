<?php
/**
 * Archive Template for Members
 * Архив всех участников с фильтрами
 */

get_header();

// Фирменные цвета "Метода"
$primary_color = '#2e466f'; // Темно-синий
$accent_color = '#ef4e4c';  // Красный

// Получаем параметры фильтрации
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$city_filter = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$type_filter = isset($_GET['member_type']) ? sanitize_text_field($_GET['member_type']) : '';

// Формируем базовый запрос
$args = array(
    'post_type' => 'members',
    'posts_per_page' => 12,
    'paged' => $paged,
);

// Добавляем поиск
if (!empty($search)) {
    $args['s'] = $search;
}

// Добавляем фильтр по городу
if (!empty($city_filter)) {
    $args['meta_query'][] = array(
        'key' => 'member_city',
        'value' => $city_filter,
        'compare' => 'LIKE'
    );
}

// Добавляем фильтр по типу (Эксперт/Участник)
if (!empty($type_filter)) {
    if (!isset($args['tax_query'])) {
        $args['tax_query'] = array();
    }
    $args['tax_query'][] = array(
        'taxonomy' => 'member_type',
        'field' => 'slug',
        'terms' => $type_filter
    );
}

// Добавляем фильтр по роли
if (!empty($role_filter)) {
    if (!isset($args['tax_query'])) {
        $args['tax_query'] = array();
    }
    $args['tax_query'][] = array(
        'taxonomy' => 'member_role',
        'field' => 'slug',
        'terms' => $role_filter
    );
}

// Сортировка: сначала эксперты по алфавиту, потом участники по алфавиту
$args['orderby'] = array(
    'meta_value' => 'DESC',  // Эксперты первые
    'title' => 'ASC'         // Внутри каждой группы - по алфавиту
);
$args['meta_key'] = '_member_type_sort';

// Добавляем мета-запрос для определения сортировки
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_post_type_archive('members') && $query->is_main_query()) {
        // Устанавливаем временное мета-поле для сортировки при выборке
        add_filter('posts_clauses', function($clauses, $wp_query) {
            global $wpdb;

            // Добавляем LEFT JOIN для таксономии member_type
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS tr_type ON ({$wpdb->posts}.ID = tr_type.object_id)";
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} AS tt_type ON (tr_type.term_taxonomy_id = tt_type.term_taxonomy_id AND tt_type.taxonomy = 'member_type')";
            $clauses['join'] .= " LEFT JOIN {$wpdb->terms} AS t_type ON (tt_type.term_id = t_type.term_id)";

            // Сортируем: эксперты (slug=ekspert) первые, затем по title
            $clauses['orderby'] = "CASE WHEN t_type.slug = 'ekspert' THEN 0 ELSE 1 END ASC, {$wpdb->posts}.post_title ASC";

            // Группировка чтобы избежать дублей
            $clauses['groupby'] = "{$wpdb->posts}.ID";

            return $clauses;
        }, 10, 2);
    }
}, 1);

$members_query = new WP_Query($args);

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
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Участники - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .metoda-primary { color: <?php echo $primary_color; ?>; }
        .metoda-primary-bg { background-color: <?php echo $primary_color; ?>; }
        .metoda-accent-bg { background-color: <?php echo $accent_color; ?>; }

        /* Прописываем все состояния элементов */
        a { transition: all 0.2s ease; }
        a:hover { opacity: 0.9; }
        a:active { transform: scale(0.98); }

        button { transition: all 0.2s ease; }
        button:hover { opacity: 0.9; }
        button:active { transform: scale(0.98); }
        button:focus { outline: 2px solid <?php echo $primary_color; ?>; outline-offset: 2px; }

        input, select { transition: all 0.2s ease; }
        input:focus, select:focus { ring: 2px; ring-color: <?php echo $primary_color; ?>; border-color: <?php echo $primary_color; ?>; }
        input:hover, select:hover { border-color: #9ca3af; }

        .member-card { transition: all 0.3s ease; }
        .member-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); }

        /* Пагинация */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 2rem; }
        .pagination li { display: inline-block; }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .pagination a:hover { background-color: <?php echo $primary_color; ?>; color: white; border-color: <?php echo $primary_color; ?>; }
        .pagination .current { background-color: <?php echo $primary_color; ?>; color: white; border-color: <?php echo $primary_color; ?>; }
        .pagination .prev, .pagination .next { background-color: #f9fafb; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $primary_color; ?>',
                        accent: '<?php echo $accent_color; ?>'
                    }
                }
            }
        }
    </script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 metoda-primary-bg rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-white text-lg"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Участники Ассоциации</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Эксперты и участники ассоциации «Метода»</h1>
            <p class="text-gray-600">Знакомьтесь с профессионалами сообщества</p>
        </div>

        <div class="flex gap-8 flex-col lg:flex-row">

            <!-- Sidebar Filters -->
            <aside class="w-full lg:w-80 flex-shrink-0">
                <form method="get" action="<?php echo esc_url(get_post_type_archive_link('members')); ?>" class="bg-white rounded-xl shadow-sm border p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                        <a href="<?php echo get_post_type_archive_link('members'); ?>" class="text-sm metoda-primary hover:underline font-medium">Сбросить</a>
                    </div>

                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                        <div class="relative">
                            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Поиск по имени..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Тип</label>
                        <div class="flex gap-2">
                            <label class="flex-1">
                                <input type="radio" name="member_type" value="" <?php checked($type_filter, ''); ?> class="sr-only peer" onchange="this.form.submit()">
                                <div class="px-4 py-2 text-center rounded-lg font-medium text-sm cursor-pointer transition-all peer-checked:metoda-primary-bg peer-checked:text-white bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    Все
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="member_type" value="uchastnik" <?php checked($type_filter, 'uchastnik'); ?> class="sr-only peer" onchange="this.form.submit()">
                                <div class="px-4 py-2 text-center rounded-lg font-medium text-sm cursor-pointer transition-all peer-checked:bg-green-600 peer-checked:text-white bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    Участники
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="member_type" value="ekspert" <?php checked($type_filter, 'ekspert'); ?> class="sr-only peer" onchange="this.form.submit()">
                                <div class="px-4 py-2 text-center rounded-lg font-medium text-sm cursor-pointer transition-all peer-checked:metoda-primary-bg peer-checked:text-white bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    Эксперты
                                </div>
                            </label>
                        </div>
                    </div>

                    <script>
                    // Автоматическая отправка формы при изменении фильтров
                    jQuery(document).ready(function($) {
                        const $form = $('form');

                        // Показать индикатор загрузки
                        function showLoading() {
                            $('body').css('opacity', '0.7');
                            $('body').css('pointer-events', 'none');
                        }

                        // Автоотправка при изменении селектов
                        $('select[name="city"], select[name="role"]').on('change', function() {
                            showLoading();
                            $(this).closest('form').submit();
                        });

                        // Поиск с задержкой (500ms после ввода)
                        let searchTimeout;
                        $('input[name="s"]').on('input', function() {
                            clearTimeout(searchTimeout);
                            const $currentForm = $(this).closest('form');
                            searchTimeout = setTimeout(function() {
                                showLoading();
                                $currentForm.submit();
                            }, 500);
                        });
                    });
                    </script>

                    <!-- City Filter -->
                    <?php if (!empty($cities)): ?>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                        <select name="city" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <option value="">Все города...</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo esc_attr($city); ?>" <?php selected($city_filter, $city); ?>>
                                    <?php echo esc_html($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Role Filter -->
                    <?php if (!empty($roles) && !is_wp_error($roles)): ?>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Роль в ассоциации</label>
                        <select name="role" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                            <option value="">Все роли...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo esc_attr($role->slug); ?>" <?php selected($role_filter, $role->slug); ?>>
                                    <?php echo esc_html($role->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="w-full metoda-primary-bg text-white py-2.5 rounded-lg hover:opacity-90 font-medium transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        Применить фильтры
                    </button>
                </form>
            </aside>

            <!-- Members Grid -->
            <section class="flex-1">
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-gray-600">Показано <span class="font-semibold text-gray-900"><?php echo $members_query->found_posts; ?></span> участников</p>
                </div>

                <?php if ($members_query->have_posts()): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php while ($members_query->have_posts()): $members_query->the_post();
                        $member_id = get_the_ID();
                        $position = get_post_meta($member_id, 'member_position', true);
                        $company = get_post_meta($member_id, 'member_company', true);
                        $city = get_post_meta($member_id, 'member_city', true);
                        $roles = wp_get_post_terms($member_id, 'member_role');
                        $member_types = wp_get_post_terms($member_id, 'member_type');

                        // Определяем тип участника для плашки
                        $is_expert = false;
                        if ($member_types && !is_wp_error($member_types)) {
                            foreach ($member_types as $type) {
                                if ($type->slug === 'ekspert' || $type->name === 'Эксперт') {
                                    $is_expert = true;
                                    break;
                                }
                            }
                        }

                        // Обработка имени: только Имя Фамилия (без отчества)
                        $full_name = get_the_title();
                        $name_parts = explode(' ', $full_name);
                        $short_name = '';
                        if (count($name_parts) >= 2) {
                            // Предполагаем формат: Фамилия Имя Отчество
                            $short_name = $name_parts[0] . ' ' . $name_parts[1];
                        } else {
                            $short_name = $full_name;
                        }
                    ?>
                    <article class="member-card bg-white rounded-xl shadow-sm border p-6">
                        <a href="<?php the_permalink(); ?>" class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover object-top')); ?>
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-300">
                                        <?php echo mb_substr($short_name, 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <!-- Member Type Badge -->
                                <?php if ($member_types && !is_wp_error($member_types) && !empty($member_types)): ?>
                                <div class="mb-2">
                                    <?php if ($is_expert): ?>
                                        <span class="inline-block px-3 py-1 metoda-accent-bg text-white text-xs font-semibold rounded-full">Эксперт</span>
                                    <?php else: ?>
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Участник</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <h3 class="text-lg font-semibold text-gray-900 mb-1 truncate"><?php echo esc_html($short_name); ?></h3>

                                <?php if ($position): ?>
                                <p class="text-xs text-gray-600 mb-1 line-clamp-2"><?php echo esc_html($position); ?></p>
                                <?php endif; ?>

                                <?php if ($company): ?>
                                <p class="text-xs font-medium text-gray-500 mb-3 line-clamp-1"><?php echo esc_html($company); ?></p>
                                <?php endif; ?>

                                <?php if ($city): ?>
                                <div class="flex items-center text-xs text-gray-500 mb-3">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                    <span><?php echo esc_html($city); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($roles && !is_wp_error($roles)): ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (array_slice($roles, 0, 3) as $role): ?>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                                        <?php echo esc_html($role->name); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($members_query->max_num_pages > 1): ?>
                <nav class="pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => max(1, $paged),
                        'total' => $members_query->max_num_pages,
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>',
                        'type' => 'list',
                        'end_size' => 2,
                        'mid_size' => 2
                    ));
                    ?>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Участники не найдены</h3>
                    <p class="text-gray-600 mb-4">Попробуйте изменить фильтры или поисковый запрос</p>
                    <a href="<?php echo get_post_type_archive_link('members'); ?>" class="inline-block metoda-primary-bg text-white px-6 py-2.5 rounded-lg hover:opacity-90 font-medium transition-all">
                        Сбросить фильтры
                    </a>
                </div>
                <?php endif; ?>

                <?php wp_reset_postdata(); ?>
            </section>
        </div>
    </main>

    <?php wp_footer(); ?>
</body>
</html>
