/**
 * Modal Focus Trap
 * Accessibility helper для модальных окон
 * v3.7.5
 */

(function() {
    'use strict';

    /**
     * Focus Trap Class
     * Управляет фокусом внутри модального окна для accessibility
     */
    class FocusTrap {
        constructor(modalElement) {
            this.modal = modalElement;
            this.focusableElements = null;
            this.firstFocusable = null;
            this.lastFocusable = null;
            this.previousActiveElement = null;

            this.handleKeyDown = this.handleKeyDown.bind(this);
            this.handleFocusIn = this.handleFocusIn.bind(this);
        }

        /**
         * Находит все фокусируемые элементы в модальном окне
         */
        updateFocusableElements() {
            const focusableSelector = [
                'a[href]',
                'button:not([disabled])',
                'textarea:not([disabled])',
                'input:not([disabled])',
                'select:not([disabled])',
                '[tabindex]:not([tabindex="-1"])'
            ].join(', ');

            this.focusableElements = this.modal.querySelectorAll(focusableSelector);

            if (this.focusableElements.length > 0) {
                this.firstFocusable = this.focusableElements[0];
                this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];
            }
        }

        /**
         * Обработчик нажатий клавиш
         */
        handleKeyDown(e) {
            // Tab key
            if (e.key === 'Tab') {
                if (this.focusableElements.length === 0) return;

                // Shift + Tab (назад)
                if (e.shiftKey) {
                    if (document.activeElement === this.firstFocusable) {
                        e.preventDefault();
                        this.lastFocusable.focus();
                    }
                }
                // Tab (вперед)
                else {
                    if (document.activeElement === this.lastFocusable) {
                        e.preventDefault();
                        this.firstFocusable.focus();
                    }
                }
            }

            // Escape key - закрыть модальное окно
            if (e.key === 'Escape') {
                this.triggerClose();
            }
        }

        /**
         * Обработчик потери фокуса
         * Если фокус ушел за пределы модального окна, возвращаем обратно
         */
        handleFocusIn(e) {
            if (!this.modal.contains(e.target)) {
                e.stopPropagation();
                if (this.firstFocusable) {
                    this.firstFocusable.focus();
                }
            }
        }

        /**
         * Триггер закрытия модального окна
         */
        triggerClose() {
            // Ищем кнопку закрытия с классом .close-modal или data-dismiss
            const closeButton = this.modal.querySelector('.close-modal, [data-dismiss="modal"]');
            if (closeButton) {
                closeButton.click();
            }
        }

        /**
         * Активировать trap
         */
        activate() {
            // Сохраняем текущий активный элемент
            this.previousActiveElement = document.activeElement;

            // Обновляем список фокусируемых элементов
            this.updateFocusableElements();

            // Фокусируем первый элемент
            if (this.firstFocusable) {
                // Небольшая задержка для корректной работы
                setTimeout(() => {
                    this.firstFocusable.focus();
                }, 100);
            }

            // Добавляем слушатели
            this.modal.addEventListener('keydown', this.handleKeyDown);
            document.addEventListener('focusin', this.handleFocusIn);
        }

        /**
         * Деактивировать trap
         */
        deactivate() {
            // Удаляем слушатели
            this.modal.removeEventListener('keydown', this.handleKeyDown);
            document.removeEventListener('focusin', this.handleFocusIn);

            // Возвращаем фокус на элемент, который был активен до открытия модального окна
            if (this.previousActiveElement && this.previousActiveElement.focus) {
                this.previousActiveElement.focus();
            }
        }
    }

    /**
     * Автоматическая инициализация для всех модальных окон
     */
    const modalTraps = new Map();

    /**
     * Инициализировать focus trap для модального окна
     */
    function initModal(modal) {
        if (!modal || modalTraps.has(modal)) return;

        const trap = new FocusTrap(modal);
        modalTraps.set(modal, trap);

        // Отслеживаем открытие/закрытие модального окна
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class' || mutation.attributeName === 'style') {
                    const isVisible = modal.classList.contains('active') ||
                                    modal.classList.contains('show') ||
                                    (modal.style.display && modal.style.display !== 'none');

                    if (isVisible) {
                        trap.activate();
                    } else {
                        trap.deactivate();
                    }
                }
            });
        });

        observer.observe(modal, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });

        return trap;
    }

    /**
     * Автоматическая инициализация при загрузке DOM
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Ищем все элементы с классом modal или data-modal
        const modals = document.querySelectorAll('.modal, [data-modal], [role="dialog"]');

        modals.forEach(modal => {
            initModal(modal);
        });
    });

    /**
     * Для jQuery-зависимых проектов
     */
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            // Дополнительная инициализация для динамически создаваемых модальных окон
            $(document).on('DOMNodeInserted', function(e) {
                if ($(e.target).is('.modal, [data-modal], [role="dialog"]')) {
                    initModal(e.target);
                }
            });
        });
    }

    /**
     * Экспортируем в глобальную область для ручной инициализации
     */
    window.MetodaFocusTrap = FocusTrap;
    window.initModalFocusTrap = initModal;

})();
