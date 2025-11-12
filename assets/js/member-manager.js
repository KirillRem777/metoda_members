(function($) {
    'use strict';

    let currentPage = 1;
    let mediaUploader;
    let galleryUploader;
    let editingMemberId = null;
    let galleryIds = [];

    $(document).ready(function() {
        loadMembers();
        initAddMember();
        initSearch();
        initModals();
        initPhotoUpload();
        initMaterials();
        initGallery();
    });

    function loadMembers(page = 1, search = '') {
        currentPage = page;

        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'GET',
            data: {
                action: 'manager_get_members',
                nonce: memberManager.nonce,
                page: page,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderMembers(response.data.members);
                    $('#total-count').text(response.data.total);
                    renderPagination(response.data.pages, page);
                }
            }
        });
    }

    function renderMembers(members) {
        const tbody = $('#members-tbody');
        tbody.empty();

        if (members.length === 0) {
            tbody.append('<tr><td colspan="6" style="text-align:center;padding:40px;">Участники не найдены</td></tr>');
            return;
        }

        members.forEach(function(member) {
            const photo = member.thumbnail ?
                '<img src="' + member.thumbnail + '" class="member-photo" alt="' + member.title + '">' :
                '<div class="member-photo" style="background:#667eea;color:white;display:flex;align-items:center;justify-content:center;font-weight:bold;">' +
                member.title.charAt(0) + '</div>';

            tbody.append(
                '<tr data-id="' + member.id + '">' +
                    '<td>' + photo + '</td>' +
                    '<td><strong>' + member.title + '</strong></td>' +
                    '<td>' + (member.position || '') + '</td>' +
                    '<td>' + (member.email || '') + '</td>' +
                    '<td>' + (member.phone || '') + '</td>' +
                    '<td>' +
                        '<div class="action-btns">' +
                            '<button class="btn btn-secondary btn-small edit-member" data-id="' + member.id + '">✏️ Редактировать</button>' +
                            '<button class="btn btn-danger btn-small delete-member" data-id="' + member.id + '" data-name="' + member.title + '">🗑️ Удалить</button>' +
                        '</div>' +
                    '</td>' +
                '</tr>'
            );
        });

        initMemberActions();
    }

    function renderPagination(pages, current) {
        const pagination = $('#pagination');
        pagination.empty();

        if (pages <= 1) return;

        for (let i = 1; i <= pages; i++) {
            const active = i === current ? 'active' : '';
            pagination.append('<button class="btn btn-secondary btn-small page-btn ' + active + '" data-page="' + i + '">' + i + '</button>');
        }

        $('.page-btn').on('click', function() {
            loadMembers($(this).data('page'), $('#member-search').val());
        });
    }

    function initAddMember() {
        $('#add-member-btn').on('click', function() {
            editingMemberId = null;
            $('#modal-title').text('Добавить участника');
            $('#member-form')[0].reset();
            $('#member-id').val('');
            $('#photo-preview').empty();
            $('input[name="member_types[]"]').prop('checked', false);
            $('input[name="member_roles[]"]').prop('checked', false);
            $('input[name="member_locations[]"]').prop('checked', false);
            clearMaterials();
            clearGallery();
            $('#member-modal').addClass('active');
        });
    }

    function initMemberActions() {
        $('.edit-member').on('click', function() {
            const memberId = $(this).data('id');
            editingMemberId = memberId;
            loadMemberData(memberId);
        });

        $('.delete-member').on('click', function() {
            const memberId = $(this).data('id');
            const memberName = $(this).data('name');
            $('#delete-member-name').text(memberName);
            $('#delete-confirm-btn').data('id', memberId);
            $('#delete-modal').addClass('active');
        });
    }

    function loadMemberData(memberId) {
        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'GET',
            data: {
                action: 'manager_get_member',
                nonce: memberManager.nonce,
                member_id: memberId
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#modal-title').text('Редактировать участника');
                    $('#member-id').val(data.id);
                    $('#member-title').val(data.title);
                    $('#member-position').val(data.position);
                    $('#member-company').val(data.company);
                    $('#member-email').val(data.email);
                    $('#member-phone').val(data.phone);
                    $('#member-bio').val(data.bio);
                    $('#member-specialization').val(data.specialization);
                    $('#member-experience').val(data.experience);
                    $('#member-interests').val(data.interests);
                    $('#member-linkedin').val(data.linkedin);
                    $('#member-website').val(data.website);
                    $('#member-expectations').val(data.expectations);
                    $('#thumbnail-id').val(data.thumbnail_id);

                    if (data.thumbnail_url) {
                        $('#photo-preview').html('<img src="' + data.thumbnail_url + '" alt="Photo">');
                    } else {
                        $('#photo-preview').empty();
                    }

                    // Set taxonomy checkboxes
                    $('input[name="member_types[]"]').prop('checked', false);
                    if (data.member_types) {
                        data.member_types.forEach(function(termId) {
                            $('input[name="member_types[]"][value="' + termId + '"]').prop('checked', true);
                        });
                    }

                    $('input[name="member_roles[]"]').prop('checked', false);
                    if (data.member_roles) {
                        data.member_roles.forEach(function(termId) {
                            $('input[name="member_roles[]"][value="' + termId + '"]').prop('checked', true);
                        });
                    }

                    $('input[name="member_locations[]"]').prop('checked', false);
                    if (data.member_locations) {
                        data.member_locations.forEach(function(termId) {
                            $('input[name="member_locations[]"][value="' + termId + '"]').prop('checked', true);
                        });
                    }

                    // Load materials
                    loadMaterials(data.materials);

                    // Load gallery
                    loadGallery(data.gallery);

                    $('#member-modal').addClass('active');
                }
            }
        });
    }

    function initSearch() {
        $('#search-btn').on('click', function() {
            loadMembers(1, $('#member-search').val());
        });

        $('#member-search').on('keypress', function(e) {
            if (e.which === 13) {
                loadMembers(1, $(this).val());
            }
        });
    }

    function initModals() {
        $('.modal-close, #cancel-btn, #delete-cancel-btn').on('click', function() {
            $(this).closest('.modal').removeClass('active');
        });

        $('#member-form').on('submit', function(e) {
            e.preventDefault();
            saveMember();
        });

        $('#delete-confirm-btn').on('click', function() {
            deleteMember($(this).data('id'));
        });
    }

    function saveMember() {
        const memberId = $('#member-id').val();
        const action = memberId ? 'manager_update_member' : 'manager_create_member';
        const $btn = $('#member-form button[type="submit"]');
        const $btnText = $btn.find('.btn-text');
        const $btnLoader = $btn.find('.btn-loader');

        $btnText.hide();
        $btnLoader.show();
        $btn.prop('disabled', true);

        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'POST',
            data: $('#member-form').serialize() + '&action=' + action + '&nonce=' + memberManager.nonce,
            success: function(response) {
                if (response.success) {
                    $('#member-modal').removeClass('active');
                    loadMembers(currentPage, $('#member-search').val());
                    showToast('success', response.data.message);
                } else {
                    showToast('error', response.data.message);
                }
            },
            complete: function() {
                $btnText.show();
                $btnLoader.hide();
                $btn.prop('disabled', false);
            }
        });
    }

    function deleteMember(memberId) {
        const $btn = $('#delete-confirm-btn');
        const $btnText = $btn.find('.btn-text');
        const $btnLoader = $btn.find('.btn-loader');

        $btnText.hide();
        $btnLoader.show();
        $btn.prop('disabled', true);

        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'manager_delete_member',
                nonce: memberManager.nonce,
                member_id: memberId
            },
            success: function(response) {
                if (response.success) {
                    $('#delete-modal').removeClass('active');
                    loadMembers(currentPage, $('#member-search').val());
                    showToast('success', response.data.message);
                } else {
                    showToast('error', response.data.message);
                }
            },
            complete: function() {
                $btnText.show();
                $btnLoader.hide();
                $btn.prop('disabled', false);
            }
        });
    }

    function initPhotoUpload() {
        $('#upload-photo-btn').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Выберите фото',
                button: { text: 'Использовать' },
                multiple: false,
                library: { type: 'image' }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#thumbnail-id').val(attachment.id);
                $('#photo-preview').html('<img src="' + attachment.url + '" alt="Photo">');
            });

            mediaUploader.open();
        });
    }

    function showToast(type, message) {
        let $toast = $('#manager-toast');
        if ($toast.length === 0) {
            $toast = $('<div id="manager-toast" style="position:fixed;top:20px;right:20px;z-index:10000;"></div>');
            $('body').append($toast);
        }

        const bg = type === 'success' ? '#d1fae5' : '#fee2e2';
        const border = type === 'success' ? '#34d399' : '#f87171';
        const color = type === 'success' ? '#065f46' : '#991b1b';

        const $msg = $('<div style="background:' + bg + ';color:' + color + ';border:2px solid ' + border + ';padding:15px 20px;border-radius:10px;margin-bottom:10px;box-shadow:0 4px 20px rgba(0,0,0,0.15);font-weight:600;">' + message + '</div>');
        $toast.append($msg);

        setTimeout(function() {
            $msg.fadeOut(300, function() { $(this).remove(); });
        }, 5000);
    }

    function initMaterials() {
        // Add material button handler
        $(document).on('click', '.add-material-btn', function() {
            const category = $(this).data('category');
            addMaterialItem(category);
        });

        // Remove material button handler
        $(document).on('click', '.remove-material-btn', function() {
            $(this).closest('.material-item').remove();
        });
    }

    function addMaterialItem(category, title = '', link = '') {
        const $container = $('#materials-' + category + '-container');
        const index = $container.find('.material-item').length;

        const $item = $('<div class="material-item">' +
            '<input type="text" name="materials[' + category + '][' + index + '][title]" placeholder="Название" value="' + title + '">' +
            '<input type="url" name="materials[' + category + '][' + index + '][link]" placeholder="Ссылка (https://...)" value="' + link + '">' +
            '<button type="button" class="remove-material-btn">Удалить</button>' +
        '</div>');

        $container.append($item);
    }

    function loadMaterials(materials) {
        // Clear all material containers
        $('.materials-container').empty();

        // Load materials for each category
        if (materials) {
            $.each(materials, function(category, items) {
                if (items && items.length > 0) {
                    $.each(items, function(index, item) {
                        addMaterialItem(category, item.title || '', item.link || '');
                    });
                }
            });
        }
    }

    function clearMaterials() {
        $('.materials-container').empty();
    }

    function initGallery() {
        $('#upload-gallery-btn').on('click', function(e) {
            e.preventDefault();

            if (galleryUploader) {
                galleryUploader.open();
                return;
            }

            galleryUploader = wp.media({
                title: 'Выберите фотографии для галереи',
                button: { text: 'Добавить в галерею' },
                multiple: true,
                library: { type: 'image' }
            });

            galleryUploader.on('select', function() {
                const attachments = galleryUploader.state().get('selection').toJSON();
                attachments.forEach(function(attachment) {
                    if (galleryIds.indexOf(attachment.id) === -1) {
                        galleryIds.push(attachment.id);
                        addGalleryImage(attachment.id, attachment.url);
                    }
                });
                updateGalleryInput();
            });

            galleryUploader.open();
        });

        // Remove gallery image handler
        $(document).on('click', '.gallery-item-remove', function() {
            const imageId = $(this).data('id');
            galleryIds = galleryIds.filter(function(id) { return id !== imageId; });
            $(this).closest('.gallery-item').remove();
            updateGalleryInput();
        });
    }

    function addGalleryImage(imageId, imageUrl) {
        const $item = $('<div class="gallery-item">' +
            '<img src="' + imageUrl + '" alt="Gallery">' +
            '<button type="button" class="gallery-item-remove" data-id="' + imageId + '">×</button>' +
        '</div>');

        $('#gallery-preview').append($item);
    }

    function updateGalleryInput() {
        $('#gallery-ids').val(galleryIds.join(','));
    }

    function loadGallery(gallery) {
        $('#gallery-preview').empty();
        galleryIds = [];

        if (gallery && gallery.length > 0) {
            gallery.forEach(function(image) {
                if (image.id && image.url) {
                    galleryIds.push(image.id);
                    addGalleryImage(image.id, image.url);
                }
            });
            updateGalleryInput();
        }
    }

    function clearGallery() {
        $('#gallery-preview').empty();
        galleryIds = [];
        $('#gallery-ids').val('');
    }

})(jQuery);
