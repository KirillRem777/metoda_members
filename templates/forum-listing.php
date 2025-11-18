<?php
/**
 * Forum Listing Template
 * Displays list of forum topics
 */

if (!defined('ABSPATH')) exit;

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Query args
$args = array(
    'post_type' => 'forum_topic',
    'posts_per_page' => 20,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
);

if ($category) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'forum_category',
            'field' => 'term_id',
            'terms' => $category,
        ),
    );
}

if ($search) {
    $args['s'] = $search;
}

// Get pinned topics separately
$pinned_args = $args;
$pinned_args['posts_per_page'] = -1;
$pinned_args['meta_query'] = array(
    array(
        'key' => 'forum_pinned',
        'value' => '1',
        'compare' => '='
    )
);

$pinned_query = new WP_Query($pinned_args);
$topics_query = new WP_Query($args);

// Get categories
$categories = get_terms(array(
    'taxonomy' => 'forum_category',
    'hide_empty' => false,
));

$current_user_id = get_current_user_id();
?>

<div class="metoda-forum">
    <!-- Forum Header -->
    <div class="forum-header">
        <div class="forum-header-content">
            <h1 class="forum-title">💬 Форум участников</h1>
            <p class="forum-subtitle">Общайтесь, делитесь опытом, находите партнеров для проектов</p>
        </div>
        <button id="create-topic-btn" class="btn-primary">
            <i class="fas fa-plus"></i> Создать тему
        </button>
    </div>

    <!-- Forum Filters -->
    <div class="forum-filters">
        <div class="forum-categories">
            <a href="?<?php echo $search ? 's=' . $search : ''; ?>" class="category-filter <?php echo !$category ? 'active' : ''; ?>">
                Все темы
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?php echo $cat->term_id; ?><?php echo $search ? '&s=' . $search : ''; ?>"
                   class="category-filter <?php echo $category == $cat->term_id ? 'active' : ''; ?>">
                    <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <form class="forum-search" method="get">
            <input type="search" name="s" placeholder="Поиск по темам..." value="<?php echo esc_attr($search); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <!-- Pinned Topics -->
    <?php if ($pinned_query->have_posts() && $paged == 1): ?>
        <div class="forum-section">
            <h3 class="section-title"><i class="fas fa-thumbtack"></i> Закрепленные темы</h3>
            <div class="forum-topics">
                <?php while ($pinned_query->have_posts()): $pinned_query->the_post();
                    $topic_id = get_the_ID();
                    $stats = Member_Forum::get_topic_stats($topic_id);
                    $author_id = get_post_field('post_author', $topic_id);
                    $member_id = get_user_meta($author_id, 'member_id', true);
                    $avatar_url = get_the_post_thumbnail_url($member_id, 'thumbnail');
                    $categories_list = wp_get_post_terms($topic_id, 'forum_category');
                    ?>
                    <div class="forum-topic-card pinned">
                        <div class="topic-avatar">
                            <?php if ($avatar_url): ?>
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?php echo substr(get_the_author(), 0, 1); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="topic-content">
                            <div class="topic-header">
                                <h3 class="topic-title">
                                    <i class="fas fa-thumbtack pin-icon"></i>
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <?php if ($categories_list): ?>
                                    <span class="topic-category"><?php echo esc_html($categories_list[0]->name); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="topic-meta">
                                <span class="topic-author">
                                    <i class="fas fa-user"></i> <?php the_author(); ?>
                                </span>
                                <span class="topic-date">
                                    <i class="fas fa-clock"></i> <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' назад'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="topic-stats">
                            <div class="stat-item">
                                <i class="fas fa-comment"></i>
                                <span><?php echo $stats['replies']; ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-heart"></i>
                                <span><?php echo $stats['likes']; ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo $stats['views']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Regular Topics -->
    <div class="forum-section">
        <?php if (!$pinned_query->have_posts() || $paged > 1): ?>
            <h3 class="section-title"><i class="fas fa-comments"></i> Все обсуждения</h3>
        <?php endif; ?>

        <?php if ($topics_query->have_posts()): ?>
            <div class="forum-topics">
                <?php while ($topics_query->have_posts()): $topics_query->the_post();
                    // Skip if pinned (already shown above)
                    if (get_post_meta(get_the_ID(), 'forum_pinned', true) && $paged == 1) {
                        continue;
                    }

                    $topic_id = get_the_ID();
                    $stats = Member_Forum::get_topic_stats($topic_id);
                    $author_id = get_post_field('post_author', $topic_id);
                    $member_id = get_user_meta($author_id, 'member_id', true);
                    $avatar_url = get_the_post_thumbnail_url($member_id, 'thumbnail');
                    $categories_list = wp_get_post_terms($topic_id, 'forum_category');
                    ?>
                    <div class="forum-topic-card">
                        <div class="topic-avatar">
                            <?php if ($avatar_url): ?>
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?php echo substr(get_the_author(), 0, 1); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="topic-content">
                            <div class="topic-header">
                                <h3 class="topic-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                <?php if ($categories_list): ?>
                                    <span class="topic-category"><?php echo esc_html($categories_list[0]->name); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="topic-excerpt">
                                <?php echo wp_trim_words(get_the_content(), 30); ?>
                            </div>
                            <div class="topic-meta">
                                <span class="topic-author">
                                    <i class="fas fa-user"></i> <?php the_author(); ?>
                                </span>
                                <span class="topic-date">
                                    <i class="fas fa-clock"></i> <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' назад'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="topic-stats">
                            <div class="stat-item">
                                <i class="fas fa-comment"></i>
                                <span><?php echo $stats['replies']; ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-heart"></i>
                                <span><?php echo $stats['likes']; ?></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-eye"></i>
                                <span><?php echo $stats['views']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($topics_query->max_num_pages > 1): ?>
                <div class="forum-pagination">
                    <?php
                    echo paginate_links(array(
                        'total' => $topics_query->max_num_pages,
                        'current' => $paged,
                        'prev_text' => '<i class="fas fa-chevron-left"></i>',
                        'next_text' => '<i class="fas fa-chevron-right"></i>',
                    ));
                    ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="forum-empty">
                <i class="fas fa-comments"></i>
                <p>Пока нет тем для обсуждения</p>
                <p class="small">Будьте первым, кто начнет обсуждение!</p>
            </div>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>

<!-- Create Topic Modal -->
<div id="create-topic-modal" class="forum-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Создать новую тему</h3>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form id="create-topic-form" class="modal-body">
            <div class="form-group">
                <label>Заголовок темы</label>
                <input type="text" id="topic-title" placeholder="О чем хотите поговорить?" required>
            </div>

            <div class="form-group">
                <label>Категория</label>
                <select id="topic-category">
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Описание</label>
                <textarea id="topic-content" rows="8" placeholder="Расскажите подробнее..." required></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">Отмена</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Создать тему
                </button>
            </div>
        </form>
    </div>
</div>
