<?php
/**
 * Dashboard Forum Section
 * Секция управления обсуждениями в личном кабинете
 */

if (!defined('ABSPATH')) exit;

$current_user_id = get_current_user_id();

// Получаем темы созданные участником
$my_topics_args = array(
    'post_type' => 'forum_topic',
    'author' => $current_user_id,
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
);
$my_topics = new WP_Query($my_topics_args);

// Получаем темы где участник оставлял комментарии
$my_replies = get_comments(array(
    'user_id' => $current_user_id,
    'post_type' => 'forum_topic',
    'status' => 'approve',
    'number' => 10
));

// Статистика
$total_my_topics = $my_topics->found_posts;
$total_my_replies = count($my_replies);
$total_my_likes = get_user_meta($current_user_id, 'forum_likes_received', true) ?: 0;
?>

<!-- Forum Section -->
<section id="forum-section" class="section-content hidden">
    <div class="bg-white border-b border-gray-200 px-8 py-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Мои обсуждения</h2>
                    <p class="text-sm text-gray-500 mt-1">Управляйте темами и участвуйте в дискуссиях</p>
                </div>
                <a href="<?php echo home_url('/forum'); ?>" target="_blank" class="px-4 py-2 text-white rounded-lg font-medium hover:opacity-90 transition-opacity" style="background-color: #0066cc;">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    Перейти на форум
                </a>
            </div>
        </div>
    </div>

    <div class="p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: rgba(0, 102, 204, 0.1);">
                            <i class="fas fa-comments text-xl" style="color: #0066cc;"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_my_topics; ?></p>
                            <p class="text-sm text-gray-500">Моих тем</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                            <i class="fas fa-reply text-xl text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_my_replies; ?></p>
                            <p class="text-sm text-gray-500">Ответов</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                            <i class="fas fa-heart text-xl text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_my_likes; ?></p>
                            <p class="text-sm text-gray-500">Лайков</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="flex gap-2 p-4">
                    <button class="forum-tab active px-6 py-2.5 rounded-lg font-medium text-sm transition-all" data-tab="my-topics">
                        💬 Мои темы (<?php echo $total_my_topics; ?>)
                    </button>
                    <button class="forum-tab px-6 py-2.5 rounded-lg font-medium text-sm transition-all" data-tab="my-replies">
                        💭 Мои ответы (<?php echo $total_my_replies; ?>)
                    </button>
                    <button class="forum-tab px-6 py-2.5 rounded-lg font-medium text-sm transition-all" data-tab="create-topic">
                        ➕ Создать тему
                    </button>
                </div>
            </div>

            <!-- My Topics -->
            <div class="forum-tab-content active" data-content="my-topics">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Темы которые я создал</h3>

                    <?php if ($my_topics->have_posts()): ?>
                        <div class="space-y-4">
                            <?php while ($my_topics->have_posts()): $my_topics->the_post(); ?>
                                <div class="p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-all">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <a href="<?php the_permalink(); ?>" target="_blank" class="text-lg font-semibold text-gray-900 hover:text-primary mb-2 block">
                                                <?php the_title(); ?>
                                            </a>
                                            <div class="flex items-center gap-4 text-sm text-gray-500">
                                                <span><i class="fas fa-comments mr-1"></i><?php comments_number('0 ответов', '1 ответ', '% ответов'); ?></span>
                                                <span><i class="fas fa-eye mr-1"></i><?php echo get_post_meta(get_the_ID(), 'views_count', true) ?: 0; ?> просмотров</span>
                                                <span><i class="fas fa-calendar mr-1"></i><?php echo get_the_date('d.m.Y'); ?></span>
                                            </div>
                                        </div>
                                        <a href="<?php echo get_edit_post_link(); ?>" class="text-blue-600 hover:text-blue-700">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">💬</div>
                            <p class="text-gray-500">Вы еще не создали ни одной темы</p>
                            <p class="text-sm text-gray-400 mt-1">Перейдите на вкладку "Создать тему"</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Replies -->
            <div class="forum-tab-content hidden" data-content="my-replies">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Темы где я оставил ответы</h3>

                    <?php if (!empty($my_replies)): ?>
                        <div class="space-y-4">
                            <?php
                            $replied_topics = array();
                            foreach ($my_replies as $reply):
                                if (in_array($reply->comment_post_ID, $replied_topics)) continue;
                                $replied_topics[] = $reply->comment_post_ID;
                                $topic = get_post($reply->comment_post_ID);
                            ?>
                                <div class="p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-all">
                                    <a href="<?php echo get_permalink($topic); ?>#comment-<?php echo $reply->comment_ID; ?>" target="_blank" class="text-lg font-semibold text-gray-900 hover:text-primary mb-2 block">
                                        <?php echo get_the_title($topic); ?>
                                    </a>
                                    <div class="text-sm text-gray-600 mb-2">
                                        <?php echo wp_trim_words($reply->comment_content, 20); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i><?php echo human_time_diff(strtotime($reply->comment_date), current_time('timestamp')); ?> назад
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">💭</div>
                            <p class="text-gray-500">Вы еще не оставили ни одного ответа</p>
                            <p class="text-sm text-gray-400 mt-1">Участвуйте в обсуждениях на форуме</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create Topic -->
            <div class="forum-tab-content hidden" data-content="create-topic">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Создать новую тему</h3>

                    <form id="create-topic-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Заголовок темы *</label>
                            <input type="text" name="title" required placeholder="О чем вы хотите поговорить?" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                            <select name="category" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                <option value="">Без категории</option>
                                <?php
                                $categories = get_terms(array('taxonomy' => 'forum_category', 'hide_empty' => false));
                                foreach ($categories as $cat):
                                ?>
                                    <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Описание темы *</label>
                            <textarea name="content" required rows="8" placeholder="Расскажите подробнее..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none"></textarea>
                        </div>

                        <button type="submit" class="w-full px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity" style="background-color: #0066cc;">
                            <i class="fas fa-plus mr-2"></i>
                            Создать тему
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .forum-tab {
        background: #f3f4f6;
        color: #6b7280;
    }

    .forum-tab:hover {
        background: #e5e7eb;
    }

    .forum-tab.active {
        background: #0066cc;
        color: white;
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.forum-tab').on('click', function() {
        var tab = $(this).data('tab');

        $('.forum-tab').removeClass('active');
        $(this).addClass('active');

        $('.forum-tab-content').addClass('hidden').removeClass('active');
        $('.forum-tab-content[data-content="' + tab + '"]').removeClass('hidden').addClass('active');
    });

    // Create topic form
    $('#create-topic-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serialize();
        formData += '&action=create_forum_topic_dashboard&nonce=' + memberDashboard.nonce;

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Тема успешно создана!');
                    window.open(response.data.url, '_blank');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data.message);
                }
            }
        });
    });
});
</script>
