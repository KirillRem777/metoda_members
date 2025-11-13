/**
 * Photo Cropper JavaScript
 * Обрезка фотографий с квадратной областью используя Cropper.js
 */

(function($) {
    'use strict';

    let cropper = null;
    let currentFile = null;
    let currentCallback = null;

    /**
     * Инициализация кроппера
     */
    window.MetodaPhotoCropper = {

        /**
         * Открыть кроппер для файла
         * @param {File} file - файл изображения
         * @param {Function} callback - функция обратного вызова с обрезанным blob
         */
        open: function(file, callback) {
            if (!file || !file.type.match('image.*')) {
                alert('Пожалуйста, выберите изображение');
                return;
            }

            currentFile = file;
            currentCallback = callback;

            // Создаем модальное окно если его еще нет
            if (!$('#photo-cropper-modal').length) {
                this.createModal();
            }

            // Читаем файл
            const reader = new FileReader();
            reader.onload = (e) => {
                this.initCropper(e.target.result);
            };
            reader.readAsDataURL(file);

            // Показываем модальное окно
            $('#photo-cropper-modal').addClass('active');
            $('body').css('overflow', 'hidden');
        },

        /**
         * Создать модальное окно
         */
        createModal: function() {
            const modal = `
                <div id="photo-cropper-modal" class="photo-cropper-modal">
                    <div class="cropper-container-wrapper">
                        <div class="cropper-header">
                            <h3><i class="fas fa-crop"></i> Обрезать фотографию</h3>
                            <button type="button" class="cropper-close" id="cropper-close">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="cropper-body">
                            <div class="cropper-hint">
                                <i class="fas fa-info-circle"></i>
                                <span>Выберите квадратную область для обрезки. Используйте кнопки для масштабирования и поворота.</span>
                            </div>
                            <div class="cropper-canvas-container">
                                <img id="cropper-image" src="" alt="Crop">
                            </div>
                        </div>
                        <div class="cropper-toolbar">
                            <div class="cropper-tools">
                                <button type="button" class="cropper-tool-btn" id="crop-zoom-in" title="Увеличить">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <button type="button" class="cropper-tool-btn" id="crop-zoom-out" title="Уменьшить">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button type="button" class="cropper-tool-btn" id="crop-rotate-left" title="Повернуть влево">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button type="button" class="cropper-tool-btn" id="crop-rotate-right" title="Повернуть вправо">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <button type="button" class="cropper-tool-btn" id="crop-flip-h" title="Отразить горизонтально">
                                    <i class="fas fa-arrows-alt-h"></i>
                                </button>
                                <button type="button" class="cropper-tool-btn" id="crop-flip-v" title="Отразить вертикально">
                                    <i class="fas fa-arrows-alt-v"></i>
                                </button>
                                <button type="button" class="cropper-tool-btn" id="crop-reset" title="Сбросить">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                            <div class="cropper-actions">
                                <button type="button" class="btn-cropper-cancel" id="cropper-cancel">
                                    Отмена
                                </button>
                                <button type="button" class="btn-cropper-save" id="cropper-save">
                                    <span class="text">Сохранить</span>
                                    <span class="loader"><i class="fas fa-spinner fa-spin"></i></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modal);

            // Привязываем события
            this.bindEvents();
        },

        /**
         * Инициализировать Cropper.js
         */
        initCropper: function(imageUrl) {
            const image = document.getElementById('cropper-image');
            image.src = imageUrl;

            // Уничтожаем предыдущий cropper если есть
            if (cropper) {
                cropper.destroy();
            }

            // Создаем новый cropper
            cropper = new Cropper(image, {
                aspectRatio: 1, // Квадрат
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                restore: false,
                guides: true,
                center: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                background: false,
                responsive: true,
                checkOrientation: true,
                minContainerWidth: 200,
                minContainerHeight: 200,
            });
        },

        /**
         * Привязать события
         */
        bindEvents: function() {
            // Закрыть модальное окно
            $(document).on('click', '#cropper-close, #cropper-cancel', () => {
                this.close();
            });

            // Закрыть по клику вне модального окна
            $(document).on('click', '#photo-cropper-modal', (e) => {
                if (e.target.id === 'photo-cropper-modal') {
                    this.close();
                }
            });

            // Инструменты обрезки
            $(document).on('click', '#crop-zoom-in', () => {
                if (cropper) cropper.zoom(0.1);
            });

            $(document).on('click', '#crop-zoom-out', () => {
                if (cropper) cropper.zoom(-0.1);
            });

            $(document).on('click', '#crop-rotate-left', () => {
                if (cropper) cropper.rotate(-45);
            });

            $(document).on('click', '#crop-rotate-right', () => {
                if (cropper) cropper.rotate(45);
            });

            $(document).on('click', '#crop-flip-h', () => {
                if (cropper) {
                    const scaleX = cropper.getData().scaleX || 1;
                    cropper.scaleX(-scaleX);
                }
            });

            $(document).on('click', '#crop-flip-v', () => {
                if (cropper) {
                    const scaleY = cropper.getData().scaleY || 1;
                    cropper.scaleY(-scaleY);
                }
            });

            $(document).on('click', '#crop-reset', () => {
                if (cropper) cropper.reset();
            });

            // Сохранить обрезанное изображение
            $(document).on('click', '#cropper-save', () => {
                this.saveCroppedImage();
            });

            // ESC для закрытия
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('#photo-cropper-modal').hasClass('active')) {
                    this.close();
                }
            });
        },

        /**
         * Сохранить обрезанное изображение
         */
        saveCroppedImage: function() {
            if (!cropper) return;

            const saveBtn = $('#cropper-save');
            saveBtn.addClass('loading').prop('disabled', true);

            // Получаем canvas с обрезанным изображением
            const canvas = cropper.getCroppedCanvas({
                width: 800,
                height: 800,
                minWidth: 400,
                minHeight: 400,
                maxWidth: 1200,
                maxHeight: 1200,
                fillColor: '#fff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            // Конвертируем в blob
            canvas.toBlob((blob) => {
                if (blob) {
                    // Создаем файл из blob
                    const fileName = currentFile.name.replace(/\.[^/.]+$/, '') + '_cropped.jpg';
                    const croppedFile = new File([blob], fileName, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });

                    // Вызываем callback
                    if (currentCallback && typeof currentCallback === 'function') {
                        currentCallback(croppedFile, blob);
                    }

                    this.close();
                } else {
                    alert('Ошибка при обрезке изображения');
                    saveBtn.removeClass('loading').prop('disabled', false);
                }
            }, 'image/jpeg', 0.9);
        },

        /**
         * Закрыть модальное окно
         */
        close: function() {
            $('#photo-cropper-modal').removeClass('active');
            $('body').css('overflow', '');

            // Уничтожаем cropper
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            // Очищаем данные
            currentFile = null;
            currentCallback = null;

            // Сбрасываем кнопку
            $('#cropper-save').removeClass('loading').prop('disabled', false);
        }
    };

    /**
     * Хелпер для загрузки изображения с кроппером
     * @param {Object} options - опции
     * @param {string} options.inputSelector - селектор input[type=file]
     * @param {Function} options.onCropped - callback после обрезки
     */
    window.initPhotoCropperInput = function(options) {
        const defaults = {
            inputSelector: '.photo-upload-input',
            onCropped: null
        };

        const settings = $.extend({}, defaults, options);

        $(settings.inputSelector).on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                window.MetodaPhotoCropper.open(file, (croppedFile, blob) => {
                    if (settings.onCropped && typeof settings.onCropped === 'function') {
                        settings.onCropped(croppedFile, blob, $(this));
                    }
                });
            }
        });
    };

})(jQuery);
