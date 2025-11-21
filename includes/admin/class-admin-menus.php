<?php
/**
 * Admin Menus
 *
 * Custom admin menu items (forum redirect, admin bar items)
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Admin_Menus {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_forum_menu'));
        add_action('admin_bar_menu', array($this, 'add_forum_to_admin_bar'), 100);
    }

    /**
     * Add forum menu item to admin menu
     *
     * Creates a redirect link to the forum archive
     */
    public function add_forum_menu() {
        add_menu_page(
            'Форум сообщества',
            'Форум',
            'read',
            'metoda-forum-redirect',
            array($this, 'forum_redirect_handler'),
            'dashicons-format-chat',
            31
        );
    }

    /**
     * Forum redirect handler
     *
     * Redirects admin users to the forum archive page
     */
    public function forum_redirect_handler() {
        $forum_url = get_post_type_archive_link('forum_topic');

        if ($forum_url) {
            ?>
            <script type="text/javascript">
                window.location.href = '<?php echo esc_url($forum_url); ?>';
            </script>
            <div class="wrap">
                <h1>Перенаправление на форум...</h1>
                <p>Если вы не были перенаправлены, <a href="<?php echo esc_url($forum_url); ?>">нажмите здесь</a>.</p>
            </div>
            <?php
        } else {
            echo '<div class="wrap"><h1>Форум недоступен</h1><p>Страница форума не настроена.</p></div>';
        }
    }

    /**
     * Add forum link to admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     */
    public function add_forum_to_admin_bar($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }

        $forum_url = get_post_type_archive_link('forum_topic');
        if ($forum_url) {
            $wp_admin_bar->add_node(array(
                'id' => 'metoda-forum',
                'title' => '<span class="ab-icon dashicons dashicons-format-chat"></span> Форум',
                'href' => $forum_url,
                'meta' => array(
                    'target' => '_blank'
                )
            ));
        }
    }
}
