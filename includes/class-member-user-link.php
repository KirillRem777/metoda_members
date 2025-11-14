<?php
/**
 * Member User Link Class
 *
 * Handles linking WordPress users to member profiles
 * Creates custom user role and manages permissions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_User_Link {

    /**
     * Initialize the class
     */
    public function __construct() {
        // Role is created during plugin activation in metoda_create_custom_roles()
        // add_action('init', array($this, 'create_member_role'));
        add_action('admin_init', array($this, 'add_member_link_metabox'));
        add_action('show_user_profile', array($this, 'show_linked_member_field'));
        add_action('edit_user_profile', array($this, 'show_linked_member_field'));
        add_action('personal_options_update', array($this, 'save_linked_member_field'));
        add_action('edit_user_profile_update', array($this, 'save_linked_member_field'));
        add_action('save_post_members', array($this, 'save_member_link_field'));
    }

    /**
     * Create custom member role
     */
    public function create_member_role() {
        if (!get_role('member')) {
            add_role(
                'member',
                'Участник',
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'upload_files' => true,
                )
            );
        }
    }

    /**
     * Add metabox to member edit screen for linking users
     */
    public function add_member_link_metabox() {
        add_meta_box(
            'member_user_link',
            'Привязка пользователя',
            array($this, 'render_member_link_metabox'),
            'members',
            'side',
            'high'
        );
    }

    /**
     * Render metabox for linking user to member
     */
    public function render_member_link_metabox($post) {
        wp_nonce_field('member_user_link_nonce', 'member_user_link_nonce_field');

        $linked_user_id = get_post_meta($post->ID, '_linked_user_id', true);

        // Get all users with 'member' role or users already linked
        $args = array(
            'role__in' => array('member', 'administrator'),
            'orderby' => 'display_name',
            'order' => 'ASC',
        );
        $users = get_users($args);

        echo '<label for="linked_user_id">Выберите пользователя:</label><br>';
        echo '<select name="linked_user_id" id="linked_user_id" style="width: 100%; margin-top: 10px;">';
        echo '<option value="">-- Не привязан --</option>';

        foreach ($users as $user) {
            $selected = ($linked_user_id == $user->ID) ? 'selected' : '';
            $existing_member = $this->get_member_by_user_id($user->ID);
            $disabled = '';
            $suffix = '';

            if ($existing_member && $existing_member != $post->ID) {
                $disabled = 'disabled';
                $suffix = ' (уже привязан к другому участнику)';
            }

            echo '<option value="' . esc_attr($user->ID) . '" ' . $selected . ' ' . $disabled . '>';
            echo esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')' . $suffix;
            echo '</option>';
        }

        echo '</select>';

        if ($linked_user_id) {
            $user = get_user_by('id', $linked_user_id);
            if ($user) {
                echo '<p style="margin-top: 15px;"><strong>Текущий пользователь:</strong><br>';
                echo esc_html($user->display_name) . '<br>';
                echo '<small>' . esc_html($user->user_email) . '</small></p>';
            }
        }

        echo '<p style="margin-top: 15px;"><small>Привязанный пользователь сможет редактировать этот профиль через личный кабинет.</small></p>';
    }

    /**
     * Get member ID by user ID
     */
    public function get_member_by_user_id($user_id) {
        $args = array(
            'post_type' => 'members',
            'meta_query' => array(
                array(
                    'key' => '_linked_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        );

        $members = get_posts($args);
        return !empty($members) ? $members[0] : null;
    }

    /**
     * Get member ID for current user
     */
    public static function get_current_user_member_id() {
        if (!is_user_logged_in()) {
            return null;
        }

        $user_id = get_current_user_id();

        $args = array(
            'post_type' => 'members',
            'meta_query' => array(
                array(
                    'key' => '_linked_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        );

        $members = get_posts($args);
        return !empty($members) ? $members[0] : null;
    }

    /**
     * Save linked user ID
     */
    public function save_member_link_field($post_id) {
        // Check nonce
        if (!isset($_POST['member_user_link_nonce_field']) ||
            !wp_verify_nonce($_POST['member_user_link_nonce_field'], 'member_user_link_nonce')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save or delete linked user
        if (isset($_POST['linked_user_id']) && !empty($_POST['linked_user_id'])) {
            $user_id = intval($_POST['linked_user_id']);

            // Check if user is already linked to another member
            $existing_member = $this->get_member_by_user_id($user_id);
            if ($existing_member && $existing_member != $post_id) {
                // Don't link if already linked to another member
                return;
            }

            update_post_meta($post_id, '_linked_user_id', $user_id);
        } else {
            delete_post_meta($post_id, '_linked_user_id');
        }
    }

    /**
     * Show linked member field in user profile
     */
    public function show_linked_member_field($user) {
        $member_id = $this->get_member_by_user_id($user->ID);

        echo '<h3>Профиль участника</h3>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label>Привязанный профиль участника</label></th>';
        echo '<td>';

        if ($member_id) {
            $member_title = get_the_title($member_id);
            $member_link = get_permalink($member_id);
            $edit_link = admin_url('post.php?post=' . $member_id . '&action=edit');

            echo '<p><strong>' . esc_html($member_title) . '</strong></p>';
            echo '<p>';
            echo '<a href="' . esc_url($member_link) . '" target="_blank" class="button">Просмотр профиля</a> ';
            echo '<a href="' . esc_url($edit_link) . '" class="button">Редактировать профиль (админка)</a>';
            echo '</p>';
        } else {
            echo '<p>Профиль участника не привязан к этому пользователю.</p>';
            echo '<p><small>Чтобы привязать профиль, перейдите в раздел "Участники" и выберите этого пользователя в метабоксе "Привязка пользователя".</small></p>';
        }

        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    /**
     * Check if current user can edit specific member
     */
    public static function can_user_edit_member($member_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $current_user_id = get_current_user_id();

        // Admins can edit all
        if (current_user_can('manage_options')) {
            return true;
        }

        // Check if user is linked to this member
        $linked_user_id = get_post_meta($member_id, '_linked_user_id', true);

        return ($linked_user_id == $current_user_id);
    }
}

// Initialize the class
new Member_User_Link();
