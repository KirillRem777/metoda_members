<?php
/**
 * Plugin Name: Metoda Community MGMT
 * Description: Полнофункциональная система управления участниками и экспертами сообщества. Включает: регистрацию с валидацией, систему кодов доступа для импортированных участников, личные кабинеты с онбордингом, управление материалами с WYSIWYG-редактором, форум в стиле Reddit с категориями и лайками, настраиваемые email-шаблоны, CSV-импорт, кроппер фото, систему ролей и прав доступа, поиск и фильтрацию участников, OTP-аутентификацию через email.
 * Version: 5.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Kirill Rem
 * Author URI: https://metoda.ru
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: metoda-community-mgmt
 * Domain Path: /languages
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// 🔴 ЯДЕРНАЯ КНОПКА: Полное отключение плагина
// Добавь в wp-config.php: define('METODA_DISABLE_PLUGIN', true);
if (defined('METODA_DISABLE_PLUGIN') && METODA_DISABLE_PLUGIN) {
    return; // Плагин ПОЛНОСТЬЮ отключен - ничего не загружается!
}

// ============================================================================
// CONSTANTS
// ============================================================================

define('METODA_VERSION', '5.1.0');
define('METODA_PATH', plugin_dir_path(__FILE__));
define('METODA_URL', plugin_dir_url(__FILE__));
define('METODA_BASENAME', plugin_basename(__FILE__));
define('METODA_MIN_PHP', '7.4');
define('METODA_MIN_WP', '6.0');

// Legacy constant for backward compatibility
if (!defined('METODA_PLUGIN_DIR')) {
    define('METODA_PLUGIN_DIR', METODA_PATH);
}

// ============================================================================
// PHP VERSION CHECK
// ============================================================================

if (version_compare(PHP_VERSION, METODA_MIN_PHP, '<')) {
    add_action('admin_notices', function() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            sprintf(
                /* translators: %s: minimum PHP version */
                esc_html__('Metoda Community MGMT requires PHP %s or higher.', 'metoda-community-mgmt'),
                METODA_MIN_PHP
            )
        );
    });
    return;
}

// ============================================================================
// CORE BOOTSTRAP
// ============================================================================

// Helper functions (must load first)
require_once METODA_PATH . 'includes/helpers/functions.php';

// i18n - Internationalization
require_once METODA_PATH . 'includes/core/class-i18n.php';

// Security - Rate Limiter
require_once METODA_PATH . 'includes/security/class-rate-limiter.php';

// REST API Controller
require_once METODA_PATH . 'includes/api/class-rest-controller.php';

// Main plugin class
require_once METODA_PATH . 'includes/core/class-plugin.php';

// ============================================================================
// ACTIVATION / DEACTIVATION HOOKS
// ============================================================================

register_activation_hook(__FILE__, array('Metoda_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Metoda_Plugin', 'deactivate'));

// ============================================================================
// PLUGIN INITIALIZATION
// ============================================================================

/**
 * Initialize the plugin
 */
function metoda_init() {
    // Load plugin instance
    Metoda_Plugin::instance();
}
add_action('plugins_loaded', 'metoda_init');

// ============================================================================
// LEGACY BOOTSTRAP (temporary - will be removed after full refactor)
// ============================================================================

/**
 * Load legacy code from old architecture
 * This section will be gradually removed as we migrate functionality to new classes
 */
function metoda_load_legacy() {
    // Legacy constants and functions will be loaded here during transition
    // TODO: Remove this function once refactoring is complete
}
add_action('init', 'metoda_load_legacy', 5);
