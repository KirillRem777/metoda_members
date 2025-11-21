/**
 * Members Archive JavaScript
 * Handles filtering, search, and pagination
 */

(function($) {
    'use strict';

    const MemberArchive = {
        currentPage: 1,
        perPage: 12,
        isLoading: false,

        init: function() {
            this.bindEvents();
            this.loadMembers(); // Initial load
        },

        bindEvents: function() {
            const self = this;

            // Search input with debounce
            let searchTimeout;
            $('#member-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.currentPage = 1;
                    self.loadMembers();
                }, 500);
            });

            // Filter changes
            $('#filter-type, #filter-role, #filter-location, #sort-by').on('change', function() {
                self.currentPage = 1;
                self.loadMembers();
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#member-search').val('');
                $('#filter-type').val('');
                $('#filter-role').val('');
                $('#filter-location').val('');
                $('#sort-by').val('title_asc');
                self.currentPage = 1;
                self.loadMembers();
            });

            // Pagination (delegated event)
            $(document).on('click', '.pagination-page', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).hasClass('active')) {
                    self.currentPage = page;
                    self.loadMembers();
                    $('html, body').animate({
                        scrollTop: $('.members-results-section').offset().top - 100
                    }, 500);
                }
            });

            $(document).on('click', '.pagination-btn', function(e) {
                e.preventDefault();
                if (!$(this).prop('disabled')) {
                    const direction = $(this).data('direction');
                    if (direction === 'prev') {
                        self.currentPage--;
                    } else {
                        self.currentPage++;
                    }
                    self.loadMembers();
                    $('html, body').animate({
                        scrollTop: $('.members-results-section').offset().top - 100
                    }, 500);
                }
            });
        },

        loadMembers: function() {
            if (this.isLoading) return;

            this.isLoading = true;
            const $grid = $('#members-grid');
            const $pagination = $('#members-pagination');

            // Show loading
            $grid.html(`
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Загрузка участников...</p>
                </div>
            `);

            const params = {
                action: 'filter_members',
                nonce: memberArchive.publicNonce, // SECURITY FIX v3.7.3: использую публичный nonce
                search: $('#member-search').val(),
                type: $('#filter-type').val(),
                role: $('#filter-role').val(),
                location: $('#filter-location').val(),
                sort: $('#sort-by').val(),
                page: this.currentPage,
                per_page: this.perPage
            };

            $.ajax({
                url: memberArchive.ajaxUrl,
                type: 'GET',
                data: params,
                success: (response) => {
                    if (response.success) {
                        this.renderMembers(response.data.members);
                        this.renderPagination(response.data);
                        this.updateCount(response.data.total);
                    } else {
                        this.showError('Ошибка загрузки данных');
                    }
                },
                error: () => {
                    this.showError('Ошибка соединения с сервером');
                },
                complete: () => {
                    this.isLoading = false;
                }
            });
        },

        renderMembers: function(members) {
            const $grid = $('#members-grid');

            if (members.length === 0) {
                $grid.html(`
                    <div class="members-empty">
                        <div class="members-empty-icon">🔍</div>
                        <h3 class="members-empty-title">Участники не найдены</h3>
                        <p class="members-empty-text">Попробуйте изменить параметры поиска или фильтры</p>
                    </div>
                `);
                return;
            }

            let html = '';
            members.forEach(member => {
                html += this.createMemberCard(member);
            });

            $grid.html(html);
        },

        createMemberCard: function(member) {
            const thumbnail = member.thumbnail
                ? `<img src="${member.thumbnail}" alt="${this.escapeHtml(member.title)}">`
                : `<div class="member-card-image-placeholder">👤</div>`;

            const type = member.type && member.type.length > 0
                ? `<div class="member-card-badge">${this.escapeHtml(member.type[0])}</div>`
                : '';

            const location = member.location && member.location.length > 0
                ? `<div class="member-card-location">
                       <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                           <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                           <circle cx="12" cy="10" r="3"></circle>
                       </svg>
                       ${this.escapeHtml(member.location.join(', '))}
                   </div>`
                : '';

            return `
                <div class="member-card">
                    <div class="member-card-image">
                        ${thumbnail}
                        ${type}
                    </div>
                    <div class="member-card-content">
                        <h3 class="member-card-title">
                            <a href="${member.url}">${this.escapeHtml(member.title)}</a>
                        </h3>
                        ${member.position ? `<div class="member-card-position">${this.escapeHtml(member.position)}</div>` : ''}
                        ${member.company ? `<div class="member-card-company">${this.escapeHtml(member.company)}</div>` : ''}
                        ${location}
                        ${member.excerpt ? `<div class="member-card-excerpt">${this.escapeHtml(member.excerpt)}</div>` : ''}
                        <a href="${member.url}" class="member-card-link">
                            Подробнее
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            `;
        },

        renderPagination: function(data) {
            const $pagination = $('#members-pagination');

            if (data.pages <= 1) {
                $pagination.html('');
                return;
            }

            let html = `
                <button class="pagination-btn" data-direction="prev" ${data.current_page === 1 ? 'disabled' : ''}>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
            `;

            // Generate page numbers
            const maxVisible = 5;
            let startPage = Math.max(1, data.current_page - Math.floor(maxVisible / 2));
            let endPage = Math.min(data.pages, startPage + maxVisible - 1);

            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            if (startPage > 1) {
                html += `<button class="pagination-page" data-page="1">1</button>`;
                if (startPage > 2) {
                    html += `<span class="pagination-ellipsis">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `<button class="pagination-page ${i === data.current_page ? 'active' : ''}" data-page="${i}">${i}</button>`;
            }

            if (endPage < data.pages) {
                if (endPage < data.pages - 1) {
                    html += `<span class="pagination-ellipsis">...</span>`;
                }
                html += `<button class="pagination-page" data-page="${data.pages}">${data.pages}</button>`;
            }

            html += `
                <button class="pagination-btn" data-direction="next" ${data.current_page === data.pages ? 'disabled' : ''}>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            `;

            $pagination.html(html);
        },

        updateCount: function(total) {
            $('#members-count').text(total);
        },

        showError: function(message) {
            $('#members-grid').html(`
                <div class="members-empty">
                    <div class="members-empty-icon">⚠️</div>
                    <h3 class="members-empty-title">Произошла ошибка</h3>
                    <p class="members-empty-text">${this.escapeHtml(message)}</p>
                </div>
            `);
        },

        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.members-archive-container').length) {
            MemberArchive.init();
        }
    });

})(jQuery);
