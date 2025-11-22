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
    <div class="member-cabinet-header px-8 py-6">
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
                    <button class="message-tab active px-4 py-2.5 rounded-lg font-medium text-sm transition-all" data-tab="inbox" style="background-color: #0066cc; color: white;">
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

                                // Для незалогиненных отправителей
                                if (empty($sender_id)) {
                                    $sender_name = get_post_meta($message->ID, 'sender_name', true);
                                    $sender_email = get_post_meta($message->ID, 'sender_email', true);
                                    $sender_display = $sender_name . ' (' . $sender_email . ')';
                                } else {
                                    $sender_display = get_the_title($sender_id);
                                }
                            ?>
                            <div class="message-item p-4 border rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all cursor-pointer <?php echo !$is_read ? 'bg-blue-50 border-blue-200 font-medium' : 'bg-white border-gray-200'; ?>" data-message-id="<?php echo $message->ID; ?>" data-sender-id="<?php echo esc_attr($sender_id); ?>" data-sender-name="<?php echo esc_attr($sender_display); ?>" onclick="openMessage(<?php echo $message->ID; ?>, <?php echo $sender_id ? $sender_id : 0; ?>, '<?php echo esc_js($sender_display); ?>')">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center text-gray-600 font-bold text-sm">
                                        <?php echo strtoupper(mb_substr($sender_display, 0, 1)); ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="<?php echo !$is_read ? 'font-bold' : 'font-semibold'; ?> text-gray-900 truncate"><?php echo esc_html($sender_display); ?></span>
                                            <?php if (!$is_read): ?>
                                            <span class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full"></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm <?php echo !$is_read ? 'font-semibold' : ''; ?> text-gray-700 truncate"><?php echo esc_html($message->post_title); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo human_time_diff(strtotime($message->post_date), current_time('timestamp')) . ' назад'; ?></p>
                                    </div>
                                    <div class="flex-shrink-0 text-gray-400">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </div>
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
                            <div class="message-item p-4 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-all cursor-pointer bg-white" data-message-id="<?php echo $message->ID; ?>" onclick="openMessage(<?php echo $message->ID; ?>, 0, '')">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-200 to-blue-300 flex items-center justify-center text-blue-700 font-bold text-sm">
                                        <i class="fas fa-paper-plane text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm text-gray-500">Кому:</span>
                                            <span class="font-semibold text-gray-900 truncate"><?php echo get_the_title($recipient_id); ?></span>
                                        </div>
                                        <p class="text-sm text-gray-700 truncate"><?php echo esc_html($message->post_title); ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo human_time_diff(strtotime($message->post_date), current_time('timestamp')) . ' назад'; ?></p>
                                    </div>
                                    <div class="flex-shrink-0 text-gray-400">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </div>
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

                            <button type="submit" class="w-full px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity" style="background-color: #0066cc;">
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
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex-shrink-0 member-cabinet-header px-6 py-4 rounded-t-2xl flex items-center justify-between border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center text-gray-600 font-bold text-sm" id="view_message_avatar">
                    ?
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900" id="view_message_title"></h3>
                    <p class="text-xs text-gray-500" id="view_message_meta"></p>
                </div>
            </div>
            <button type="button" onclick="closeViewMessageModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none flex-shrink-0 ml-4">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="prose max-w-none text-gray-700" id="view_message_content"></div>
        </div>

        <!-- Modal Footer with Reply Button -->
        <div class="flex-shrink-0 border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-2xl flex gap-3" id="view_message_actions">
            <button type="button" onclick="replyToMessage()" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-medium hover:from-blue-600 hover:to-blue-700 transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-reply"></i>
                <span>Ответить</span>
            </button>
            <button type="button" onclick="closeViewMessageModal()" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-all">
                Закрыть
            </button>
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
    background: #0066cc;
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
        $(this).addClass('active').css({'background': '#0066cc', 'color': 'white'});

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

    // Global variables for reply functionality
    window.currentMessageSenderId = null;
    window.currentMessageSenderName = '';
    window.currentMessageSubject = '';
});

// Open message (new email-like function)
function openMessage(messageId, senderId, senderName) {
    jQuery.ajax({
        url: memberDashboard.ajaxUrl,
        type: 'POST',
        data: {
            action: 'view_member_message',
            nonce: memberDashboard.nonce,
            message_id: messageId
        },
        success: function(response) {
            if (response.success) {
                // Update modal content
                jQuery('#view_message_title').text(response.data.title);
                jQuery('#view_message_meta').html(response.data.meta);
                jQuery('#view_message_content').html(response.data.content);

                // Update avatar with first letter
                if (senderName) {
                    jQuery('#view_message_avatar').text(senderName.charAt(0).toUpperCase());
                } else {
                    jQuery('#view_message_avatar').text('?');
                }

                // Store sender info for reply
                window.currentMessageSenderId = senderId;
                window.currentMessageSenderName = senderName;
                window.currentMessageSubject = response.data.title;

                // Show/hide reply button based on whether sender exists
                if (senderId > 0) {
                    jQuery('#view_message_actions').show();
                } else {
                    jQuery('#view_message_actions').hide();
                }

                // Open modal
                jQuery('#view-message-modal').removeClass('hidden').css('display', 'flex');
                jQuery('body').css('overflow', 'hidden');
            }
        }
    });
}

// Reply to message
function replyToMessage() {
    if (!window.currentMessageSenderId || window.currentMessageSenderId == 0) {
        alert('Невозможно ответить на это сообщение');
        return;
    }

    // Close view modal
    closeViewMessageModal();

    // Switch to compose tab
    jQuery('.message-tab[data-tab="compose"]').click();

    // Pre-fill recipient
    jQuery('#compose_recipient').val(window.currentMessageSenderId);

    // Pre-fill subject with "Re: "
    var reSubject = window.currentMessageSubject;
    if (!reSubject.startsWith('Re: ')) {
        reSubject = 'Re: ' + reSubject;
    }
    jQuery('#compose_subject').val(reSubject);

    // Focus on editor
    setTimeout(function() {
        var composeEditor = jQuery('#compose-editor .ql-editor');
        if (composeEditor.length) {
            composeEditor.focus();
        }
    }, 300);
}

function closeViewMessageModal() {
    jQuery('#view-message-modal').addClass('hidden').css('display', 'none');
    jQuery('body').css('overflow', 'auto');

    // Clear stored info
    window.currentMessageSenderId = null;
    window.currentMessageSenderName = '';
    window.currentMessageSubject = '';
}
</script>
