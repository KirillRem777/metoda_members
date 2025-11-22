/**
 * Member Forum JavaScript
 * Handles all forum interactions
 */

(function($) {
    'use strict';

    // Create Topic Modal
    $('#create-topic-btn').on('click', function() {
        $('#create-topic-modal').addClass('active');
    });

    $('.modal-close').on('click', function() {
        $('.forum-modal').removeClass('active');
    });

    // Close modal on outside click
    $('.forum-modal').on('click', function(e) {
        if ($(e.target).hasClass('forum-modal')) {
            $(this).removeClass('active');
        }
    });

    // Create Topic Form Submit
    $('#create-topic-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Создание...');

        $.ajax({
            url: forumData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'forum_create_topic',
                nonce: forumData.nonce,
                title: $('#topic-title').val(),
                content: $('#topic-content').val(),
                category: $('#topic-category').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.topic_url;
                } else {
                    alert(response.data.message);
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Произошла ошибка. Попробуйте позже.');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Like Topic
    $(document).on('click', '.like-topic', function() {
        if (!forumData.isLoggedIn) {
            alert('Войдите, чтобы лайкать темы');
            return;
        }

        const $btn = $(this);
        const topicId = $btn.data('topic-id');

        $.ajax({
            url: forumData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'forum_like_topic',
                nonce: forumData.nonce,
                topic_id: topicId
            },
            success: function(response) {
                if (response.success) {
                    $btn.find('.like-count').text(response.data.likes);
                    $btn.toggleClass('liked', response.data.is_liked);
                }
            },
            error: function() {
                // Silent fail for likes
            }
        });
    });

    // Like Reply
    $(document).on('click', '.like-reply', function() {
        if (!forumData.isLoggedIn) {
            alert('Войдите, чтобы лайкать ответы');
            return;
        }

        const $btn = $(this);
        const commentId = $btn.data('comment-id');

        $.ajax({
            url: forumData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'forum_like_reply',
                nonce: forumData.nonce,
                comment_id: commentId
            },
            success: function(response) {
                if (response.success) {
                    $btn.find('.like-count').text(response.data.likes);
                    $btn.toggleClass('liked', response.data.is_liked);
                }
            },
            error: function() {
                // Silent fail for likes
            }
        });
    });

    // Subscribe to Topic
    $(document).on('click', '.subscribe-topic', function() {
        if (!forumData.isLoggedIn) {
            alert('Войдите, чтобы подписаться');
            return;
        }

        const $btn = $(this);
        const topicId = $btn.data('topic-id');

        $.ajax({
            url: forumData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'forum_subscribe_topic',
                nonce: forumData.nonce,
                topic_id: topicId
            },
            success: function(response) {
                if (response.success) {
                    $btn.toggleClass('subscribed', response.data.is_subscribed);
                    const icon = response.data.is_subscribed ? 'fa-bell' : 'fa-bell-slash';
                    const text = response.data.is_subscribed ? 'Подписаны' : 'Подписаться';
                    $btn.html(`<i class="fas ${icon}"></i> ${text}`);
                    showToast('success', response.data.message);
                }
            },
            error: function() {
                showToast('error', 'Ошибка соединения с сервером');
            }
        });
    });

    // Reply Form Submit
    $(document).on('submit', '.reply-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const $textarea = $form.find('textarea');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: forumData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'forum_reply_topic',
                nonce: forumData.nonce,
                topic_id: $form.data('topic-id'),
                content: $textarea.val(),
                parent_id: $form.data('parent-id') || 0
            },
            success: function(response) {
                if (response.success) {
                    $textarea.val('');
                    $('.forum-replies').append(response.data.comment_html);
                    showToast('success', response.data.message);
                    $('.replies-count').text(parseInt($('.replies-count').text()) + 1);
                } else {
                    alert(response.data.message);
                }
                $btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                alert('Ошибка отправки ответа');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Reply to Comment
    $(document).on('click', '.reply-to-comment', function() {
        const commentId = $(this).data('comment-id');
        const $replyForm = $('.main-reply-form').clone();
        $replyForm.removeClass('main-reply-form').addClass('nested-reply-form');
        $replyForm.attr('data-parent-id', commentId);
        $replyForm.find('textarea').attr('placeholder', 'Ваш ответ...');

        // Remove existing nested forms
        $('.nested-reply-form').remove();

        // Insert after the comment
        $(this).closest('.forum-reply').after($replyForm);
        $replyForm.find('textarea').focus();
    });

    // Toast Notification
    function showToast(type, message) {
        const $toast = $('<div class="forum-toast ' + type + '">' + message + '</div>');
        $('body').append($toast);

        setTimeout(function() {
            $toast.addClass('show');
        }, 100);

        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }

    // Pin Topic (Admin Only)
    $(document).on('click', '.pin-topic', function() {
        const $btn = $(this);
        const topicId = $btn.data('topic-id');

        if (!confirm('Закрепить/открепить эту тему?')) {
            return;
        }

        $.ajax({
            url: forumData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'forum_pin_topic',
                nonce: forumData.nonce,
                topic_id: topicId
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            },
            error: function() {
                showToast('error', 'Ошибка соединения с сервером');
            }
        });
    });

})(jQuery);

// Toast CSS (injected dynamically)
if (!document.getElementById('forum-toast-styles')) {
    const style = document.createElement('style');
    style.id = 'forum-toast-styles';
    style.textContent = `
        .forum-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px 24px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 100000;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .forum-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .forum-toast.success {
            border-left: 4px solid #10b981;
        }
        .forum-toast.error {
            border-left: 4px solid #ef4444;
        }
    `;
    document.head.appendChild(style);
}
