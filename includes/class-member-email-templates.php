<?php
/**
 * Member Email Templates Class
 *
 * Manages custom email templates with WYSIWYG editor
 * Allows customization of emails sent to members
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_Email_Templates {

    /**
     * Available email templates
     */
    private static $templates = array(
        'welcome' => array(
            'title' => 'Приветственное письмо',
            'description' => 'Отправляется новым участникам после регистрации',
            'subject_default' => 'Добро пожаловать в ассоциацию Метода!',
            'variables' => array(
                '{user_name}' => 'Имя пользователя',
                '{user_email}' => 'Email пользователя',
                '{site_name}' => 'Название сайта',
                '{dashboard_url}' => 'Ссылка на личный кабинет',
                '{site_url}' => 'Ссылка на главную страницу'
            )
        ),
        'access_code_activated' => array(
            'title' => 'Профиль активирован',
            'description' => 'Отправляется при активации профиля через код доступа',
            'subject_default' => 'Ваш профиль успешно активирован',
            'variables' => array(
                '{user_name}' => 'Имя пользователя',
                '{user_email}' => 'Email пользователя',
                '{member_name}' => 'Имя участника',
                '{access_code}' => 'Использованный код доступа',
                '{dashboard_url}' => 'Ссылка на личный кабинет',
                '{site_name}' => 'Название сайта'
            )
        ),
        'password_reset' => array(
            'title' => 'Сброс пароля',
            'description' => 'Отправляется при запросе сброса пароля',
            'subject_default' => 'Восстановление пароля',
            'variables' => array(
                '{user_name}' => 'Имя пользователя',
                '{reset_link}' => 'Ссылка для сброса пароля',
                '{site_name}' => 'Название сайта',
                '{valid_time}' => 'Время действия ссылки'
            )
        ),
        'profile_moderation' => array(
            'title' => 'Профиль на модерации',
            'description' => 'Отправляется после отправки профиля на модерацию',
            'subject_default' => 'Ваш профиль отправлен на модерацию',
            'variables' => array(
                '{user_name}' => 'Имя пользователя',
                '{member_name}' => 'Имя участника',
                '{dashboard_url}' => 'Ссылка на личный кабинет',
                '{site_name}' => 'Название сайта'
            )
        ),
        'manager_new_member' => array(
            'title' => 'Уведомление менеджеру о новом участнике',
            'description' => 'Отправляется менеджерам при регистрации нового участника',
            'subject_default' => 'Новый участник зарегистрирован',
            'variables' => array(
                '{member_name}' => 'Имя участника',
                '{member_email}' => 'Email участника',
                '{member_company}' => 'Компания',
                '{member_position}' => 'Должность',
                '{member_city}' => 'Город',
                '{registration_date}' => 'Дата регистрации',
                '{profile_url}' => 'Ссылка на профиль в админке',
                '{member_public_url}' => 'Публичная страница профиля',
                '{is_claimed}' => 'Активирован по коду (Да/Нет)',
                '{site_name}' => 'Название сайта'
            )
        )
    );

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Hook into registration
        add_action('metoda_member_registered', array($this, 'send_welcome_email'), 10, 2);
        add_action('metoda_profile_activated', array($this, 'send_activation_email'), 10, 3);
        add_action('metoda_member_registered', array($this, 'send_manager_notification'), 10, 3);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=members',
            'Email-шаблоны',
            'Email-шаблоны',
            'manage_options',
            'member-email-templates',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Logo setting
        register_setting('metoda_email_settings', 'metoda_email_logo');

        // Email templates
        foreach (self::$templates as $key => $template) {
            register_setting('metoda_email_settings', 'metoda_email_subject_' . $key);
            register_setting('metoda_email_settings', 'metoda_email_content_' . $key);
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'members_page_member-email-templates') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_editor();
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (isset($_POST['save_email_templates']) && check_admin_referer('save_email_templates', 'email_templates_nonce')) {
            $this->save_templates();
            echo '<div class="notice notice-success"><p>Настройки сохранены!</p></div>';
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'welcome';
        ?>
        <div class="wrap metoda-email-settings">
            <h1>📧 Email-шаблоны</h1>
            <p class="description">Настройте внешний вид и содержание писем, отправляемых участникам</p>

            <h2 class="nav-tab-wrapper">
                <a href="?post_type=members&page=member-email-templates&tab=logo" class="nav-tab <?php echo $current_tab === 'logo' ? 'nav-tab-active' : ''; ?>">
                    🎨 Логотип
                </a>
                <?php foreach (self::$templates as $key => $template): ?>
                    <a href="?post_type=members&page=member-email-templates&tab=<?php echo $key; ?>" class="nav-tab <?php echo $current_tab === $key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($template['title']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field('save_email_templates', 'email_templates_nonce'); ?>

                <div class="metoda-tab-content">
                    <?php if ($current_tab === 'logo'): ?>
                        <?php $this->render_logo_settings(); ?>
                    <?php else: ?>
                        <?php $this->render_template_editor($current_tab); ?>
                    <?php endif; ?>
                </div>

                <p class="submit">
                    <button type="submit" name="save_email_templates" class="button button-primary button-large">
                        💾 Сохранить изменения
                    </button>
                </p>
            </form>
        </div>

        <style>
            .metoda-email-settings .nav-tab-wrapper {
                margin: 20px 0;
            }
            .metoda-tab-content {
                background: white;
                border: 1px solid #ccd0d4;
                padding: 30px;
                margin: 20px 0;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .metoda-variable-box {
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .metoda-variable-box h4 {
                margin: 0 0 15px;
                color: #374151;
            }
            .metoda-variable-list {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 12px;
            }
            .metoda-variable-item {
                background: white;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                padding: 10px 14px;
                cursor: pointer;
                transition: all 0.2s;
                font-family: 'Courier New', monospace;
            }
            .metoda-variable-item:hover {
                border-color: #0066cc;
                background: #eff6ff;
            }
            .metoda-variable-item code {
                color: #0066cc;
                font-weight: 600;
            }
            .metoda-variable-item small {
                display: block;
                color: #6b7280;
                margin-top: 4px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            .metoda-logo-preview {
                max-width: 300px;
                max-height: 150px;
                margin: 20px 0;
                border: 2px dashed #ddd;
                padding: 10px;
                border-radius: 8px;
            }
            .metoda-logo-preview img {
                max-width: 100%;
                height: auto;
                display: block;
            }
            .metoda-preview-section {
                margin: 30px 0;
                padding: 20px;
                background: #f9fafb;
                border-radius: 8px;
                border: 1px solid #e5e7eb;
            }
            .metoda-preview-section h4 {
                margin: 0 0 15px;
            }
            .metoda-preview-email {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                max-width: 600px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Copy variable to clipboard
            $('.metoda-variable-item').on('click', function() {
                const code = $(this).find('code').text();
                navigator.clipboard.writeText(code).then(function() {
                    const $item = $('.metoda-variable-item').filter(function() {
                        return $(this).find('code').text() === code;
                    });
                    const originalBg = $item.css('background-color');
                    $item.css('background-color', '#10b981');
                    setTimeout(function() {
                        $item.css('background-color', originalBg);
                    }, 500);
                });
            });

            // Logo uploader
            $('#upload_logo_button').on('click', function(e) {
                e.preventDefault();
                const mediaUploader = wp.media({
                    title: 'Выберите логотип',
                    button: { text: 'Использовать логотип' },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#metoda_email_logo').val(attachment.url);
                    $('#logo_preview').html('<img src="' + attachment.url + '" alt="Logo">');
                });

                mediaUploader.open();
            });

            $('#remove_logo_button').on('click', function(e) {
                e.preventDefault();
                $('#metoda_email_logo').val('');
                $('#logo_preview').html('<p style="color: #9ca3af;">Логотип не загружен</p>');
            });
        });
        </script>
        <?php
    }

    /**
     * Render logo settings
     */
    private function render_logo_settings() {
        $logo_url = get_option('metoda_email_logo', '');
        ?>
        <h3>Логотип для email-писем</h3>
        <p class="description">Загрузите логотип, который будет отображаться в шапке всех email-писем. Рекомендуемый размер: 200x60px</p>

        <div class="metoda-logo-preview" id="logo_preview">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo">
            <?php else: ?>
                <p style="color: #9ca3af; text-align: center; padding: 30px;">Логотип не загружен</p>
            <?php endif; ?>
        </div>

        <input type="hidden" id="metoda_email_logo" name="metoda_email_logo" value="<?php echo esc_attr($logo_url); ?>">

        <p>
            <button type="button" id="upload_logo_button" class="button button-secondary">
                📤 Загрузить логотип
            </button>
            <button type="button" id="remove_logo_button" class="button button-secondary" style="margin-left: 10px;">
                🗑️ Удалить логотип
            </button>
        </p>
        <?php
    }

    /**
     * Render template editor
     */
    private function render_template_editor($template_key) {
        if (!isset(self::$templates[$template_key])) {
            return;
        }

        $template = self::$templates[$template_key];
        $subject = get_option('metoda_email_subject_' . $template_key, $template['subject_default']);
        $content = get_option('metoda_email_content_' . $template_key, $this->get_default_content($template_key));
        ?>
        <h3><?php echo esc_html($template['title']); ?></h3>
        <p class="description"><?php echo esc_html($template['description']); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="subject_<?php echo $template_key; ?>">Тема письма</label>
                </th>
                <td>
                    <input type="text"
                           id="subject_<?php echo $template_key; ?>"
                           name="metoda_email_subject_<?php echo $template_key; ?>"
                           value="<?php echo esc_attr($subject); ?>"
                           class="large-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="content_<?php echo $template_key; ?>">Содержание письма</label>
                </th>
                <td>
                    <?php
                    wp_editor($content, 'content_' . $template_key, array(
                        'textarea_name' => 'metoda_email_content_' . $template_key,
                        'textarea_rows' => 15,
                        'media_buttons' => false,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'formatselect,bold,italic,underline,link,unlink,forecolor,bullist,numlist,alignleft,aligncenter,alignright',
                        )
                    ));
                    ?>
                </td>
            </tr>
        </table>

        <div class="metoda-variable-box">
            <h4>📋 Доступные переменные (нажмите, чтобы скопировать)</h4>
            <div class="metoda-variable-list">
                <?php foreach ($template['variables'] as $var => $desc): ?>
                    <div class="metoda-variable-item">
                        <code><?php echo esc_html($var); ?></code>
                        <small><?php echo esc_html($desc); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Save templates
     */
    private function save_templates() {
        // Save logo
        if (isset($_POST['metoda_email_logo'])) {
            update_option('metoda_email_logo', esc_url_raw($_POST['metoda_email_logo']));
        }

        // Save each template
        foreach (self::$templates as $key => $template) {
            if (isset($_POST['metoda_email_subject_' . $key])) {
                update_option('metoda_email_subject_' . $key, sanitize_text_field($_POST['metoda_email_subject_' . $key]));
            }
            if (isset($_POST['metoda_email_content_' . $key])) {
                update_option('metoda_email_content_' . $key, wp_kses_post($_POST['metoda_email_content_' . $key]));
            }
        }
    }

    /**
     * Get default content for template
     */
    private function get_default_content($template_key) {
        $defaults = array(
            'welcome' => '<p>Здравствуйте, <strong>{user_name}</strong>!</p>
<p>Добро пожаловать в ассоциацию <strong>{site_name}</strong>!</p>
<p>Ваша регистрация успешно завершена. Теперь вы можете воспользоваться всеми возможностями нашей платформы:</p>
<ul>
    <li>Управление вашим профилем участника</li>
    <li>Добавление материалов и достижений</li>
    <li>Нетворкинг с другими участниками</li>
    <li>Участие в мероприятиях ассоциации</li>
</ul>
<p><a href="{dashboard_url}" style="background: #0066cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 0;">Перейти в личный кабинет</a></p>
<p>Если у вас возникнут вопросы, не стесняйтесь обращаться к нам.</p>
<p>С уважением,<br>Команда {site_name}</p>',

            'access_code_activated' => '<p>Здравствуйте, <strong>{user_name}</strong>!</p>
<p>Отличные новости! Ваш профиль <strong>{member_name}</strong> успешно активирован с использованием кода доступа <code>{access_code}</code>.</p>
<p>Теперь вы можете:</p>
<ul>
    <li>Редактировать информацию в профиле</li>
    <li>Загружать фотографии и материалы</li>
    <li>Управлять вашими достижениями</li>
</ul>
<p><a href="{dashboard_url}" style="background: #0066cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 0;">Открыть личный кабинет</a></p>
<p>Добро пожаловать в ассоциацию Метода!</p>
<p>С уважением,<br>Команда {site_name}</p>',

            'password_reset' => '<p>Здравствуйте, <strong>{user_name}</strong>!</p>
<p>Вы запросили сброс пароля для вашего аккаунта на <strong>{site_name}</strong>.</p>
<p>Чтобы создать новый пароль, перейдите по ссылке ниже:</p>
<p><a href="{reset_link}" style="background: #0066cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 0;">Сбросить пароль</a></p>
<p><strong>Важно:</strong> Ссылка действительна в течение {valid_time}.</p>
<p>Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.</p>
<p>С уважением,<br>Команда {site_name}</p>',

            'profile_moderation' => '<p>Здравствуйте, <strong>{user_name}</strong>!</p>
<p>Ваш профиль <strong>{member_name}</strong> успешно отправлен на модерацию.</p>
<p>Наши менеджеры проверят предоставленную информацию в ближайшее время. Обычно это занимает 1-2 рабочих дня.</p>
<p>После одобрения вы получите уведомление, и ваш профиль станет виден другим участникам ассоциации.</p>
<p><a href="{dashboard_url}" style="background: #0066cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 0;">Вернуться в кабинет</a></p>
<p>Спасибо за терпение!</p>
<p>С уважением,<br>Команда {site_name}</p>',

            'manager_new_member' => '<h2 style="color: #0066cc;">Новый участник зарегистрирован</h2>
<p>В системе зарегистрирован новый участник:</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr style="background: #f9fafb;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Имя:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{member_name}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Email:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{member_email}</td>
    </tr>
    <tr style="background: #f9fafb;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Компания:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{member_company}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Должность:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{member_position}</td>
    </tr>
    <tr style="background: #f9fafb;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Город:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{member_city}</td>
    </tr>
    <tr>
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Дата регистрации:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{registration_date}</td>
    </tr>
    <tr style="background: #f9fafb;">
        <td style="padding: 10px; border: 1px solid #e5e7eb; font-weight: 600;">Активирован по коду:</td>
        <td style="padding: 10px; border: 1px solid #e5e7eb;">{is_claimed}</td>
    </tr>
</table>
<p><a href="{profile_url}" style="background: #0066cc; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 10px 10px 0;">Редактировать в админке</a> <a href="{member_public_url}" style="background: #6b7280; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin: 10px 0;">Посмотреть профиль</a></p>
<p style="color: #6b7280; font-size: 14px;">Это автоматическое уведомление из системы {site_name}</p>'
        );

        return isset($defaults[$template_key]) ? $defaults[$template_key] : '';
    }

    /**
     * Get email wrapper HTML
     */
    private static function get_email_wrapper($content) {
        $logo_url = get_option('metoda_email_logo', '');
        $site_name = get_bloginfo('name');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    line-height: 1.6;
                    color: #374151;
                    margin: 0;
                    padding: 0;
                    background-color: #f3f4f6;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                }
                .email-header {
                    background: linear-gradient(135deg, #0066cc 0%, #ff6600 100%);
                    padding: 30px 20px;
                    text-align: center;
                }
                .email-logo {
                    max-width: 200px;
                    height: auto;
                }
                .email-body {
                    padding: 40px 30px;
                }
                .email-footer {
                    background: #f9fafb;
                    padding: 20px 30px;
                    text-align: center;
                    font-size: 14px;
                    color: #6b7280;
                    border-top: 1px solid #e5e7eb;
                }
                a {
                    color: #0066cc;
                }
                code {
                    background: #f3f4f6;
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-family: 'Courier New', monospace;
                    color: #d97706;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="email-header">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="email-logo">
                    <?php else: ?>
                        <h1 style="color: white; margin: 0;"><?php echo esc_html($site_name); ?></h1>
                    <?php endif; ?>
                </div>
                <div class="email-body">
                    <?php echo $content; ?>
                </div>
                <div class="email-footer">
                    <p>© <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. Все права защищены.</p>
                    <p><a href="<?php echo home_url(); ?>"><?php echo home_url(); ?></a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send email with template
     */
    public static function send_email($template_key, $to_email, $variables = array()) {
        if (!isset(self::$templates[$template_key])) {
            return false;
        }

        $template = self::$templates[$template_key];
        $subject = get_option('metoda_email_subject_' . $template_key, $template['subject_default']);
        $content = get_option('metoda_email_content_' . $template_key, '');

        if (empty($content)) {
            return false;
        }

        // Replace variables in subject
        foreach ($variables as $key => $value) {
            $subject = str_replace($key, $value, $subject);
        }

        // Replace variables in content
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        // Wrap in email template
        $html = self::get_email_wrapper($content);

        // Set content type to HTML
        add_filter('wp_mail_content_type', function() { return 'text/html'; });

        $result = wp_mail($to_email, $subject, $html);

        // Reset content type
        remove_filter('wp_mail_content_type', function() { return 'text/html'; });

        return $result;
    }

    /**
     * Send welcome email
     */
    public function send_welcome_email($user_id, $member_id) {
        $user = get_user_by('id', $user_id);
        $member = get_post($member_id);

        if (!$user || !$member) {
            return;
        }

        $variables = array(
            '{user_name}' => $member->post_title,
            '{user_email}' => $user->user_email,
            '{site_name}' => get_bloginfo('name'),
            '{dashboard_url}' => home_url('/member-dashboard/'),
            '{site_url}' => home_url()
        );

        self::send_email('welcome', $user->user_email, $variables);
    }

    /**
     * Send activation email
     */
    public function send_activation_email($user_id, $member_id, $access_code) {
        $user = get_user_by('id', $user_id);
        $member = get_post($member_id);

        if (!$user || !$member) {
            return;
        }

        $variables = array(
            '{user_name}' => $member->post_title,
            '{user_email}' => $user->user_email,
            '{member_name}' => $member->post_title,
            '{access_code}' => $access_code,
            '{dashboard_url}' => home_url('/member-dashboard/'),
            '{site_name}' => get_bloginfo('name')
        );

        self::send_email('access_code_activated', $user->user_email, $variables);
    }

    /**
     * Send notification to manager about new member
     */
    public function send_manager_notification($user_id, $member_id, $is_claimed = false) {
        $user = get_user_by('id', $user_id);
        $member = get_post($member_id);

        if (!$user || !$member) {
            return;
        }

        // Get manager emails
        $manager_emails = $this->get_manager_emails();
        if (empty($manager_emails)) {
            return;
        }

        $variables = array(
            '{member_name}' => $member->post_title,
            '{member_email}' => $user->user_email,
            '{member_company}' => get_post_meta($member_id, 'member_company', true) ?: '—',
            '{member_position}' => get_post_meta($member_id, 'member_position', true) ?: '—',
            '{member_city}' => get_post_meta($member_id, 'member_city', true) ?: '—',
            '{registration_date}' => date_i18n('d.m.Y H:i', strtotime($member->post_date)),
            '{profile_url}' => admin_url('post.php?post=' . $member_id . '&action=edit'),
            '{member_public_url}' => get_permalink($member_id),
            '{is_claimed}' => $is_claimed ? 'Да' : 'Нет',
            '{site_name}' => get_bloginfo('name')
        );

        foreach ($manager_emails as $email) {
            self::send_email('manager_new_member', $email, $variables);
        }
    }

    /**
     * Get emails of managers and administrators
     */
    private function get_manager_emails() {
        $emails = array();

        // Get admin email
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $emails[] = $admin_email;
        }

        // Get all users with manager or administrator role
        $managers = get_users(array(
            'role__in' => array('administrator', 'manager'),
            'fields' => array('user_email')
        ));

        foreach ($managers as $manager) {
            if (!in_array($manager->user_email, $emails)) {
                $emails[] = $manager->user_email;
            }
        }

        return $emails;
    }

    /**
     * Get available templates
     */
    public static function get_templates() {
        return self::$templates;
    }
}

// Initialize the class
new Member_Email_Templates();
