<?php
/**
 * Template Name: Manager Panel
 * Панель управления для менеджеров ассоциации
 */

if (!defined('ABSPATH')) exit;

// KILL SWITCH: Не редиректим если отключены редиректы
if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
    echo '<div style="padding: 20px; background: #ffeb3b; border: 2px solid #ff9800;">';
    echo '<h3>⚠️ Редиректы отключены (METODA_DISABLE_REDIRECTS)</h3>';
    if (is_user_logged_in()) {
        echo '<p>Вы авторизованы. <a href="' . admin_url() . '">Перейти в админку →</a></p>';
    } else {
        echo '<p>Вам нужно авторизоваться. <a href="' . wp_login_url() . '">Войти →</a></p>';
    }
    echo '</div>';
    return;
}

// Не показываем в админке
if (is_admin()) {
    return;
}

// Проверка доступа
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
if (!in_array('manager', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
    wp_redirect(home_url());
    exit;
}

// Цвета Метода
$primary_color = '#0066cc';
$accent_color = '#ff6600';

// Получаем статистику
global $wpdb;
$total_members = wp_count_posts('members')->publish;
$pending_members = wp_count_posts('members')->pending;
$draft_members = wp_count_posts('members')->draft;

// Получаем города для фильтра
$cities = $wpdb->get_col($wpdb->prepare("
    SELECT DISTINCT meta_value
    FROM {$wpdb->postmeta}
    WHERE meta_key = %s
    AND meta_value != ''
    ORDER BY meta_value ASC
", 'member_city'));

// Получаем типы участников
$member_types = get_terms(array(
    'taxonomy' => 'member_type',
    'hide_empty' => false
));

// Получаем участников для таблицы
$args = array(
    'post_type' => 'members',
    'posts_per_page' => 20,
    'post_status' => array('publish', 'pending', 'draft'),
    'orderby' => 'date',
    'order' => 'DESC'
);

// Применяем фильтры если есть
if (isset($_GET['member_city']) && !empty($_GET['member_city'])) {
    $args['meta_query'][] = array(
        'key' => 'member_city',
        'value' => sanitize_text_field($_GET['member_city']),
        'compare' => '='
    );
}

if (isset($_GET['member_status']) && !empty($_GET['member_status'])) {
    $args['post_status'] = sanitize_text_field($_GET['member_status']);
}

if (isset($_GET['s']) && !empty($_GET['s'])) {
    $args['s'] = sanitize_text_field($_GET['s']);
}

$members_query = new WP_Query($args);
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        * { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'admin-blue': '<?php echo $primary_color; ?>',
                        'admin-gray': '#f8fafc',
                        'status-active': '#10b981',
                        'status-pending': '#f59e0b',
                        'status-blocked': '#ef4444',
                        'status-draft': '#6b7280'
                    }
                }
            }
        }
    </script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg border-r border-gray-200 z-50">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: <?php echo $primary_color; ?>">
                <i class="fas fa-users text-white text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Метода</h1>
                <p class="text-xs text-gray-500">Панель управления</p>
            </div>
        </div>
    </div>

    <nav class="mt-6">
        <a href="#dashboard" id="nav-dashboard" class="flex items-center px-6 py-3 text-gray-700 hover:bg-admin-gray hover:border-r-4 hover:border-admin-blue transition-all">
            <i class="fa-solid fa-chart-line w-5 h-5 mr-3"></i>
            Статистика
        </a>
        <a href="#members" id="nav-members" class="flex items-center px-6 py-3 bg-admin-gray border-r-4 border-admin-blue text-admin-blue font-medium">
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
            <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold">
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
    <header id="header" class="bg-white shadow-sm border-b border-gray-200 px-8 py-4 sticky top-0 z-40">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold text-gray-800">Управление участниками</h2>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <i class="fa-solid fa-bell text-gray-500 cursor-pointer"></i>
                    <?php if ($pending_members > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?php echo $pending_members; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Stats Cards -->
    <div class="p-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Всего участников</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $total_members; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">На модерации</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $pending_members; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Черновики</p>
                        <p class="text-3xl font-bold text-gray-600"><?php echo $draft_members; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file text-gray-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Городов</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo count($cities); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Bar -->
        <div id="controls-bar" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <a href="<?php echo admin_url('post-new.php?post_type=members'); ?>" class="bg-admin-blue text-white px-6 py-2 rounded-lg font-medium hover:opacity-90 transition-all">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Добавить участника
                </a>
                <div class="text-sm text-gray-600">
                    Найдено: <span class="font-semibold"><?php echo $members_query->found_posts; ?></span>
                </div>
            </div>

            <!-- Search and Filters -->
            <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" placeholder="Поиск по имени или компании..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue focus:border-transparent">
                </div>
                <select name="member_status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                    <option value="">Все статусы</option>
                    <option value="publish" <?php selected(isset($_GET['member_status']) && $_GET['member_status'] == 'publish'); ?>>Активные</option>
                    <option value="pending" <?php selected(isset($_GET['member_status']) && $_GET['member_status'] == 'pending'); ?>>На модерации</option>
                    <option value="draft" <?php selected(isset($_GET['member_status']) && $_GET['member_status'] == 'draft'); ?>>Черновики</option>
                </select>
                <select name="member_city" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-admin-blue">
                    <option value="">Все города</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo esc_attr($city); ?>" <?php selected(isset($_GET['member_city']) && $_GET['member_city'] == $city); ?>>
                            <?php echo esc_html($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-200 transition-all">
                    <i class="fas fa-filter mr-2"></i>Применить
                </button>
            </form>
        </div>

        <!-- Members Table -->
        <div id="members-table" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ФИО</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Компания</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Город</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Регистрация</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($members_query->have_posts()): ?>
                        <?php while ($members_query->have_posts()): $members_query->the_post();
                            $member_id = get_the_ID();
                            $company = get_post_meta($member_id, 'member_company', true);
                            $city = get_post_meta($member_id, 'member_city', true);
                            $email = get_post_meta($member_id, 'member_email', true);
                            $status = get_post_status($member_id);

                            $status_colors = array(
                                'publish' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'draft' => 'bg-gray-100 text-gray-800'
                            );

                            $status_labels = array(
                                'publish' => 'Активен',
                                'pending' => 'На модерации',
                                'draft' => 'Черновик'
                            );
                        ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if (has_post_thumbnail($member_id)): ?>
                                        <?php echo get_the_post_thumbnail($member_id, 'thumbnail', array('class' => 'w-8 h-8 rounded-full mr-3 object-cover')); ?>
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3 text-gray-600 font-bold text-sm">
                                            <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php the_title(); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo esc_html($email); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo esc_html($company ?: '—'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo esc_html($city ?: '—'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo get_the_date('d.m.Y'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_colors[$status]; ?>">
                                    <?php echo $status_labels[$status]; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <a href="<?php echo get_edit_post_link($member_id); ?>" class="text-admin-blue hover:underline font-medium">
                                    Редактировать
                                </a>
                                <button onclick="changeMemberStatus(<?php echo $member_id; ?>, 'publish')" class="text-green-600 hover:underline font-medium">
                                    Одобрить
                                </button>
                                <button onclick="deleteMember(<?php echo $member_id; ?>)" class="text-red-600 hover:underline font-medium">
                                    Удалить
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                <p>Участники не найдены</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($members_query->max_num_pages > 1): ?>
        <div id="pagination" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Показано с <span class="font-medium">1</span> по <span class="font-medium"><?php echo min(20, $members_query->found_posts); ?></span> из <span class="font-medium"><?php echo $members_query->found_posts; ?></span>
            </div>
            <div class="flex space-x-2">
                <?php
                echo paginate_links(array(
                    'total' => $members_query->max_num_pages,
                    'prev_text' => 'Назад',
                    'next_text' => 'Вперед',
                    'type' => 'list'
                ));
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function changeMemberStatus(memberId, status) {
    if (!confirm('Изменить статус участника?')) return;

    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'manager_change_member_status',
            nonce: '<?php echo wp_create_nonce("manager_actions"); ?>',
            member_id: memberId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert(data.data.message || 'Ошибка при изменении статуса');
        }
    })
    .catch(error => {
        alert('Произошла ошибка');
    });
}

function deleteMember(memberId) {
    if (!confirm('Вы уверены, что хотите удалить этого участника? Это действие необратимо.')) return;

    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'manager_delete_member',
            nonce: '<?php echo wp_create_nonce("manager_actions"); ?>',
            member_id: memberId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert(data.data.message || 'Ошибка при удалении');
        }
    })
    .catch(error => {
        alert('Произошла ошибка');
    });
}
</script>

<?php wp_footer(); ?>
<?php wp_reset_postdata(); ?>
</body>
</html>
