<?php
/**
 * User Onboarding
 *
 * @package Metoda
 * @since 5.0.0
 */

if (!defined('ABSPATH')) exit;

class Metoda_Onboarding {
    private $legacy_onboarding;
    
    public function __construct() {
        // Wrap existing Member_Onboarding class (legacy)
        if (class_exists('Member_Onboarding')) {
            $this->legacy_onboarding = new Member_Onboarding();
        }
    }
}
