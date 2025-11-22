<?php
/**
 * Email Reply Handler
 *
 * Handles incoming email replies via webhook
 * Processes reply-to emails with embedded tokens
 *
 * @package Metoda_Members
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Email_Reply_Handler
 *
 * REST API endpoint for processing email replies
 */
class Metoda_Email_Reply_Handler {

    /**
     * Webhook secret key
     *
     * @var string
     */
    private $webhook_secret;

    /**
     * Constructor
     */
    public function __construct() {
        $this->webhook_secret = get_option('metoda_email_webhook_secret');

        // Generate secret if not exists
        if (empty($this->webhook_secret)) {
            $this->webhook_secret = wp_generate_password(32, false);
            update_option('metoda_email_webhook_secret', $this->webhook_secret);
        }

        // Register REST API endpoint
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    /**
     * Register REST API endpoints
     *
     * @return void
     */
    public function register_endpoints() {
        register_rest_route('metoda/v1', '/email-reply', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => array($this, 'verify_webhook'),
        ));
    }

    /**
     * Verify webhook request
     *
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function verify_webhook($request) {
        $secret = $request->get_header('X-Webhook-Secret');

        if (empty($secret)) {
            return new WP_Error('missing_secret', 'Missing webhook secret', array('status' => 401));
        }

        if ($secret !== $this->webhook_secret) {
            return new WP_Error('invalid_secret', 'Invalid webhook secret', array('status' => 403));
        }

        return true;
    }

    /**
     * Handle incoming webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function handle_webhook($request) {
        $data = $request->get_json_params();

        // Log incoming webhook for debugging
        $this->log_webhook($data);

        // Extract required fields
        $to = $data['to'] ?? '';
        $from = $data['from'] ?? '';
        $subject = $data['subject'] ?? '';
        $body_text = $data['body_text'] ?? '';
        $body_html = $data['body_html'] ?? '';

        // Prefer plain text, fallback to HTML stripped
        $content = !empty($body_text) ? $body_text : wp_strip_all_tags($body_html);

        // Extract reply token from 'to' address
        $token = $this->extract_token($to);

        if (empty($token)) {
            return new WP_Error('invalid_address', 'Could not extract reply token from address', array('status' => 400));
        }

        // Clean content (remove quoted text)
        $content = $this->clean_reply_content($content);

        if (empty($content)) {
            return new WP_Error('empty_content', 'Reply content is empty', array('status' => 400));
        }

        // Process the reply
        $email_notifier = new Metoda_Notification_Email();
        $result = $email_notifier->process_reply($token, $content);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Reply processed successfully'
        ), 200);
    }

    /**
     * Extract token from email address
     *
     * Format: reply+TOKEN@domain.com
     *
     * @param string $email Email address
     * @return string|null Token or null if not found
     */
    private function extract_token($email) {
        // Pattern: reply+TOKEN@domain.com
        if (preg_match('/reply\+([a-zA-Z0-9]+)@/', $email, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Clean reply content
     *
     * Removes quoted text, signatures, and unnecessary whitespace
     *
     * @param string $content Raw reply content
     * @return string Cleaned content
     */
    private function clean_reply_content($content) {
        // Remove email signatures (common patterns)
        $content = preg_replace('/^--\s*$/m', '[[SIGNATURE]]', $content);
        $content = preg_split('/\[\[SIGNATURE\]\]/', $content)[0];

        // Remove quoted text (lines starting with >)
        $lines = explode("\n", $content);
        $clean_lines = array();
        $quote_started = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Detect quote start
            if (strpos($trimmed, '>') === 0 ||
                strpos($trimmed, 'On ') === 0 && strpos($trimmed, 'wrote:') !== false) {
                $quote_started = true;
                continue;
            }

            // Skip if we're in quoted section
            if ($quote_started) {
                continue;
            }

            // Keep line
            $clean_lines[] = $line;
        }

        $content = implode("\n", $clean_lines);

        // Remove common email client separators
        $content = preg_replace('/_{3,}/', '', $content);
        $content = preg_replace('/-{3,}/', '', $content);

        // Normalize whitespace
        $content = trim($content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return $content;
    }

    /**
     * Log webhook data for debugging
     *
     * @param array $data Webhook data
     * @return void
     */
    private function log_webhook($data) {
        // Only log if WP_DEBUG is enabled
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'to' => $data['to'] ?? '',
            'from' => $data['from'] ?? '',
            'subject' => $data['subject'] ?? '',
            'body_length' => strlen($data['body_text'] ?? $data['body_html'] ?? ''),
        );

        error_log('Metoda Email Reply Webhook: ' . json_encode($log_entry));
    }

    /**
     * Get webhook URL
     *
     * @return string Webhook URL
     */
    public function get_webhook_url() {
        return rest_url('metoda/v1/email-reply');
    }

    /**
     * Get webhook secret
     *
     * @return string Webhook secret
     */
    public function get_webhook_secret() {
        return $this->webhook_secret;
    }

    /**
     * Regenerate webhook secret
     *
     * @return string New webhook secret
     */
    public function regenerate_secret() {
        $this->webhook_secret = wp_generate_password(32, false);
        update_option('metoda_email_webhook_secret', $this->webhook_secret);
        return $this->webhook_secret;
    }
}
