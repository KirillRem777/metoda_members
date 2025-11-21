<?php
/**
 * AJAX Messages
 *
 * Handles AJAX requests for member messaging system
 * Includes anti-spam protection with rate limiting and cooldowns
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Ajax_Messages {

    /**
     * Message limits
     */
    const LOGGED_IN_DAILY_LIMIT = 10;
    const GUEST_DAILY_LIMIT = 5;
    const LOGGED_IN_COOLDOWN = 120; // 2 minutes
    const GUEST_COOLDOWN = 300; // 5 minutes

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_send_member_message', array($this, 'send_message'));
        add_action('wp_ajax_nopriv_send_member_message', array($this, 'send_message'));
        add_action('wp_ajax_view_member_message', array($this, 'view_message'));
    }

    /**
     * Send member message
     *
     * Supports both logged-in and guest users with anti-spam protection
     */
    public function send_message() {
        check_ajax_referer('send_member_message', 'nonce');

        // Honeypot check (anti-spam)
        if (!empty($_POST['website'])) {
            wp_send_json_error(array('message' => 'Обнаружена подозрительная активность'));
        }

        $is_logged_in = is_user_logged_in();

        // Get sender data
        $sender_data = $this->get_sender_data($is_logged_in);
        if (is_wp_error($sender_data)) {
            wp_send_json_error(array('message' => $sender_data->get_error_message()));
        }

        // Get and validate message data
        $recipient_member_id = intval($_POST['recipient_id']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);

        if (empty($subject) || empty($content)) {
            wp_send_json_error(array('message' => 'Заполните все обязательные поля'));
        }

        if (empty($recipient_member_id)) {
            wp_send_json_error(array('message' => 'Получатель не указан'));
        }

        // Check: can't send message to yourself
        if ($is_logged_in && $sender_data['member_id'] == $recipient_member_id) {
            wp_send_json_error(array('message' => 'Нельзя отправить сообщение самому себе'));
        }

        // Anti-spam check
        $spam_check = $this->check_spam($is_logged_in, $sender_data);
        if (is_wp_error($spam_check)) {
            wp_send_json_error(array('message' => $spam_check->get_error_message()));
        }

        // Create message
        $message_id = $this->create_message($sender_data, $recipient_member_id, $subject, $content, $is_logged_in);
        if (is_wp_error($message_id)) {
            wp_send_json_error(array('message' => $message_id->get_error_message()));
        }

        // Update last sent time
        if ($is_logged_in) {
            update_user_meta($sender_data['user_id'], 'last_message_sent_time', time());
        }

        // Send email notification
        $this->send_email_notification($sender_data, $recipient_member_id, $subject, $is_logged_in);

        wp_send_json_success(array(
            'message' => 'Сообщение успешно отправлено!',
            'message_id' => $message_id
        ));
    }

    /**
     * View member message
     *
     * Marks message as read and returns content
     */
    public function view_message() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо войти в систему'));
        }

        $message_id = intval($_POST['message_id']);
        $message = get_post($message_id);

        if (!$message || $message->post_type !== 'member_message') {
            wp_send_json_error(array('message' => 'Сообщение не найдено'));
        }

        $current_member_id = Member_User_Link::get_current_user_member_id();
        $recipient_id = get_post_meta($message_id, 'recipient_member_id', true);
        $sender_id = get_post_meta($message_id, 'sender_member_id', true);

        // Access check: only sender or recipient can view
        if ($current_member_id != $recipient_id && $current_member_id != $sender_id) {
            wp_send_json_error(array('message' => 'Доступ запрещен'));
        }

        // Mark as read (if recipient)
        if ($current_member_id == $recipient_id) {
            update_post_meta($message_id, 'is_read', 1);
            update_post_meta($message_id, 'read_at', current_time('mysql'));
        }

        // Build meta info
        $meta = $this->build_message_meta($message_id, $current_member_id, $recipient_id, $sender_id);

        wp_send_json_success(array(
            'title' => $message->post_title,
            'content' => $message->post_content,
            'meta' => $meta
        ));
    }

    /**
     * Get sender data
     *
     * @param bool $is_logged_in Whether user is logged in
     * @return array|WP_Error Sender data or error
     */
    private function get_sender_data($is_logged_in) {
        if ($is_logged_in) {
            $sender_user_id = get_current_user_id();
            $sender_member_id = Member_User_Link::get_current_user_member_id();
            $sender_name = get_the_title($sender_member_id);
            $sender_email = wp_get_current_user()->user_email;

            return array(
                'user_id' => $sender_user_id,
                'member_id' => $sender_member_id,
                'name' => $sender_name,
                'email' => $sender_email,
                'ip' => $_SERVER['REMOTE_ADDR']
            );
        } else {
            // Guest user
            $sender_name = sanitize_text_field($_POST['sender_name']);
            $sender_email = sanitize_email($_POST['sender_email']);

            if (empty($sender_name) || empty($sender_email)) {
                return new WP_Error('missing_data', 'Укажите ваше имя и email');
            }

            if (!is_email($sender_email)) {
                return new WP_Error('invalid_email', 'Укажите корректный email');
            }

            return array(
                'user_id' => 0,
                'member_id' => 0,
                'name' => $sender_name,
                'email' => $sender_email,
                'ip' => $_SERVER['REMOTE_ADDR']
            );
        }
    }

    /**
     * Check for spam
     *
     * @param bool $is_logged_in Whether user is logged in
     * @param array $sender_data Sender data
     * @return true|WP_Error True if OK, error if spam detected
     */
    private function check_spam($is_logged_in, $sender_data) {
        if ($is_logged_in) {
            return $this->check_spam_logged_in($sender_data['user_id']);
        } else {
            return $this->check_spam_guest($sender_data['ip']);
        }
    }

    /**
     * Check spam for logged-in users
     *
     * @param int $user_id User ID
     * @return true|WP_Error
     */
    private function check_spam_logged_in($user_id) {
        // Rate limiting: 10 messages per day
        $today_start = strtotime('today');
        $messages_today = get_posts(array(
            'post_type' => 'member_message',
            'author' => $user_id,
            'date_query' => array(
                array(
                    'after' => date('Y-m-d 00:00:00', $today_start),
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        if (count($messages_today) >= self::LOGGED_IN_DAILY_LIMIT) {
            return new WP_Error('rate_limit', 'Вы достигли лимита сообщений на сегодня (10 в день)');
        }

        // Cooldown: 2 minutes between messages
        $last_message_time = get_user_meta($user_id, 'last_message_sent_time', true);
        if ($last_message_time) {
            $time_diff = time() - intval($last_message_time);
            if ($time_diff < self::LOGGED_IN_COOLDOWN) {
                $wait_time = self::LOGGED_IN_COOLDOWN - $time_diff;
                return new WP_Error('cooldown', 'Пожалуйста, подождите ' . $wait_time . ' секунд перед отправкой следующего сообщения');
            }
        }

        return true;
    }

    /**
     * Check spam for guest users
     *
     * @param string $ip IP address
     * @return true|WP_Error
     */
    private function check_spam_guest($ip) {
        // Rate limiting by IP: 5 messages per day
        $messages_from_ip = get_posts(array(
            'post_type' => 'member_message',
            'meta_query' => array(
                array(
                    'key' => 'sender_ip',
                    'value' => $ip,
                ),
            ),
            'date_query' => array(
                array(
                    'after' => date('Y-m-d 00:00:00', strtotime('today')),
                ),
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        if (count($messages_from_ip) >= self::GUEST_DAILY_LIMIT) {
            return new WP_Error('rate_limit', 'Превышен лимит сообщений на сегодня');
        }

        // Cooldown by IP: 5 minutes between messages
        $last_message_from_ip = get_posts(array(
            'post_type' => 'member_message',
            'meta_query' => array(
                array(
                    'key' => 'sender_ip',
                    'value' => $ip,
                ),
            ),
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (!empty($last_message_from_ip)) {
            $last_time = strtotime($last_message_from_ip[0]->post_date);
            $time_diff = time() - $last_time;
            if ($time_diff < self::GUEST_COOLDOWN) {
                $wait_time = ceil((self::GUEST_COOLDOWN - $time_diff) / 60);
                return new WP_Error('cooldown', 'Пожалуйста, подождите ' . $wait_time . ' мин. перед отправкой следующего сообщения');
            }
        }

        return true;
    }

    /**
     * Create message post
     *
     * @param array $sender_data Sender data
     * @param int $recipient_id Recipient member ID
     * @param string $subject Message subject
     * @param string $content Message content
     * @param bool $is_logged_in Whether sender is logged in
     * @return int|WP_Error Message ID or error
     */
    private function create_message($sender_data, $recipient_id, $subject, $content, $is_logged_in) {
        $message_data = array(
            'post_title' => $subject,
            'post_content' => $content,
            'post_type' => 'member_message',
            'post_status' => 'publish',
            'post_author' => $sender_data['user_id']
        );

        $message_id = wp_insert_post($message_data);

        if (is_wp_error($message_id)) {
            return new WP_Error('insert_failed', 'Ошибка отправки сообщения');
        }

        // Save meta data
        update_post_meta($message_id, 'recipient_member_id', $recipient_id);
        update_post_meta($message_id, 'sender_member_id', $sender_data['member_id']);
        update_post_meta($message_id, 'is_read', 0);
        update_post_meta($message_id, 'sent_at', current_time('mysql'));

        // For guest users - save additional data
        if (!$is_logged_in) {
            update_post_meta($message_id, 'sender_name', $sender_data['name']);
            update_post_meta($message_id, 'sender_email', $sender_data['email']);
            update_post_meta($message_id, 'sender_ip', $sender_data['ip']);
        }

        return $message_id;
    }

    /**
     * Send email notification to recipient
     *
     * @param array $sender_data Sender data
     * @param int $recipient_id Recipient member ID
     * @param string $subject Message subject
     * @param bool $is_logged_in Whether sender is logged in
     */
    private function send_email_notification($sender_data, $recipient_id, $subject, $is_logged_in) {
        $recipient_user = get_user_by('ID', get_post_field('post_author', $recipient_id));
        if (!$recipient_user) {
            return;
        }

        $recipient_name = get_the_title($recipient_id);
        $sender_name = $sender_data['name'];

        $email_subject = '[Метода] Новое сообщение от ' . $sender_name;
        $email_body = "Здравствуйте, {$recipient_name}!\n\n";
        $email_body .= "Вам пришло новое личное сообщение от {$sender_name}";

        if (!$is_logged_in) {
            $email_body .= " ({$sender_data['email']})";
        }

        $email_body .= ".\n\nТема: {$subject}\n\n";

        if ($is_logged_in) {
            $email_body .= "Чтобы прочитать сообщение и ответить, войдите в личный кабинет:\n";
            $email_body .= get_permalink(get_option('metoda_dashboard_page_id')) . "\n\n";
        } else {
            $email_body .= "Для ответа напишите на: {$sender_data['email']}\n\n";
            $email_body .= "Или прочитайте сообщение в личном кабинете:\n";
            $email_body .= get_permalink(get_option('metoda_dashboard_page_id')) . "\n\n";
        }

        $email_body .= "---\n";
        $email_body .= "Это сообщение отправлено через форму на сайте Метода.";

        wp_mail($recipient_user->user_email, $email_subject, $email_body);
    }

    /**
     * Build message meta info for display
     *
     * @param int $message_id Message ID
     * @param int $current_member_id Current member ID
     * @param int $recipient_id Recipient member ID
     * @param int $sender_id Sender member ID
     * @return string HTML meta info
     */
    private function build_message_meta($message_id, $current_member_id, $recipient_id, $sender_id) {
        $meta = '';

        if ($current_member_id == $recipient_id) {
            // Show sender
            if (empty($sender_id)) {
                // Message from guest user
                $sender_name = get_post_meta($message_id, 'sender_name', true);
                $sender_email = get_post_meta($message_id, 'sender_email', true);
                $meta .= '<strong>От:</strong> ' . esc_html($sender_name) . ' (' . esc_html($sender_email) . ')<br>';
            } else {
                $meta .= '<strong>От:</strong> ' . get_the_title($sender_id) . '<br>';
            }
        } else {
            $meta .= '<strong>Кому:</strong> ' . get_the_title($recipient_id) . '<br>';
        }

        $meta .= '<strong>Дата:</strong> ' . get_the_date('d.m.Y H:i', $message_id);

        return $meta;
    }
}
