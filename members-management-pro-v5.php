<?php
/**
 * Plugin Name: Metoda Community MGMT
 * Description: Полнофункциональная система управления участниками и экспертами сообщества. Включает: регистрацию с валидацией, систему кодов доступа для импортированных участников, личные кабинеты с онбордингом, управление материалами с WYSIWYG-редактором, форум в стиле Reddit с категориями и лайками, настраиваемые email-шаблоны, CSV-импорт, кроппер фото, систему ролей и прав доступа, поиск и фильтрацию участников, OTP-аутентификацию через email.
 * Version: 5.0.0
 * Author: Kirill Rem
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

define('METODA_VERSION', '5.0.0');
define('METODA_PATH', plugin_dir_path(__FILE__));
define('METODA_URL', plugin_dir_url(__FILE__));

// Legacy constant for backward compatibility
if (!defined('METODA_PLUGIN_DIR')) {
    define('METODA_PLUGIN_DIR', METODA_PATH);
}

// ============================================================================
// CORE BOOTSTRAP
// ============================================================================

// Helper functions (must load first)
require_once METODA_PATH . 'includes/helpers/functions.php';

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
