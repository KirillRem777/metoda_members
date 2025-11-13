/**
 * AJAX фильтрация для архива участников
 */
(function($) {
    'use strict';

    const MembersArchive = {

        init: function() {
            this.bindEvents();
            this.initMobileFilters();
        },

        bindEvents: function() {
            // Поиск
            $('#member-search').on('input', $.debounce(500, function() {
                MembersArchive.loadMembers();
            }));

            // Фильтр по городу
            $('#city-filter').on('change', function() {
                MembersArchive.loadMembers();
            });

            // Фильтр по роли (чекбоксы)
            $('.role-checkbox').on('change', function() {
                MembersArchive.loadMembers();
            });

            // Сортировка
            $('#sort-filter').on('change', function() {
                MembersArchive.loadMembers();
            });

            // Кнопка "Сбросить"
            $('#reset-filters').on('click', function(e) {
                e.preventDefault();
                MembersArchive.resetFilters();
            });

            // Пагинация (делегирование событий)
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                MembersArchive.loadMembers(page);
            });
        },

        loadMembers: function(page = 1) {
            const $grid = $('#members-grid');
            const $count = $('#members-count');
            const $loader = $('#members-loader');

            // Собираем выбранные роли
            const roles = [];
            $('.role-checkbox:checked').each(function() {
                roles.push($(this).val());
            });

            const data = {
                action: 'filter_members',
                nonce: membersAjax.nonce,
                search: $('#member-search').val(),
                city: $('#city-filter').val(),
                roles: roles,
                sort: $('#sort-filter').val(),
                paged: page
            };

            // Показываем лоадер
            $loader.removeClass('hidden');
            $grid.addClass('opacity-50');

            $.ajax({
                url: membersAjax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $grid.html(response.data.html);
                        $count.text(response.data.found);

                        // Обновляем пагинацию
                        $('#members-pagination').html(response.data.pagination);

                        // Прокручиваем к началу результатов
                        if (page > 1) {
                            $('html, body').animate({
                                scrollTop: $('#members-grid').offset().top - 100
                            }, 300);
                        }
                    } else {
                        console.error('Error:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Произошла ошибка при загрузке участников. Попробуйте обновить страницу.');
                },
                complete: function() {
                    $loader.addClass('hidden');
                    $grid.removeClass('opacity-50');
                }
            });
        },

        resetFilters: function() {
            $('#member-search').val('');
            $('#city-filter').val('');
            $('#sort-filter').val('title-asc');
            $('.role-checkbox').prop('checked', false);
            this.loadMembers();
        },

        initMobileFilters: function() {
            // Кнопка открытия фильтров на мобильных
            $('#mobile-filter-toggle').on('click', function() {
                $('#filter-sidebar').toggleClass('hidden');
                $(this).find('i').toggleClass('fa-filter fa-times');
            });

            // Закрытие при клике вне фильтров на мобильных
            $(document).on('click', function(e) {
                if ($(window).width() < 1024) {
                    const $sidebar = $('#filter-sidebar');
                    const $toggle = $('#mobile-filter-toggle');

                    if (!$sidebar.is(e.target) && $sidebar.has(e.target).length === 0 &&
                        !$toggle.is(e.target) && $toggle.has(e.target).length === 0) {
                        $sidebar.addClass('hidden');
                        $toggle.find('i').removeClass('fa-times').addClass('fa-filter');
                    }
                }
            });
        }
    };

    // Debounce функция
    $.debounce = function(delay, fn) {
        let timer = null;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, delay);
        };
    };

    // Инициализация при загрузке страницы
    $(document).ready(function() {
        if ($('#members-archive').length) {
            MembersArchive.init();
        }
    });

})(jQuery);
