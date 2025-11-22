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
    <div class="member-cabinet-header px-8 py-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Мои обсуждения</h2>
                    <p class="text-sm text-gray-500 mt-1">Управляйте темами и участвуйте в дискуссиях</p>
                </div>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="openCreateTopicModal()" class="px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition-all flex items-center gap-2 shadow-sm">
                        <i class="fas fa-plus"></i>
                        <span>Создать тему</span>
                    </button>
                    <button onclick="openCreatePollModal()" class="px-4 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg font-medium hover:from-purple-600 hover:to-purple-700 transition-all flex items-center gap-2 shadow-sm">
                        <i class="fas fa-poll"></i>
                        <span>Создать опрос</span>
                    </button>
                    <a href="<?php echo home_url('/forum'); ?>" target="_blank" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-all flex items-center gap-2">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Перейти на форум</span>
                    </a>
                </div>
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

        </div>
    </div>
</section>

<!-- Create Topic Modal -->
<div id="create-topic-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex-shrink-0 member-cabinet-header px-6 py-4 rounded-t-2xl flex items-center justify-between border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-blue-600">
                    <i class="fas fa-plus text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Создать новую тему</h3>
            </div>
            <button type="button" onclick="closeCreateTopicModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="create-topic-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Заголовок темы *</label>
                    <input type="text" name="title" required placeholder="О чем вы хотите поговорить?" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                    <select name="category" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
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
                    <textarea name="content" required rows="8" placeholder="Расскажите подробнее..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Создать тему</span>
                    </button>
                    <button type="button" onclick="closeCreateTopicModal()" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-all">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Poll Modal -->
<div id="create-poll-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex-shrink-0 member-cabinet-header px-6 py-4 rounded-t-2xl flex items-center justify-between border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center text-purple-600">
                    <i class="fas fa-poll text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Создать опрос</h3>
            </div>
            <button type="button" onclick="closeCreatePollModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            <form id="create-poll-form" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Вопрос опроса *</label>
                    <input type="text" name="poll_question" required placeholder="Какой вопрос вы хотите задать?" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Категория</label>
                    <select name="poll_category" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                        <option value="">Без категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Варианты ответа *</label>
                    <div id="poll-options-container" class="space-y-2 mb-2">
                        <div class="flex gap-2">
                            <input type="text" name="poll_options[]" required placeholder="Вариант 1" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                        </div>
                        <div class="flex gap-2">
                            <input type="text" name="poll_options[]" required placeholder="Вариант 2" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
                        </div>
                    </div>
                    <button type="button" onclick="addPollOption()" class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center gap-1">
                        <i class="fas fa-plus-circle"></i>
                        <span>Добавить вариант</span>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Описание (опционально)</label>
                    <textarea name="poll_description" rows="4" placeholder="Дополнительная информация об опросе..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none"></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg font-medium hover:from-purple-600 hover:to-purple-700 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-poll"></i>
                        <span>Создать опрос</span>
                    </button>
                    <button type="button" onclick="closeCreatePollModal()" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-all">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
            beforeSend: function() {
                $('#create-topic-form button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Создание...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Тема успешно создана!');
                    closeCreateTopicModal();
                    window.open(response.data.url, '_blank');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data.message);
                    $('#create-topic-form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus mr-2"></i>Создать тему');
                }
            },
            error: function() {
                alert('Произошла ошибка при создании темы');
                $('#create-topic-form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus mr-2"></i>Создать тему');
            }
        });
    });

    // Create poll form
    $('#create-poll-form').on('submit', function(e) {
        e.preventDefault();

        // Collect poll options
        var pollOptions = [];
        $('input[name="poll_options[]"]').each(function() {
            var value = $(this).val().trim();
            if (value) {
                pollOptions.push(value);
            }
        });

        if (pollOptions.length < 2) {
            alert('Необходимо минимум 2 варианта ответа');
            return;
        }

        var formData = {
            action: 'create_forum_poll_dashboard',
            nonce: memberDashboard.nonce,
            poll_question: $('input[name="poll_question"]').val(),
            poll_category: $('select[name="poll_category"]').val(),
            poll_options: pollOptions,
            poll_description: $('textarea[name="poll_description"]').val()
        };

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#create-poll-form button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Создание...');
            },
            success: function(response) {
                if (response.success) {
                    alert('Опрос успешно создан!');
                    closeCreatePollModal();
                    window.open(response.data.url, '_blank');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data.message);
                    $('#create-poll-form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-poll mr-2"></i>Создать опрос');
                }
            },
            error: function() {
                alert('Произошла ошибка при создании опроса');
                $('#create-poll-form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-poll mr-2"></i>Создать опрос');
            }
        });
    });
});

// Modal functions
function openCreateTopicModal() {
    jQuery('#create-topic-modal').removeClass('hidden').css('display', 'flex');
    jQuery('body').css('overflow', 'hidden');
    // Reset form
    jQuery('#create-topic-form')[0].reset();
}

function closeCreateTopicModal() {
    jQuery('#create-topic-modal').addClass('hidden').css('display', 'none');
    jQuery('body').css('overflow', 'auto');
    // Reset form and button
    jQuery('#create-topic-form')[0].reset();
    jQuery('#create-topic-form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-plus"></i><span>Создать тему</span>');
}

function openCreatePollModal() {
    jQuery('#create-poll-modal').removeClass('hidden').css('display', 'flex');
    jQuery('body').css('overflow', 'hidden');
    // Reset form
    jQuery('#create-poll-form')[0].reset();
    // Reset to 2 options
    jQuery('#poll-options-container').html(`
        <div class="flex gap-2">
            <input type="text" name="poll_options[]" required placeholder="Вариант 1" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
        </div>
        <div class="flex gap-2">
            <input type="text" name="poll_options[]" required placeholder="Вариант 2" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
        </div>
    `);
}

function closeCreatePollModal() {
    jQuery('#create-poll-modal').addClass('hidden').css('display', 'none');
    jQuery('body').css('overflow', 'auto');
    // Reset form and button
    jQuery('#create-poll-form')[0].reset();
    jQuery('#create-poll-form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-poll"></i><span>Создать опрос</span>');
}

function addPollOption() {
    var optionCount = jQuery('#poll-options-container .flex').length + 1;
    var newOption = `
        <div class="flex gap-2">
            <input type="text" name="poll_options[]" placeholder="Вариант ${optionCount}" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none">
            <button type="button" onclick="jQuery(this).parent().remove()" class="px-3 py-2.5 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    jQuery('#poll-options-container').append(newOption);
}
</script>
