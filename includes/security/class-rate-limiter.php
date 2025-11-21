<?php
/**
 * Rate Limiter
 *
 * Prevents brute-force attacks on login and OTP endpoints
 * Uses WordPress transients for storage (works on all hosts)
 *
 * @package Metoda
 * @since 5.1.0
 */

namespace Metoda\Security;

if (!defined('ABSPATH')) {
    exit;
}

class Rate_Limiter {

    /**
     * Rate limit configurations per action type
     */
    private const LIMITS = array(
        'login' => array(
            'max_attempts' => 5,
            'window'       => 900,      // 15 minutes
            'block_time'   => 1800,     // 30 minutes block
        ),
        'otp_request' => array(
            'max_attempts' => 3,
            'window'       => 300,      // 5 minutes
            'block_time'   => 600,      // 10 minutes block
        ),
        'otp_verify' => array(
            'max_attempts' => 5,
            'window'       => 300,      // 5 minutes
            'block_time'   => 900,      // 15 minutes block
        ),
        'access_code' => array(
            'max_attempts' => 10,
            'window'       => 3600,     // 1 hour
            'block_time'   => 3600,     // 1 hour block
        ),
    );

    /**
     * Transient prefix
     */
    private const PREFIX = 'metoda_rl_';

    /**
     * Check if IP is blocked for specific action
     *
     * @param string $ip Client IP address
     * @param string $action Action type (login, otp_request, otp_verify)
     * @return bool True if blocked
     */
    public function is_blocked(string $ip, string $action): bool {
        $block_key = $this->get_block_key($ip, $action);
        return (bool) get_transient($block_key);
    }

    /**
     * Record an attempt for IP/action
     *
     * @param string $ip Client IP address
     * @param string $action Action type
     * @return int Current attempt count
     */
    public function record_attempt(string $ip, string $action): int {
        $config = $this->get_config($action);
        $key = $this->get_attempts_key($ip, $action);

        $data = get_transient($key);

        if (!$data) {
            $data = array(
                'count'     => 0,
                'first_at'  => time(),
            );
        }

        // Check if window has reset
        if (time() - $data['first_at'] > $config['window']) {
            $data = array(
                'count'     => 0,
                'first_at'  => time(),
            );
        }

        $data['count']++;
        $data['last_at'] = time();

        // Store with window expiration
        set_transient($key, $data, $config['window']);

        // Check if should block
        if ($data['count'] >= $config['max_attempts']) {
            $this->block($ip, $action);

            // Log the block event
            $this->log_block($ip, $action, $data['count']);
        }

        return $data['count'];
    }

    /**
     * Clear attempts for IP/action (call on successful auth)
     *
     * @param string $ip Client IP address
     * @param string $action Action type
     */
    public function clear_attempts(string $ip, string $action): void {
        $key = $this->get_attempts_key($ip, $action);
        delete_transient($key);
    }

    /**
     * Block IP for specific action
     *
     * @param string $ip Client IP address
     * @param string $action Action type
     */
    private function block(string $ip, string $action): void {
        $config = $this->get_config($action);
        $block_key = $this->get_block_key($ip, $action);

        set_transient($block_key, array(
            'blocked_at' => time(),
            'reason'     => $action,
        ), $config['block_time']);

        // Fire action for external logging/notifications
        do_action('metoda_ip_blocked', $ip, $action, $config['block_time']);
    }

    /**
     * Manually unblock IP
     *
     * @param string $ip Client IP address
     * @param string $action Action type (optional, unblocks all if not specified)
     */
    public function unblock(string $ip, string $action = ''): void {
        if ($action) {
            delete_transient($this->get_block_key($ip, $action));
            delete_transient($this->get_attempts_key($ip, $action));
        } else {
            // Unblock all actions
            foreach (array_keys(self::LIMITS) as $act) {
                delete_transient($this->get_block_key($ip, $act));
                delete_transient($this->get_attempts_key($ip, $act));
            }
        }
    }

    /**
     * Get remaining attempts before block
     *
     * @param string $ip Client IP address
     * @param string $action Action type
     * @return int Remaining attempts
     */
    public function get_remaining_attempts(string $ip, string $action): int {
        $config = $this->get_config($action);
        $key = $this->get_attempts_key($ip, $action);
        $data = get_transient($key);

        if (!$data) {
            return $config['max_attempts'];
        }

        $remaining = $config['max_attempts'] - $data['count'];
        return max(0, $remaining);
    }

    /**
     * Get block remaining time in seconds
     *
     * @param string $ip Client IP address
     * @param string $action Action type
     * @return int Seconds until unblock, 0 if not blocked
     */
    public function get_block_remaining_time(string $ip, string $action): int {
        $block_key = $this->get_block_key($ip, $action);
        $data = get_transient($block_key);

        if (!$data) {
            return 0;
        }

        $config = $this->get_config($action);
        $elapsed = time() - $data['blocked_at'];
        $remaining = $config['block_time'] - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Get configuration for action
     *
     * @param string $action Action type
     * @return array Configuration
     */
    private function get_config(string $action): array {
        return self::LIMITS[$action] ?? self::LIMITS['login'];
    }

    /**
     * Generate attempts cache key
     *
     * @param string $ip Client IP
     * @param string $action Action type
     * @return string Cache key
     */
    private function get_attempts_key(string $ip, string $action): string {
        return self::PREFIX . 'attempts_' . $action . '_' . md5($ip);
    }

    /**
     * Generate block cache key
     *
     * @param string $ip Client IP
     * @param string $action Action type
     * @return string Cache key
     */
    private function get_block_key(string $ip, string $action): string {
        return self::PREFIX . 'block_' . $action . '_' . md5($ip);
    }

    /**
     * Log block event
     *
     * @param string $ip Client IP
     * @param string $action Action type
     * @param int $attempts Number of attempts
     */
    private function log_block(string $ip, string $action, int $attempts): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        error_log(sprintf(
            '[Metoda Security] IP %s blocked for %s after %d attempts',
            $ip,
            $action,
            $attempts
        ));

        // Store in activity log if available
        if (class_exists('Metoda_Activity_Log')) {
            \Metoda_Activity_Log::log(
                'security',
                sprintf('IP %s blocked for %s', $ip, $action),
                array(
                    'ip'       => $ip,
                    'action'   => $action,
                    'attempts' => $attempts,
                )
            );
        }
    }

    /**
     * Get all currently blocked IPs (admin function)
     *
     * @return array List of blocked IPs with details
     */
    public static function get_blocked_ips(): array {
        global $wpdb;

        $blocked = array();
        $prefix = '_transient_' . self::PREFIX . 'block_';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_name LIKE %s",
            $prefix . '%'
        ));

        foreach ($results as $row) {
            $data = maybe_unserialize($row->option_value);
            if (is_array($data)) {
                $blocked[] = array(
                    'key'        => $row->option_name,
                    'blocked_at' => $data['blocked_at'] ?? 0,
                    'reason'     => $data['reason'] ?? 'unknown',
                );
            }
        }

        return $blocked;
    }

    /**
     * Cleanup old transients (can be called via cron)
     */
    public static function cleanup(): void {
        global $wpdb;

        // WordPress automatically cleans expired transients,
        // but we can force cleanup of our specific ones
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             AND option_name LIKE %s",
            '_transient_timeout_' . self::PREFIX . '%',
            '_transient_' . self::PREFIX . '%'
        ));
    }
}
