<?php
/**
 * Email Notification System
 *
 * Handles sending email notifications with full content and reply capability
 *
 * @package Metoda_Members
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Notification_Email
 *
 * Sends email notifications to members with full message content
 */
class Metoda_Notification_Email {

    /**
     * Send notification email
     *
     * @param int $user_id User ID to send notification to
     * @param array $data Notification data
     * @return bool Success status
     */
    public function send($user_id, $data) {
        // Проверяем, включены ли email уведомления
        $channel_enabled = get_user_meta($user_id, 'notify_channel_email', true);
        if ($channel_enabled !== '1') {
            return false;
        }

        // Проверяем тип уведомления
        $type = $data['type'] ?? 'message';
        $notify_type_enabled = get_user_meta($user_id, 'notify_' . $type . 's', true);

        if ($notify_type_enabled !== '1') {
            return false;
        }

        // Проверяем тихие часы
        if ($this->is_quiet_hours($user_id)) {
            $this->queue_for_later($user_id, $data);
            return false;
        }

        // Получаем email адрес
        $user = get_userdata($user_id);
        $custom_email = get_user_meta($user_id, 'notify_custom_email', true);
        $to = !empty($custom_email) ? $custom_email : $user->user_email;

        // Генерируем reply token
        $reply_token = $this->generate_reply_token($user_id, $data);

        // Формируем email
        $subject = $this->get_subject($data);
        $message = $this->get_message_body($data, $reply_token);
        $headers = $this->get_headers($reply_token);

        // Отправляем
        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Check if user is in quiet hours
     *
     * @param int $user_id User ID
     * @return bool
     */
    private function is_quiet_hours($user_id) {
        $quiet_enabled = get_user_meta($user_id, 'quiet_hours_enabled', true);

        if ($quiet_enabled !== '1') {
            return false;
        }

        $start = get_user_meta($user_id, 'quiet_hours_start', true) ?: '22:00';
        $end = get_user_meta($user_id, 'quiet_hours_end', true) ?: '08:00';

        $current_time = current_time('H:i');

        // Если период переходит через полночь (например 22:00 - 08:00)
        if ($start > $end) {
            return $current_time >= $start || $current_time <= $end;
        }

        // Обычный период (например 12:00 - 14:00)
        return $current_time >= $start && $current_time <= $end;
    }

    /**
     * Queue notification for later delivery
     *
     * @param int $user_id User ID
     * @param array $data Notification data
     * @return void
     */
    private function queue_for_later($user_id, $data) {
        $queue = get_user_meta($user_id, 'notification_queue', true) ?: array();
        $queue[] = array(
            'data' => $data,
            'time' => time(),
            'type' => 'email'
        );
        update_user_meta($user_id, 'notification_queue', $queue);
    }

    /**
     * Generate reply token for email responses
     *
     * @param int $user_id User ID
     * @param array $data Notification data
     * @return string
     */
    private function generate_reply_token($user_id, $data) {
        // Генерируем уникальный токен
        $token = wp_generate_password(32, false);

        // Сохраняем токен в transient на 30 дней
        $token_data = array(
            'user_id' => $user_id,
            'type' => $data['type'] ?? 'message',
            'reference_id' => $data['reference_id'] ?? 0,
            'sender_id' => $data['sender_id'] ?? 0,
            'created' => time()
        );

        set_transient('email_reply_' . $token, $token_data, 30 * DAY_IN_SECONDS);

        return $token;
    }

    /**
     * Get email subject based on notification type
     *
     * @param array $data Notification data
     * @return string
     */
    private function get_subject($data) {
        $type = $data['type'] ?? 'message';

        switch ($type) {
            case 'message':
                $sender_name = $data['sender_name'] ?? 'Участник';
                return "💬 Новое сообщение от {$sender_name} - Metoda Members";

            case 'forum':
                return "💭 Новый ответ в форуме - Metoda Members";

            case 'project':
                return "📁 Обновление проекта - Metoda Members";

            case 'learning':
                return "📚 Уведомление с платформы обучения - Metoda Members";

            default:
                return "🔔 Уведомление - Metoda Members";
        }
    }

    /**
     * Get email body with full content
     *
     * @param array $data Notification data
     * @param string $reply_token Reply token
     * @return string
     */
    private function get_message_body($data, $reply_token) {
        $type = $data['type'] ?? 'message';
        $message = '';

        // Заголовок
        $message .= "Здравствуйте!\n\n";

        // Основной контент в зависимости от типа
        switch ($type) {
            case 'message':
                $message .= $this->format_message_notification($data);
                break;

            case 'forum':
                $message .= $this->format_forum_notification($data);
                break;

            default:
                $message .= $data['content'] ?? 'Новое уведомление';
        }

        $message .= "\n\n";

        // Инструкция для ответа
        if (!empty($data['allow_reply'])) {
            $reply_email = $this->get_reply_email($reply_token);
            $message .= "──────────────────────────────────────\n";
            $message .= "💬 ОТВЕТИТЬ НА ЭТО СООБЩЕНИЕ\n\n";
            $message .= "Вы можете ответить прямо из этого письма!\n";
            $message .= "Просто нажмите 'Ответить' и напишите ваше сообщение.\n";
            $message .= "Или отправьте на: {$reply_email}\n";
            $message .= "──────────────────────────────────────\n\n";
        }

        // Ссылка на платформу
        if (!empty($data['link'])) {
            $message .= "🔗 Перейти на платформу: {$data['link']}\n\n";
        }

        // Футер
        $message .= "──────────────────────────────────────\n";
        $message .= "С уважением,\n";
        $message .= "Команда Metoda Members\n\n";
        $message .= "Настроить уведомления: " . home_url('/member-dashboard#notifications') . "\n";

        return $message;
    }

    /**
     * Format message notification content
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_message_notification($data) {
        $sender_name = $data['sender_name'] ?? 'Участник';
        $content = $data['content'] ?? '';
        $subject = $data['subject'] ?? '';

        $message = "Вам пришло новое личное сообщение от {$sender_name}.\n\n";

        if (!empty($subject)) {
            $message .= "Тема: {$subject}\n\n";
        }

        $message .= "Сообщение:\n";
        $message .= "──────────────────────────────────────\n";
        $message .= $content . "\n";
        $message .= "──────────────────────────────────────\n";

        return $message;
    }

    /**
     * Format forum notification content
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_forum_notification($data) {
        $author_name = $data['author_name'] ?? 'Участник';
        $topic_title = $data['topic_title'] ?? 'Тема форума';
        $content = $data['content'] ?? '';

        $message = "{$author_name} ответил в теме '{$topic_title}':\n\n";
        $message .= "──────────────────────────────────────\n";
        $message .= $content . "\n";
        $message .= "──────────────────────────────────────\n";

        return $message;
    }

    /**
     * Get email headers including reply-to
     *
     * @param string $reply_token Reply token
     * @return array
     */
    private function get_headers($reply_token) {
        $headers = array();
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'From: Metoda Members <' . get_option('admin_email') . '>';

        // Reply-to адрес с токеном
        $reply_email = $this->get_reply_email($reply_token);
        $headers[] = 'Reply-To: ' . $reply_email;

        return $headers;
    }

    /**
     * Get reply email address with token
     *
     * @param string $reply_token Reply token
     * @return string
     */
    private function get_reply_email($reply_token) {
        // Формат: reply+TOKEN@domain.com
        $admin_email = get_option('admin_email');
        $parts = explode('@', $admin_email);

        if (count($parts) === 2) {
            return "reply+{$reply_token}@{$parts[1]}";
        }

        return $admin_email;
    }

    /**
     * Process incoming email reply
     *
     * @param string $token Reply token
     * @param string $content Reply content
     * @return bool|WP_Error
     */
    public function process_reply($token, $content) {
        // Получаем данные токена
        $token_data = get_transient('email_reply_' . $token);

        if (!$token_data) {
            return new WP_Error('invalid_token', 'Неверный или истекший токен ответа');
        }

        $user_id = $token_data['user_id'];
        $type = $token_data['type'];
        $reference_id = $token_data['reference_id'];
        $sender_id = $token_data['sender_id'];

        // Обрабатываем в зависимости от типа
        switch ($type) {
            case 'message':
                return $this->process_message_reply($user_id, $sender_id, $content);

            case 'forum':
                return $this->process_forum_reply($user_id, $reference_id, $content);

            default:
                return new WP_Error('unsupported_type', 'Неподдерживаемый тип ответа');
        }
    }

    /**
     * Process message reply
     *
     * @param int $user_id User ID sending reply
     * @param int $recipient_id Recipient user ID
     * @param string $content Reply content
     * @return bool|WP_Error
     */
    private function process_message_reply($user_id, $recipient_id, $content) {
        // Создаем новое сообщение
        $message_id = wp_insert_post(array(
            'post_type' => 'member_message',
            'post_title' => 'Re: ' . date('Y-m-d H:i:s'),
            'post_content' => sanitize_textarea_field($content),
            'post_status' => 'publish',
            'post_author' => $user_id
        ));

        if (is_wp_error($message_id)) {
            return $message_id;
        }

        // Устанавливаем мета-данные
        update_post_meta($message_id, 'sender_id', $user_id);
        update_post_meta($message_id, 'recipient_id', $recipient_id);
        update_post_meta($message_id, 'read_status', '0');
        update_post_meta($message_id, 'sent_via', 'email');

        return true;
    }

    /**
     * Process forum reply
     *
     * @param int $user_id User ID sending reply
     * @param int $topic_id Topic ID
     * @param string $content Reply content
     * @return bool|WP_Error
     */
    private function process_forum_reply($user_id, $topic_id, $content) {
        // Создаем ответ в форуме
        $reply_id = wp_insert_post(array(
            'post_type' => 'forum_reply',
            'post_title' => 'Reply to topic #' . $topic_id,
            'post_content' => sanitize_textarea_field($content),
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_parent' => $topic_id
        ));

        if (is_wp_error($reply_id)) {
            return $reply_id;
        }

        update_post_meta($reply_id, 'sent_via', 'email');

        return true;
    }
}
