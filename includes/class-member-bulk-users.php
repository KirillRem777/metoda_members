<?php
/**
 * Member Bulk Users Class
 *
 * Handles bulk creation of WordPress users for members
 * Generates credentials table for admin
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Member_Bulk_Users {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_bulk_users_page'), 20);
        add_action('admin_post_create_bulk_users', array($this, 'handle_bulk_creation'));
        add_action('admin_post_download_credentials_csv', array($this, 'download_credentials_csv'));
    }

    /**
     * Add bulk users page to admin menu
     */
    public function add_bulk_users_page() {
        add_submenu_page(
            'edit.php?post_type=members',
            'Создать пользователей',
            'Создать пользователей',
            'manage_options',
            'members-bulk-users',
            array($this, 'render_bulk_users_page')
        );
    }

    /**
     * Render bulk users page
     */
    public function render_bulk_users_page() {
        // Check if we have stored credentials to display
        $stored_credentials = get_transient('member_bulk_credentials');

        ?>
        <div class="wrap">
            <h1>Массовое создание пользователей</h1>

            <?php if ($stored_credentials) : ?>
                <!-- Show results -->
                <div class="notice notice-success">
                    <p><strong>✅ Пользователи успешно созданы!</strong></p>
                    <p>Создано пользователей: <?php echo count($stored_credentials); ?></p>
                </div>

                <div class="card" style="max-width: 100%; margin-top: 20px;">
                    <h2>Учетные данные для передачи участникам</h2>
                    <p>Сохраните эти данные! Они больше не будут доступны после закрытия этой страницы.</p>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom: 20px;">
                        <input type="hidden" name="action" value="download_credentials_csv">
                        <?php wp_nonce_field('download_credentials_csv'); ?>
                        <button type="submit" class="button button-primary">
                            📥 Скачать CSV файл
                        </button>
                        <button type="button" class="button" onclick="copyTableToClipboard()">
                            📋 Копировать в буфер обмена
                        </button>
                    </form>

                    <div style="overflow-x: auto;">
                        <table class="wp-list-table widefat fixed striped" id="credentials-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">№</th>
                                    <th style="width: 25%;">ФИО участника</th>
                                    <th style="width: 20%;">Email</th>
                                    <th style="width: 20%;">Логин</th>
                                    <th style="width: 20%;">Временный пароль</th>
                                    <th style="width: 10%;">Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $index = 1;
                                foreach ($stored_credentials as $cred) :
                                ?>
                                    <tr>
                                        <td><?php echo $index++; ?></td>
                                        <td><strong><?php echo esc_html($cred['name']); ?></strong></td>
                                        <td><?php echo esc_html($cred['email']); ?></td>
                                        <td><code><?php echo esc_html($cred['login']); ?></code></td>
                                        <td>
                                            <code style="background: #fff3cd; padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                                                <?php echo esc_html($cred['password']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php if ($cred['success']) : ?>
                                                <span style="color: #10b981;">✓ Создан</span>
                                            <?php else : ?>
                                                <span style="color: #ef4444;">✗ Ошибка</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                        <h3 style="margin-top: 0;">⚠️ Важно!</h3>
                        <ul style="margin: 10px 0;">
                            <li>Сохраните эту информацию в безопасном месте</li>
                            <li>Передайте учетные данные каждому участнику индивидуально</li>
                            <li>При первом входе участники будут обязаны сменить пароль</li>
                            <li>Ссылка для входа: <code><?php echo wp_login_url(); ?></code></li>
                        </ul>
                    </div>

                    <form method="post" style="margin-top: 20px;">
                        <button type="submit" name="clear_results" class="button">
                            Очистить результаты и начать заново
                        </button>
                    </form>
                </div>

                <script>
                function copyTableToClipboard() {
                    const table = document.getElementById('credentials-table');
                    let text = '';

                    // Headers
                    text += 'ФИО\tEmail\tЛогин\tПароль\n';

                    // Rows
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        text += cells[1].textContent.trim() + '\t';
                        text += cells[2].textContent.trim() + '\t';
                        text += cells[3].textContent.trim() + '\t';
                        text += cells[4].textContent.trim() + '\n';
                    });

                    navigator.clipboard.writeText(text).then(() => {
                        alert('Таблица скопирована в буфер обмена!');
                    });
                }
                </script>

            <?php
                // Clear stored credentials if requested
                if (isset($_POST['clear_results'])) {
                    delete_transient('member_bulk_credentials');
                    echo '<script>window.location.href = window.location.href.split("?")[0] + "?page=members-bulk-users";</script>';
                }
            ?>

            <?php else : ?>
                <!-- Show creation form -->
                <?php
                // Get members without linked users
                $members_without_users = $this->get_members_without_users();
                $total_count = count($members_without_users);
                ?>

                <div class="card" style="max-width: 800px;">
                    <h2>Автоматическое создание пользователей</h2>
                    <p>Эта функция автоматически создаст WordPress-пользователей для всех участников, у которых еще нет привязанного пользователя.</p>

                    <?php if ($total_count > 0) : ?>
                        <div style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px;">
                            <h3 style="margin-top: 0;">📊 Найдено участников без пользователей: <?php echo $total_count; ?></h3>
                            <p>Будут созданы пользователи для следующих участников:</p>
                            <ul style="max-height: 300px; overflow-y: auto; background: white; padding: 15px; border-radius: 4px;">
                                <?php foreach ($members_without_users as $member) : ?>
                                    <li>
                                        <strong><?php echo esc_html($member['name']); ?></strong>
                                        <?php if (!empty($member['email'])) : ?>
                                            (<?php echo esc_html($member['email']); ?>)
                                        <?php else : ?>
                                            <span style="color: #f59e0b;">⚠️ Email не указан - будет создан автоматически</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                            <h3 style="margin-top: 0;">ℹ️ Что произойдет:</h3>
                            <ol>
                                <li>Для каждого участника будет создан WordPress-пользователь</li>
                                <li>Логин будет создан на основе ФИО (транслитерация)</li>
                                <li>Если email не указан, будет создан автоматически</li>
                                <li>Будет сгенерирован безопасный временный пароль (12 символов)</li>
                                <li>Пользователю будет назначена роль "Участник"</li>
                                <li>Пользователь будет привязан к профилю участника</li>
                                <li>При первом входе пользователь будет обязан сменить пароль</li>
                            </ol>
                        </div>

                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('Вы уверены, что хотите создать <?php echo $total_count; ?> пользователей?');">
                            <input type="hidden" name="action" value="create_bulk_users">
                            <?php wp_nonce_field('create_bulk_users'); ?>

                            <button type="submit" class="button button-primary button-hero">
                                🚀 Создать <?php echo $total_count; ?> пользователей
                            </button>
                        </form>

                    <?php else : ?>
                        <div class="notice notice-info inline">
                            <p><strong>ℹ️ Все участники уже имеют привязанных пользователей!</strong></p>
                            <p>Нет участников, требующих создания пользователей.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="max-width: 800px; margin-top: 20px;">
                    <h2>📋 Инструкция по использованию</h2>
                    <ol>
                        <li>Нажмите кнопку "Создать пользователей"</li>
                        <li>Система создаст пользователей и покажет таблицу с учетными данными</li>
                        <li>Скачайте CSV-файл или скопируйте таблицу</li>
                        <li>Передайте учетные данные менеджеру или каждому участнику индивидуально</li>
                        <li>Участники смогут войти на сайт и будут обязаны сменить пароль</li>
                    </ol>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get members without linked users
     */
    private function get_members_without_users() {
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $members = get_posts($args);
        $members_without_users = array();

        foreach ($members as $member) {
            $linked_user = get_post_meta($member->ID, '_linked_user_id', true);

            if (empty($linked_user)) {
                $email = get_post_meta($member->ID, 'member_email', true);

                $members_without_users[] = array(
                    'id' => $member->ID,
                    'name' => $member->post_title,
                    'email' => $email,
                );
            }
        }

        return $members_without_users;
    }

    /**
     * Handle bulk user creation
     */
    public function handle_bulk_creation() {
        check_admin_referer('create_bulk_users');

        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }

        $members = $this->get_members_without_users();
        $credentials = array();

        foreach ($members as $member) {
            $result = $this->create_user_for_member($member);
            $credentials[] = $result;
        }

        // Store credentials in transient for 1 hour
        set_transient('member_bulk_credentials', $credentials, HOUR_IN_SECONDS);

        // Redirect back to page
        wp_redirect(admin_url('edit.php?post_type=members&page=members-bulk-users'));
        exit;
    }

    /**
     * Create WordPress user for member
     */
    private function create_user_for_member($member) {
        $name = $member['name'];
        $email = $member['email'];
        $member_id = $member['id'];

        // Generate username from name (transliteration)
        $username = $this->generate_username($name);

        // Generate email if not provided
        if (empty($email)) {
            $email = $username . '@temp.local';
        }

        // Generate secure temporary password
        $password = $this->generate_password();

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return array(
                'name' => $name,
                'email' => $email,
                'login' => $username,
                'password' => $password,
                'success' => false,
                'error' => $user_id->get_error_message(),
            );
        }

        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('member');

        // Update display name
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $name,
        ));

        // Link user to member
        update_post_meta($member_id, '_linked_user_id', $user_id);

        // Mark as needing onboarding
        update_user_meta($user_id, '_member_needs_onboarding', '1');

        return array(
            'name' => $name,
            'email' => $email,
            'login' => $username,
            'password' => $password,
            'success' => true,
        );
    }

    /**
     * Generate username from name
     */
    private function generate_username($name) {
        // Transliteration array
        $transliteration = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        );

        // Transliterate
        $username = strtr($name, $transliteration);

        // Clean up
        $username = strtolower($username);
        $username = preg_replace('/[^a-z0-9._-]/', '', $username);
        $username = preg_replace('/[._-]+/', '_', $username);
        $username = trim($username, '_.-');

        // Ensure unique
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate secure password
     */
    private function generate_password($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Download credentials as CSV
     */
    public function download_credentials_csv() {
        check_admin_referer('download_credentials_csv');

        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }

        $credentials = get_transient('member_bulk_credentials');

        if (!$credentials) {
            wp_die('Данные не найдены');
        }

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="member-credentials-' . date('Y-m-d-H-i-s') . '.csv"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write header
        fputcsv($output, array('ФИО', 'Email', 'Логин', 'Временный пароль', 'Ссылка для входа'));

        // Write data
        foreach ($credentials as $cred) {
            if ($cred['success']) {
                fputcsv($output, array(
                    $cred['name'],
                    $cred['email'],
                    $cred['login'],
                    $cred['password'],
                    wp_login_url(),
                ));
            }
        }

        fclose($output);
        exit;
    }
}

// Initialize the class
new Member_Bulk_Users();
