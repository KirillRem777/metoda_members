<?php
/**
 * Forum Topic Template
 * Displays single forum topic with replies
 */

if (!defined('ABSPATH')) exit;

// Increment views
Member_Forum::increment_views(get_the_ID());

$topic_id = get_the_ID();
$stats = Member_Forum::get_topic_stats($topic_id);
$author_id = get_post_field('post_author', $topic_id);
$member_id = get_user_meta($author_id, 'member_id', true);
$avatar_url = get_the_post_thumbnail_url($member_id, 'thumbnail');
$categories_list = wp_get_post_terms($topic_id, 'forum_category');
$current_user_id = get_current_user_id();

// Check if user liked this topic
$liked_topics = get_user_meta($current_user_id, 'forum_liked_topics', true);
$liked_topics = $liked_topics ? explode(',', $liked_topics) : array();
$is_liked = in_array($topic_id, $liked_topics);

// Check if user subscribed
$subscribers = get_post_meta($topic_id, 'forum_subscribers', true);
$subscribers = $subscribers ? explode(',', $subscribers) : array();
$is_subscribed = in_array($current_user_id, $subscribers);

// Get comments
$comments = get_comments(array(
    'post_id' => $topic_id,
    'status' => 'approve',
    'hierarchical' => 'threaded',
    'order' => 'ASC',
));

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> - Форум - <?php bloginfo('name'); ?></title>
    <?php metoda_enqueue_frontend_styles(); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .metoda-primary { color: #0066cc; }
        .metoda-primary-bg { background-color: #0066cc; }
        .metoda-accent { color: #ff6600; }
    </style>
    <script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

<div class="min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="<?php echo get_post_type_archive_link('forum_topic'); ?>" class="flex items-center gap-3 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left"></i>
                    <span class="font-medium">Вернуться к форуму</span>
                </a>
                <?php if (is_user_logged_in()): ?>
                <div class="flex items-center gap-2">
                    <?php if (current_user_can('manage_options')): ?>
                    <button class="pin-topic px-4 py-2 rounded-lg <?php echo $stats['is_pinned'] ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'; ?> hover:bg-gray-200 transition-colors text-sm font-medium" data-topic-id="<?php echo $topic_id; ?>">
                        <i class="fas fa-thumbtack"></i> <?php echo $stats['is_pinned'] ? 'Открепить' : 'Закрепить'; ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-6 py-8">

        <!-- Topic Card -->
        <article class="bg-white rounded-xl shadow-sm border p-8 mb-6">
            <!-- Topic Header -->
            <div class="flex items-start gap-4 mb-6">
                <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                    <?php if ($avatar_url): ?>
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php the_author(); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-300">
                            <?php echo mb_substr(get_the_author(), 0, 1); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-xl font-bold text-gray-900"><?php the_author(); ?></h2>
                        <?php if ($categories_list): ?>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                                <?php echo esc_html($categories_list[0]->name); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($stats['is_pinned']): ?>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full">
                                <i class="fas fa-thumbtack"></i> Закреплено
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-gray-500">
                        <i class="far fa-clock"></i> <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' назад'; ?>
                    </div>
                </div>
            </div>

            <!-- Topic Title -->
            <h1 class="text-3xl font-bold text-gray-900 mb-6"><?php the_title(); ?></h1>

            <!-- Topic Content -->
            <div class="prose prose-lg max-w-none mb-6 text-gray-700">
                <?php the_content(); ?>
            </div>

            <!-- Topic Stats & Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                <div class="flex items-center gap-6 text-sm text-gray-500">
                    <span><i class="fas fa-eye"></i> <?php echo $stats['views']; ?> просмотров</span>
                    <span><i class="fas fa-comment"></i> <span class="replies-count"><?php echo $stats['replies']; ?></span> ответов</span>
                    <span><i class="fas fa-heart"></i> <span class="likes-count"><?php echo $stats['likes']; ?></span> лайков</span>
                </div>

                <?php if (is_user_logged_in()): ?>
                <div class="flex items-center gap-3">
                    <button class="like-topic px-4 py-2 rounded-lg font-medium transition-colors <?php echo $is_liked ? 'bg-red-50 text-red-600 liked' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>" data-topic-id="<?php echo $topic_id; ?>">
                        <i class="fas fa-heart"></i> <span class="like-count"><?php echo $stats['likes']; ?></span>
                    </button>
                    <button class="subscribe-topic px-4 py-2 rounded-lg font-medium transition-colors <?php echo $is_subscribed ? 'bg-blue-50 text-blue-600 subscribed' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>" data-topic-id="<?php echo $topic_id; ?>">
                        <i class="fas fa-<?php echo $is_subscribed ? 'bell' : 'bell-slash'; ?>"></i> <?php echo $is_subscribed ? 'Подписаны' : 'Подписаться'; ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </article>

        <!-- Replies Section -->
        <div class="bg-white rounded-xl shadow-sm border p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-comments metoda-primary"></i>
                Ответы (<span class="replies-count"><?php echo $stats['replies']; ?></span>)
            </h3>

            <!-- Replies List -->
            <div class="forum-replies space-y-6 mb-8">
                <?php if ($comments): ?>
                    <?php foreach ($comments as $comment):
                        $comment_author_id = $comment->user_id;
                        $comment_member_id = get_user_meta($comment_author_id, 'member_id', true);
                        $comment_avatar_url = get_the_post_thumbnail_url($comment_member_id, 'thumbnail');

                        // Check if user liked this comment
                        $liked_comments = get_user_meta($current_user_id, 'forum_liked_comments', true);
                        $liked_comments = $liked_comments ? explode(',', $liked_comments) : array();
                        $comment_is_liked = in_array($comment->comment_ID, $liked_comments);

                        $comment_likes = get_comment_meta($comment->comment_ID, 'forum_likes', true);
                        $comment_likes = $comment_likes ? intval($comment_likes) : 0;
                    ?>
                    <div class="forum-reply flex gap-4 p-4 rounded-lg hover:bg-gray-50 transition-colors <?php echo $comment->comment_parent ? 'ml-12 border-l-2 border-gray-200' : ''; ?>" id="comment-<?php echo $comment->comment_ID; ?>">
                        <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                            <?php if ($comment_avatar_url): ?>
                                <img src="<?php echo esc_url($comment_avatar_url); ?>" alt="<?php echo esc_attr($comment->comment_author); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-lg font-bold text-gray-300">
                                    <?php echo mb_substr($comment->comment_author, 0, 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-semibold text-gray-900"><?php echo esc_html($comment->comment_author); ?></span>
                                <span class="text-sm text-gray-500">
                                    <?php echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' назад'; ?>
                                </span>
                            </div>
                            <div class="text-gray-700 mb-3">
                                <?php echo wpautop($comment->comment_content); ?>
                            </div>
                            <div class="flex items-center gap-4">
                                <?php if (is_user_logged_in()): ?>
                                <button class="like-reply text-sm font-medium transition-colors <?php echo $comment_is_liked ? 'text-red-600 liked' : 'text-gray-500 hover:text-red-600'; ?>" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                    <i class="fas fa-heart"></i> <span class="like-count"><?php echo $comment_likes; ?></span>
                                </button>
                                <button class="reply-to-comment text-sm font-medium text-gray-500 hover:text-blue-600 transition-colors" data-comment-id="<?php echo $comment->comment_ID; ?>">
                                    <i class="fas fa-reply"></i> Ответить
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-comments text-4xl mb-3 text-gray-300"></i>
                        <p class="text-lg">Пока нет ответов</p>
                        <p class="text-sm">Будьте первым, кто ответит!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reply Form -->
            <?php if (is_user_logged_in()): ?>
                <?php
                $current_user = wp_get_current_user();
                $current_member_id = get_user_meta($current_user_id, 'member_id', true);
                $current_avatar_url = get_the_post_thumbnail_url($current_member_id, 'thumbnail');
                ?>
                <form class="reply-form main-reply-form flex gap-4" data-topic-id="<?php echo $topic_id; ?>">
                    <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                        <?php if ($current_avatar_url): ?>
                            <img src="<?php echo esc_url($current_avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-lg font-bold text-gray-300">
                                <?php echo mb_substr($current_user->display_name, 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <textarea name="reply_content" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent resize-none" placeholder="Напишите ваш ответ..." required></textarea>
                        <div class="flex justify-end mt-3">
                            <button type="submit" class="px-6 py-3 metoda-primary-bg text-white rounded-lg hover:opacity-90 font-medium transition-opacity">
                                <i class="fas fa-paper-plane"></i> Отправить ответ
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                    <i class="fas fa-lock text-3xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 mb-3">Войдите, чтобы оставить ответ</p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="inline-block px-6 py-2 metoda-primary-bg text-white rounded-lg hover:opacity-90 font-medium">
                        Войти
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php wp_footer(); ?>
</body>
</html>
