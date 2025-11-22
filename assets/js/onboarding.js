/**
 * Onboarding JavaScript
 * Обучающий экран для новых пользователей при первом входе
 */

(function($) {
    'use strict';

    // Onboarding slides data
    const slides = [
        {
            icon: 'fa-hand-wave',
            title: 'Добро пожаловать в ассоциацию Метода!',
            description: 'Мы рады приветствовать вас в нашем сообществе. Давайте кратко познакомим вас с возможностями вашего личного кабинета.',
            emoji: '👋'
        },
        {
            icon: 'fa-user-circle',
            title: 'Ваш профиль',
            description: 'Заполните информацию о себе, загрузите фотографии и расскажите о вашей деятельности.',
            features: [
                'Редактирование основной информации',
                'Загрузка фотографий в галерею',
                'Управление специализациями и ролями',
                'Добавление контактной информации'
            ]
        },
        {
            icon: 'fa-folder-open',
            title: 'Материалы и достижения',
            description: 'Добавляйте ваши материалы: отзывы, благодарности, интервью, видео, рецензии и разработки.',
            features: [
                'Три типа материалов: ссылки, файлы, текст',
                'Категоризация по типам',
                'Поддержка YouTube видео',
                'Форматированный текст'
            ]
        },
        {
            icon: 'fa-eye',
            title: 'Публичная страница',
            description: 'Ваш профиль будет виден посетителям сайта после модерации менеджером.',
            features: [
                'Красивое отображение информации',
                'Слайдер фотографий',
                'Все ваши материалы и достижения',
                'Контактная информация'
            ]
        },
        {
            icon: 'fa-rocket',
            title: 'Готовы начать?',
            description: 'Заполните ваш профиль, добавьте материалы и отправьте на модерацию. Удачи!',
            emoji: '🚀'
        }
    ];

    let currentSlide = 0;

    /**
     * Initialize onboarding
     */
    function initOnboarding() {
        // Check if user has seen onboarding
        if (onboardingData.showOnboarding !== '1') {
            return;
        }

        // Build onboarding modal
        buildOnboardingModal();

        // Show modal
        setTimeout(function() {
            $('.onboarding-modal').addClass('active');
        }, 500);
    }

    /**
     * Build onboarding modal HTML
     */
    function buildOnboardingModal() {
        const modalHTML = `
            <div class="onboarding-modal">
                <div class="onboarding-container">
                    <button class="onboarding-close" id="close-onboarding">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="onboarding-progress">
                        <div class="progress-dots" id="progress-dots"></div>
                    </div>

                    <div class="onboarding-content" id="onboarding-slides"></div>

                    <div class="onboarding-footer">
                        <button class="onboarding-btn btn-skip" id="skip-onboarding">
                            Пропустить
                        </button>
                        <div>
                            <button class="onboarding-btn btn-prev" id="prev-slide" disabled>
                                <i class="fas fa-arrow-left"></i>
                                Назад
                            </button>
                            <button class="onboarding-btn btn-next" id="next-slide">
                                Далее
                                <i class="fas fa-arrow-right"></i>
                            </button>
                            <button class="onboarding-btn btn-finish" id="finish-onboarding" style="display: none;">
                                Начать работу
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHTML);

        // Build slides
        buildSlides();

        // Build progress dots
        buildProgressDots();

        // Bind events
        bindOnboardingEvents();
    }

    /**
     * Build slides HTML
     */
    function buildSlides() {
        const $container = $('#onboarding-slides');

        slides.forEach(function(slide, index) {
            let slideHTML = `
                <div class="onboarding-slide ${index === 0 ? 'active' : ''}" data-slide="${index}">
            `;

            // Emoji or icon
            if (slide.emoji) {
                slideHTML += `<div class="welcome-emoji">${slide.emoji}</div>`;
            } else {
                slideHTML += `
                    <div class="slide-icon">
                        <i class="fas ${slide.icon}"></i>
                    </div>
                `;
            }

            // Title and description
            slideHTML += `
                <h2 class="slide-title">${slide.title}</h2>
                <p class="slide-description">${slide.description}</p>
            `;

            // Features list
            if (slide.features) {
                slideHTML += '<ul class="features-list">';
                slide.features.forEach(function(feature) {
                    slideHTML += `
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>${feature}</span>
                        </li>
                    `;
                });
                slideHTML += '</ul>';
            }

            slideHTML += '</div>';

            $container.append(slideHTML);
        });
    }

    /**
     * Build progress dots
     */
    function buildProgressDots() {
        const $container = $('#progress-dots');

        slides.forEach(function(slide, index) {
            $container.append(`<div class="progress-dot ${index === 0 ? 'active' : ''}" data-dot="${index}"></div>`);
        });
    }

    /**
     * Bind onboarding events
     */
    function bindOnboardingEvents() {
        // Close button
        $('#close-onboarding, #skip-onboarding').on('click', function() {
            closeOnboarding();
        });

        // Previous slide
        $('#prev-slide').on('click', function() {
            if (currentSlide > 0) {
                goToSlide(currentSlide - 1);
            }
        });

        // Next slide
        $('#next-slide').on('click', function() {
            if (currentSlide < slides.length - 1) {
                goToSlide(currentSlide + 1);
            }
        });

        // Finish onboarding
        $('#finish-onboarding').on('click', function() {
            closeOnboarding();
        });

        // Close on outside click
        $('.onboarding-modal').on('click', function(e) {
            if ($(e.target).hasClass('onboarding-modal')) {
                closeOnboarding();
            }
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (!$('.onboarding-modal').hasClass('active')) return;

            if (e.key === 'Escape') {
                closeOnboarding();
            } else if (e.key === 'ArrowRight' && currentSlide < slides.length - 1) {
                goToSlide(currentSlide + 1);
            } else if (e.key === 'ArrowLeft' && currentSlide > 0) {
                goToSlide(currentSlide - 1);
            }
        });
    }

    /**
     * Go to specific slide
     */
    function goToSlide(slideIndex) {
        currentSlide = slideIndex;

        // Update slides
        $('.onboarding-slide').removeClass('active');
        $(`.onboarding-slide[data-slide="${slideIndex}"]`).addClass('active');

        // Update progress dots
        $('.progress-dot').removeClass('active');
        $(`.progress-dot[data-dot="${slideIndex}"]`).addClass('active');

        // Update buttons
        $('#prev-slide').prop('disabled', slideIndex === 0);

        if (slideIndex === slides.length - 1) {
            $('#next-slide').hide();
            $('#finish-onboarding').show();
        } else {
            $('#next-slide').show();
            $('#finish-onboarding').hide();
        }
    }

    /**
     * Close onboarding and mark as seen
     */
    function closeOnboarding() {
        $('.onboarding-modal').removeClass('active');

        // Mark onboarding as seen
        $.ajax({
            url: onboardingData.ajaxUrl,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'mark_onboarding_seen',
                nonce: onboardingData.nonce
            },
            error: function() {
                // Silent fail for marking as seen
            }
        });

        // Remove modal after animation
        setTimeout(function() {
            $('.onboarding-modal').remove();
        }, 300);
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if (typeof onboardingData !== 'undefined') {
            initOnboarding();
        }
    });

})(jQuery);
