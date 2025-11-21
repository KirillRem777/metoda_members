<?php
/**
 * Shortcodes
 *
 * Handles all plugin shortcodes for frontend display
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Shortcodes {

    /**
     * Constructor - register all shortcodes
     */
    public function __construct() {
        add_shortcode('members_directory', array($this, 'members_directory'));
        add_shortcode('member_registration', array($this, 'member_registration'));
        add_shortcode('manager_panel', array($this, 'manager_panel'));
        add_shortcode('custom_login', array($this, 'custom_login'));
    }

    /**
     * Members Directory shortcode
     *
     * Displays a filterable grid of members
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function members_directory($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'yes',
            'columns' => '3',
            'show_search' => 'yes',
        ), $atts, 'members_directory');

        ob_start();
        ?>
        <div class="members-directory-wrapper">
            <?php if ($atts['show_filters'] === 'yes'): ?>
            <?php $this->render_directory_filters($atts); ?>
            <?php endif; ?>

            <div class="members-grid columns-<?php echo esc_attr($atts['columns']); ?>" id="members-grid">
                <?php $this->render_directory_members(); ?>
            </div>
        </div>

        <?php $this->render_directory_scripts(); ?>
        <?php $this->render_directory_styles(); ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Render directory filters
     *
     * @param array $atts Shortcode attributes
     */
    private function render_directory_filters($atts) {
        ?>
        <div class="members-filters">
            <?php if ($atts['show_search'] === 'yes'): ?>
            <div class="members-search">
                <input type="text" id="member-search" placeholder="Поиск участников..." class="search-field">
            </div>
            <?php endif; ?>

            <div class="filter-group">
                <h4>Тип участника</h4>
                <div class="filter-buttons" data-filter="member_type">
                    <button class="filter-btn active" data-value="all">Все</button>
                    <?php
                    $types = get_terms(array('taxonomy' => 'member_type', 'hide_empty' => false));
                    foreach ($types as $type) {
                        echo '<button class="filter-btn" data-value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</button>';
                    }
                    ?>
                </div>
            </div>

            <div class="filter-group">
                <h4>Роль в ассоциации</h4>
                <div class="filter-buttons" data-filter="member_role">
                    <button class="filter-btn active" data-value="all">Все роли</button>
                    <?php
                    $roles = get_terms(array('taxonomy' => 'member_role', 'hide_empty' => false));
                    foreach ($roles as $role) {
                        echo '<button class="filter-btn" data-value="' . esc_attr($role->slug) . '">' . esc_html($role->name) . '</button>';
                    }
                    ?>
                </div>
            </div>

            <div class="filter-group">
                <h4>Локация</h4>
                <select id="location-filter" class="filter-select">
                    <option value="all">Все локации</option>
                    <?php
                    $locations = get_terms(array('taxonomy' => 'member_location', 'hide_empty' => false));
                    foreach ($locations as $location) {
                        echo '<option value="' . esc_attr($location->slug) . '">' . esc_html($location->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php
    }

    /**
     * Render directory member cards
     */
    private function render_directory_members() {
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $this->render_directory_member_card(get_the_ID());
            }
            wp_reset_postdata();
        }
    }

    /**
     * Render single member card for directory
     *
     * @param int $member_id Member post ID
     */
    private function render_directory_member_card($member_id) {
        $position = get_post_meta($member_id, 'member_position', true);
        $company = get_post_meta($member_id, 'member_company', true);

        // Get taxonomies
        $types = wp_get_post_terms($member_id, 'member_type', array('fields' => 'slugs'));
        $roles = wp_get_post_terms($member_id, 'member_role', array('fields' => 'slugs'));
        $locations = wp_get_post_terms($member_id, 'member_location', array('fields' => 'slugs'));

        $data_attributes = 'data-types="' . esc_attr(implode(' ', $types)) . '"';
        $data_attributes .= ' data-roles="' . esc_attr(implode(' ', $roles)) . '"';
        $data_attributes .= ' data-locations="' . esc_attr(implode(' ', $locations)) . '"';
        $data_attributes .= ' data-search="' . esc_attr(strtolower(get_the_title() . ' ' . $position . ' ' . $company)) . '"';
        ?>
        <div class="member-card" <?php echo $data_attributes; ?>>
            <a href="<?php the_permalink(); ?>" class="member-card-link">
                <div class="member-photo">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else : ?>
                        <div class="member-avatar-placeholder">
                            <?php echo mb_substr(get_the_title(), 0, 1); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="member-info">
                    <h3 class="member-name"><?php the_title(); ?></h3>
                    <?php if ($position) : ?>
                        <p class="member-position"><?php echo esc_html($position); ?></p>
                    <?php endif; ?>
                    <?php if ($company) : ?>
                        <p class="member-company"><?php echo esc_html($company); ?></p>
                    <?php endif; ?>

                    <div class="member-tags">
                        <?php
                        $type_terms = wp_get_post_terms($member_id, 'member_type');
                        foreach ($type_terms as $term) {
                            echo '<span class="tag tag-type">' . esc_html($term->name) . '</span>';
                        }

                        $role_terms = wp_get_post_terms($member_id, 'member_role');
                        foreach ($role_terms as $term) {
                            echo '<span class="tag tag-role">' . esc_html($term->name) . '</span>';
                        }
                        ?>
                    </div>
                </div>
            </a>
        </div>
        <?php
    }

    /**
     * Render directory JavaScript
     */
    private function render_directory_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Filter by type and role
            $('.filter-btn').on('click', function() {
                var $this = $(this);
                $this.siblings().removeClass('active');
                $this.addClass('active');
                filterMembers();
            });

            // Filter by location
            $('#location-filter').on('change', function() {
                filterMembers();
            });

            // Search
            $('#member-search').on('keyup', function() {
                filterMembers();
            });

            function filterMembers() {
                var typeFilter = $('.filter-buttons[data-filter="member_type"] .filter-btn.active').data('value');
                var roleFilter = $('.filter-buttons[data-filter="member_role"] .filter-btn.active').data('value');
                var locationFilter = $('#location-filter').val();
                var searchTerm = $('#member-search').val().toLowerCase();

                $('.member-card').each(function() {
                    var $card = $(this);
                    var show = true;

                    // Type filter
                    if (typeFilter !== 'all') {
                        var types = $card.data('types') || '';
                        if (types.indexOf(typeFilter) === -1) {
                            show = false;
                        }
                    }

                    // Role filter
                    if (show && roleFilter !== 'all') {
                        var roles = $card.data('roles') || '';
                        if (roles.indexOf(roleFilter) === -1) {
                            show = false;
                        }
                    }

                    // Location filter
                    if (show && locationFilter !== 'all') {
                        var locations = $card.data('locations') || '';
                        if (locations.indexOf(locationFilter) === -1) {
                            show = false;
                        }
                    }

                    // Search
                    if (show && searchTerm) {
                        var searchData = $card.data('search') || '';
                        if (searchData.indexOf(searchTerm) === -1) {
                            show = false;
                        }
                    }

                    if (show) {
                        $card.fadeIn();
                    } else {
                        $card.fadeOut();
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render directory styles
     */
    private function render_directory_styles() {
        ?>
        <style>
        .members-directory-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 24px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }
        .members-filters {
            background: linear-gradient(135deg, #f8f9fb 0%, #e9ecef 100%);
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 48px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.6);
        }
        .members-search { margin-bottom: 36px; }
        .search-field {
            width: 100%;
            padding: 16px 28px;
            font-size: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            outline: none;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .search-field:focus {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .filter-group { margin-bottom: 28px; }
        .filter-group:last-child { margin-bottom: 0; }
        .filter-group h4 {
            margin: 0 0 16px 0;
            color: #1a1a2e;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 700;
        }
        .filter-buttons { display: flex; flex-wrap: wrap; gap: 12px; }
        .filter-btn {
            padding: 10px 24px;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
        }
        .filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
        }
        .filter-select {
            width: 100%;
            max-width: 320px;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            background: white;
        }
        .members-grid {
            display: grid;
            gap: 32px;
            margin-top: 48px;
        }
        .members-grid.columns-2 { grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); }
        .members-grid.columns-3 { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
        .members-grid.columns-4 { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        .member-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .member-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
        }
        .member-card-link { text-decoration: none; color: inherit; display: block; }
        .member-photo {
            width: 100%;
            height: 320px;
            overflow: hidden;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .member-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
        .member-card:hover .member-photo img { transform: scale(1.08); }
        .member-avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 96px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .member-info { padding: 28px; }
        .member-name {
            margin: 0 0 10px 0;
            font-size: 22px;
            font-weight: 700;
            color: #1a1a2e;
            transition: color 0.3s ease;
        }
        .member-card:hover .member-name { color: #667eea; }
        .member-position { margin: 0 0 6px 0; font-size: 15px; color: #64748b; }
        .member-company { margin: 0 0 18px 0; font-size: 14px; color: #94a3b8; }
        .member-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
        .tag {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
        }
        .tag-type { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4c51bf; }
        .tag-role { background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); color: #be185d; }
        @media (max-width: 768px) {
            .members-directory-wrapper { padding: 24px 16px; }
            .members-filters { padding: 24px; }
            .members-grid { grid-template-columns: 1fr !important; gap: 24px; }
            .filter-buttons { flex-direction: column; }
            .filter-btn { width: 100%; }
            .member-photo { height: 280px; }
        }
        </style>
        <?php
    }

    /**
     * Member Registration shortcode
     *
     * Displays registration form
     *
     * @return string HTML output
     */
    public function member_registration() {
        ob_start();
        include(METODA_PATH . 'templates/member-registration.php');
        return ob_get_clean();
    }

    /**
     * Manager Panel shortcode
     *
     * Displays manager panel for managers and admins
     *
     * @return string HTML output
     */
    public function manager_panel() {
        if (!is_user_logged_in()) {
            return '<p>Пожалуйста, <a href="' . wp_login_url(get_permalink()) . '">войдите</a>, чтобы получить доступ к панели управления.</p>';
        }

        $user = wp_get_current_user();
        if (!in_array('manager', $user->roles) && !in_array('administrator', $user->roles)) {
            return '<p>У вас нет доступа к этой странице.</p>';
        }

        ob_start();
        include(METODA_PATH . 'templates/manager-panel.php');
        return ob_get_clean();
    }

    /**
     * Custom Login shortcode
     *
     * Displays custom login form with redirects
     *
     * @return string HTML output
     */
    public function custom_login() {
        // Kill switch: disable all redirects
        if (defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS) {
            return '<div style="padding: 20px; background: #ffeb3b; border: 2px solid #ff9800;">
                <h3>⚠️ Редиректы отключены (METODA_DISABLE_REDIRECTS)</h3>
                <p><a href="' . admin_url() . '">Перейти в админку →</a></p>
            </div>';
        }

        // Don't show shortcode in admin
        if (is_admin()) {
            return '';
        }

        if (is_user_logged_in()) {
            $user = wp_get_current_user();

            // Administrators should NOT redirect
            if (current_user_can('administrator') || current_user_can('manage_options')) {
                return '<div style="padding: 40px; text-align: center;">
                    <h2>Вы уже авторизованы как администратор</h2>
                    <p><a href="' . admin_url() . '">Перейти в админку →</a></p>
                </div>';
            }

            // Managers redirect to manager panel
            if (in_array('manager', $user->roles)) {
                wp_redirect(home_url('/manager-panel/'));
                exit;
            } else {
                // Regular users redirect to dashboard
                wp_redirect(home_url('/member-dashboard/'));
                exit;
            }
        }

        ob_start();
        include(METODA_PATH . 'templates/custom-login.php');
        return ob_get_clean();
    }
}
