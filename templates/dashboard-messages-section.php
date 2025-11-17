<?php
/**
 * Dashboard Messages Section
 * Секция личных сообщений в личном кабинете
 */

if (!defined('ABSPATH')) exit;

// Получаем ID текущего участника
$current_member_id = $member_id;

// Получаем входящие сообщения
$inbox_args = array(
    'post_type' => 'member_message',
    'posts_per_page' => 50,
    'meta_query' => array(
        array(
            'key' => 'recipient_member_id',
            'value' => $current_member_id,
            'compare' => '='
        )
    ),
    'orderby' => 'date',
    'order' => 'DESC'
);
$inbox_messages = get_posts($inbox_args);
$unread_count = 0;
foreach ($inbox_messages as $msg) {
    if (!get_post_meta($msg->ID, 'is_read', true)) {
        $unread_count++;
    }
}

// Получаем отправленные сообщения
$sent_args = array(
    'post_type' => 'member_message',
    'posts_per_page' => 50,
    'meta_query' => array(
        array(
            'key' => 'sender_member_id',
            'value' => $current_member_id,
            'compare' => '='
        )
    ),
    'orderby' => 'date',
    'order' => 'DESC'
);
$sent_messages = get_posts($sent_args);

// Получаем список всех участников для выбора получателя
$all_members = get_posts(array(
    'post_type' => 'members',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
    'post__not_in' => array($current_member_id) // Исключаем себя
));
?>

