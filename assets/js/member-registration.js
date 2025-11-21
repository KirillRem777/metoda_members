/**
 * Member Registration JavaScript
 * Многошаговая форма регистрации участника
 */

(function($) {
    'use strict';

    let currentStep = 1;
    const totalSteps = 4;
    let roleTags = [];
    let specializationFields = [];
    let interestFields = [];

    // Инициализация при загрузке страницы
    $(document).ready(function() {
        initFormSteps();
        initPasswordStrength();
        initRoleTags();
        initRepeaterFields();
        initCharCounters();
        initAccessCodeValidation();
        initFormSubmission();
    });

    /**
     * Инициализация шагов формы
     */
    function initFormSteps() {
        // Навигация по шагам
        $('.btn-next').on('click', function() {
            if (validateStep(currentStep)) {
                nextStep();
            }
        });

        $('.btn-prev').on('click', function() {
            prevStep();
        });

        // Обновление прогресса при загрузке
        updateProgress();
    }

    /**
     * Переход к следующему шагу
     */
    function nextStep() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateProgress();
        }
    }

    /**
     * Возврат к предыдущему шагу
     */
    function prevStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
            updateProgress();
        }
    }

    /**
     * Отображение конкретного шага
     */
    function showStep(step) {
        $('.form-step').removeClass('active');
        $('#step-' + step).addClass('active');

        // Обновление кнопок
        $('.btn-prev').toggle(step > 1);
        $('.btn-next').toggle(step < totalSteps);
        $('.btn-submit').toggle(step === totalSteps);

        // Скролл вверх
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    /**
     * Обновление прогресс-индикатора
     */
    function updateProgress() {
        // Обновление шагов
        $('.progress-step').each(function(index) {
            const stepNum = index + 1;
            $(this).removeClass('active completed');

            if (stepNum < currentStep) {
                $(this).addClass('completed');
                $(this).find('.step-circle').html('<i class="fas fa-check"></i>');
            } else if (stepNum === currentStep) {
                $(this).addClass('active');
                $(this).find('.step-circle').text(stepNum);
            } else {
                $(this).find('.step-circle').text(stepNum);
            }
        });

        // Обновление прогресс-бара
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        $('.progress-bar-fill').css('width', progress + '%');
    }

    /**
     * Валидация шага
     */
    function validateStep(step) {
        const stepElement = $('#step-' + step);
        let isValid = true;

        // Очистка предыдущих ошибок
        stepElement.find('.error-message').remove();
        stepElement.find('.error').removeClass('error');

        // Валидация обязательных полей
        stepElement.find('[required]').each(function() {
            const field = $(this);
            const value = field.val().trim();

            if (!value) {
                isValid = false;
                field.addClass('error');
                field.after('<span class="error-message" style="color: #ef4444; font-size: 13px; margin-top: 4px; display: block;">Это поле обязательно</span>');
            }
        });

        // Специфичная валидация для шага 1
        if (step === 1) {
            // Проверка email
            const email = $('#email').val();
            if (email && !isValidEmail(email)) {
                isValid = false;
                $('#email').addClass('error');
                $('#email').after('<span class="error-message" style="color: #ef4444; font-size: 13px; margin-top: 4px; display: block;">Введите корректный email</span>');
            }

            // Проверка пароля
            const password = $('#password').val();
            if (password && password.length < 8) {
                isValid = false;
                $('#password').addClass('error');
                $('#password').after('<span class="error-message" style="color: #ef4444; font-size: 13px; margin-top: 4px; display: block;">Пароль должен быть не менее 8 символов</span>');
            }

            // Проверка чекбокса согласия
            if (!$('#terms').is(':checked')) {
                isValid = false;
                alert('Вы должны принять условия использования');
            }
        }

        return isValid;
    }

    /**
     * Валидация email
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * Индикатор силы пароля
     */
    function initPasswordStrength() {
        $('#password').on('input', function() {
            const password = $(this).val();
            const strengthBar = $('.password-strength');
            const feedback = $('#password-feedback');

            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthBar.removeClass('strength-weak strength-medium strength-good strength-strong');

            if (strength === 0) {
                feedback.text('');
            } else if (strength === 1) {
                strengthBar.addClass('strength-weak');
                feedback.text('Слабый пароль').css('color', '#ef4444');
            } else if (strength === 2) {
                strengthBar.addClass('strength-medium');
                feedback.text('Средняя надежность').css('color', '#f59e0b');
            } else if (strength === 3) {
                strengthBar.addClass('strength-good');
                feedback.text('Хороший пароль').css('color', '#10b981');
            } else {
                strengthBar.addClass('strength-strong');
                feedback.text('Отличный пароль!').css('color', '#059669');
            }
        });

        // Переключатель видимости пароля
        $('.password-toggle').on('click', function() {
            const passwordField = $('#password');
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

    /**
     * Инициализация тегов ролей
     */
    function initRoleTags() {
        const input = $('#role-input');
        const container = $('#role-tags-container');

        input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const tagName = input.val().trim();

                if (tagName && !roleTags.includes(tagName)) {
                    roleTags.push(tagName);
                    addRoleTag(tagName, container);
                    input.val('');
                    updateRoleField();
                }
            }
        });

        // Добавление из существующих ролей
        window.addExistingRole = function(roleName) {
            if (!roleTags.includes(roleName)) {
                roleTags.push(roleName);
                addRoleTag(roleName, container);
                updateRoleField();
            }
        };

        // Удаление тега
        window.removeRoleTag = function(tagName) {
            const index = roleTags.indexOf(tagName);
            if (index > -1) {
                roleTags.splice(index, 1);
                updateRoleField();
            }
        };
    }

    /**
     * Добавление визуального тега роли
     */
    function addRoleTag(tagName, container) {
        const tag = $('<span>', {
            class: 'tag',
            html: tagName + ' <i class="fas fa-times remove" onclick="removeRoleTag(\'' + tagName + '\')"></i>'
        });

        container.append(tag);
    }

    /**
     * Обновление скрытого поля с ролями
     */
    function updateRoleField() {
        $('#member_roles_hidden').val(roleTags.join(','));
    }

    /**
     * Инициализация repeater полей
     */
    function initRepeaterFields() {
        // Специализации
        $('#add-specialization').on('click', function() {
            const container = $('#specialization-items');
            const index = specializationFields.length;

            const item = $(`
                <div class="repeater-item" data-index="${index}">
                    <input type="text" placeholder="Специализация" name="specializations[]" required>
                    <input type="text" placeholder="Опыт (лет)" name="specialization_years[]" style="max-width: 120px;" required>
                    <button type="button" class="remove-item" onclick="removeSpecialization(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `);

            container.append(item);
            specializationFields.push({ index: index });
        });

        // Интересы
        $('#add-interest').on('click', function() {
            const container = $('#interest-items');
            const index = interestFields.length;

            const item = $(`
                <div class="repeater-item" data-index="${index}">
                    <input type="text" placeholder="Профессиональный интерес" name="interests[]" required>
                    <button type="button" class="remove-item" onclick="removeInterest(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `);

            container.append(item);
            interestFields.push({ index: index });
        });

        // Удаление специализации
        window.removeSpecialization = function(index) {
            $('.repeater-item[data-index="' + index + '"]').remove();
            specializationFields = specializationFields.filter(f => f.index !== index);
        };

        // Удаление интереса
        window.removeInterest = function(index) {
            $('.repeater-item[data-index="' + index + '"]').remove();
            interestFields = interestFields.filter(f => f.index !== index);
        };

        // Добавить хотя бы одно поле по умолчанию
        $('#add-specialization').trigger('click');
        $('#add-interest').trigger('click');
    }

    /**
     * Инициализация счетчиков символов
     */
    function initCharCounters() {
        $('textarea[maxlength]').each(function() {
            const textarea = $(this);
            const maxLength = textarea.attr('maxlength');
            const counter = $('<div class="char-counter"></div>');

            textarea.after(counter);

            textarea.on('input', function() {
                const currentLength = $(this).val().length;
                counter.text(currentLength + ' / ' + maxLength);

                if (currentLength > maxLength * 0.9) {
                    counter.css('color', '#ef4444');
                } else {
                    counter.css('color', '#6b7280');
                }
            });

            textarea.trigger('input');
        });
    }

    /**
     * Отправка формы
     */
    function initFormSubmission() {
        $('#registration-form').on('submit', function(e) {
            e.preventDefault();

            if (!validateStep(totalSteps)) {
                return;
            }

            const submitBtn = $('.btn-submit');
            const btnText = submitBtn.find('.btn-text');
            const btnLoader = submitBtn.find('.btn-loader');

            // Показываем лоадер
            submitBtn.prop('disabled', true);
            btnText.hide();
            btnLoader.show();

            // Собираем данные формы
            const formData = new FormData(this);
            formData.append('action', 'member_register');
            formData.append('nonce', memberRegistrationData.nonce);

            // Отправка AJAX
            $.ajax({
                url: memberRegistrationData.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Показываем экран успеха
                        $('.registration-content').html(`
                            <div class="success-screen">
                                <div class="success-icon">🎉</div>
                                <h2>Регистрация завершена!</h2>
                                <p>Добро пожаловать в сообщество МЕТОДА. Ваш профиль был успешно создан.</p>
                                <div class="success-actions">
                                    <a href="${response.data.redirect}" class="btn btn-primary">
                                        <i class="fas fa-user"></i> Перейти в личный кабинет
                                    </a>
                                    <a href="/" class="btn btn-outline">
                                        <i class="fas fa-home"></i> На главную
                                    </a>
                                </div>
                            </div>
                        `);

                        // Скрываем прогресс
                        $('.progress-container').hide();
                    } else {
                        alert(response.data.message || 'Ошибка при регистрации');
                        submitBtn.prop('disabled', false);
                        btnText.show();
                        btnLoader.hide();
                    }
                },
                error: function() {
                    alert('Произошла ошибка. Попробуйте еще раз.');
                    submitBtn.prop('disabled', false);
                    btnText.show();
                    btnLoader.hide();
                }
            });
        });
    }

    /**
     * Инициализация валидации кода доступа
     */
    function initAccessCodeValidation() {
        const $accessCodeField = $('#access_code');
        const $feedback = $('#access-code-feedback');
        let validationTimeout;

        if (!$accessCodeField.length) return;

        $accessCodeField.on('input', function() {
            const code = $(this).val().trim().toUpperCase();

            // Update field value to uppercase
            $(this).val(code);

            // Clear previous timeout
            clearTimeout(validationTimeout);

            // Hide feedback if empty
            if (!code) {
                $feedback.hide().removeClass('text-green-600 text-red-600');
                $accessCodeField.removeClass('border-green-500 border-red-500');
                return;
            }

            // Show loading state
            $feedback.html('<i class="fas fa-spinner fa-spin"></i> Проверка кода...').show().removeClass('text-green-600 text-red-600').addClass('text-gray-500');

            // Validate after 800ms delay
            validationTimeout = setTimeout(function() {
                $.ajax({
                    url: memberRegistrationData.ajaxUrl,
                    type: 'POST',
                    timeout: 10000,
                    data: {
                        action: 'validate_access_code',
                        code: code,
                        nonce: memberRegistrationData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Code is valid
                            $feedback.html('<i class="fas fa-check-circle"></i> ' + response.data.message)
                                .removeClass('text-gray-500 text-red-600')
                                .addClass('text-green-600')
                                .show();
                            $accessCodeField.removeClass('border-red-500').addClass('border-green-500');
                        } else {
                            // Code is invalid
                            $feedback.html('<i class="fas fa-times-circle"></i> ' + response.data.message)
                                .removeClass('text-gray-500 text-green-600')
                                .addClass('text-red-600')
                                .show();
                            $accessCodeField.removeClass('border-green-500').addClass('border-red-500');
                        }
                    },
                    error: function() {
                        $feedback.html('<i class="fas fa-exclamation-triangle"></i> Ошибка проверки кода')
                            .removeClass('text-gray-500 text-green-600')
                            .addClass('text-red-600')
                            .show();
                        $accessCodeField.removeClass('border-green-500').addClass('border-red-500');
                    }
                });
            }, 800);
        });
    }

})(jQuery);
