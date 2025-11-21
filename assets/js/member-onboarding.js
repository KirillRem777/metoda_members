/**
 * Member Onboarding JavaScript
 * Handles password change and onboarding completion
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initPasswordChange();
        initPasswordStrength();
        initOnboardingCompletion();
    });

    /**
     * Initialize password change form
     */
    function initPasswordChange() {
        $('#password-change-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const $btnText = $button.find('.btn-text');
            const $btnLoader = $button.find('.btn-loader');
            const $message = $form.find('.form-message');

            // Show loader
            $btnText.hide();
            $btnLoader.show();
            $button.prop('disabled', true);
            $message.hide();

            // Get form data
            const formData = {
                action: 'member_change_password',
                nonce: memberOnboarding.nonce,
                current_password: $('#current_password').val(),
                new_password: $('#new_password').val(),
                confirm_password: $('#confirm_password').val()
            };

            // Send AJAX request
            $.ajax({
                url: memberOnboarding.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showMessage($message, 'success', response.data.message);

                        // Switch to welcome step after delay
                        setTimeout(function() {
                            switchToWelcomeStep();
                        }, 1500);
                    } else {
                        showMessage($message, 'error', response.data.message || 'Произошла ошибка');
                        $btnText.show();
                        $btnLoader.hide();
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    showMessage($message, 'error', 'Ошибка соединения с сервером');
                    $btnText.show();
                    $btnLoader.hide();
                    $button.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize password strength checker
     */
    function initPasswordStrength() {
        $('#new_password').on('input', function() {
            const password = $(this).val();
            const strength = calculatePasswordStrength(password);

            updateStrengthMeter(strength);
        });
    }

    /**
     * Calculate password strength
     */
    function calculatePasswordStrength(password) {
        if (!password) {
            return {
                score: 0,
                label: 'Введите пароль',
                class: ''
            };
        }

        let score = 0;

        // Length check
        if (password.length >= 8) score += 1;
        if (password.length >= 12) score += 1;

        // Character variety checks
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^a-zA-Z0-9]/.test(password)) score += 1;

        // Determine strength level
        if (score <= 2) {
            return {
                score: 33,
                label: 'Слабый пароль',
                class: 'weak'
            };
        } else if (score <= 4) {
            return {
                score: 66,
                label: 'Средний пароль',
                class: 'medium'
            };
        } else {
            return {
                score: 100,
                label: 'Надежный пароль',
                class: 'strong'
            };
        }
    }

    /**
     * Update strength meter UI
     */
    function updateStrengthMeter(strength) {
        const $fill = $('.strength-fill');
        const $text = $('.strength-text');

        // Update fill width and class
        $fill.removeClass('weak medium strong');
        if (strength.class) {
            $fill.addClass(strength.class);
        }

        // Update text
        $text.removeClass('weak medium strong');
        if (strength.class) {
            $text.addClass(strength.class);
        }
        $text.text(strength.label);
    }

    /**
     * Switch to welcome step
     */
    function switchToWelcomeStep() {
        // Update step indicator
        $('.step').first().removeClass('active').addClass('completed');
        $('.step').last().addClass('active');
        $('.step-line').addClass('active');

        // Switch steps
        $('#step-password').removeClass('active').addClass('hidden');
        $('#step-welcome').removeClass('hidden').addClass('active');

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    /**
     * Initialize onboarding completion
     */
    function initOnboardingCompletion() {
        $('#complete-onboarding').on('click', function(e) {
            e.preventDefault();

            const $button = $(this);
            const $btnText = $button.find('.btn-text');
            const $btnIcon = $button.find('.btn-icon');

            // Show loading state
            $btnText.text('Загрузка...');
            $btnIcon.text('⏳');
            $button.prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: memberOnboarding.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'member_complete_onboarding',
                    nonce: memberOnboarding.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success state
                        $btnText.text('Перенаправление...');
                        $btnIcon.text('✓');

                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 500);
                    } else {
                        showToast('error', response.data.message || 'Произошла ошибка');
                        $btnText.text('Перейти в личный кабинет');
                        $btnIcon.text('→');
                        $button.prop('disabled', false);
                    }
                },
                error: function() {
                    showToast('error', 'Ошибка соединения с сервером');
                    $btnText.text('Перейти в личный кабинет');
                    $btnIcon.text('→');
                    $button.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Show message in element
     */
    function showMessage($element, type, message) {
        $element
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();
    }

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        // Create toast container if doesn't exist
        let $toast = $('#onboarding-toast');
        if ($toast.length === 0) {
            $toast = $('<div id="onboarding-toast" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;"></div>');
            $('body').append($toast);
        }

        const bgColor = type === 'success' ? '#d1fae5' : '#fee2e2';
        const borderColor = type === 'success' ? '#34d399' : '#f87171';
        const textColor = type === 'success' ? '#065f46' : '#991b1b';

        const $message = $('<div style="background: ' + bgColor + '; color: ' + textColor + '; border: 2px solid ' + borderColor + '; padding: 15px 20px; border-radius: 10px; margin-bottom: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); font-weight: 600;">' + message + '</div>');

        $toast.append($message);

        // Auto-remove after 5 seconds
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Prevent accidental navigation away
     */
    let formChanged = false;

    $('#password-change-form input').on('change', function() {
        formChanged = true;
    });

    $(window).on('beforeunload', function(e) {
        if (formChanged && $('#step-password').hasClass('active')) {
            const message = 'Вы уверены, что хотите покинуть страницу? Несохраненные изменения будут потеряны.';
            e.returnValue = message;
            return message;
        }
    });

    // Reset flag on successful submission
    $('#password-change-form').on('submit', function() {
        formChanged = false;
    });

})(jQuery);
