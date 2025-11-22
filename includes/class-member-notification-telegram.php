<?php
/**
 * Telegram Notification System
 *
 * Handles sending Telegram notifications with interactive buttons
 *
 * @package Metoda_Members
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Notification_Telegram
 *
 * Sends Telegram notifications to members with full message content and reply capability
 */
class Metoda_Notification_Telegram {

    /**
     * Telegram Bot API URL
     *
     * @var string
     */
    private $api_url;

    /**
     * Bot token
     *
     * @var string
     */
    private $bot_token;

    /**
     * Constructor
     */
    public function __construct() {
        $this->bot_token = get_option('metoda_telegram_bot_token');
        $this->api_url = "https://api.telegram.org/bot{$this->bot_token}/";
    }

    /**
     * Send notification via Telegram
     *
     * @param int $user_id User ID to send notification to
     * @param array $data Notification data
     * @return bool|WP_Error Success status
     */
    public function send($user_id, $data) {
        // Проверяем, включены ли Telegram уведомления
        $channel_enabled = get_user_meta($user_id, 'notify_channel_telegram', true);
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

        // Получаем chat_id
        $chat_id = get_user_meta($user_id, 'telegram_chat_id', true);

        if (empty($chat_id)) {
            return new WP_Error('no_chat_id', 'Telegram не подключен');
        }

        // Формируем сообщение
        $message = $this->format_message($data);
        $keyboard = $this->get_inline_keyboard($data, $user_id);

        // Отправляем
        return $this->send_message($chat_id, $message, $keyboard);
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

        if ($start > $end) {
            return $current_time >= $start || $current_time <= $end;
        }

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
            'type' => 'telegram'
        );
        update_user_meta($user_id, 'notification_queue', $queue);
    }

    /**
     * Format message for Telegram
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_message($data) {
        $type = $data['type'] ?? 'message';

        switch ($type) {
            case 'message':
                return $this->format_message_notification($data);

            case 'forum':
                return $this->format_forum_notification($data);

            case 'project':
                return $this->format_project_notification($data);

            case 'learning':
                return $this->format_learning_notification($data);

            default:
                return $data['content'] ?? 'Новое уведомление';
        }
    }

    /**
     * Format message notification for Telegram
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_message_notification($data) {
        $sender_name = $data['sender_name'] ?? 'Участник';
        $content = $data['content'] ?? '';
        $subject = $data['subject'] ?? '';

        $message = "💬 <b>Новое личное сообщение</b>\n\n";
        $message .= "От: <b>{$sender_name}</b>\n";

        if (!empty($subject)) {
            $message .= "Тема: {$subject}\n";
        }

        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= $this->escape_html($content) . "\n";
        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";

        return $message;
    }

    /**
     * Format forum notification for Telegram
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_forum_notification($data) {
        $author_name = $data['author_name'] ?? 'Участник';
        $topic_title = $data['topic_title'] ?? 'Тема форума';
        $content = $data['content'] ?? '';

        $message = "💭 <b>Новый ответ в форуме</b>\n\n";
        $message .= "Автор: <b>{$author_name}</b>\n";
        $message .= "Тема: {$topic_title}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= $this->escape_html($content) . "\n";
        $message .= "\n━━━━━━━━━━━━━━━━━━━━\n";

        return $message;
    }

    /**
     * Format project notification for Telegram
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_project_notification($data) {
        $project_name = $data['project_name'] ?? 'Проект';
        $content = $data['content'] ?? '';

        $message = "📁 <b>Обновление проекта</b>\n\n";
        $message .= "Проект: <b>{$project_name}</b>\n\n";
        $message .= $this->escape_html($content) . "\n";

        return $message;
    }

    /**
     * Format learning notification for Telegram
     *
     * @param array $data Notification data
     * @return string
     */
    private function format_learning_notification($data) {
        $course_name = $data['course_name'] ?? 'Курс';
        $content = $data['content'] ?? '';

        $message = "📚 <b>Уведомление с платформы обучения</b>\n\n";
        $message .= "Курс: <b>{$course_name}</b>\n\n";
        $message .= $this->escape_html($content) . "\n";

        return $message;
    }

    /**
     * Escape HTML for Telegram
     *
     * @param string $text Text to escape
     * @return string
     */
    private function escape_html($text) {
        // Telegram поддерживает только <b>, <i>, <code>, <pre>, <a>
        $text = str_replace('&', '&amp;', $text);
        $text = str_replace('<', '&lt;', $text);
        $text = str_replace('>', '&gt;', $text);
        return $text;
    }

    /**
     * Get inline keyboard for message
     *
     * @param array $data Notification data
     * @param int $user_id User ID
     * @return array|null
     */
    private function get_inline_keyboard($data, $user_id) {
        $type = $data['type'] ?? 'message';
        $keyboard = array('inline_keyboard' => array());

        // Генерируем callback_data для ответа
        $reply_callback = $this->generate_reply_callback($user_id, $data);

        switch ($type) {
            case 'message':
                $keyboard['inline_keyboard'][] = array(
                    array(
                        'text' => '✍️ Ответить',
                        'callback_data' => $reply_callback
                    )
                );

                if (!empty($data['link'])) {
                    $keyboard['inline_keyboard'][] = array(
                        array(
                            'text' => '🔗 Открыть на сайте',
                            'url' => $data['link']
                        )
                    );
                }
                break;

            case 'forum':
                $keyboard['inline_keyboard'][] = array(
                    array(
                        'text' => '✍️ Ответить в форуме',
                        'callback_data' => $reply_callback
                    )
                );

                if (!empty($data['link'])) {
                    $keyboard['inline_keyboard'][] = array(
                        array(
                            'text' => '🔗 Открыть тему',
                            'url' => $data['link']
                        )
                    );
                }
                break;

            default:
                if (!empty($data['link'])) {
                    $keyboard['inline_keyboard'][] = array(
                        array(
                            'text' => '🔗 Открыть',
                            'url' => $data['link']
                        )
                    );
                }
        }

        return !empty($keyboard['inline_keyboard']) ? $keyboard : null;
    }

    /**
     * Generate callback data for reply button
     *
     * @param int $user_id User ID
     * @param array $data Notification data
     * @return string
     */
    private function generate_reply_callback($user_id, $data) {
        $type = $data['type'] ?? 'message';
        $reference_id = $data['reference_id'] ?? 0;
        $sender_id = $data['sender_id'] ?? 0;

        // Формат: reply:type:user_id:reference_id:sender_id
        return "reply:{$type}:{$user_id}:{$reference_id}:{$sender_id}";
    }

    /**
     * Send message via Telegram API
     *
     * @param string $chat_id Chat ID
     * @param string $text Message text
     * @param array|null $keyboard Inline keyboard
     * @return bool|WP_Error
     */
    private function send_message($chat_id, $text, $keyboard = null) {
        $params = array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        );

        if ($keyboard) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        $response = wp_remote_post($this->api_url . 'sendMessage', array(
            'body' => $params,
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['ok']) || !$body['ok']) {
            return new WP_Error(
                'telegram_api_error',
                $body['description'] ?? 'Ошибка Telegram API'
            );
        }

        return true;
    }

    /**
     * Process callback query (button click)
     *
     * @param array $callback_query Callback query data from Telegram
     * @return bool|WP_Error
     */
    public function process_callback($callback_query) {
        $data = $callback_query['data'] ?? '';
        $chat_id = $callback_query['message']['chat']['id'] ?? '';

        if (empty($data) || empty($chat_id)) {
            return new WP_Error('invalid_callback', 'Некорректные данные callback');
        }

        // Парсим callback_data
        $parts = explode(':', $data);

        if ($parts[0] !== 'reply' || count($parts) < 5) {
            return new WP_Error('invalid_format', 'Некорректный формат callback_data');
        }

        list($action, $type, $user_id, $reference_id, $sender_id) = $parts;

        // Переводим бота в режим ожидания ответа
        $this->set_reply_mode($chat_id, array(
            'type' => $type,
            'user_id' => $user_id,
            'reference_id' => $reference_id,
            'sender_id' => $sender_id
        ));

        // Отправляем подтверждение
        $this->answer_callback_query($callback_query['id'], 'Напишите ваш ответ следующим сообщением');

        // Отправляем инструкцию
        $instruction = "✍️ <b>Режим ответа</b>\n\n";
        $instruction .= "Напишите ваше сообщение, и оно будет отправлено.\n";
        $instruction .= "Для отмены нажмите /cancel";

        $this->send_message($chat_id, $instruction);

        return true;
    }

    /**
     * Answer callback query
     *
     * @param string $callback_query_id Callback query ID
     * @param string $text Alert text
     * @return bool
     */
    private function answer_callback_query($callback_query_id, $text) {
        wp_remote_post($this->api_url . 'answerCallbackQuery', array(
            'body' => array(
                'callback_query_id' => $callback_query_id,
                'text' => $text
            )
        ));

        return true;
    }

    /**
     * Set reply mode for user
     *
     * @param string $chat_id Chat ID
     * @param array $data Reply context data
     * @return void
     */
    private function set_reply_mode($chat_id, $data) {
        set_transient('telegram_reply_mode_' . $chat_id, $data, HOUR_IN_SECONDS);
    }

    /**
     * Get reply mode data
     *
     * @param string $chat_id Chat ID
     * @return array|false
     */
    public function get_reply_mode($chat_id) {
        return get_transient('telegram_reply_mode_' . $chat_id);
    }

    /**
     * Clear reply mode
     *
     * @param string $chat_id Chat ID
     * @return void
     */
    public function clear_reply_mode($chat_id) {
        delete_transient('telegram_reply_mode_' . $chat_id);
    }

    /**
     * Process incoming message reply
     *
     * @param string $chat_id Chat ID
     * @param string $text Reply text
     * @return bool|WP_Error
     */
    public function process_reply($chat_id, $text) {
        // Получаем контекст ответа
        $reply_data = $this->get_reply_mode($chat_id);

        if (!$reply_data) {
            return new WP_Error('no_reply_mode', 'Не найден контекст ответа');
        }

        $type = $reply_data['type'];
        $user_id = $reply_data['user_id'];
        $reference_id = $reply_data['reference_id'];
        $sender_id = $reply_data['sender_id'];

        // Обрабатываем в зависимости от типа
        $result = false;

        switch ($type) {
            case 'message':
                $result = $this->process_message_reply($user_id, $sender_id, $text);
                break;

            case 'forum':
                $result = $this->process_forum_reply($user_id, $reference_id, $text);
                break;
        }

        // Очищаем режим ответа
        $this->clear_reply_mode($chat_id);

        // Отправляем подтверждение
        if (!is_wp_error($result) && $result) {
            $this->send_message($chat_id, "✅ Ваш ответ отправлен!");
        } else {
            $this->send_message($chat_id, "❌ Ошибка отправки ответа");
        }

        return $result;
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

        update_post_meta($message_id, 'sender_id', $user_id);
        update_post_meta($message_id, 'recipient_id', $recipient_id);
        update_post_meta($message_id, 'read_status', '0');
        update_post_meta($message_id, 'sent_via', 'telegram');

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

        update_post_meta($reply_id, 'sent_via', 'telegram');

        return true;
    }
}
