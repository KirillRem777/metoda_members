/**
 * Member Dashboard JavaScript
 * Handles all interactive functionality for the personal cabinet
 * 
 * FIXED: Добавлена передача member_id во всех AJAX запросах для корректной работы админского просмотра
 * Version: 1.0.1
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initNavigation();
        initProfileForm();
        initGalleryManager();
        initMaterialsManager();
    });

    /**
     * ADDED: Helper function to get member_id for AJAX requests
     * Возвращает ID участника из локализованных данных
     */
    function getMemberId() {
        return memberDashboard.memberId || null;
    }

    /**
     * Initialize sidebar navigation
     */
    function initNavigation() {
        $('.nav-item').on('click', function() {
            const section = $(this).data('section');

            // Update nav items
            $('.nav-item').removeClass('active');
            $(this).addClass('active');

            // Update sections
            $('.dashboard-section').removeClass('active');
            $('#section-' + section).addClass('active');
        });
    }

    /**
     * Initialize profile form
     */
    function initProfileForm() {
        $('#profile-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const $btnText = $button.find('.btn-text');
            const $btnLoader = $button.find('.btn-loader');
            const $message = $('.form-message');

            // Show loader
            $btnText.hide();
            $btnLoader.show();
            $button.prop('disabled', true);
            $message.hide();

            // Get form data
            const formData = $form.serialize();

            // FIXED: Добавляем member_id в запрос
            const memberId = getMemberId();
            const memberIdParam = memberId ? '&member_id=' + memberId : '';

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: formData + '&action=member_update_profile&nonce=' + memberDashboard.nonce + memberIdParam,
                success: function(response) {
                    if (response.success) {
                        showMessage($message, 'success', response.data.message);
                    } else {
                        showMessage($message, 'error', response.data.message || 'Произошла ошибка');
                    }
                },
                error: function() {
                    showMessage($message, 'error', 'Ошибка соединения с сервером');
                },
                complete: function() {
                    $btnText.show();
                    $btnLoader.hide();
                    $button.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize gallery manager
     */
    function initGalleryManager() {
        // Create hidden file input
        const $fileInput = $('<input type="file" id="gallery-file-input" accept="image/*" style="display: none;">');
        $('body').append($fileInput);

        // Add images button - triggers file input
        $('#add-gallery-images').on('click', function(e) {
            e.preventDefault();
            $fileInput.trigger('click');
        });

        // When file is selected - open cropper
        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            // Check if MetodaPhotoCropper is available
            if (typeof window.MetodaPhotoCropper === 'undefined') {
                alert('Кроппер фотографий не загружен. Пожалуйста, обновите страницу.');
                return;
            }

            // Open cropper
            window.MetodaPhotoCropper.open(file, function(croppedFile, blob) {
                // Upload cropped image
                uploadGalleryPhoto(croppedFile, blob);
            });

            // Reset input
            $(this).val('');
        });

        // Remove image from gallery
        $(document).on('click', '.remove-gallery-item', function(e) {
            e.preventDefault();
            $(this).closest('.gallery-item').remove();

            // Show empty message if no images
            if ($('.gallery-item').length === 0) {
                $('#gallery-grid').html('<div class="gallery-empty"><p>Галерея пуста. Добавьте свои фотографии.</p></div>');
            }

            updateGalleryIds();
        });

        // Save gallery button
        $('#save-gallery').on('click', function(e) {
            e.preventDefault();

            const $button = $(this);
            const $btnText = $button.find('.btn-text');
            const $btnLoader = $button.find('.btn-loader');
            const $message = $('.gallery-message');
            const galleryIds = $('#gallery_ids').val();

            // Show loader
            $btnText.hide();
            $btnLoader.show();
            $button.prop('disabled', true);
            $message.hide();

            // FIXED: Используем member_id из локализованных данных
            const memberId = getMemberId();

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: {
                    action: 'member_update_gallery',
                    nonce: memberDashboard.nonce,
                    gallery_ids: galleryIds,
                    member_id: memberId  // FIXED: всегда передаём member_id
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($message, 'success', response.data.message);
                    } else {
                        showMessage($message, 'error', response.data.message || 'Произошла ошибка');
                    }
                },
                error: function() {
                    showMessage($message, 'error', 'Ошибка соединения с сервером');
                },
                complete: function() {
                    $btnText.show();
                    $btnLoader.hide();
                    $button.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Upload photo to gallery
     */
    function uploadGalleryPhoto(file, blob) {
        const formData = new FormData();
        formData.append('action', 'member_upload_gallery_photo');
        formData.append('nonce', memberDashboard.nonce);  // FIXED: было memberDashboardData
        formData.append('photo', file, file.name);
        formData.append('member_id', getMemberId());  // ADDED: member_id

        // Show uploading indicator
        const $grid = $('#gallery-grid');
        $grid.find('.gallery-empty').remove();

        const $loader = $('<div class="gallery-item uploading"><div class="upload-progress"><i class="fas fa-spinner fa-spin"></i></div></div>');
        $grid.append($loader);

        // Send AJAX request
        $.ajax({
            url: memberDashboard.ajaxUrl,  // FIXED: было memberDashboardData
            type: 'POST',
            timeout: 10000,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $loader.remove();

                if (response.success) {
                    // Add image to grid
                    const data = response.data;
                    const $item = $('<div class="gallery-item" data-id="' + data.attachment_id + '"></div>');
                    $item.append('<img src="' + data.thumbnail_url + '" alt="">');
                    $item.append('<button type="button" class="remove-gallery-item" title="Удалить">×</button>');
                    $grid.append($item);

                    updateGalleryIds();
                    showToast('success', 'Фото успешно добавлено в галерею');
                } else {
                    showToast('error', response.data.message || 'Ошибка загрузки фото');
                }
            },
            error: function() {
                $loader.remove();
                showToast('error', 'Ошибка соединения с сервером');
            }
        });
    }

    /**
     * Update gallery IDs hidden field
     */
    function updateGalleryIds() {
        const ids = [];
        $('.gallery-item').each(function() {
            const id = $(this).data('id');
            if (id) ids.push(id);
        });
        $('#gallery_ids').val(ids.join(','));
    }

    /**
     * Initialize materials manager
     */
    function initMaterialsManager() {
        // Material tabs
        $('.materials-tab').on('click', function() {
            const category = $(this).data('category');

            // Update tabs
            $('.materials-tab').removeClass('active');
            $(this).addClass('active');

            // Update panes
            $('.materials-pane').removeClass('active');
            $('#materials-' + category).addClass('active');
        });

        // Material type selector
        $(document).on('click', '.type-btn', function() {
            const $pane = $(this).closest('.materials-pane');
            const type = $(this).data('type');

            // Update buttons
            $pane.find('.type-btn').removeClass('active');
            $(this).addClass('active');

            // Show corresponding form
            $pane.find('.material-form').removeClass('active');
            $pane.find('.' + type + '-form').addClass('active');
        });

        // Add link form
        $(document).on('submit', '.link-form', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $pane = $form.closest('.materials-pane');
            const category = $pane.data('category');
            const $button = $form.find('.btn-add-link');
            const $btnText = $button.find('.btn-text');
            const $btnLoader = $button.find('.btn-loader');

            // Show loader
            $btnText.hide();
            $btnLoader.show();
            $button.prop('disabled', true);

            // Get form data - FIXED: добавлен member_id
            const formData = {
                action: 'member_add_link',
                nonce: memberDashboard.nonce,
                member_id: getMemberId(),  // ADDED
                category: category,
                title: $form.find('[name="title"]').val(),
                url: $form.find('[name="url"]').val(),
                description: $form.find('[name="description"]').val()
            };

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.data.message);
                        $form[0].reset();
                        // Reload page to show new material
                        location.reload();
                    } else {
                        showToast('error', response.data.message || 'Произошла ошибка');
                    }
                },
                error: function() {
                    showToast('error', 'Ошибка соединения с сервером');
                },
                complete: function() {
                    $btnText.show();
                    $btnLoader.hide();
                    $button.prop('disabled', false);
                }
            });
        });

        // Add file form
        $(document).on('submit', '.file-form', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $pane = $form.closest('.materials-pane');
            const category = $pane.data('category');
            const $button = $form.find('.btn-add-file');
            const $btnText = $button.find('.btn-text');
            const $btnLoader = $button.find('.btn-loader');

            // Show loader
            $btnText.hide();
            $btnLoader.show();
            $button.prop('disabled', true);

            // Create FormData for file upload - FIXED: добавлен member_id
            const formData = new FormData();
            formData.append('action', 'member_upload_file');
            formData.append('nonce', memberDashboard.nonce);
            formData.append('member_id', getMemberId());  // ADDED
            formData.append('category', category);
            formData.append('title', $form.find('[name="title"]').val());
            formData.append('description', $form.find('[name="description"]').val());
            formData.append('file', $form.find('[name="file"]')[0].files[0]);

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.data.message);
                        $form[0].reset();
                        // Reload page to show new material
                        location.reload();
                    } else {
                        showToast('error', response.data.message || 'Произошла ошибка');
                    }
                },
                error: function() {
                    showToast('error', 'Ошибка соединения с сервером');
                },
                complete: function() {
                    $btnText.show();
                    $btnLoader.hide();
                    $button.prop('disabled', false);
                }
            });
        });

        // Delete material
        $(document).on('click', '.delete-material', function(e) {
            e.preventDefault();

            if (!confirm('Вы уверены, что хотите удалить этот материал?')) {
                return;
            }

            const $button = $(this);
            const $card = $button.closest('.material-card');
            const $pane = $button.closest('.materials-pane');
            const category = $pane.data('category');
            const index = $button.data('index');

            // Send AJAX request - FIXED: добавлен member_id
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                timeout: 10000,
                data: {
                    action: 'member_delete_material',
                    nonce: memberDashboard.nonce,
                    member_id: getMemberId(),  // ADDED
                    category: category,
                    index: index
                },
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.data.message);
                        $card.fadeOut(300, function() {
                            $(this).remove();

                            // Show empty message if no materials
                            if ($pane.find('.material-card').length === 0) {
                                $pane.find('.materials-grid').replaceWith(
                                    '<div class="materials-empty"><p>Материалов пока нет. Добавьте первый материал выше.</p></div>'
                                );
                            }
                        });
                    } else {
                        showToast('error', response.data.message || 'Произошла ошибка');
                    }
                },
                error: function() {
                    showToast('error', 'Ошибка соединения с сервером');
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

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $element.fadeOut();
        }, 5000);
    }

    /**
     * Show toast notification
     */
    function showToast(type, message) {
        // Create toast element if doesn't exist
        let $toast = $('#dashboard-toast');
        if ($toast.length === 0) {
            $toast = $('<div id="dashboard-toast" style="position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 400px;"></div>');
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

})(jQuery);
