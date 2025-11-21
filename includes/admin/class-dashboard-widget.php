<?php
/**
 * Dashboard Widget
 *
 * Members statistics widget for WordPress dashboard
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Dashboard_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_widget'));
    }

    /**
     * Add dashboard widget
     */
    public function add_widget() {
        wp_add_dashboard_widget(
            'members_statistics_widget',
            '📊 Статистика участников',
            array($this, 'render_widget')
        );
    }

    /**
     * Render widget
     */
    public function render_widget() {
        $stats = $this->get_statistics();
        $recent_members = $this->get_recent_members();

        $this->render_html($stats, $recent_members);
    }

    /**
     * Get statistics
     *
     * @return array Statistics data
     */
    private function get_statistics() {
        global $wpdb;

        // Count members
        $total_members = wp_count_posts('members');

        // Get roles
        $roles = get_terms(array(
            'taxonomy' => 'member_role',
            'hide_empty' => false
        ));

        // Get cities count
        $cities_count = $wpdb->get_var("
            SELECT COUNT(DISTINCT meta_value)
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'member_city'
            AND meta_value != ''
        ");

        return array(
            'published' => $total_members->publish,
            'draft' => $total_members->draft,
            'cities' => $cities_count,
            'roles' => count($roles)
        );
    }

    /**
     * Get recent members
     *
     * @return array Recent members
     */
    private function get_recent_members() {
        return get_posts(array(
            'post_type' => 'members',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }

    /**
     * Render HTML
     *
     * @param array $stats Statistics
     * @param array $recent_members Recent members
     */
    private function render_html($stats, $recent_members) {
        ?>
        <?php $this->render_styles(); ?>

        <div class="members-stats-grid">
            <div class="members-stat-card">
                <div class="members-stat-number"><?php echo $stats['published']; ?></div>
                <div class="members-stat-label">Опубликовано</div>
            </div>

            <div class="members-stat-card">
                <div class="members-stat-number"><?php echo $stats['draft']; ?></div>
                <div class="members-stat-label">Черновики</div>
            </div>

            <div class="members-stat-card">
                <div class="members-stat-number"><?php echo $stats['cities']; ?></div>
                <div class="members-stat-label">Городов</div>
            </div>

            <div class="members-stat-card">
                <div class="members-stat-number"><?php echo $stats['roles']; ?></div>
                <div class="members-stat-label">Ролей</div>
            </div>
        </div>

        <?php if (!empty($recent_members)): ?>
        <h4 style="margin-top: 20px; margin-bottom: 10px;">Недавно добавленные</h4>
        <ul class="members-recent-list">
            <?php foreach ($recent_members as $member): ?>
            <li>
                <a href="<?php echo get_edit_post_link($member->ID); ?>" class="members-recent-name">
                    <?php echo esc_html($member->post_title); ?>
                </a>
                <span class="members-recent-date">
                    <?php echo human_time_diff(strtotime($member->post_date), current_time('timestamp')); ?> назад
                </span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <a href="<?php echo admin_url('edit.php?post_type=members'); ?>" class="members-view-all">
            Посмотреть всех участников →
        </a>

        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
            <p style="margin: 0 0 10px 0; font-weight: 500;">Быстрые действия:</p>
            <a href="<?php echo admin_url('edit.php?post_type=members&page=member-csv-import'); ?>" class="button">
                📥 Импорт из CSV
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=members'); ?>" class="button button-primary">
                ➕ Добавить участника
            </a>
        </div>
        <?php
    }

    /**
     * Render inline styles
     */
    private function render_styles() {
        ?>
        <style>
            .members-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }

            .members-stat-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #0066cc;
            }

            .members-stat-number {
                font-size: 32px;
                font-weight: bold;
                color: #0066cc;
                line-height: 1;
                margin-bottom: 5px;
            }

            .members-stat-label {
                font-size: 13px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .members-recent-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .members-recent-list li {
                padding: 8px 0;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .members-recent-list li:last-child {
                border-bottom: none;
            }

            .members-recent-name {
                font-weight: 500;
                color: #0066cc;
                text-decoration: none;
            }

            .members-recent-name:hover {
                text-decoration: underline;
            }

            .members-recent-date {
                font-size: 12px;
                color: #999;
            }

            .members-view-all {
                display: inline-block;
                margin-top: 15px;
                padding: 8px 16px;
                background: #0066cc;
                color: white !important;
                text-decoration: none;
                border-radius: 4px;
                font-size: 13px;
                transition: opacity 0.2s;
            }

            .members-view-all:hover {
                opacity: 0.9;
            }
        </style>
        <?php
    }
}
