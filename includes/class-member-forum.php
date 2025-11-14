<?php
/**
 * Member Forum Class
 *
 * Minimalist forum system for member discussions
 * Clean design, Reddit-style functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_Forum {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'create_forum_pages'));

        // Shortcodes
        add_shortcode('member_forum', array($this, 'render_forum'));
        add_shortcode('forum_topic', array($this, 'render_topic'));

        // AJAX handlers
        add_action('wp_ajax_forum_create_topic', array($this, 'ajax_create_topic'));
        add_action('wp_ajax_forum_reply_topic', array($this, 'ajax_reply_topic'));
        add_action('wp_ajax_forum_like_topic', array($this, 'ajax_like_topic'));
        add_action('wp_ajax_forum_like_reply', array($this, 'ajax_like_reply'));
        add_action('wp_ajax_forum_subscribe_topic', array($this, 'ajax_subscribe_topic'));
        add_action('wp_ajax_forum_pin_topic', array($this, 'ajax_pin_topic'));

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Notification hooks
        add_action('forum_new_reply', array($this, 'notify_subscribers'), 10, 2);
    }

    /**
     * Register forum post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'Обсуждения',
            'singular_name' => 'Обсуждение',
            'add_new' => 'Добавить тему',
            'add_new_item' => 'Добавить новую тему',
            'edit_item' => 'Редактировать тему',
            'new_item' => 'Новая тема',
            'view_item' => 'Просмотр темы',
            'search_items' => 'Поиск тем',
            'not_found' => 'Темы не найдены',
            'menu_name' => 'Форум'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'forum'),
            'supports' => array('title', 'editor', 'author', 'comments'),
            'menu_icon' => 'dashicons-format-chat',
            'show_in_menu' => 'edit.php?post_type=members',
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'edit_posts',
            ),
            'map_meta_cap' => true,
        );

        register_post_type('forum_topic', $args);
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Forum categories
        register_taxonomy('forum_category', 'forum_topic', array(
            'label' => 'Категории форума',
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'forum/category'),
        ));

        // Forum tags
        register_taxonomy('forum_tag', 'forum_topic', array(
            'label' => 'Теги форума',
            'hierarchical' => false,
            'show_admin_column' => true,
        ));
    }

    /**
     * Create forum pages
     */
    public function create_forum_pages() {
        // Check if already created
        if (get_option('metoda_forum_pages_created')) {
            return;
        }

        // Forum main page
        if (!get_page_by_path('forum')) {
            wp_insert_post(array(
                'post_title' => 'Форум',
                'post_name' => 'forum',
                'post_content' => '[member_forum]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
        }

        update_option('metoda_forum_pages_created', '1');
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        if (is_page('forum') || is_singular('forum_topic')) {
            wp_enqueue_style('member-forum', plugin_dir_url(dirname(__FILE__)) . 'assets/css/member-forum.css', array(), '1.0.0');
            wp_enqueue_script('member-forum', plugin_dir_url(dirname(__FILE__)) . 'assets/js/member-forum.js', array('jquery'), '1.0.0', true);

            wp_localize_script('member-forum', 'forumData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('forum_nonce'),
                'userId' => get_current_user_id(),
                'isLoggedIn' => is_user_logged_in(),
            ));
        }
    }

    /**
     * Render forum listing
     */
    public function render_forum() {
        if (!is_user_logged_in()) {
            return '<div class="forum-notice">Для доступа к форуму необходимо войти в систему.</div>';
        }

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'templates/forum-listing.php';
        return ob_get_clean();
    }

    /**
     * Render single topic
     */
    public function render_topic() {
        if (!is_user_logged_in()) {
            return '<div class="forum-notice">Для доступа к форуму необходимо войти в систему.</div>';
        }

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'templates/forum-topic.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Create new topic
     */
    public function ajax_create_topic() {
        check_ajax_referer('forum_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;

        if (empty($title) || empty($content)) {
            wp_send_json_error(array('message' => 'Заполните все поля'));
        }

        $topic_id = wp_insert_post(array(
            'post_type' => 'forum_topic',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ));

        if (is_wp_error($topic_id)) {
            wp_send_json_error(array('message' => 'Ошибка создания темы'));
        }

        // Set category
        if ($category) {
            wp_set_object_terms($topic_id, array($category), 'forum_category');
        }

        // Initialize meta
        update_post_meta($topic_id, 'forum_likes', 0);
        update_post_meta($topic_id, 'forum_views', 0);
        update_post_meta($topic_id, 'forum_pinned', 0);

        wp_send_json_success(array(
            'message' => 'Тема создана!',
            'topic_id' => $topic_id,
            'topic_url' => get_permalink($topic_id)
        ));
    }

    /**
     * AJAX: Reply to topic
     */
    public function ajax_reply_topic() {
        check_ajax_referer('forum_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $topic_id = intval($_POST['topic_id']);
        $content = wp_kses_post($_POST['content']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

        if (empty($content)) {
            wp_send_json_error(array('message' => 'Введите текст ответа'));
        }

        $comment_id = wp_insert_comment(array(
            'comment_post_ID' => $topic_id,
            'comment_content' => $content,
            'comment_author' => wp_get_current_user()->display_name,
            'comment_author_email' => wp_get_current_user()->user_email,
            'user_id' => get_current_user_id(),
            'comment_parent' => $parent_id,
            'comment_approved' => 1,
        ));

        if (!$comment_id) {
            wp_send_json_error(array('message' => 'Ошибка добавления ответа'));
        }

        // Initialize comment meta
        add_comment_meta($comment_id, 'forum_likes', 0);

        // Trigger notification
        do_action('forum_new_reply', $topic_id, $comment_id);

        // Get comment data
        $comment = get_comment($comment_id);
        $author_id = $comment->user_id;
        $member_id = get_user_meta($author_id, 'member_id', true);
        $avatar_url = get_the_post_thumbnail_url($member_id, 'thumbnail');

        wp_send_json_success(array(
            'message' => 'Ответ добавлен',
            'comment_id' => $comment_id,
            'comment_html' => $this->render_comment_html($comment, $avatar_url)
        ));
    }

    /**
     * AJAX: Like topic
     */
    public function ajax_like_topic() {
        check_ajax_referer('forum_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $topic_id = intval($_POST['topic_id']);
        $user_id = get_current_user_id();

        // Check if already liked
        $liked_topics = get_user_meta($user_id, 'forum_liked_topics', true);
        $liked_topics = $liked_topics ? explode(',', $liked_topics) : array();

        if (in_array($topic_id, $liked_topics)) {
            // Unlike
            $liked_topics = array_diff($liked_topics, array($topic_id));
            $likes = intval(get_post_meta($topic_id, 'forum_likes', true));
            $likes = max(0, $likes - 1);
            $is_liked = false;
        } else {
            // Like
            $liked_topics[] = $topic_id;
            $likes = intval(get_post_meta($topic_id, 'forum_likes', true)) + 1;
            $is_liked = true;
        }

        update_user_meta($user_id, 'forum_liked_topics', implode(',', $liked_topics));
        update_post_meta($topic_id, 'forum_likes', $likes);

        wp_send_json_success(array(
            'likes' => $likes,
            'is_liked' => $is_liked
        ));
    }

    /**
     * AJAX: Like comment
     */
    public function ajax_like_reply() {
        check_ajax_referer('forum_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $comment_id = intval($_POST['comment_id']);
        $user_id = get_current_user_id();

        // Check if already liked
        $liked_comments = get_user_meta($user_id, 'forum_liked_comments', true);
        $liked_comments = $liked_comments ? explode(',', $liked_comments) : array();

        if (in_array($comment_id, $liked_comments)) {
            // Unlike
            $liked_comments = array_diff($liked_comments, array($comment_id));
            $likes = intval(get_comment_meta($comment_id, 'forum_likes', true));
            $likes = max(0, $likes - 1);
            $is_liked = false;
        } else {
            // Like
            $liked_comments[] = $comment_id;
            $likes = intval(get_comment_meta($comment_id, 'forum_likes', true)) + 1;
            $is_liked = true;
        }

        update_user_meta($user_id, 'forum_liked_comments', implode(',', $liked_comments));
        update_comment_meta($comment_id, 'forum_likes', $likes);

        wp_send_json_success(array(
            'likes' => $likes,
            'is_liked' => $is_liked
        ));
    }

    /**
     * AJAX: Subscribe to topic
     */
    public function ajax_subscribe_topic() {
        check_ajax_referer('forum_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо авторизоваться'));
        }

        $topic_id = intval($_POST['topic_id']);
        $user_id = get_current_user_id();

        // Get subscribers
        $subscribers = get_post_meta($topic_id, 'forum_subscribers', true);
        $subscribers = $subscribers ? explode(',', $subscribers) : array();

        if (in_array($user_id, $subscribers)) {
            // Unsubscribe
            $subscribers = array_diff($subscribers, array($user_id));
            $is_subscribed = false;
            $message = 'Подписка отменена';
        } else {
            // Subscribe
            $subscribers[] = $user_id;
            $is_subscribed = true;
            $message = 'Вы подписались на тему';
        }

        update_post_meta($topic_id, 'forum_subscribers', implode(',', $subscribers));

        wp_send_json_success(array(
            'is_subscribed' => $is_subscribed,
            'message' => $message
        ));
    }

    /**
     * AJAX: Pin/unpin topic (admin only)
     */
    public function ajax_pin_topic() {
        check_ajax_referer('forum_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нет прав'));
        }

        $topic_id = intval($_POST['topic_id']);
        $pinned = get_post_meta($topic_id, 'forum_pinned', true);

        $new_pinned = $pinned ? 0 : 1;
        update_post_meta($topic_id, 'forum_pinned', $new_pinned);

        wp_send_json_success(array(
            'is_pinned' => $new_pinned,
            'message' => $new_pinned ? 'Тема закреплена' : 'Тема откреплена'
        ));
    }

    /**
     * Notify subscribers about new reply
     */
    public function notify_subscribers($topic_id, $comment_id) {
        $subscribers = get_post_meta($topic_id, 'forum_subscribers', true);
        if (!$subscribers) {
            return;
        }

        $subscribers = explode(',', $subscribers);
        $comment = get_comment($comment_id);
        $topic = get_post($topic_id);

        foreach ($subscribers as $user_id) {
            // Don't notify the author of the comment
            if ($user_id == $comment->user_id) {
                continue;
            }

            $user = get_user_by('id', $user_id);
            if (!$user) {
                continue;
            }

            // Send email notification
            $subject = 'Новый ответ в теме: ' . $topic->post_title;
            $message = sprintf(
                'В теме "%s", на которую вы подписаны, появился новый ответ от %s.<br><br>%s<br><br><a href="%s">Перейти к обсуждению</a>',
                $topic->post_title,
                $comment->comment_author,
                wp_trim_words($comment->comment_content, 50),
                get_permalink($topic_id) . '#comment-' . $comment_id
            );

            Member_Email_Templates::send_email('forum_new_reply', $user->user_email, array(
                '{topic_title}' => $topic->post_title,
                '{author_name}' => $comment->comment_author,
                '{reply_excerpt}' => wp_trim_words($comment->comment_content, 50),
                '{topic_url}' => get_permalink($topic_id) . '#comment-' . $comment_id,
                '{site_name}' => get_bloginfo('name')
            ));
        }
    }

    /**
     * Render comment HTML
     */
    private function render_comment_html($comment, $avatar_url) {
        ob_start();
        ?>
        <div class="forum-reply" id="comment-<?php echo $comment->comment_ID; ?>">
            <div class="reply-avatar">
                <?php if ($avatar_url): ?>
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo substr($comment->comment_author, 0, 1); ?></div>
                <?php endif; ?>
            </div>
            <div class="reply-content">
                <div class="reply-header">
                    <span class="reply-author"><?php echo esc_html($comment->comment_author); ?></span>
                    <span class="reply-date"><?php echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' назад'; ?></span>
                </div>
                <div class="reply-text"><?php echo wpautop($comment->comment_content); ?></div>
                <div class="reply-actions">
                    <button class="reply-action like-reply" data-comment-id="<?php echo $comment->comment_ID; ?>">
                        <i class="fas fa-heart"></i> <span class="like-count">0</span>
                    </button>
                    <button class="reply-action reply-to-comment" data-comment-id="<?php echo $comment->comment_ID; ?>">
                        <i class="fas fa-reply"></i> Ответить
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get topic statistics
     */
    public static function get_topic_stats($topic_id) {
        return array(
            'views' => intval(get_post_meta($topic_id, 'forum_views', true)),
            'likes' => intval(get_post_meta($topic_id, 'forum_likes', true)),
            'replies' => wp_count_comments($topic_id)->approved,
            'is_pinned' => intval(get_post_meta($topic_id, 'forum_pinned', true)),
        );
    }

    /**
     * Increment topic views
     */
    public static function increment_views($topic_id) {
        $views = intval(get_post_meta($topic_id, 'forum_views', true));
        update_post_meta($topic_id, 'forum_views', $views + 1);
    }
}

// Initialize the class
new Member_Forum();
