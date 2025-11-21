<?php
/**
 * OTP Authentication
 *
 * Wrapper for Member_OTP class with additional utilities
 * Provides one-time password authentication via email or Telegram
 *
 * @package Metoda
 * @since 5.0.0
 * @updated 5.1.0 - Added Telegram support with fallback to email
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Otp {

    /**
     * Legacy OTP instance
     *
     * @var Member_OTP|null
     */
    private $legacy_otp = null;

    /**
     * Telegram instance
     *
     * @var \Metoda\Auth\Telegram|null
     */
    private $telegram = null;

    /**
     * OTP expiry time in seconds (10 minutes)
     */
    const EXPIRY_TIME = 600;

    /**
     * Delivery methods
     */
    const METHOD_EMAIL = 'email';
    const METHOD_TELEGRAM = 'telegram';

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize legacy OTP class
        if (class_exists('Member_OTP')) {
            $this->legacy_otp = new Member_OTP();
        }

        // Initialize Telegram if available
        if (class_exists('Metoda\\Auth\\Telegram')) {
            $this->telegram = \Metoda\Auth\Telegram::instance();
        }
    }

    /**
     * Check if OTP system is available
     *
     * @return bool
     */
    public function is_available() {
        return $this->legacy_otp !== null;
    }

    /**
     * Generate OTP for user
     *
     * @param int $user_id User ID
     * @return string|false 6-digit OTP or false on failure
     */
    public function generate($user_id) {
        if (!$this->legacy_otp) {
            return false;
        }

        return $this->legacy_otp->generate_otp($user_id);
    }

    /**
     * Verify OTP code
     *
     * @param int $user_id User ID
     * @param string $otp OTP code to verify
     * @return bool True if valid
     */
    public function verify($user_id, $otp) {
        if (!$this->legacy_otp) {
            return false;
        }

        return $this->legacy_otp->verify_otp($user_id, $otp);
    }

    /**
     * Send OTP via email
     *
     * @param int $user_id User ID
     * @param string $otp OTP code
     * @return bool True if sent
     */
    public function send($user_id, $otp) {
        if (!$this->legacy_otp) {
            return false;
        }

        return $this->legacy_otp->send_otp_email($user_id, $otp);
    }

    /**
     * Generate and send OTP in one call
     *
     * @param int $user_id User ID
     * @return bool True if OTP was generated and sent
     */
    public function send_new_code($user_id) {
        $otp = $this->generate($user_id);

        if (!$otp) {
            return false;
        }

        return $this->send($user_id, $otp);
    }

    /**
     * Check if user has OTP login method enabled
     *
     * @param int $user_id User ID
     * @return bool
     */
    public static function is_enabled_for_user($user_id) {
        $method = get_user_meta($user_id, 'login_method', true);
        return $method === 'otp';
    }

    /**
     * Enable OTP login for user
     *
     * @param int $user_id User ID
     */
    public static function enable_for_user($user_id) {
        update_user_meta($user_id, 'login_method', 'otp');
    }

    /**
     * Disable OTP login for user (switch to password)
     *
     * @param int $user_id User ID
     */
    public static function disable_for_user($user_id) {
        update_user_meta($user_id, 'login_method', 'password');
    }

    /**
     * Get remaining time for OTP validity
     *
     * @param int $user_id User ID
     * @return int Seconds remaining, 0 if expired
     */
    public static function get_remaining_time($user_id) {
        $expires = get_user_meta($user_id, 'otp_expires', true);

        if (empty($expires)) {
            return 0;
        }

        $remaining = intval($expires) - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Check if user has a pending OTP
     *
     * @param int $user_id User ID
     * @return bool
     */
    public static function has_pending_otp($user_id) {
        return self::get_remaining_time($user_id) > 0;
    }

    /**
     * Send OTP with smart delivery: Telegram first, then Email fallback
     *
     * @param int $user_id User ID
     * @param string $otp OTP code
     * @return array Result with 'success', 'method', 'message' keys
     */
    public function send_with_fallback($user_id, $otp) {
        // Try Telegram first if available and linked
        if ($this->telegram && \Metoda\Auth\Telegram::is_linked($user_id)) {
            $result = $this->telegram->send_otp($user_id, $otp);

            if ($result === true) {
                return array(
                    'success' => true,
                    'method'  => self::METHOD_TELEGRAM,
                    'message' => __('Code sent to Telegram', 'metoda-community-mgmt'),
                );
            }

            // Log Telegram failure for debugging
            if (is_wp_error($result) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Metoda OTP] Telegram failed, falling back to email: ' . $result->get_error_message());
            }
        }

        // Fallback to email
        $email_sent = $this->send($user_id, $otp);

        return array(
            'success' => $email_sent,
            'method'  => self::METHOD_EMAIL,
            'message' => $email_sent
                ? __('Code sent to email', 'metoda-community-mgmt')
                : __('Failed to send code', 'metoda-community-mgmt'),
        );
    }

    /**
     * Generate and send OTP with smart delivery
     *
     * @param int $user_id User ID
     * @return array Result with 'success', 'method', 'message' keys
     */
    public function send_new_code_smart($user_id) {
        $otp = $this->generate($user_id);

        if (!$otp) {
            return array(
                'success' => false,
                'method'  => null,
                'message' => __('Failed to generate code', 'metoda-community-mgmt'),
            );
        }

        return $this->send_with_fallback($user_id, $otp);
    }

    /**
     * Check if Telegram delivery is available for user
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function is_telegram_available($user_id) {
        if (!$this->telegram || !$this->telegram->is_configured()) {
            return false;
        }

        return \Metoda\Auth\Telegram::is_linked($user_id);
    }

    /**
     * Get preferred delivery method for user
     *
     * @param int $user_id User ID
     * @return string 'telegram' or 'email'
     */
    public function get_delivery_method($user_id) {
        // If Telegram is linked, prefer it
        if ($this->is_telegram_available($user_id)) {
            return self::METHOD_TELEGRAM;
        }

        return self::METHOD_EMAIL;
    }

    /**
     * Get delivery method info for UI display
     *
     * @param int $user_id User ID
     * @return array Method info with 'method', 'label', 'destination' keys
     */
    public function get_delivery_info($user_id) {
        if ($this->is_telegram_available($user_id)) {
            $username = \Metoda\Auth\Telegram::get_username($user_id);
            return array(
                'method'      => self::METHOD_TELEGRAM,
                'label'       => __('Telegram', 'metoda-community-mgmt'),
                'destination' => $username ? "@{$username}" : 'Telegram',
                'icon'        => 'telegram',
            );
        }

        $user = get_user_by('ID', $user_id);
        return array(
            'method'      => self::METHOD_EMAIL,
            'label'       => __('Email', 'metoda-community-mgmt'),
            'destination' => $user ? $user->user_email : '',
            'icon'        => 'email',
        );
    }
}
