<?php
/**
 * Template для архива форума (главная страница форума)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Получаем категории форума
$categories = get_terms(array(
    'taxonomy' => 'forum_category',
    'hide_empty' => false,
));

// Получаем последние топики
$args = array(
    'post_type' => 'forum_topic',
    'posts_per_page' => 20,
    'orderby' => 'date',
    'order' => 'DESC'
);

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'forum_category',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['category'])
        )
    );
}

$query = new WP_Query($args);

$primary_color = get_option('metoda_primary_color', '#0066cc');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форум сообщества - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php wp_head(); ?>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .forum-category-badge { transition: all 0.2s; }
        .forum-category-badge:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .forum-topic-row:hover { background-color: #f9fafb; }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo home_url(); ?>" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-comments mr-2" style="color: <?php echo $primary_color; ?>"></i>
                        Форум сообщества
                    </h1>
                </div>
                <?php if (is_user_logged_in()): ?>
                <a href="<?php echo home_url('/member-dashboard/'); ?>" class="px-4 py-2 rounded-lg text-white font-medium" style="background-color: <?php echo $primary_color; ?>">
                    <i class="fas fa-user mr-2"></i>
                    Личный кабинет
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Категории -->
        <?php if (!empty($categories)): ?>
        <div class="bg-white rounded-xl shadow-sm border p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Категории</h2>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo get_post_type_archive_link('forum_topic'); ?>" class="forum-category-badge px-4 py-2 rounded-lg border <?php echo (!isset($_GET['category']) || empty($_GET['category'])) ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'; ?>">
                    <i class="fas fa-th-large mr-2"></i>
                    Все темы
                </a>
                <?php foreach ($categories as $category): 
                    $is_active = isset($_GET['category']) && $_GET['category'] === $category->slug;
                ?>
                <a href="?category=<?php echo esc_attr($category->slug); ?>" class="forum-category-badge px-4 py-2 rounded-lg border <?php echo $is_active ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'; ?>">
                    <i class="fas fa-folder mr-2"></i>
                    <?php echo esc_html($category->name); ?>
                    <span class="ml-1 text-xs opacity-75">(<?php echo $category->count; ?>)</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Список топиков -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="border-b border-gray-200 p-4 bg-gray-50">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Обсуждения (<?php echo $query->found_posts; ?>)
                    </h2>
                </div>
            </div>

            <?php if ($query->have_posts()): ?>
            <div class="divide-y divide-gray-200">
                <?php while ($query->have_posts()): $query->the_post(); 
                    $author_member_id = get_post_meta(get_the_ID(), 'author_member_id', true);
                    $author_name = $author_member_id ? get_the_title($author_member_id) : get_the_author();
                    $comments_count = get_comments_number();
                    $likes_count = get_post_meta(get_the_ID(), 'likes_count', true) ?: 0;
                    $topic_categories = wp_get_post_terms(get_the_ID(), 'forum_category');
                ?>
                <a href="<?php the_permalink(); ?>" class="forum-topic-row block p-4 transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold" style="background-color: <?php echo $primary_color; ?>">
                                <?php echo strtoupper(substr($author_name, 0, 1)); ?>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <h3 class="text-base font-semibold text-gray-900 hover:text-blue-600 mb-1">
                                        <?php the_title(); ?>
                                    </h3>
                                    <div class="flex items-center gap-3 text-xs text-gray-500">
                                        <span><i class="fas fa-user mr-1"></i><?php echo esc_html($author_name); ?></span>
                                        <span><i class="fas fa-clock mr-1"></i><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' назад'; ?></span>
                                        <?php if (!empty($topic_categories)): ?>
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded">
                                                <i class="fas fa-folder mr-1"></i><?php echo esc_html($topic_categories[0]->name); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span><i class="fas fa-comments mr-1"></i><?php echo $comments_count; ?> <?php echo _n('ответ', 'ответов', $comments_count); ?></span>
                                <span><i class="fas fa-heart mr-1"></i><?php echo $likes_count; ?></span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($query->max_num_pages > 1): ?>
            <div class="border-t border-gray-200 p-4 bg-gray-50">
                <div class="flex justify-center gap-2">
                    <?php
                    $current_page = max(1, get_query_var('paged'));
                    for ($i = 1; $i <= $query->max_num_pages; $i++):
                        $is_current = $i === $current_page;
                    ?>
                    <a href="<?php echo add_query_arg('paged', $i); ?>" class="px-4 py-2 rounded-lg <?php echo $is_current ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                <p>Пока нет обсуждений в этой категории</p>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <?php wp_footer(); ?>
</body>
</html>

<?php wp_reset_postdata(); ?>
