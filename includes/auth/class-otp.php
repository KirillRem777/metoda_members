<?php
/**
 * OTP Authentication
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) exit;

class Metoda_Otp {
    private $legacy_otp;
    
    public function __construct() {
        // Wrap existing Member_OTP class (legacy)
        if (class_exists('Member_OTP')) {
            $this->legacy_otp = new Member_OTP();
        }
    }
}
