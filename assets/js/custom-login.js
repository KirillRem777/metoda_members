/**
 * Custom Login Page JavaScript
 * Брендированная страница входа
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initPasswordToggle();
    });

    /**
     * Переключатель видимости пароля
     */
    function initPasswordToggle() {
        $('.password-toggle').on('click', function() {
            const passwordField = $('#user_pass');
            const icon = $(this);

            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    }

})(jQuery);
