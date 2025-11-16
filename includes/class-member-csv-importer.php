<?php
/**
 * Member CSV Importer
 *
 * Handles importing members from CSV with automatic photo matching
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_CSV_Importer {

    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 30);
        add_action('admin_post_member_import_csv', array(__CLASS__, 'handle_csv_upload'));
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=members',
            'Импорт из CSV',
            'Импорт CSV',
            'manage_options',
            'member-csv-import',
            array(__CLASS__, 'render_import_page')
        );
    }

    /**
     * Render import page
     */
    public static function render_import_page() {
        $stats = get_transient('member_import_stats');
        $errors = get_transient('member_import_errors');

        // Clear transients after displaying
        if ($stats || $errors) {
            delete_transient('member_import_stats');
            delete_transient('member_import_errors');
        }

        ?>
        <div class="wrap">
            <h1>Импорт участников из CSV</h1>

            <?php if ($stats): ?>
                <div class="notice notice-success is-dismissible">
                    <h3>Импорт завершен успешно!</h3>
                    <ul>
                        <li>✅ Создано участников: <strong><?php echo $stats['created']; ?></strong></li>
                        <li>🔄 Обновлено участников: <strong><?php echo $stats['updated']; ?></strong></li>
                        <li>📷 Загружено фотографий: <strong><?php echo $stats['photos']; ?></strong></li>
                        <li>👤 Создано пользователей: <strong><?php echo $stats['users']; ?></strong></li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="notice notice-warning">
                    <h3>⚠️ Предупреждения при импорте:</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Загрузить CSV файл</h2>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" style="padding: 20px;">
                    <?php wp_nonce_field('member_csv_import', 'member_import_nonce'); ?>
                    <input type="hidden" name="action" value="member_import_csv">

                    <table class="form-table">
                        <tr>
                            <th><label for="csv_file">CSV файл</label></th>
                            <td>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                                <p class="description">Выберите CSV файл с данными участников</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="photos_folder">Папка с фотографиями</label></th>
                            <td>
                                <input type="text" name="photos_folder" id="photos_folder" class="regular-text" value="photos" placeholder="photos">
                                <p class="description">Укажите папку с фотографиями (относительно корня плагина). Фото должны называться как ФИО участника (например: "Иванов Иван Иванович.jpg")</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="create_users">Создать пользователей</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="create_users" id="create_users" value="1" checked>
                                    Автоматически создать пользователей WordPress для участников
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="update_existing">Обновить существующие</label></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="update_existing" id="update_existing" value="1">
                                    Обновить данные для существующих участников (если найдены по ФИО)
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-hero">
                            🚀 Начать импорт
                        </button>
                    </p>
                </form>

                <div style="padding: 20px; background: #f0f0f1; margin-top: 20px;">
                    <h3>📋 Формат CSV файла</h3>
                    <p>CSV файл должен содержать следующие колонки:</p>
                    <ul style="column-count: 2;">
                        <li><code>post_title</code> - ФИО участника</li>
                        <li><code>member_position</code> - Должность</li>
                        <li><code>member_company</code> - Организация</li>
                        <li><code>member_email</code> - Email</li>
                        <li><code>member_phone</code> - Телефон</li>
                        <li><code>member_bio</code> - Биография</li>
                        <li><code>member_specialization</code> - Специализация</li>
                        <li><code>member_experience</code> - Опыт работы</li>
                        <li><code>member_interests</code> - Интересы</li>
                        <li><code>taxonomy_member_type</code> - Тип (Эксперт/Участник)</li>
                        <li><code>taxonomy_member_roles</code> - Роли (через |)</li>
                        <li><code>taxonomy_member_location</code> - Локация</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle CSV upload and import
     */
    public static function handle_csv_upload() {
        // Check permissions and nonce
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав доступа');
        }

        if (!isset($_POST['member_import_nonce']) || !wp_verify_nonce($_POST['member_import_nonce'], 'member_csv_import')) {
            wp_die('Ошибка безопасности');
        }

        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('page', 'member-csv-import', admin_url('edit.php?post_type=members')));
            exit;
        }

        $csv_file = $_FILES['csv_file']['tmp_name'];
        $photos_folder = isset($_POST['photos_folder']) ? sanitize_text_field($_POST['photos_folder']) : 'photos';
        $create_users = isset($_POST['create_users']);
        $update_existing = isset($_POST['update_existing']);

        // Process import
        $result = self::import_csv($csv_file, $photos_folder, $create_users, $update_existing);

        // Store results in transient
        set_transient('member_import_stats', $result['stats'], 60);
        if (!empty($result['errors'])) {
            set_transient('member_import_errors', $result['errors'], 60);
        }

        // Redirect back
        wp_redirect(add_query_arg('page', 'member-csv-import', admin_url('edit.php?post_type=members')));
        exit;
    }

    /**
     * Import CSV file
     */
    private static function import_csv($csv_file, $photos_folder, $create_users, $update_existing) {
        $stats = array(
            'created' => 0,
            'updated' => 0,
            'photos' => 0,
            'users' => 0
        );
        $errors = array();

        // Parse CSV
        $csv_data = array();
        if (($handle = fopen($csv_file, 'r')) !== false) {
            // Read first line and remove BOM if present
            $headers = fgetcsv($handle);

            if (!empty($headers)) {
                // Remove BOM from first header
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

                // Clean all headers
                $headers = array_map('trim', $headers);
            }

            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Ensure row has same number of columns as headers
                if (count($row) === count($headers)) {
                    $csv_data[] = array_combine($headers, $row);
                } else {
                    $errors[] = 'Строка имеет неправильное количество колонок: ' . implode(',', array_slice($row, 0, 3));
                }
            }

            fclose($handle);
        }

        // Check if we have data
        if (empty($csv_data)) {
            $errors[] = 'CSV файл пуст или имеет неправильный формат';
            return array(
                'stats' => $stats,
                'errors' => $errors
            );
        }

        // Get photos directory
        $photos_dir = plugin_dir_path(dirname(__FILE__)) . $photos_folder;

        // Process each row
        foreach ($csv_data as $index => $row) {
            try {
                $result = self::import_member($row, $photos_dir, $create_users, $update_existing);

                if ($result['created']) {
                    $stats['created']++;
                } elseif ($result['updated']) {
                    $stats['updated']++;
                }

                if ($result['photo']) {
                    $stats['photos']++;
                }

                if ($result['user']) {
                    $stats['users']++;
                }

                if (!empty($result['warnings'])) {
                    $errors = array_merge($errors, $result['warnings']);
                }

            } catch (Exception $e) {
                $errors[] = sprintf('Строка %d (%s): %s', $index + 2, $row['post_title'], $e->getMessage());
            }
        }

        return array(
            'stats' => $stats,
            'errors' => $errors
        );
    }

    /**
     * Import single member
     */
    private static function import_member($data, $photos_dir, $create_users, $update_existing) {
        $result = array(
            'created' => false,
            'updated' => false,
            'photo' => false,
            'user' => false,
            'warnings' => array()
        );

        // Validate required field
        if (empty($data['post_title'])) {
            $result['warnings'][] = 'Пропущена строка: отсутствует post_title';
            return $result;
        }

        // Check if member exists
        $existing_member = get_page_by_title($data['post_title'], OBJECT, 'members');

        if ($existing_member && !$update_existing) {
            $result['warnings'][] = sprintf('Участник "%s" уже существует (пропущен)', $data['post_title']);
            return $result;
        }

        // Prepare post data
        $post_data = array(
            'post_type' => 'members',
            'post_title' => sanitize_text_field($data['post_title']),
            'post_status' => 'publish',
            'post_content' => wp_kses_post(isset($data['post_content']) ? $data['post_content'] : '')
        );

        // Create or update post
        if ($existing_member) {
            $post_data['ID'] = $existing_member->ID;
            $member_id = wp_update_post($post_data);
            $result['updated'] = true;
        } else {
            $member_id = wp_insert_post($post_data);
            $result['created'] = true;
        }

        if (is_wp_error($member_id)) {
            throw new Exception('Не удалось создать запись: ' . $member_id->get_error_message());
        }

        // Save simple text meta fields
        $text_fields = array(
            'member_position',
            'member_company',
            'member_city',
            'member_email',
            'member_phone'
        );

        foreach ($text_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                update_post_meta($member_id, $field, sanitize_text_field($data[$field]));
            }
        }

        // Save textarea fields (with line breaks preserved)
        $textarea_fields = array(
            'member_specialization_experience',
            'member_professional_interests'
        );

        foreach ($textarea_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                // Сохраняем с разделителем | (будем делать explode в шаблоне)
                // Используем wp_kses для удаления опасных тегов, но сохраняем символ |
                $value = wp_kses($data[$field], array());
                update_post_meta($member_id, $field, $value);
            }
        }

        // Save HTML/WYSIWYG fields
        $html_fields = array(
            'member_expectations',
            'member_bio'
        );

        foreach ($html_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                // Заменяем <br> на переносы для лучшего отображения
                $value = str_replace('<br>', "\n", $data[$field]);
                update_post_meta($member_id, $field, wp_kses_post($value));
            }
        }

        // Handle taxonomies
        if (!empty($data['taxonomy_member_type'])) {
            self::set_taxonomy_terms($member_id, 'member_type', $data['taxonomy_member_type']);
        }

        if (!empty($data['taxonomy_member_roles'])) {
            self::set_taxonomy_terms($member_id, 'member_role', $data['taxonomy_member_roles']);
        }

        if (!empty($data['taxonomy_member_location'])) {
            self::set_taxonomy_terms($member_id, 'member_location', $data['taxonomy_member_location']);
        }

        // Try to attach photo
        if (is_dir($photos_dir)) {
            $photo_attached = self::attach_photo($member_id, $data['post_title'], $photos_dir);
            if ($photo_attached) {
                $result['photo'] = true;
            }
        }

        // Create WordPress user if needed
        if ($create_users && !empty($data['member_email'])) {
            $user_created = self::create_wp_user($member_id, $data['post_title'], $data['member_email']);
            if ($user_created) {
                $result['user'] = true;
            }
        }

        return $result;
    }

    /**
     * Set taxonomy terms (handles multiple terms separated by |)
     */
    private static function set_taxonomy_terms($post_id, $taxonomy, $terms_string) {
        $terms = array_map('trim', explode('|', $terms_string));
        $term_ids = array();

        foreach ($terms as $term_name) {
            if (empty($term_name)) continue;

            $term = get_term_by('name', $term_name, $taxonomy);

            if (!$term) {
                $term = wp_insert_term($term_name, $taxonomy);
                if (!is_wp_error($term)) {
                    $term_ids[] = $term['term_id'];
                }
            } else {
                $term_ids[] = $term->term_id;
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($post_id, $term_ids, $taxonomy);
        }
    }

    /**
     * Attach photo to member
     */
    private static function attach_photo($member_id, $member_name, $photos_dir) {
        // Try different extensions
        $extensions = array('jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG');
        $photo_path = null;

        foreach ($extensions as $ext) {
            $path = $photos_dir . '/' . $member_name . '.' . $ext;
            if (file_exists($path)) {
                $photo_path = $path;
                break;
            }
        }

        if (!$photo_path) {
            return false;
        }

        // Upload to media library
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_upload_bits(basename($photo_path), null, file_get_contents($photo_path));

        if ($upload['error']) {
            return false;
        }

        $attachment = array(
            'post_mime_type' => wp_check_filetype($upload['file'])['type'],
            'post_title' => $member_name,
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file'], $member_id);

        if (is_wp_error($attach_id)) {
            return false;
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        set_post_thumbnail($member_id, $attach_id);

        return true;
    }

    /**
     * Create WordPress user for member
     */
    private static function create_wp_user($member_id, $member_name, $email) {
        // Check if user already exists
        if (email_exists($email)) {
            $user = get_user_by('email', $email);
            update_post_meta($member_id, 'member_user_id', $user->ID);
            return false;
        }

        // Generate username
        $username = self::transliterate($member_name);
        $username = strtolower($username);
        $username = preg_replace('/[^a-z0-9._-]/', '', $username);

        // Ensure unique username
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }

        // Generate password
        $password = wp_generate_password(12, true, false);

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return false;
        }

        // Set role
        $user = new WP_User($user_id);
        $user->set_role('member');

        // Link to member post
        update_post_meta($member_id, 'member_user_id', $user_id);
        update_user_meta($user_id, 'member_post_id', $member_id);

        return true;
    }

    /**
     * Transliterate Cyrillic to Latin
     */
    private static function transliterate($text) {
        $transliteration = array(
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya', ' ' => '_'
        );

        return strtr($text, $transliteration);
    }
}

Member_CSV_Importer::init();
