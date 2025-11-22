<?php
/**
 * Member Archive Class
 *
 * Handles the members archive page with filters and search
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_Archive {

    /**
     * Initialize
     */
    public function __construct() {
        add_shortcode('members_archive', array($this, 'render_archive'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        // AJAX фильтрация перенесена в members-management-pro.php (filter_members_ajax)
        // add_action('wp_ajax_filter_members', array($this, 'ajax_filter_members'));
        // add_action('wp_ajax_nopriv_filter_members', array($this, 'ajax_filter_members'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        $current_post = get_post();
        if ($current_post && has_shortcode($current_post->post_content, 'members_archive')) {
            // v3.7.4: Подключаем variables.css первым
            wp_enqueue_style('metoda-variables', plugin_dir_url(dirname(__FILE__)) . 'assets/css/variables.css', array(), '1.0.0');
            wp_enqueue_style('member-archive', plugin_dir_url(dirname(__FILE__)) . 'assets/css/member-archive.css', array('metoda-variables'), '1.0.0');
            wp_enqueue_script('member-archive', plugin_dir_url(dirname(__FILE__)) . 'assets/js/member-archive.js', array('jquery'), '1.0.0', true);

            wp_localize_script('member-archive', 'memberArchive', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('public_members_nonce'),
                'publicNonce' => wp_create_nonce('public_members_nonce'), // Для совместимости с JS
            ));
        }
    }

    /**
     * Render archive shortcode
     */
    public function render_archive($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'columns' => 3,
            'show_filters' => 'yes',
            'show_search' => 'yes',
            'default_type' => '',
        ), $atts);

        ob_start();
        $this->render_filters($atts);
        $this->render_members_grid($atts);
        return ob_get_clean();
    }

    /**
     * Render filters
     */
    private function render_filters($atts) {
        if ($atts['show_filters'] !== 'yes' && $atts['show_search'] !== 'yes') {
            return;
        }

        $member_types = get_terms(array(
            'taxonomy' => 'member_type',
            'hide_empty' => true,
        ));

        $member_roles = get_terms(array(
            'taxonomy' => 'member_role',
            'hide_empty' => true,
        ));

        $member_locations = get_terms(array(
            'taxonomy' => 'member_location',
            'hide_empty' => true,
        ));

        ?>
        <div class="members-archive-container">
            <!-- Filters Section -->
            <?php if ($atts['show_filters'] === 'yes' || $atts['show_search'] === 'yes'): ?>
            <div class="members-filters-section">
                <div class="filters-header">
                    <h2 class="filters-title">Участники и Эксперты</h2>
                    <p class="filters-description">Найдите участников по специализации, локации или с помощью поиска</p>
                </div>

                <div class="filters-row">
                    <?php if ($atts['show_search'] === 'yes'): ?>
                    <!-- Search -->
                    <div class="filter-group filter-search">
                        <label for="member-search">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            Поиск
                        </label>
                        <input type="text"
                               id="member-search"
                               class="filter-input"
                               placeholder="Введите имя или организацию...">
                    </div>
                    <?php endif; ?>

                    <?php if ($atts['show_filters'] === 'yes'): ?>
                    <!-- Type Filter -->
                    <?php if (!empty($member_types) && !is_wp_error($member_types)): ?>
                    <div class="filter-group">
                        <label for="filter-type">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Тип участника
                        </label>
                        <select id="filter-type" class="filter-select">
                            <option value="">Все типы</option>
                            <?php foreach ($member_types as $type): ?>
                                <option value="<?php echo esc_attr($type->slug); ?>"
                                        <?php selected($atts['default_type'], $type->slug); ?>>
                                    <?php echo esc_html($type->name); ?> (<?php echo $type->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Role Filter -->
                    <?php if (!empty($member_roles) && !is_wp_error($member_roles)): ?>
                    <div class="filter-group">
                        <label for="filter-role">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Роль в ассоциации
                        </label>
                        <select id="filter-role" class="filter-select">
                            <option value="">Все роли</option>
                            <?php foreach ($member_roles as $role): ?>
                                <option value="<?php echo esc_attr($role->slug); ?>">
                                    <?php echo esc_html($role->name); ?> (<?php echo $role->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Location Filter -->
                    <?php if (!empty($member_locations) && !is_wp_error($member_locations)): ?>
                    <div class="filter-group">
                        <label for="filter-location">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            Локация
                        </label>
                        <select id="filter-location" class="filter-select">
                            <option value="">Все локации</option>
                            <?php foreach ($member_locations as $location): ?>
                                <option value="<?php echo esc_attr($location->slug); ?>">
                                    <?php echo esc_html($location->name); ?> (<?php echo $location->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Reset Button -->
                    <div class="filter-group filter-actions">
                        <button type="button" id="reset-filters" class="btn-reset">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="1 4 1 10 7 10"></polyline>
                                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                            </svg>
                            Сбросить
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Results Section -->
            <div class="members-results-section">
                <div class="results-header">
                    <div class="results-count">
                        Найдено: <strong id="members-count">0</strong> участников
                    </div>
                    <div class="results-sort">
                        <label for="sort-by">Сортировка:</label>
                        <select id="sort-by" class="sort-select">
                            <option value="title_asc">По имени (А-Я)</option>
                            <option value="title_desc">По имени (Я-А)</option>
                            <option value="date_desc">Сначала новые</option>
                            <option value="date_asc">Сначала старые</option>
                        </select>
                    </div>
                </div>

                <!-- Members Grid -->
                <div id="members-grid" class="members-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                    <!-- Will be populated by AJAX -->
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Загрузка участников...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="members-pagination" class="members-pagination">
                    <!-- Will be populated by AJAX -->
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render members grid (initial load)
     */
    private function render_members_grid($atts) {
        // Grid is rendered via AJAX for better filtering experience
    }

    /**
     * AJAX filter members
     */
    public function ajax_filter_members() {
        check_ajax_referer('public_members_nonce', 'nonce');

        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
        $location = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
        $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'title_asc';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 12;

        // Parse sort
        $sort_parts = explode('_', $sort);
        $orderby = $sort_parts[0];
        $order = isset($sort_parts[1]) ? strtoupper($sort_parts[1]) : 'ASC';

        // Build query
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => 'publish',
        );

        // Add search
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Add taxonomy filters
        $tax_query = array('relation' => 'AND');

        if (!empty($type)) {
            $tax_query[] = array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => $type,
            );
        }

        if (!empty($role)) {
            $tax_query[] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $role,
            );
        }

        if (!empty($location)) {
            $tax_query[] = array(
                'taxonomy' => 'member_location',
                'field' => 'slug',
                'terms' => $location,
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        // Execute query
        $query = new WP_Query($args);
        $members = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $member_id = get_the_ID();

                $members[] = array(
                    'id' => $member_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($member_id, 'medium'),
                    'position' => get_post_meta($member_id, 'member_position', true),
                    'company' => get_post_meta($member_id, 'member_company', true),
                    'location' => wp_get_post_terms($member_id, 'member_location', array('fields' => 'names')),
                    'type' => wp_get_post_terms($member_id, 'member_type', array('fields' => 'names')),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                );
            }
        }

        wp_reset_postdata();

        wp_send_json_success(array(
            'members' => $members,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ));
    }
}

// Initialize
new Member_Archive();
