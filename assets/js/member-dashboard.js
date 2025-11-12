/**
 * Member Dashboard JavaScript
 * Handles all interactive functionality for the personal cabinet
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

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                data: formData + '&action=member_update_profile&nonce=' + memberDashboard.nonce,
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
        let mediaUploader;

        // Add images button
        $('#add-gallery-images').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Create WordPress media uploader
            mediaUploader = wp.media({
                title: 'Выберите изображения',
                button: {
                    text: 'Добавить в галерею'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });

            // When images are selected
            mediaUploader.on('select', function() {
                const selection = mediaUploader.state().get('selection');
                const $grid = $('#gallery-grid');

                // Remove empty message if exists
                $grid.find('.gallery-empty').remove();

                selection.map(function(attachment) {
                    attachment = attachment.toJSON();

                    // Add image to grid
                    const $item = $('<div class="gallery-item" data-id="' + attachment.id + '"></div>');
                    $item.append('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
                    $item.append('<button type="button" class="remove-gallery-item" title="Удалить">×</button>');

                    $grid.append($item);
                });

                updateGalleryIds();
            });

            mediaUploader.open();
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

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'member_update_gallery',
                    nonce: memberDashboard.nonce,
                    gallery_ids: galleryIds
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
     * Update gallery IDs hidden field
     */
    function updateGalleryIds() {
        const ids = [];
        $('.gallery-item').each(function() {
            ids.push($(this).data('id'));
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

            // Get form data
            const formData = {
                action: 'member_add_link',
                nonce: memberDashboard.nonce,
                category: category,
                title: $form.find('[name="title"]').val(),
                url: $form.find('[name="url"]').val(),
                description: $form.find('[name="description"]').val()
            };

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
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

            // Create FormData for file upload
            const formData = new FormData();
            formData.append('action', 'member_upload_file');
            formData.append('nonce', memberDashboard.nonce);
            formData.append('category', category);
            formData.append('title', $form.find('[name="title"]').val());
            formData.append('description', $form.find('[name="description"]').val());
            formData.append('file', $form.find('[name="file"]')[0].files[0]);

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
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

            // Send AJAX request
            $.ajax({
                url: memberDashboard.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'member_delete_material',
                    nonce: memberDashboard.nonce,
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