<!-- Messages Section -->
<section id="messages-section" class="section-content hidden">
    <div class="bg-white border-b border-gray-200 px-8 py-6">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900">Сообщения</h2>
            <p class="text-sm text-gray-500 mt-1">Внутренняя почта Методы</p>
        </div>
    </div>

    <div class="p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="flex gap-2 p-4">
                    <button class="message-tab active px-4 py-2.5 rounded-lg font-medium text-sm transition-all" data-tab="inbox" style="background-color: <?php echo $primary_color; ?>; color: white;">
                        📥 Входящие
                        <?php if ($unread_count > 0): ?>
                        <span class="ml-2 px-2 py-0.5 bg-white bg-opacity-30 rounded-full text-xs"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="message-tab px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-gray-100 text-gray-700" data-tab="sent">
                        📤 Отправленные <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($sent_messages); ?></span>
                    </button>
                    <button class="message-tab px-4 py-2.5 rounded-lg font-medium text-sm transition-all bg-gray-100 text-gray-700" data-tab="compose">
                        ✏️ Написать
                    </button>
                </div>
            </div>

            <!-- Inbox Tab -->
            <div class="message-tab-content" id="tab-inbox">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Входящие сообщения</h3>

                    <?php if (empty($inbox_messages)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-5xl mb-4 opacity-30"></i>
                            <p>Нет входящих сообщений</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($inbox_messages as $message):
                                $sender_id = get_post_meta($message->ID, 'sender_member_id', true);
                                $is_read = get_post_meta($message->ID, 'is_read', true);
                            ?>
                            <div class="message-item p-4 border rounded-lg hover:border-gray-300 transition-all cursor-pointer <?php echo !$is_read ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200'; ?>" data-message-id="<?php echo $message->ID; ?>">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-semibold text-gray-900"><?php echo get_the_title($sender_id); ?></span>
                                            <?php if (!$is_read): ?>
                                            <span class="px-2 py-0.5 bg-blue-500 text-white text-xs rounded-full">Новое</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm font-medium text-gray-700 mb-1"><?php echo esc_html($message->post_title); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo human_time_diff(strtotime($message->post_date), current_time('timestamp')) . ' назад'; ?></p>
                                    </div>
                                    <button class="view-message-btn text-blue-600 hover:text-blue-700" data-id="<?php echo $message->ID; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sent Tab -->
            <div class="message-tab-content hidden" id="tab-sent">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Отправленные сообщения</h3>

                    <?php if (empty($sent_messages)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-paper-plane text-5xl mb-4 opacity-30"></i>
                            <p>Нет отправленных сообщений</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($sent_messages as $message):
                                $recipient_id = get_post_meta($message->ID, 'recipient_member_id', true);
                            ?>
                            <div class="message-item p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-all cursor-pointer bg-white" data-message-id="<?php echo $message->ID; ?>">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm text-gray-500">Кому:</span>
                                            <span class="font-semibold text-gray-900"><?php echo get_the_title($recipient_id); ?></span>
                                        </div>
                                        <p class="text-sm font-medium text-gray-700 mb-1"><?php echo esc_html($message->post_title); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo human_time_diff(strtotime($message->post_date), current_time('timestamp')) . ' назад'; ?></p>
                                    </div>
                                    <button class="view-message-btn text-blue-600 hover:text-blue-700" data-id="<?php echo $message->ID; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Compose Tab -->
            <div class="message-tab-content hidden" id="tab-compose">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">✏️ Новое сообщение</h3>

                    <form id="compose-message-form">
                        <div class="space-y-4">
                            <!-- Получатель -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Кому *</label>
                                <select name="recipient_id_compose" id="compose_recipient" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none">
                                    <option value="">Выберите получателя</option>
                                    <?php foreach ($all_members as $member): ?>
                                    <option value="<?php echo $member->ID; ?>"><?php echo get_the_title($member->ID); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Тема -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Тема *</label>
                                <input type="text" name="subject_compose" id="compose_subject" required maxlength="200" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none" placeholder="О чем вы хотите написать?">
                            </div>

                            <!-- Сообщение -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Сообщение *</label>
                                <div class="quill-editor-wrapper">
                                    <div id="compose-editor" class="quill-editor"></div>
                                </div>
                                <textarea name="content_compose" id="compose_content_hidden" style="display: none;"></textarea>
                            </div>

                            <!-- Honeypot -->
                            <div style="position: absolute; left: -5000px;">
                                <input type="text" name="website_compose" id="compose_website" tabindex="-1" autocomplete="off">
                            </div>

                            <button type="submit" class="w-full px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity" style="background-color: <?php echo $primary_color; ?>;">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Отправить сообщение
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- View Message Modal -->
<div id="view-message-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900" id="view_message_title"></h3>
            <button type="button" onclick="closeViewMessageModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4">
                <p class="text-sm text-gray-500" id="view_message_meta"></p>
            </div>
            <div class="prose max-w-none" id="view_message_content"></div>
        </div>
    </div>
</div>

<style>
.message-tab {
    background: #f3f4f6;
    color: #6b7280;
}
.message-tab:hover {
    background: #e5e7eb;
}
.message-tab.active {
    background: <?php echo $primary_color; ?>;
    color: white;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Quill editor for compose
    var composeQuill = new Quill('#compose-editor', {
        theme: 'snow',
        placeholder: 'Напишите сообщение...',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'link'],
                ['clean']
            ]
        }
    });

    // Tab switching
    $('.message-tab').on('click', function() {
        var tab = $(this).data('tab');

        $('.message-tab').removeClass('active').css({'background': '', 'color': ''});
        $(this).addClass('active').css({'background': '<?php echo $primary_color; ?>', 'color': 'white'});

        $('.message-tab-content').addClass('hidden');
        $('#tab-' + tab).removeClass('hidden');
    });

    // Compose form
    $('#compose-message-form').on('submit', function(e) {
        e.preventDefault();

        if ($('#compose_website').val() !== '') {
            alert('Обнаружена подозрительная активность');
            return;
        }

        var content = composeQuill.root.innerHTML;
        $('#compose_content_hidden').val(content);

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'send_member_message',
                nonce: '<?php echo wp_create_nonce("send_member_message"); ?>',
                recipient_id: $('#compose_recipient').val(),
                subject: $('#compose_subject').val(),
                content: content
            },
            success: function(response) {
                if (response.success) {
                    alert('Сообщение отправлено!');
                    location.reload();
                } else {
                    alert('Ошибка: ' + response.data.message);
                }
            }
        });
    });

    // View message
    $(document).on('click', '.view-message-btn', function(e) {
        e.stopPropagation();
        var messageId = $(this).data('id');

        $.ajax({
            url: memberDashboard.ajaxUrl,
            type: 'POST',
            data: {
                action: 'view_member_message',
                nonce: memberDashboard.nonce,
                message_id: messageId
            },
            success: function(response) {
                if (response.success) {
                    $('#view_message_title').text(response.data.title);
                    $('#view_message_meta').html(response.data.meta);
                    $('#view_message_content').html(response.data.content);
                    $('#view-message-modal').removeClass('hidden').css('display', 'flex');
                    $('body').css('overflow', 'hidden');
                }
            }
        });
    });
});

function closeViewMessageModal() {
    jQuery('#view-message-modal').addClass('hidden').css('display', 'none');
    jQuery('body').css('overflow', 'auto');
}
</script>
