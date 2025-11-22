<?php
/**
 * Notification Dispatcher
 *
 * Central coordinator for all notification types and channels
 * Routes notifications to appropriate handlers (Email, Telegram)
 *
 * @package Metoda_Members
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Notification_Dispatcher
 *
 * Coordinates notification delivery across all channels and types
 */
class Metoda_Notification_Dispatcher {

    /**
     * Email notifier instance
     *
     * @var Metoda_Notification_Email
     */
    private $email_notifier;

    /**
     * Telegram notifier instance
     *
     * @var Metoda_Notification_Telegram
     */
    private $telegram_notifier;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize notification handlers
        $this->email_notifier = new Metoda_Notification_Email();
        $this->telegram_notifier = new Metoda_Notification_Telegram();

        // Hook into platform events
        $this->register_hooks();
    }

    /**
     * Register WordPress hooks for platform events
     *
     * @return void
     */
    private function register_hooks() {
        // Messages
        add_action('metoda_new_message', array($this, 'handle_new_message'), 10, 2);

        // Forum
        add_action('metoda_new_forum_reply', array($this, 'handle_forum_reply'), 10, 3);
        add_action('metoda_forum_mention', array($this, 'handle_forum_mention'), 10, 3);

        // Projects (future)
        add_action('metoda_project_update', array($this, 'handle_project_update'), 10, 2);
        add_action('metoda_project_comment', array($this, 'handle_project_comment'), 10, 3);

        // Learning platform (future)
        add_action('metoda_learning_assignment', array($this, 'handle_learning_assignment'), 10, 2);
        add_action('metoda_learning_feedback', array($this, 'handle_learning_feedback'), 10, 2);

        // Quiet hours - process queued notifications
        add_action('metoda_process_notification_queue', array($this, 'process_queued_notifications'));
    }

    /**
     * Dispatch notification to user
     *
     * @param int $user_id User ID to send notification to
     * @param array $data Notification data
     * @return array Results from each channel
     */
    public function dispatch($user_id, $data) {
        $results = array(
            'email' => false,
            'telegram' => false
        );

        // Check if user has this notification type enabled
        $type = $data['type'] ?? 'message';
        $type_enabled = get_user_meta($user_id, 'notify_' . $type . 's', true);

        if ($type_enabled !== '1') {
            return $results;
        }

        // Send to Email if enabled
        $email_enabled = get_user_meta($user_id, 'notify_channel_email', true);
        if ($email_enabled === '1') {
            $results['email'] = $this->email_notifier->send($user_id, $data);
        }

        // Send to Telegram if enabled
        $telegram_enabled = get_user_meta($user_id, 'notify_channel_telegram', true);
        if ($telegram_enabled === '1') {
            $results['telegram'] = $this->telegram_notifier->send($user_id, $data);
        }

        return $results;
    }

    /**
     * Handle new private message
     *
     * @param int $message_id Message post ID
     * @param int $recipient_id Recipient user ID
     * @return void
     */
    public function handle_new_message($message_id, $recipient_id) {
        $message = get_post($message_id);
        if (!$message) {
            return;
        }

        $sender_id = get_post_meta($message_id, 'sender_id', true);
        $sender = get_userdata($sender_id);

        $data = array(
            'type' => 'message',
            'sender_id' => $sender_id,
            'sender_name' => $sender ? $sender->display_name : 'Участник',
            'subject' => $message->post_title,
            'content' => $message->post_content,
            'reference_id' => $message_id,
            'link' => home_url('/member-dashboard#messages'),
            'allow_reply' => true
        );

        $this->dispatch($recipient_id, $data);
    }

    /**
     * Handle forum reply notification
     *
     * @param int $reply_id Reply post ID
     * @param int $topic_id Topic post ID
     * @param int $user_id User to notify
     * @return void
     */
    public function handle_forum_reply($reply_id, $topic_id, $user_id) {
        $reply = get_post($reply_id);
        $topic = get_post($topic_id);

        if (!$reply || !$topic) {
            return;
        }

        $author = get_userdata($reply->post_author);

        $data = array(
            'type' => 'forum',
            'author_id' => $reply->post_author,
            'author_name' => $author ? $author->display_name : 'Участник',
            'topic_id' => $topic_id,
            'topic_title' => $topic->post_title,
            'content' => $reply->post_content,
            'reference_id' => $reply_id,
            'link' => get_permalink($topic_id) . '#reply-' . $reply_id,
            'allow_reply' => true
        );

        $this->dispatch($user_id, $data);
    }

    /**
     * Handle forum mention (@username)
     *
     * @param int $post_id Post ID where mention occurred
     * @param int $mentioned_user_id User who was mentioned
     * @param int $author_id Author who mentioned
     * @return void
     */
    public function handle_forum_mention($post_id, $mentioned_user_id, $author_id) {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        $author = get_userdata($author_id);

        // Get topic
        $topic_id = $post->post_parent ?: $post_id;
        $topic = get_post($topic_id);

        $data = array(
            'type' => 'forum',
            'author_id' => $author_id,
            'author_name' => $author ? $author->display_name : 'Участник',
            'topic_id' => $topic_id,
            'topic_title' => $topic ? $topic->post_title : 'Тема форума',
            'content' => "Вас упомянули в обсуждении:\n\n" . wp_trim_words($post->post_content, 50),
            'reference_id' => $post_id,
            'link' => get_permalink($topic_id) . '#post-' . $post_id,
            'allow_reply' => true
        );

        $this->dispatch($mentioned_user_id, $data);
    }

    /**
     * Handle project update notification
     *
     * @param int $project_id Project ID
     * @param array $members Array of member user IDs
     * @return void
     */
    public function handle_project_update($project_id, $members) {
        $project = get_post($project_id);
        if (!$project) {
            return;
        }

        $update_author = get_userdata($project->post_author);

        $data = array(
            'type' => 'project',
            'author_name' => $update_author ? $update_author->display_name : 'Участник',
            'project_id' => $project_id,
            'project_title' => $project->post_title,
            'content' => wp_trim_words($project->post_content, 50),
            'reference_id' => $project_id,
            'link' => get_permalink($project_id),
            'allow_reply' => false
        );

        // Notify all project members
        foreach ($members as $member_id) {
            // Don't notify the author
            if ($member_id != $project->post_author) {
                $this->dispatch($member_id, $data);
            }
        }
    }

    /**
     * Handle project comment notification
     *
     * @param int $comment_id Comment ID
     * @param int $project_id Project ID
     * @param int $user_id User to notify
     * @return void
     */
    public function handle_project_comment($comment_id, $project_id, $user_id) {
        $comment = get_comment($comment_id);
        $project = get_post($project_id);

        if (!$comment || !$project) {
            return;
        }

        $data = array(
            'type' => 'project',
            'author_name' => $comment->comment_author,
            'project_id' => $project_id,
            'project_title' => $project->post_title,
            'content' => $comment->comment_content,
            'reference_id' => $comment_id,
            'link' => get_permalink($project_id) . '#comment-' . $comment_id,
            'allow_reply' => true
        );

        $this->dispatch($user_id, $data);
    }

    /**
     * Handle learning platform assignment
     *
     * @param int $assignment_id Assignment ID
     * @param int $student_id Student user ID
     * @return void
     */
    public function handle_learning_assignment($assignment_id, $student_id) {
        $assignment = get_post($assignment_id);
        if (!$assignment) {
            return;
        }

        $teacher = get_userdata($assignment->post_author);

        $data = array(
            'type' => 'learning',
            'author_name' => $teacher ? $teacher->display_name : 'Преподаватель',
            'assignment_id' => $assignment_id,
            'assignment_title' => $assignment->post_title,
            'content' => "Вам назначено новое задание:\n\n" . wp_trim_words($assignment->post_content, 50),
            'reference_id' => $assignment_id,
            'link' => home_url('/learning/assignment/' . $assignment_id),
            'allow_reply' => false
        );

        $this->dispatch($student_id, $data);
    }

    /**
     * Handle learning platform feedback
     *
     * @param int $submission_id Submission ID
     * @param int $student_id Student user ID
     * @return void
     */
    public function handle_learning_feedback($submission_id, $student_id) {
        $submission = get_post($submission_id);
        if (!$submission) {
            return;
        }

        $teacher_id = get_post_meta($submission_id, 'reviewer_id', true);
        $teacher = get_userdata($teacher_id);
        $feedback = get_post_meta($submission_id, 'feedback', true);

        $data = array(
            'type' => 'learning',
            'author_name' => $teacher ? $teacher->display_name : 'Преподаватель',
            'content' => "Получен отзыв на ваше задание:\n\n" . $feedback,
            'reference_id' => $submission_id,
            'link' => home_url('/learning/submission/' . $submission_id),
            'allow_reply' => false
        );

        $this->dispatch($student_id, $data);
    }

    /**
     * Process queued notifications (after quiet hours end)
     *
     * @return void
     */
    public function process_queued_notifications() {
        global $wpdb;

        // Get all users with queued notifications
        $users = $wpdb->get_results("
            SELECT DISTINCT user_id
            FROM {$wpdb->usermeta}
            WHERE meta_key = 'notification_queue'
        ");

        foreach ($users as $user) {
            $user_id = $user->user_id;

            // Check if still in quiet hours
            $quiet_enabled = get_user_meta($user_id, 'quiet_hours_enabled', true);
            if ($quiet_enabled === '1') {
                $start = get_user_meta($user_id, 'quiet_hours_start', true) ?: '22:00';
                $end = get_user_meta($user_id, 'quiet_hours_end', true) ?: '08:00';
                $current_time = current_time('H:i');

                // If still in quiet hours, skip
                if ($start > $end) {
                    if ($current_time >= $start || $current_time <= $end) {
                        continue;
                    }
                } else {
                    if ($current_time >= $start && $current_time <= $end) {
                        continue;
                    }
                }
            }

            // Get queued notifications
            $queue = get_user_meta($user_id, 'notification_queue', true) ?: array();

            if (empty($queue)) {
                continue;
            }

            // Send digest notification
            $this->send_digest($user_id, $queue);

            // Clear queue
            delete_user_meta($user_id, 'notification_queue');
        }
    }

    /**
     * Send digest notification
     *
     * @param int $user_id User ID
     * @param array $queue Queued notifications
     * @return void
     */
    private function send_digest($user_id, $queue) {
        $count = count($queue);

        // Group by type
        $grouped = array();
        foreach ($queue as $item) {
            $type = $item['data']['type'] ?? 'message';
            if (!isset($grouped[$type])) {
                $grouped[$type] = array();
            }
            $grouped[$type][] = $item;
        }

        // Build digest content
        $content = "За время вашего отсутствия накопилось {$count} уведомлений:\n\n";

        foreach ($grouped as $type => $items) {
            $type_count = count($items);
            $type_label = $this->get_type_label($type);
            $content .= "📌 {$type_label}: {$type_count}\n";
        }

        $content .= "\n──────────────────────────────────────\n\n";

        // Add details for each notification
        foreach ($queue as $item) {
            $data = $item['data'];
            $content .= $this->format_digest_item($data) . "\n\n";
        }

        $digest_data = array(
            'type' => 'digest',
            'title' => "Сводка уведомлений",
            'content' => $content,
            'link' => home_url('/member-dashboard'),
            'allow_reply' => false
        );

        // Send via enabled channels
        $email_enabled = get_user_meta($user_id, 'notify_channel_email', true);
        if ($email_enabled === '1') {
            $this->email_notifier->send($user_id, $digest_data);
        }

        $telegram_enabled = get_user_meta($user_id, 'notify_channel_telegram', true);
        if ($telegram_enabled === '1') {
            $this->telegram_notifier->send($user_id, $digest_data);
        }
    }

    /**
     * Get human-readable type label
     *
     * @param string $type Notification type
     * @return string Label
     */
    private function get_type_label($type) {
        $labels = array(
            'message' => 'Личные сообщения',
            'forum' => 'Форум',
            'project' => 'Проекты',
            'learning' => 'Обучение'
        );

        return $labels[$type] ?? 'Уведомления';
    }

    /**
     * Format digest item
     *
     * @param array $data Notification data
     * @return string Formatted text
     */
    private function format_digest_item($data) {
        $type = $data['type'] ?? 'message';

        switch ($type) {
            case 'message':
                return "💬 Сообщение от {$data['sender_name']}";

            case 'forum':
                return "💭 {$data['author_name']} в теме '{$data['topic_title']}'";

            case 'project':
                return "📁 Обновление проекта '{$data['project_title']}'";

            case 'learning':
                return "📚 {$data['author_name']}: {$data['assignment_title']}";

            default:
                return "🔔 Уведомление";
        }
    }
}
