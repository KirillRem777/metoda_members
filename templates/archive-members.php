<?php
/**
 * Archive Template for Members
 * Архив всех участников с фильтрами и AJAX подгрузкой
 */

get_header();

// Получаем параметры фильтрации
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$city_filter = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$type_filter = isset($_GET['member_type']) ? sanitize_text_field($_GET['member_type']) : '';

// Начальная загрузка - показываем первые 12 участников
$posts_per_page = 12;

// Если нет фильтра по типу - делаем два отдельных запроса и объединяем
$all_members = array();
$total_found = 0;

if (empty($type_filter)) {
    // Запрос для экспертов
    $experts_args = array(
        'post_type' => 'members',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => 'ekspert'
            )
        )
    );

    // Добавляем поиск
    if (!empty($search)) {
        $experts_args['s'] = $search;
    }

    // Добавляем фильтр по городу
    if (!empty($city_filter)) {
        $experts_args['meta_query'][] = array(
            'key' => 'member_city',
            'value' => $city_filter,
            'compare' => 'LIKE'
        );
    }

    // Добавляем фильтр по роли
    if (!empty($role_filter)) {
        $experts_args['tax_query'][] = array(
            'taxonomy' => 'member_role',
            'field' => 'slug',
            'terms' => $role_filter
        );
    }

    $experts_query = new WP_Query($experts_args);

    // Запрос для участников
    $members_args = array(
        'post_type' => 'members',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'tax_query' => array(
            array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => 'uchastnik'
            )
        )
    );

    // Добавляем те же фильтры
    if (!empty($search)) {
        $members_args['s'] = $search;
    }
    if (!empty($city_filter)) {
        $members_args['meta_query'][] = array(
            'key' => 'member_city',
            'value' => $city_filter,
            'compare' => 'LIKE'
        );
    }
    if (!empty($role_filter)) {
        $members_args['tax_query'][] = array(
            'taxonomy' => 'member_role',
            'field' => 'slug',
            'terms' => $role_filter
        );
    }

    $members_query_temp = new WP_Query($members_args);

    // Объединяем результаты
    $all_members = array_merge($experts_query->posts, $members_query_temp->posts);
    $total_found = count($all_members);

    // Берем только первые N для начального отображения
    $paged_members = array_slice($all_members, 0, $posts_per_page);

} else {
    // Если выбран конкретный тип - обычный запрос
    $args = array(
        'post_type' => 'members',
        'posts_per_page' => $posts_per_page,
        'orderby' => 'title',
        'order' => 'ASC'
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    if (!empty($city_filter)) {
        $args['meta_query'][] = array(
            'key' => 'member_city',
            'value' => $city_filter,
            'compare' => 'LIKE'
        );
    }

    $args['tax_query'] = array();

    if (!empty($type_filter)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'member_type',
            'field' => 'slug',
            'terms' => $type_filter
        );
    }

    if (!empty($role_filter)) {
        $args['tax_query'][] = array(
            'taxonomy' => 'member_role',
            'field' => 'slug',
            'terms' => $role_filter
        );
    }

    $members_query = new WP_Query($args);
    $paged_members = $members_query->posts;
    $total_found = $members_query->found_posts;
}

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
    <?php metoda_enqueue_frontend_styles(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        * { font-family: 'Montserrat', sans-serif; }
        .metoda-primary { color: #2e466f; }
        .metoda-primary-bg { background-color: #2e466f; }
        .metoda-accent-bg { background-color: #ef4e4c; }

        /* Прописываем все состояния элементов */
        a {
            transition: all 0.2s ease;
            text-decoration: none !important;
            border: none !important;
            outline: none !important;
        }
        a:hover { opacity: 0.9; }
        a:active { transform: scale(0.98); }

        button {
            transition: all 0.2s ease;
            border: none !important;
            outline: none !important;
        }
        button:hover { opacity: 0.9; }
        button:active { transform: scale(0.98); }
        button:focus {
            outline: 2px solid #2e466f !important;
            outline-offset: 2px;
            border: none !important;
        }

        input, select {
            transition: all 0.2s ease;
            border-color: #d1d5db !important;
        }
        input:focus, select:focus {
            ring: 2px;
            ring-color: #2e466f;
            border-color: #2e466f !important;
            outline: none !important;
        }
        input:hover, select:hover { border-color: #9ca3af !important; }

        .member-card { transition: all 0.3s ease; }
        .member-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.15); }

        /* Кнопки фильтра - без border */
        .filter-radio-label {
            border: none !important;
        }

        /* Стили для активных кнопок фильтра */
        .filter-radio:checked + div {
            background-color: #2e466f !important;
            color: white !important;
        }

        /* Селекты - увеличиваем padding справа для стрелки */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 2.5rem !important;
            background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 6L11 1' stroke='%236b7280' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
        }

        /* Loader animation */
        .loader {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #2e466f;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fade in animation для новых карточек */
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

        .member-card.fade-in {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
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

            <!-- Sidebar Filters - STICKY -->
            <aside class="w-full lg:w-80 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-sm border p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900">Фильтры</h2>
                        <a href="<?php echo get_post_type_archive_link('members'); ?>" class="text-sm metoda-primary hover:underline font-medium" style="border: none !important;">Сбросить</a>
                    </div>

                    <!-- Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
                        <div class="relative">
                            <input type="text" id="search-input" value="<?php echo esc_attr($search); ?>" placeholder="Поиск по имени..." class="w-full pl-11 pr-4 py-2.5 border border-gray-300 rounded-lg" style="padding-left: 2.75rem !important;">
                            <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Тип</label>
                        <div class="flex gap-2">
                            <label class="flex-1 filter-radio-label">
                                <input type="radio" name="member_type" value="" <?php checked($type_filter, ''); ?> class="sr-only filter-radio">
                                <div class="px-2 py-2 text-center rounded-lg font-medium text-xs cursor-pointer transition-all text-gray-600 bg-gray-100 hover:bg-gray-200">
                                    Все
                                </div>
                            </label>
                            <label class="flex-1 filter-radio-label">
                                <input type="radio" name="member_type" value="uchastnik" <?php checked($type_filter, 'uchastnik'); ?> class="sr-only filter-radio">
                                <div class="px-2 py-2 text-center rounded-lg font-medium text-xs cursor-pointer transition-all text-gray-600 bg-gray-100 hover:bg-gray-200">
                                    Участники
                                </div>
                            </label>
                            <label class="flex-1 filter-radio-label">
                                <input type="radio" name="member_type" value="ekspert" <?php checked($type_filter, 'ekspert'); ?> class="sr-only filter-radio">
                                <div class="px-2 py-2 text-center rounded-lg font-medium text-xs cursor-pointer transition-all text-gray-600 bg-gray-100 hover:bg-gray-200">
                                    Эксперты
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- City Filter -->
                    <?php if (!empty($cities)): ?>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                        <select id="city-filter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
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
                        <select id="role-filter" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg">
                            <option value="">Все роли...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo esc_attr($role->slug); ?>" <?php selected($role_filter, $role->slug); ?>>
                                    <?php echo esc_html($role->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button id="apply-filters" type="button" class="w-full metoda-primary-bg text-white py-2.5 rounded-lg hover:opacity-90 font-medium transition-all mb-2" style="border: none !important; outline: none !important;">
                        Применить фильтры
                    </button>
                    <?php
                    // Показываем кнопку "Очистить фильтр" только если есть активные фильтры
                    $has_active_filters = !empty($search) || !empty($city_filter) || !empty($role_filter) || !empty($type_filter);
                    ?>
                    <a href="<?php echo get_post_type_archive_link('members'); ?>" class="block w-full text-center bg-gray-100 text-gray-700 py-2.5 rounded-lg hover:bg-gray-200 font-medium transition-all <?php echo !$has_active_filters ? 'hidden' : ''; ?>" id="clear-filters-btn" style="border: none !important; text-decoration: none;">
                        <i class="fas fa-times mr-2"></i>Очистить фильтр
                    </a>
                </div>
            </aside>

            <!-- Members Grid -->
            <section class="flex-1">
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-gray-600">Показано <span id="shown-count" class="font-semibold text-gray-900"><?php echo count($paged_members); ?></span> из <span id="total-count" class="font-semibold text-gray-900"><?php echo $total_found; ?></span> участников</p>
                </div>

                <?php if (!empty($paged_members)): ?>
                <div id="members-grid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($paged_members as $post):
                        setup_postdata($post);
                        $member_id = $post->ID;
                        include(plugin_dir_path(__DIR__) . 'templates/member-card.php');
                    endforeach; wp_reset_postdata(); ?>
                </div>

                <!-- Load More Button -->
                <?php if ($total_found > count($paged_members)): ?>
                <div class="mt-8 text-center">
                    <button id="load-more-btn" data-offset="<?php echo $posts_per_page; ?>" data-total="<?php echo $total_found; ?>" class="metoda-primary-bg text-white px-8 py-3 rounded-lg font-medium transition-all inline-flex items-center gap-2" style="border: none !important; outline: none !important; background-color: #2e466f !important;">
                        <span>Показать еще</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Участники не найдены</h3>
                    <p class="text-gray-600 mb-4">Попробуйте изменить фильтры или поисковый запрос</p>
                    <a href="<?php echo get_post_type_archive_link('members'); ?>" class="inline-block metoda-primary-bg text-white px-6 py-2.5 rounded-lg hover:opacity-90 font-medium transition-all" style="border: none !important;">
                        Сбросить фильтры
                    </a>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
    jQuery(document).ready(function($) {
        let isLoading = false;

        // Load More Button
        $('#load-more-btn').on('click', function() {
            if (isLoading) return;

            const $btn = $(this);
            const offset = parseInt($btn.data('offset'));
            const total = parseInt($btn.data('total'));

            isLoading = true;
            $btn.prop('disabled', true);
            $btn.find('span').text('Загрузка...');
            $btn.find('i').removeClass('fa-chevron-down').addClass('fa-spinner fa-spin');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'load_more_members',
                    nonce: '<?php echo wp_create_nonce('public_members_nonce'); ?>',
                    offset: offset,
                    search: $('#search-input').val(),
                    city: $('#city-filter').val(),
                    role: $('#role-filter').val(),
                    member_type: $('input[name="member_type"]:checked').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Добавляем новые карточки
                        $('#members-grid').append(response.data.html);

                        // Обновляем offset
                        const newOffset = offset + response.data.count;
                        $btn.data('offset', newOffset);

                        // Обновляем счетчик
                        $('#shown-count').text(newOffset);

                        // Скрываем кнопку если больше нечего загружать
                        if (newOffset >= total) {
                            $btn.parent().fadeOut();
                        }

                        $btn.find('span').text('Показать еще');
                        $btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-chevron-down');
                    } else {
                        console.error('Load more failed:', response);
                        alert('Ошибка загрузки данных');
                        $btn.find('span').text('Показать еще');
                        $btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-chevron-down');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('Ошибка соединения с сервером');
                    $btn.find('span').text('Показать еще');
                    $btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-chevron-down');
                },
                complete: function() {
                    isLoading = false;
                    $btn.prop('disabled', false);
                }
            });
        });

        // Apply Filters
        $('#apply-filters').on('click', function() {
            applyFilters();
        });

        // Filter on radio change
        $('.filter-radio').on('change', function() {
            applyFilters();
        });

        // Search with delay
        let searchTimeout;
        $('#search-input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 500);
        });

        // Apply filters function
        function applyFilters() {
            if (isLoading) return;

            isLoading = true;
            const $grid = $('#members-grid');

            // Показываем индикатор загрузки
            $grid.css('opacity', '0.5');

            const filterData = {
                action: 'filter_members',
                nonce: '<?php echo wp_create_nonce('public_members_nonce'); ?>',
                search: $('#search-input').val(),
                city: $('#city-filter').val(),
                role: $('#role-filter').val(),
                member_type: $('input[name="member_type"]:checked').val()
            };

            console.log('Sending filter request:', filterData);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: filterData,
                success: function(response) {
                    console.log('Filter response:', response);
                    if (response.success) {
                        // Заменяем содержимое
                        $grid.html(response.data.html);

                        // Обновляем счетчики
                        $('#shown-count').text(response.data.shown);
                        $('#total-count').text(response.data.total);

                        // Показываем/скрываем кнопку "Показать еще"
                        if (response.data.has_more) {
                            $('#load-more-btn').data('offset', 12).data('total', response.data.total).parent().show();
                        } else {
                            $('#load-more-btn').parent().hide();
                        }

                        $grid.css('opacity', '1');
                    } else {
                        console.error('Filter failed:', response);
                        $grid.html('<div class="text-center text-red-600 p-8">Ошибка загрузки данных</div>');
                        $grid.css('opacity', '1');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error details:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    $grid.html('<div class="text-center text-red-600 p-8">Ошибка соединения с сервером. Проверьте консоль браузера.</div>');
                    $grid.css('opacity', '1');
                },
                complete: function() {
                    isLoading = false;
                }
            });
        }
    });
    </script>

    <?php wp_footer(); ?>
</body>
</html>
