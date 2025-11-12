<?php
/**
 * Member Template Loader
 *
 * Automatically loads single member template from plugin if theme doesn't have it
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_Template_Loader {

    /**
     * Initialize
     */
    public static function init() {
        add_filter('template_include', array(__CLASS__, 'load_member_template'), 99);
        add_action('admin_notices', array(__CLASS__, 'template_notice'));
        add_action('admin_post_copy_member_template', array(__CLASS__, 'handle_copy_template'));
    }

    /**
     * Load member template from plugin if theme doesn't have it
     */
    public static function load_member_template($template) {
        // Check if this is a single member post
        if (is_singular('members')) {
            // Check if theme has the template
            $theme_template = locate_template(array('single-members.php'));

            // If theme doesn't have template, use plugin's template
            if (!$theme_template) {
                $plugin_template = plugin_dir_path(dirname(__FILE__)) . 'single-members.php';
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }

        return $template;
    }

    /**
     * Show admin notice if template is not in theme
     */
    public static function template_notice() {
        // Only show on members pages
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'members') {
            return;
        }

        // Check if template exists in theme
        $theme_template = locate_template(array('single-members.php'));

        if (!$theme_template) {
            $copy_url = admin_url('admin-post.php?action=copy_member_template');
            $copy_url = wp_nonce_url($copy_url, 'copy_member_template');

            ?>
            <div class="notice notice-warning is-dismissible">
                <h3>⚠️ Шаблон страницы участника не найден в теме</h3>
                <p>
                    <strong>Временное решение:</strong> Плагин использует свой встроенный шаблон для отображения страниц участников.<br>
                    <strong>Рекомендация:</strong> Скопируйте шаблон в вашу тему для возможности кастомизации.
                </p>
                <p>
                    <a href="<?php echo esc_url($copy_url); ?>" class="button button-primary">
                        📋 Скопировать шаблон в тему
                    </a>
                    <span style="margin-left: 15px; color: #666;">
                        Файл будет скопирован в: <code><?php echo get_stylesheet_directory(); ?>/single-members.php</code>
                    </span>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Handle copying template to theme
     */
    public static function handle_copy_template() {
        // Check permissions and nonce
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'copy_member_template')) {
            wp_die('Ошибка безопасности');
        }

        // Get source and destination paths
        $source = plugin_dir_path(dirname(__FILE__)) . 'single-members.php';
        $theme_dir = get_stylesheet_directory();
        $destination = $theme_dir . '/single-members.php';

        // Check if source exists
        if (!file_exists($source)) {
            wp_die('Файл шаблона не найден в плагине');
        }

        // Check if theme directory is writable
        if (!is_writable($theme_dir)) {
            wp_die('Директория темы не доступна для записи. Скопируйте файл вручную из плагина в тему.');
        }

        // Check if destination already exists
        if (file_exists($destination)) {
            wp_die('Файл single-members.php уже существует в теме');
        }

        // Copy the file
        $result = copy($source, $destination);

        if ($result) {
            // Success - redirect back with success message
            $redirect_url = add_query_arg(
                array(
                    'post_type' => 'members',
                    'template_copied' => '1'
                ),
                admin_url('edit.php')
            );
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('Ошибка копирования файла. Пожалуйста, скопируйте файл вручную.');
        }
    }

    /**
     * Show success notice after template copy
     */
    public static function show_success_notice() {
        if (isset($_GET['template_copied']) && $_GET['template_copied'] === '1') {
            ?>
            <div class="notice notice-success is-dismissible">
                <h3>✅ Шаблон успешно скопирован!</h3>
                <p>
                    Файл <code>single-members.php</code> скопирован в вашу тему.<br>
                    Теперь вы можете редактировать его в: <code><?php echo get_stylesheet_directory(); ?>/single-members.php</code>
                </p>
            </div>
            <?php
        }
    }
}

// Initialize
Member_Template_Loader::init();
add_action('admin_notices', array('Member_Template_Loader', 'show_success_notice'));
