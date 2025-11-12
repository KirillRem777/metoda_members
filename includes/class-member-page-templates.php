<?php
/**
 * Member Page Templates Manager
 *
 * Automatically creates and manages required pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_Page_Templates {

    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 25);
        add_action('admin_post_create_member_pages', array(__CLASS__, 'handle_page_creation'));
    }

    /**
     * Get required pages configuration
     */
    public static function get_required_pages() {
        return array(
            'member_dashboard' => array(
                'title' => 'Личный кабинет участника',
                'slug' => 'member-dashboard',
                'shortcode' => '[member_dashboard]',
                'description' => 'Страница личного кабинета для участников. Здесь участники могут просматривать свой профиль и загружать материалы.',
                'icon' => '👤'
            ),
            'manager_panel' => array(
                'title' => 'Панель менеджера',
                'slug' => 'manager-panel',
                'shortcode' => '[member_manager]',
                'description' => 'Административная панель для менеджеров. Позволяет управлять участниками, добавлять/редактировать/удалять записи.',
                'icon' => '⚙️'
            ),
            'member_onboarding' => array(
                'title' => 'Онбординг участника',
                'slug' => 'member-onboarding',
                'shortcode' => '[member_onboarding]',
                'description' => 'Страница первого входа для участников. Смена пароля и приветствие при первом входе в систему.',
                'icon' => '🚀'
            )
        );
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=members',
            'Шаблоны страниц',
            'Шаблоны страниц',
            'manage_options',
            'member-page-templates',
            array(__CLASS__, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        $pages = self::get_required_pages();
        $status = array();

        // Check status of each page
        foreach ($pages as $key => $config) {
            $page = get_page_by_path($config['slug']);
            $status[$key] = array(
                'exists' => !empty($page),
                'page_id' => $page ? $page->ID : null,
                'url' => $page ? get_permalink($page->ID) : null,
                'has_shortcode' => $page ? has_shortcode($page->post_content, str_replace(array('[', ']'), '', $config['shortcode'])) : false
            );
        }

        // Check if action was performed
        $action_result = get_transient('member_pages_created');
        if ($action_result) {
            delete_transient('member_pages_created');
        }

        ?>
        <div class="wrap">
            <h1>🎨 Шаблоны страниц участников</h1>
            <p class="description">Управление обязательными страницами для системы участников</p>

            <?php if ($action_result): ?>
                <div class="notice notice-success is-dismissible" style="margin-top: 20px;">
                    <h3>✅ Страницы созданы успешно!</h3>
                    <ul>
                        <?php foreach ($action_result as $result): ?>
                            <li><?php echo esc_html($result); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>📄 Статус страниц</h2>

                <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>Страница</th>
                            <th>Описание</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $key => $config): ?>
                            <?php $page_status = $status[$key]; ?>
                            <tr>
                                <td style="font-size: 32px; text-align: center;">
                                    <?php echo $config['icon']; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 16px;"><?php echo esc_html($config['title']); ?></strong><br>
                                    <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">
                                        <?php echo esc_html($config['shortcode']); ?>
                                    </code>
                                </td>
                                <td style="color: #666;">
                                    <?php echo esc_html($config['description']); ?>
                                </td>
                                <td>
                                    <?php if ($page_status['exists']): ?>
                                        <span style="display: inline-block; background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                            ✅ СОЗДАНА
                                        </span>
                                        <?php if (!$page_status['has_shortcode']): ?>
                                            <br>
                                            <span style="display: inline-block; background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 11px; margin-top: 5px;">
                                                ⚠️ Нет шорткода
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="display: inline-block; background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 12px;">
                                            ❌ НЕ СОЗДАНА
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($page_status['exists']): ?>
                                        <a href="<?php echo esc_url($page_status['url']); ?>"
                                           class="button button-small"
                                           target="_blank"
                                           style="margin-right: 5px;">
                                            👁️ Просмотр
                                        </a>
                                        <a href="<?php echo get_edit_post_link($page_status['page_id']); ?>"
                                           class="button button-small">
                                            ✏️ Редактировать
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="padding: 20px; margin-top: 20px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                        <?php wp_nonce_field('create_member_pages', 'pages_nonce'); ?>
                        <input type="hidden" name="action" value="create_member_pages">

                        <button type="submit"
                                class="button button-primary button-hero"
                                style="background: linear-gradient(135deg, #2E466F 0%, #EF4E4C 100%); border: none; text-shadow: none;">
                            🚀 Создать все страницы
                        </button>
                    </form>

                    <p style="margin-top: 15px; color: #666; font-size: 13px;">
                        <strong>Примечание:</strong> Если страница уже существует, она будет пропущена.
                        Для пересоздания страницы удалите её вручную и нажмите кнопку снова.
                    </p>
                </div>
            </div>

            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>📚 Информация о страницах</h2>

                <div style="padding: 20px;">
                    <h3>👤 Личный кабинет участника</h3>
                    <p>
                        <strong>URL:</strong> <code>/member-dashboard</code><br>
                        <strong>Доступ:</strong> Только для пользователей с ролью "member"<br>
                        <strong>Функции:</strong> Просмотр профиля, загрузка материалов (отзывы, благодарности, интервью, видео, рецензии, разработки)
                    </p>

                    <h3>⚙️ Панель менеджера</h3>
                    <p>
                        <strong>URL:</strong> <code>/manager-panel</code><br>
                        <strong>Доступ:</strong> Только для пользователей с ролью "manager" или администраторов<br>
                        <strong>Функции:</strong> CRUD операции с участниками, управление фотографиями, материалами и галереями
                    </p>

                    <h3>🚀 Онбординг участника</h3>
                    <p>
                        <strong>URL:</strong> <code>/member-onboarding</code><br>
                        <strong>Доступ:</strong> Автоматический редирект при первом входе<br>
                        <strong>Функции:</strong> Смена временного пароля, приветственное сообщение
                    </p>
                </div>
            </div>

            <div class="card" style="max-width: 1200px; margin-top: 20px; background: #eff6ff; border-left: 4px solid #2563eb;">
                <h2 style="color: #1e40af;">💡 Полезные ссылки</h2>
                <div style="padding: 20px;">
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            📊 <a href="<?php echo admin_url('edit.php?post_type=members&page=member-csv-import'); ?>">
                                Импорт участников из CSV
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            👥 <a href="<?php echo admin_url('edit.php?post_type=members&page=bulk-create-users'); ?>">
                                Создать пользователей для участников
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            📝 <a href="<?php echo admin_url('edit.php?post_type=members'); ?>">
                                Все участники
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            👤 <a href="<?php echo admin_url('users.php'); ?>">
                                Пользователи WordPress
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <style>
            .wrap h1 {
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .wrap h2 {
                font-size: 20px;
                font-weight: 600;
                margin-bottom: 15px;
                padding: 20px 20px 0;
            }
            .wrap h3 {
                font-size: 16px;
                font-weight: 600;
                margin-top: 20px;
                margin-bottom: 10px;
                color: #2E466F;
            }
            .wrap .card {
                background: white;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                margin-top: 20px;
            }
            .button-hero {
                padding: 12px 36px !important;
                height: auto !important;
                font-size: 16px !important;
            }
        </style>
        <?php
    }

    /**
     * Handle page creation
     */
    public static function handle_page_creation() {
        // Check permissions and nonce
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }

        if (!isset($_POST['pages_nonce']) || !wp_verify_nonce($_POST['pages_nonce'], 'create_member_pages')) {
            wp_die('Ошибка безопасности');
        }

        $pages = self::get_required_pages();
        $results = array();

        foreach ($pages as $key => $config) {
            $result = self::create_page($config);
            $results[] = $result['message'];
        }

        // Store results in transient
        set_transient('member_pages_created', $results, 60);

        // Redirect back
        wp_redirect(add_query_arg('page', 'member-page-templates', admin_url('edit.php?post_type=members')));
        exit;
    }

    /**
     * Create single page
     */
    private static function create_page($config) {
        // Check if page already exists
        $existing_page = get_page_by_path($config['slug']);

        if ($existing_page) {
            return array(
                'success' => false,
                'message' => sprintf('⚠️ %s уже существует (ID: %d)', $config['title'], $existing_page->ID)
            );
        }

        // Create page
        $page_data = array(
            'post_title' => $config['title'],
            'post_name' => $config['slug'],
            'post_content' => $config['shortcode'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );

        $page_id = wp_insert_post($page_data);

        if (is_wp_error($page_id)) {
            return array(
                'success' => false,
                'message' => sprintf('❌ Ошибка создания "%s": %s', $config['title'], $page_id->get_error_message())
            );
        }

        return array(
            'success' => true,
            'message' => sprintf('✅ %s создана (ID: %d, URL: /%s)', $config['title'], $page_id, $config['slug'])
        );
    }

    /**
     * Create pages on plugin activation (optional)
     */
    public static function activate() {
        $pages = self::get_required_pages();

        foreach ($pages as $key => $config) {
            self::create_page($config);
        }

        flush_rewrite_rules();
    }
}

Member_Page_Templates::init();
