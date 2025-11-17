<?php
/**
 * Archive Template for Members
 * Архив всех участников с фильтрами
 */

get_header();

// Фирменные цвета "Метода"
$primary_color = '#0066cc';
$accent_color = '#ff6600';

// Получаем параметры фильтрации
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$city_filter = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$type_filter = isset($_GET['member_type']) ? sanitize_text_field($_GET['member_type']) : '';

// Формируем запрос
$args = array(
    'post_type' => 'members',
    'posts_per_page' => 12,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC'
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
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Каталог участников и экспертов</h1>
            <p class="text-gray-600">Знакомьтесь с профессионалами сообщества</p>
        </div>

        <div class="flex gap-8 flex-col lg:flex-row">

            <!-- Sidebar Filters -->
            <aside class="w-full lg:w-80 flex-shrink-0">
                <form method="get" class="bg-white rounded-xl shadow-sm border p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                        <a href="<?php echo get_post_type_archive_link('members'); ?>" class="text-sm metoda-primary hover:underline font-medium">Сбросить</a>
                    </div>

                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                        <div class="relative">
                            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Поиск по имени..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Тип</label>
                        <div class="flex gap-2">
                            <button type="button" onclick="window.location.href='<?php echo remove_query_arg('member_type'); ?>'" class="flex-1 px-4 py-2 <?php echo empty($type_filter) ? 'metoda-primary-bg text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium text-sm">
                                Все
                            </button>
                            <button type="button" onclick="setMemberType('uchastnik')" class="flex-1 px-4 py-2 <?php echo $type_filter === 'uchastnik' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium text-sm">
                                Участники
                            </button>
                            <button type="button" onclick="setMemberType('ekspert')" class="flex-1 px-4 py-2 <?php echo $type_filter === 'ekspert' ? 'metoda-primary-bg text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> rounded-lg font-medium text-sm">
                                Эксперты
                            </button>
                        </div>
                        <input type="hidden" name="member_type" id="member_type_input" value="<?php echo esc_attr($type_filter); ?>">
                    </div>

                    <script>
                    function setMemberType(type) {
                        document.getElementById('member_type_input').value = type;
                        document.querySelector('form').submit();
                    }

                    // Автоматическая отправка формы при изменении фильтров
                    jQuery(document).ready(function($) {
                        const $form = $('.bg-white.rounded-xl.shadow-sm.border.p-6.sticky');

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
                        <select name="city" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
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
                        <select name="role" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Все роли...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo esc_attr($role->slug); ?>" <?php selected($role_filter, $role->slug); ?>>
                                    <?php echo esc_html($role->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="w-full metoda-primary-bg text-white py-2.5 rounded-lg hover:opacity-90 font-medium">
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
                    ?>
                    <article class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                        <a href="<?php the_permalink(); ?>" class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover')); ?>
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-300">
                                        <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex-1 min-w-0">
                                <!-- Member Type Badge -->
                                <?php if ($member_types && !is_wp_error($member_types) && !empty($member_types)): ?>
                                <div class="flex items-center justify-between mb-2">
                                    <?php if ($is_expert): ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Эксперт</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Участник</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <h3 class="text-lg font-semibold text-gray-900 mb-1 truncate"><?php the_title(); ?></h3>

                                <?php if ($position): ?>
                                <p class="text-sm text-gray-600 mb-1"><?php echo esc_html($position); ?></p>
                                <?php endif; ?>

                                <?php if ($company): ?>
                                <p class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html($company); ?></p>
                                <?php endif; ?>

                                <?php if ($city): ?>
                                <div class="flex items-center text-sm text-gray-500 mb-3">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <span><?php echo esc_html($city); ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if ($roles && !is_wp_error($roles)): ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (array_slice($roles, 0, 3) as $role): ?>
                                    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
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
                <div class="mt-8">
                    <?php
                    echo paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => max(1, $paged),
                        'total' => $members_query->max_num_pages,
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>',
                        'type' => 'list',
                        'class' => 'flex justify-center space-x-2'
                    ));
                    ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Участники не найдены</h3>
                    <p class="text-gray-600">Попробуйте изменить параметры поиска</p>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php wp_footer(); ?>
</body>
</html>

<?php
wp_reset_postdata();
get_footer();
?>
