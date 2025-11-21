(function($) {
    'use strict';

    let currentPage = 1;
    let currentFilters = {
        search: '',
        type: '',
        city: '',
        status: ''
    };
    let mediaUploader;
    let editingMemberId = null;

    $(document).ready(function() {
        console.log('Member Manager JS loaded');
        loadMembers();
        initFilters();
        initButtons();
        initModals();
        initPhotoUpload();
    });

    // Загрузка участников
    function loadMembers(page = 1) {
        currentPage = page;

        console.log('Loading members...', currentFilters);

        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'GET',
            data: {
                action: 'manager_get_members',
                nonce: memberManager.nonce,
                page: page,
                search: currentFilters.search,
                type: currentFilters.type,
                city: currentFilters.city,
                status: currentFilters.status
            },
            success: function(response) {
                console.log('Members loaded:', response);
                if (response.success) {
                    renderMembers(response.data.members);
                    $('#total-count').text(response.data.total || 0);
                    renderPagination(response.data.pages, page, response.data.total);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                $('#members-tbody').html('<tr><td colspan="7" class="px-6 py-12 text-center text-red-600">Ошибка загрузки данных</td></tr>');
            }
        });
    }

    // Отрисовка таблицы участников
    function renderMembers(members) {
        const tbody = $('#members-tbody');
        tbody.empty();

        if (!members || members.length === 0) {
            tbody.html('<tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">Участники не найдены</td></tr>');
            return;
        }

        members.forEach(function(member) {
            // Фото или инициал
            const photo = member.thumbnail ?
                '<img src="' + member.thumbnail + '" alt="' + member.title + '" class="w-8 h-8 rounded-full mr-3">' :
                '<div class="w-8 h-8 rounded-full mr-3 bg-admin-blue text-white flex items-center justify-center font-bold text-sm">' +
                member.title.charAt(0).toUpperCase() + '</div>';

            // Тип участника badge
            const typeLabel = member.member_type || 'Участник';
            const typeColor = typeLabel === 'Эксперт' || typeLabel === 'Expert' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
            const typeBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + typeColor + '">' + typeLabel + '</span>';

            // Статус badge
            let statusBadge = '';
            if (member.post_status === 'publish') {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Активен</span>';
            } else if (member.post_status === 'pending') {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">На модерации</span>';
            } else {
                statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Черновик</span>';
            }

            // Дата регистрации
            const date = member.post_date ? member.post_date.split(' ')[0] : '';

            const row = '<tr class="hover:bg-gray-50" data-id="' + member.id + '">' +
                '<td class="px-6 py-4 whitespace-nowrap">' +
                    '<div class="flex items-center">' +
                        photo +
                        '<div>' +
                            '<div class="text-sm font-medium text-gray-900">' + member.title + '</div>' +
                            '<div class="text-sm text-gray-500">' + (member.email || '') + '</div>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
                '<td class="px-6 py-4 whitespace-nowrap">' + typeBadge + '</td>' +
                '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + (member.company || '') + '</td>' +
                '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + (member.city || '') + '</td>' +
                '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' + date + '</td>' +
                '<td class="px-6 py-4 whitespace-nowrap">' + statusBadge + '</td>' +
                '<td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">' +
                    '<button class="text-admin-blue hover:text-blue-700 font-medium edit-member" data-id="' + member.id + '">Edit</button>' +
                    '<button class="text-red-600 hover:text-red-700 font-medium delete-member" data-id="' + member.id + '" data-name="' + member.title + '">Delete</button>' +
                '</td>' +
            '</tr>';

            tbody.append(row);
        });

        // Инициализируем обработчики для кнопок
        initMemberActions();
    }

    // Пагинация
    function renderPagination(pages, current, total) {
        const paginationButtons = $('#pagination-buttons');
        paginationButtons.empty();

        // Обновляем счетчик
        const from = total > 0 ? ((current - 1) * 20 + 1) : 0;
        const to = Math.min(current * 20, total);
        $('#page-from').text(from);
        $('#page-to').text(to);
        $('#page-total').text(total);

        if (pages <= 1) return;

        // Previous button
        if (current > 1) {
            paginationButtons.append('<button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 page-btn" data-page="' + (current - 1) + '">Previous</button>');
        }

        // Page numbers
        for (let i = 1; i <= Math.min(pages, 5); i++) {
            const activeClass = i === current ? 'bg-admin-blue text-white' : 'border border-gray-300 hover:bg-gray-50';
            paginationButtons.append('<button class="px-3 py-2 text-sm rounded-lg page-btn ' + activeClass + '" data-page="' + i + '">' + i + '</button>');
        }

        // Next button
        if (current < pages) {
            paginationButtons.append('<button class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 page-btn" data-page="' + (current + 1) + '">Next</button>');
        }

        $('.page-btn').on('click', function() {
            loadMembers($(this).data('page'));
        });
    }

    // Инициализация фильтров
    function initFilters() {
        // Поиск
        let searchTimeout;
        $('#member-search').on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                currentFilters.search = $('#member-search').val();
                loadMembers(1);
            }, 500);
        });

        // Фильтр по типу
        $('#filter-type').on('change', function() {
            currentFilters.type = $(this).val();
            loadMembers(1);
        });

        // Фильтр по городу
        $('#filter-city').on('change', function() {
            currentFilters.city = $(this).val();
            loadMembers(1);
        });

        // Фильтр по статусу
        $('#filter-status').on('change', function() {
            currentFilters.status = $(this).val();
            loadMembers(1);
        });
    }

    // Инициализация кнопок
    function initButtons() {
        // Кнопка добавления участника
        $('#add-member-btn').on('click', function() {
            console.log('Add member clicked');
            openModal('add');
        });
    }

    // Инициализация действий над участниками
    function initMemberActions() {
        // Редактирование
        $('.edit-member').off('click').on('click', function() {
            const memberId = $(this).data('id');
            console.log('Edit member:', memberId);
            loadMemberData(memberId);
        });

        // Удаление
        $('.delete-member').off('click').on('click', function() {
            const memberId = $(this).data('id');
            const memberName = $(this).data('name');
            console.log('Delete member:', memberId);
            openDeleteModal(memberId, memberName);
        });
    }

    // Открытие модального окна
    function openModal(mode, memberId = null) {
        editingMemberId = memberId;

        if (mode === 'add') {
            $('#modal-title').text('Добавить участника');
            $('#member-form')[0].reset();
            $('#member-id').val('');
            $('#photo-preview').html('<i class="fas fa-user text-gray-400 text-2xl"></i>');
            $('input[name="member_types[]"]').prop('checked', false);
            $('.status-btn').removeClass('bg-green-600 bg-yellow-600 bg-gray-600').addClass('bg-gray-300');
            $('.status-btn[data-status="publish"]').removeClass('bg-gray-300').addClass('bg-green-600 text-white');
            $('#member-status').val('publish');
        }

        $('#member-detail-modal').removeClass('hidden').addClass('flex');
    }

    // Загрузка данных участника
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
                console.log('Member data loaded:', response);
                if (response.success) {
                    fillModalForm(response.data);
                    editingMemberId = memberId;
                    $('#modal-title').text('Редактирование участника');
                    $('#member-detail-modal').removeClass('hidden').addClass('flex');
                }
            },
            error: function() {
                alert('Ошибка загрузки данных участника');
            }
        });
    }

    // Заполнение формы модального окна
    function fillModalForm(data) {
        $('#member-id').val(data.id);
        $('#member-title').val(data.title);
        $('#member-email').val(data.email);
        $('#member-company').val(data.company);
        $('#member-position').val(data.position);
        $('#member-city').val(data.city);
        $('#member-phone').val(data.phone);

        // Фото
        if (data.thumbnail) {
            $('#photo-preview').html('<img src="' + data.thumbnail + '" class="w-full h-full object-cover rounded-lg">');
            $('#thumbnail-id').val(data.thumbnail_id);
        }

        // Типы
        if (data.member_types) {
            data.member_types.forEach(function(typeId) {
                $('input[name="member_types[]"][value="' + typeId + '"]').prop('checked', true);
            });
        }

        // Роль
        if (data.member_role) {
            $('#member-role').val(data.member_role);
        }

        // Статус
        $('.status-btn').removeClass('bg-green-600 bg-yellow-600 bg-gray-600 text-white').addClass('bg-gray-300 text-gray-700');
        $('.status-btn[data-status="' + data.post_status + '"]').removeClass('bg-gray-300 text-gray-700');

        if (data.post_status === 'publish') {
            $('.status-btn[data-status="publish"]').addClass('bg-green-600 text-white');
        } else if (data.post_status === 'pending') {
            $('.status-btn[data-status="pending"]').addClass('bg-yellow-600 text-white');
        } else {
            $('.status-btn[data-status="draft"]').addClass('bg-gray-600 text-white');
        }

        $('#member-status').val(data.post_status);
    }

    // Инициализация модальных окон
    function initModals() {
        // Закрытие модального окна редактирования
        $('#close-modal, #cancel-btn').on('click', function() {
            $('#member-detail-modal').addClass('hidden').removeClass('flex');
        });

        // Закрытие при клике вне модального окна
        $('#member-detail-modal').on('click', function(e) {
            if ($(e.target).is('#member-detail-modal')) {
                $(this).addClass('hidden').removeClass('flex');
            }
        });

        // Кнопки статуса
        $('.status-btn').on('click', function() {
            const status = $(this).data('status');
            $('.status-btn').removeClass('bg-green-600 bg-yellow-600 bg-gray-600 text-white').addClass('bg-gray-300 text-gray-700');
            $(this).removeClass('bg-gray-300 text-gray-700');

            if (status === 'publish') {
                $(this).addClass('bg-green-600 text-white');
            } else if (status === 'pending') {
                $(this).addClass('bg-yellow-600 text-white');
            } else {
                $(this).addClass('bg-gray-600 text-white');
            }

            $('#member-status').val(status);
        });

        // Отправка формы
        $('#member-form').on('submit', function(e) {
            e.preventDefault();
            saveMember();
        });

        // Закрытие модального окна удаления
        $('#delete-cancel-btn').on('click', function() {
            $('#delete-modal').addClass('hidden').removeClass('flex');
        });

        $('#delete-modal').on('click', function(e) {
            if ($(e.target).is('#delete-modal')) {
                $(this).addClass('hidden').removeClass('flex');
            }
        });

        // Подтверждение удаления
        $('#delete-confirm-btn').on('click', function() {
            deleteMember();
        });
    }

    // Сохранение участника
    function saveMember() {
        const formData = new FormData($('#member-form')[0]);
        formData.append('action', editingMemberId ? 'manager_update_member' : 'manager_create_member');
        formData.append('nonce', memberManager.nonce);

        if (editingMemberId) {
            formData.set('member_id', editingMemberId);
        }

        // Показываем индикатор загрузки
        $('.btn-text').text('Сохранение...');
        $('.btn-loader').removeClass('hidden');

        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Save response:', response);
                $('.btn-text').text('Сохранить');
                $('.btn-loader').addClass('hidden');

                if (response.success) {
                    $('#member-detail-modal').addClass('hidden').removeClass('flex');
                    loadMembers(currentPage);
                    alert(editingMemberId ? 'Участник обновлен' : 'Участник добавлен');
                } else {
                    alert('Ошибка: ' + (response.data || 'Неизвестная ошибка'));
                }
            },
            error: function() {
                $('.btn-text').text('Сохранить');
                $('.btn-loader').addClass('hidden');
                alert('Ошибка сохранения');
            }
        });
    }

    // Открытие модального окна удаления
    function openDeleteModal(memberId, memberName) {
        editingMemberId = memberId;
        $('#delete-member-name').text(memberName);
        $('#delete-modal').removeClass('hidden').addClass('flex');
    }

    // Удаление участника
    function deleteMember() {
        $('.btn-text').text('Удаление...');
        $('.btn-loader').removeClass('hidden');

        $.ajax({
            url: memberManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'manager_delete_member',
                nonce: memberManager.nonce,
                member_id: editingMemberId
            },
            success: function(response) {
                $('.btn-text').text('Удалить');
                $('.btn-loader').addClass('hidden');

                if (response.success) {
                    $('#delete-modal').addClass('hidden').removeClass('flex');
                    loadMembers(currentPage);
                    alert('Участник удален');
                } else {
                    alert('Ошибка удаления');
                }
            },
            error: function() {
                $('.btn-text').text('Удалить');
                $('.btn-loader').addClass('hidden');
                alert('Ошибка удаления');
            }
        });
    }

    // Загрузка фото
    function initPhotoUpload() {
        $('#upload-photo-btn').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Выберите фото',
                button: {
                    text: 'Использовать'
                },
                multiple: false
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#thumbnail-id').val(attachment.id);
                $('#photo-preview').html('<img src="' + attachment.url + '" class="w-full h-full object-cover rounded-lg">');
            });

            mediaUploader.open();
        });
    }

})(jQuery);
