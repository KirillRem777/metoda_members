<?php
/**
 * OTP Authentication
 *
 * Wrapper for Member_OTP class with additional utilities
 * Provides one-time password authentication via email
 *
 * @package Metoda
 * @since 5.0.0
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
     * OTP expiry time in seconds (10 minutes)
     */
    const EXPIRY_TIME = 600;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize legacy OTP class
        if (class_exists('Member_OTP')) {
            $this->legacy_otp = new Member_OTP();
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
}
