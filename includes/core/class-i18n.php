<?php
/**
 * Internationalization
 *
 * Loads and handles plugin translations
 *
 * @package Metoda
 * @since 5.1.0
 */

namespace Metoda\Core;

if (!defined('ABSPATH')) {
    exit;
}

class I18n {

    /**
     * Text domain
     */
    const TEXT_DOMAIN = 'metoda-community-mgmt';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Load plugin translations
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename(METODA_PATH)) . '/languages'
        );
    }

    /**
     * Get translated string (static helper)
     *
     * @param string $text String to translate
     * @return string Translated string
     */
    public static function __($text): string {
        return __($text, self::TEXT_DOMAIN);
    }

    /**
     * Echo translated string (static helper)
     *
     * @param string $text String to translate
     */
    public static function _e($text): void {
        _e($text, self::TEXT_DOMAIN);
    }

    /**
     * Get translated string with escaping for HTML
     *
     * @param string $text String to translate
     * @return string Escaped translated string
     */
    public static function esc_html__($text): string {
        return esc_html__($text, self::TEXT_DOMAIN);
    }

    /**
     * Get translated string with escaping for attributes
     *
     * @param string $text String to translate
     * @return string Escaped translated string
     */
    public static function esc_attr__($text): string {
        return esc_attr__($text, self::TEXT_DOMAIN);
    }

    /**
     * Translate string with placeholders
     *
     * @param string $text String with placeholders
     * @param mixed ...$args Arguments for sprintf
     * @return string Translated and formatted string
     */
    public static function sprintf($text, ...$args): string {
        return sprintf(__($text, self::TEXT_DOMAIN), ...$args);
    }

    /**
     * Plural translation
     *
     * @param string $single Singular form
     * @param string $plural Plural form
     * @param int $number Number to check
     * @return string Appropriate form
     */
    public static function _n($single, $plural, $number): string {
        return _n($single, $plural, $number, self::TEXT_DOMAIN);
    }
}

/**
 * Global helper functions for convenience
 */
if (!function_exists('metoda__')) {
    /**
     * Translate string with Metoda text domain
     *
     * @param string $text String to translate
     * @return string Translated string
     */
    function metoda__($text) {
        return __($text, 'metoda-community-mgmt');
    }
}

if (!function_exists('metoda_e')) {
    /**
     * Echo translated string with Metoda text domain
     *
     * @param string $text String to translate
     */
    function metoda_e($text) {
        _e($text, 'metoda-community-mgmt');
    }
}

if (!function_exists('metoda_esc_html__')) {
    /**
     * Translate and escape for HTML
     *
     * @param string $text String to translate
     * @return string Escaped translated string
     */
    function metoda_esc_html__($text) {
        return esc_html__($text, 'metoda-community-mgmt');
    }
}

if (!function_exists('metoda_esc_attr__')) {
    /**
     * Translate and escape for attributes
     *
     * @param string $text String to translate
     * @return string Escaped translated string
     */
    function metoda_esc_attr__($text) {
        return esc_attr__($text, 'metoda-community-mgmt');
    }
}
