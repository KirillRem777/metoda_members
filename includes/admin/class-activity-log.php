<?php
/**
 * Activity Log
 *
 * Admin page for monitoring member activity (messages, forum posts, dashboard access)
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Activity_Log {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
    }

    /**
     * Add activity log menu page
     */
    public function add_menu_page() {
        add_menu_page(
            'Логи активности',
            'Активность',
            'manage_options',
            'metoda-activity-log',
            array($this, 'render_page'),
            'dashicons-visibility',
            30
        );
    }

    /**
     * Render activity log page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die('У вас нет прав для просмотра этой страницы');
        }

        // Get recent messages
        $messages_query = $this->get_recent_messages();

        // Get recent forum posts
        $forum_query = $this->get_recent_forum_posts();

        // Get all members for quick access dropdown
        $members_query = $this->get_all_members();

        $this->render_html($messages_query, $forum_query, $members_query);
    }

    /**
     * Get recent messages
     *
     * @return WP_Query
     */
    private function get_recent_messages() {
        $args = array(
            'post_type' => 'member_message',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => array('publish', 'private')
        );
        return new WP_Query($args);
    }

    /**
     * Get recent forum posts
     *
     * @return WP_Query
     */
    private function get_recent_forum_posts() {
        $args = array(
            'post_type' => 'forum_topic',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        return new WP_Query($args);
    }

    /**
     * Get all members
     *
     * @return WP_Query
     */
    private function get_all_members() {
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        return new WP_Query($args);
    }

    /**
     * Render HTML
     *
     * @param WP_Query $messages_query Messages query
     * @param WP_Query $forum_query Forum posts query
     * @param WP_Query $members_query Members query
     */
    private function render_html($messages_query, $forum_query, $members_query) {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-visibility" style="font-size: 30px; width: 30px; height: 30px;"></span>
                Логи активности участников
            </h1>
            <p class="description">Мониторинг активности пользователей: сообщения, посты на форуме и доступ к личным кабинетам</p>

            <hr class="wp-header-end">

            <!-- Quick dashboard access -->
            <div class="card" style="margin-top: 20px;">
                <h2>🚀 Быстрый доступ к личным кабинетам</h2>
                <p>Выберите участника для просмотра его личного кабинета:</p>
                <select id="member-select" style="width: 400px; max-width: 100%;" onchange="if(this.value) window.open(this.value, '_blank')">
                    <option value="">-- Выберите участника --</option>
                    <?php
                    while ($members_query->have_posts()) {
                        $members_query->the_post();
                        $dashboard_url = add_query_arg('member_id', get_the_ID(), home_url('/member-dashboard/'));
                        echo '<option value="' . esc_url($dashboard_url) . '">' . esc_html(get_the_title()) . '</option>';
                    }
                    wp_reset_postdata();
                    ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                <!-- Recent messages -->
                <div class="card">
                    <h2>💬 Последние сообщения (<?php echo $messages_query->found_posts; ?>)</h2>
                    <?php if ($messages_query->have_posts()): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>От кого → Кому</th>
                                    <th>Тема</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($messages_query->have_posts()): $messages_query->the_post();
                                    $sender_id = get_post_meta(get_the_ID(), 'sender_member_id', true);
                                    $recipient_id = get_post_meta(get_the_ID(), 'recipient_member_id', true);

                                    // Determine sender name
                                    if ($sender_id) {
                                        $sender_name = get_the_title($sender_id);
                                    } else {
                                        // Check if admin
                                        $post_author_id = get_post_field('post_author', get_the_ID());
                                        if ($post_author_id && user_can($post_author_id, 'administrator')) {
                                            $sender_name = '👑 Администратор';
                                        } else {
                                            $sender_name = get_post_meta(get_the_ID(), 'sender_name', true) ?: 'Неизвестно';
                                        }
                                    }

                                    $recipient_name = $recipient_id ? get_the_title($recipient_id) : 'Неизвестно';
                                ?>
                                    <tr>
                                        <td><?php echo get_the_date('d.m.Y H:i'); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($sender_name); ?></strong>
                                            →
                                            <strong><?php echo esc_html($recipient_name); ?></strong>
                                        </td>
                                        <td><?php the_title(); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Сообщений пока нет</p>
                    <?php endif; wp_reset_postdata(); ?>
                </div>

                <!-- Recent forum posts -->
                <div class="card">
                    <h2>📝 Последние посты на форуме (<?php echo $forum_query->found_posts; ?>)</h2>
                    <?php if ($forum_query->have_posts()): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Автор</th>
                                    <th>Тема</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($forum_query->have_posts()): $forum_query->the_post();
                                    $author_member_id = get_post_meta(get_the_ID(), 'author_member_id', true);
                                    $author_name = $author_member_id ? get_the_title($author_member_id) : get_the_author();
                                ?>
                                    <tr>
                                        <td><?php echo get_the_date('d.m.Y H:i'); ?></td>
                                        <td><strong><?php echo esc_html($author_name); ?></strong></td>
                                        <td>
                                            <a href="<?php the_permalink(); ?>" target="_blank">
                                                <?php the_title(); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Постов на форуме пока нет</p>
                    <?php endif; wp_reset_postdata(); ?>
                </div>
            </div>

            <?php $this->render_styles(); ?>
        </div>
        <?php
    }

    /**
     * Render inline styles
     */
    private function render_styles() {
        ?>
        <style>
            .card {
                background: white;
                padding: 20px;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .card h2 {
                margin-top: 0;
                font-size: 18px;
                font-weight: 600;
            }
            .card table {
                margin-top: 15px;
            }
            .card table th {
                font-weight: 600;
                background: #f6f7f7;
            }
            .card table td {
                vertical-align: middle;
            }
            #member-select {
                padding: 8px;
                font-size: 14px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
            }
        </style>
        <?php
    }
}
