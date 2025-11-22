<?php
/**
 * AJAX Handlers for Members
 *
 * Handles all AJAX requests for member functionality
 *
 * @package Metoda_Members
 * @subpackage Ajax
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Ajax_Members
 *
 * Handles all AJAX requests related to members
 */
class Metoda_Ajax_Members {

    /**
     * Constructor - registers all AJAX hooks
     */
    public function __construct() {
        // Private AJAX handlers (logged-in users only)
        add_action('wp_ajax_dismiss_image_crop_notice', array($this, 'dismiss_image_crop_notice'));
        add_action('wp_ajax_manager_change_member_status', array($this, 'manager_change_member_status'));
        add_action('wp_ajax_member_save_gallery', array($this, 'member_save_gallery'));
        add_action('wp_ajax_member_upload_gallery_photo', array($this, 'member_upload_gallery_photo'));
        add_action('wp_ajax_member_add_material_link', array($this, 'member_add_material_link'));
        add_action('wp_ajax_member_add_material_file', array($this, 'member_add_material_file'));
        add_action('wp_ajax_add_portfolio_material', array($this, 'add_portfolio_material'));
        add_action('wp_ajax_delete_portfolio_material', array($this, 'delete_portfolio_material'));
        add_action('wp_ajax_edit_portfolio_material', array($this, 'edit_portfolio_material'));
        add_action('wp_ajax_create_forum_topic_dashboard', array($this, 'create_forum_topic_dashboard'));
        add_action('wp_ajax_view_member_message', array($this, 'view_member_message'));

        // Notification system handlers
        add_action('wp_ajax_save_notification_settings', array($this, 'save_notification_settings'));
        add_action('wp_ajax_check_telegram_connection', array($this, 'check_telegram_connection'));
        add_action('wp_ajax_send_test_notification', array($this, 'send_test_notification'));
        add_action('wp_ajax_disconnect_telegram', array($this, 'disconnect_telegram'));

        // Public AJAX handlers (available to non-logged-in users)
        add_action('wp_ajax_filter_members', array($this, 'filter_members'));
        add_action('wp_ajax_nopriv_filter_members', array($this, 'filter_members'));

        add_action('wp_ajax_member_register', array($this, 'member_register'));
        add_action('wp_ajax_nopriv_member_register', array($this, 'member_register'));

        add_action('wp_ajax_load_more_members', array($this, 'load_more_members'));
        add_action('wp_ajax_nopriv_load_more_members', array($this, 'load_more_members'));

        add_action('wp_ajax_filter_members_v2', array($this, 'filter_members_v2'));
        add_action('wp_ajax_nopriv_filter_members_v2', array($this, 'filter_members_v2'));

        add_action('wp_ajax_send_member_message', array($this, 'send_member_message'));
        add_action('wp_ajax_nopriv_send_member_message', array($this, 'send_member_message'));
    }

    /**
     * Dismiss image crop notice
     *
     * @since 5.0.0
     */
    public function dismiss_image_crop_notice() {
        check_ajax_referer('dismiss_image_crop_notice', 'nonce');

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'dismissed_image_crop_notice', true);

        wp_send_json_success();
    }

    /**
     * Filter members (AJAX handler)
     *
     * @since 5.0.0
     */
    public function filter_members() {
        // Проверка nonce
        check_ajax_referer('public_members_nonce', 'nonce');

        // Получаем параметры фильтрации
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $roles = isset($_POST['roles']) ? array_map('sanitize_text_field', $_POST['roles']) : array();
        $member_type = isset($_POST['member_type']) ? sanitize_text_field($_POST['member_type']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'title-asc';
        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;

        // Определяем сортировку
        $orderby = 'title';
        $order = 'ASC';

        switch ($sort) {
            case 'title-desc':
                $orderby = 'title';
                $order = 'DESC';
                break;
            case 'date-desc':
                $orderby = 'date';
                $order = 'DESC';
                break;
            case 'date-asc':
                $orderby = 'date';
                $order = 'ASC';
                break;
        }

        // Формируем запрос
        $args = array(
            'post_type' => 'members',
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => $orderby,
            'order' => $order
        );

        // Добавляем поиск
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Добавляем фильтр по городу
        if (!empty($city)) {
            $args['meta_query'][] = array(
                'key' => 'member_city',
                'value' => $city,
                'compare' => 'LIKE'
            );
        }

        // Добавляем фильтр по ролям
        if (!empty($roles)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_role',
                'field' => 'slug',
                'terms' => $roles,
                'operator' => 'IN'
            );
        }

        // Добавляем фильтр по типу участника
        if (!empty($member_type)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'member_type',
                'field' => 'slug',
                'terms' => $member_type
            );
        }

        $query = new WP_Query($args);

        // Генерируем HTML карточек
        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) : $query->the_post();
                $member_id = get_the_ID();
                $position = get_post_meta($member_id, 'member_position', true);
                $company = get_post_meta($member_id, 'member_company', true);
                $city_meta = get_post_meta($member_id, 'member_city', true);
                $roles_terms = wp_get_post_terms($member_id, 'member_role');
                ?>
                <article class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                    <a href="<?php the_permalink(); ?>" class="flex items-start gap-4">
                        <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('thumbnail', array('class' => 'w-full h-full object-cover')); ?>
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-300">
                                    <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 mb-1 truncate"><?php the_title(); ?></h3>

                            <?php if ($position): ?>
                            <p class="text-sm text-gray-600 mb-1"><?php echo esc_html($position); ?></p>
                            <?php endif; ?>

                            <?php if ($company): ?>
                            <p class="text-sm font-medium text-gray-700 mb-3"><?php echo esc_html($company); ?></p>
                            <?php endif; ?>

                            <?php if ($city_meta): ?>
                            <div class="flex items-center text-sm text-gray-500 mb-3">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                <span><?php echo esc_html($city_meta); ?></span>
                            </div>
                            <?php endif; ?>

                            <?php if ($roles_terms && !is_wp_error($roles_terms)): ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (array_slice($roles_terms, 0, 3) as $role): ?>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                                    <?php echo esc_html($role->name); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
                <?php
            endwhile;
        } else {
            ?>
            <div class="col-span-2 bg-white rounded-xl shadow-sm border p-12 text-center">
                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Участники не найдены</h3>
                <p class="text-gray-600">Попробуйте изменить параметры поиска</p>
            </div>
            <?php
        }

        $html = ob_get_clean();

        // Генерируем пагинацию
        $pagination = '';
        if ($query->max_num_pages > 1) {
            ob_start();
            ?>
            <div class="flex justify-center items-center space-x-2 mt-8">
                <?php if ($paged > 1): ?>
                <a href="#" data-page="<?php echo ($paged - 1); ?>" class="pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $query->max_num_pages; $i++): ?>
                    <?php if ($i == $paged): ?>
                    <span class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="#" data-page="<?php echo $i; ?>" class="pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($paged < $query->max_num_pages): ?>
                <a href="#" data-page="<?php echo ($paged + 1); ?>" class="pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php
            $pagination = ob_get_clean();
        }

        wp_reset_postdata();

        // Возвращаем результат
        wp_send_json_success(array(
            'html' => $html,
            'found' => $query->found_posts,
            'pagination' => $pagination,
            'max_pages' => $query->max_num_pages
        ));
    }

    /**
     * Member registration (AJAX handler)
     *
     * @since 5.0.0
     */
    public function member_register() {
        check_ajax_referer('member_registration_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        // Валидация пароля
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Пароль должен содержать не менее 8 символов'));
        }

        // Дополнительная проверка на слабый пароль (опционально)
        if (preg_match('/^[0-9]+$/', $password)) {
            wp_send_json_error(array('message' => 'Пароль не должен состоять только из цифр'));
        }

        $fullname = sanitize_text_field($_POST['fullname']);
        $account_type = sanitize_text_field($_POST['account_type']);
        $company = sanitize_text_field($_POST['company']);
        $position = sanitize_text_field($_POST['position']);
        $city = sanitize_text_field($_POST['city']);
        $roles = sanitize_text_field($_POST['roles']);
        $specializations = sanitize_textarea_field($_POST['specializations']);
        $interests = sanitize_textarea_field($_POST['interests']);
        $bio = wp_kses_post($_POST['bio']);
        $expectations = wp_kses_post($_POST['expectations']);
        $access_code = isset($_POST['access_code']) ? sanitize_text_field($_POST['access_code']) : '';

        // Проверка email
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Этот email уже зарегистрирован'));
        }

        // Создаем пользователя WordPress
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Устанавливаем роль
        $user = new WP_User($user_id);
        $user->set_role($account_type); // member or expert

        // Проверяем наличие кода доступа
        $member_id = null;
        $is_claimed_profile = false;

        if (!empty($access_code)) {
            // Ищем профиль по коду доступа
            $existing_member = Member_Access_Codes::find_member_by_code($access_code);

            if ($existing_member) {
                // Проверяем, не занят ли профиль
                $linked_user = get_post_meta($existing_member->ID, '_linked_user_id', true);

                if ($linked_user) {
                    wp_delete_user($user_id);
                    wp_send_json_error(array('message' => 'Этот код доступа уже активирован'));
                }

                // Используем существующий профиль
                $member_id = $existing_member->ID;
                $is_claimed_profile = true;

                // Обновляем существующий профиль новой информацией (опционально, если пользователь заполнил дополнительные данные)
                if (!empty($company)) {
                    update_post_meta($member_id, 'member_company', $company);
                }
                if (!empty($position)) {
                    update_post_meta($member_id, 'member_position', $position);
                }
                if (!empty($city)) {
                    update_post_meta($member_id, 'member_city', $city);
                }
            } else {
                // Код неверный
                wp_delete_user($user_id);
                wp_send_json_error(array('message' => 'Неверный код доступа'));
            }
        }

        // Если код не указан или не найден - создаем новый профиль
        if (!$member_id) {
            $member_id = wp_insert_post(array(
                'post_title' => $fullname,
                'post_type' => 'members',
                'post_status' => 'publish',
                'post_author' => $user_id
            ));

            if (is_wp_error($member_id)) {
                wp_delete_user($user_id);
                wp_send_json_error(array('message' => 'Ошибка создания профиля'));
            }

            // Сохраняем метаданные для нового профиля
            update_post_meta($member_id, 'member_company', $company);
            update_post_meta($member_id, 'member_position', $position);
            update_post_meta($member_id, 'member_city', $city);
            update_post_meta($member_id, 'member_email', $email);
        }

        // Сохраняем общие метаданные (для обоих случаев)
        update_post_meta($member_id, 'member_specialization_experience', $specializations);
        update_post_meta($member_id, 'member_professional_interests', $interests);
        update_post_meta($member_id, 'member_bio', $bio);
        update_post_meta($member_id, 'member_expectations', $expectations);

        // Связываем пользователя с участником
        update_post_meta($member_id, '_linked_user_id', $user_id);
        update_user_meta($user_id, 'member_id', $member_id);

        // Добавляем роли
        if (!empty($roles)) {
            $role_slugs = array_map('sanitize_title', explode(',', $roles));
            wp_set_object_terms($member_id, $role_slugs, 'member_role');
        }

        // Автоматический вход
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // Отправка email уведомлений
        do_action('metoda_member_registered', $user_id, $member_id, $is_claimed_profile);

        // Если профиль был активирован по коду доступа
        if ($is_claimed_profile && !empty($access_code)) {
            do_action('metoda_profile_activated', $user_id, $member_id, $access_code);
        }

        $message = $is_claimed_profile
            ? 'Регистрация завершена! Ваш профиль успешно активирован.'
            : 'Регистрация успешно завершена!';

        wp_send_json_success(array(
            'message' => $message,
            'redirect' => home_url('/member-dashboard/')
        ));
    }

    /**
     * Manager change member status
     *
     * @since 5.0.0
     */
    public function manager_change_member_status() {
        check_ajax_referer('manager_actions_nonce', 'nonce');

        if (!current_user_can('manager') && !current_user_can('administrator')) {
            wp_send_json_error(array('message' => 'Нет прав доступа'));
        }

        $member_id = intval($_POST['member_id']);
        $status = sanitize_text_field($_POST['status']);

        if (!in_array($status, array('publish', 'pending', 'draft'))) {
            wp_send_json_error(array('message' => 'Некорректный статус'));
        }

        $result = wp_update_post(array(
            'ID' => $member_id,
            'post_status' => $status
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Ошибка при изменении статуса'));
        }

        $status_labels = array(
            'publish' => 'одобрен',
            'pending' => 'отправлен на модерацию',
            'draft' => 'переведен в черновики'
        );

        wp_send_json_success(array(
            'message' => 'Участник ' . $status_labels[$status]
        ));
    }

    /**
     * Save member gallery
     *
     * @since 5.0.0
     */
    public function member_save_gallery() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $gallery_ids = sanitize_text_field($_POST['gallery_ids']);

        // Сохраняем IDs изображений галереи
        update_post_meta($member_id, 'member_gallery', $gallery_ids);

        wp_send_json_success(array(
            'message' => 'Галерея успешно сохранена!'
        ));
    }

    /**
     * Upload gallery photo
     *
     * @since 5.0.0
     */
    public function member_upload_gallery_photo() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        // Проверяем, был ли загружен файл
        if (empty($_FILES['photo'])) {
            wp_send_json_error(array('message' => 'Файл не загружен'));
        }

        // SECURITY FIX v3.7.3: Валидация типа файла и размера
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif');
        $file_type = $_FILES['photo']['type'];

        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error(array('message' => 'Недопустимый тип файла. Разрешены только изображения (JPEG, PNG, WebP, GIF)'));
        }

        // Проверка размера файла (максимум 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB в байтах
        if ($_FILES['photo']['size'] > $max_size) {
            wp_send_json_error(array('message' => 'Файл слишком большой. Максимальный размер: 5MB'));
        }

        // Дополнительная проверка на реальный MIME-тип (защита от подмены расширения)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $real_mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($real_mime, $allowed_types)) {
            wp_send_json_error(array('message' => 'Обнаружена попытка загрузки файла с поддельным типом'));
        }

        // Подключаем необходимые файлы WordPress
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Загружаем файл в медиабиблиотеку
        $attachment_id = media_handle_upload('photo', $member_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => 'Ошибка загрузки файла: ' . $attachment_id->get_error_message()));
        }

        // Получаем URL миниатюры
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'medium');

        // Получаем текущие ID галереи
        $current_gallery = get_post_meta($member_id, 'member_gallery', true);
        $gallery_ids = !empty($current_gallery) ? explode(',', $current_gallery) : array();

        // Добавляем новое фото
        $gallery_ids[] = $attachment_id;

        // Сохраняем обновленную галерею
        update_post_meta($member_id, 'member_gallery', implode(',', $gallery_ids));

        wp_send_json_success(array(
            'message' => 'Фото успешно загружено!',
            'attachment_id' => $attachment_id,
            'thumbnail_url' => $thumbnail_url
        ));
    }

    /**
     * Add material link
     *
     * @since 5.0.0
     */
    public function member_add_material_link() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Проверяем, редактирует ли админ чужой профиль
        $is_admin = current_user_can('administrator');
        $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

        if ($is_admin && $editing_member_id) {
            $member_post = get_post($editing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
            $member_id = $editing_member_id;
        } else {
            $member_id = Member_User_Link::get_current_user_member_id();
            if (!$member_id) {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
        }

        $category = sanitize_text_field($_POST['category']);
        $title = sanitize_text_field($_POST['title']);
        $url = esc_url_raw($_POST['url']);
        $description = sanitize_textarea_field($_POST['description']);

        // Получаем текущие материалы
        $current_materials = get_post_meta($member_id, 'member_' . $category, true);

        // Создаем новую запись материала
        $new_material = sprintf(
            "[LINK|%s|%s|%s|%s]",
            $title,
            $url,
            $description,
            current_time('Y-m-d H:i:s')
        );

        // Добавляем новый материал
        if (empty($current_materials)) {
            $updated_materials = $new_material;
        } else {
            $updated_materials = $current_materials . "\n" . $new_material;
        }

        update_post_meta($member_id, 'member_' . $category, $updated_materials);

        wp_send_json_success(array(
            'message' => 'Ссылка успешно добавлена!',
            'reload' => true
        ));
    }

    /**
     * Add material file
     *
     * @since 5.0.0
     */
    public function member_add_material_file() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Проверяем, редактирует ли админ чужой профиль
        $is_admin = current_user_can('administrator');
        $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

        if ($is_admin && $editing_member_id) {
            $member_post = get_post($editing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
            $member_id = $editing_member_id;
        } else {
            $member_id = Member_User_Link::get_current_user_member_id();
            if (!$member_id) {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
        }

        // Проверяем, был ли загружен файл
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'Файл не загружен'));
        }

        $category = sanitize_text_field($_POST['category']);
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);

        // Загружаем файл в медиабиблиотеку
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('file', $member_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => 'Ошибка загрузки файла: ' . $attachment_id->get_error_message()));
        }

        $file_url = wp_get_attachment_url($attachment_id);

        // Получаем текущие материалы
        $current_materials = get_post_meta($member_id, 'member_' . $category, true);

        // Создаем новую запись материала
        $new_material = sprintf(
            "[FILE|%s|%s|%s|%s]",
            $title,
            $file_url,
            $description,
            current_time('Y-m-d H:i:s')
        );

        // Добавляем новый материал
        if (empty($current_materials)) {
            $updated_materials = $new_material;
        } else {
            $updated_materials = $current_materials . "\n" . $new_material;
        }

        update_post_meta($member_id, 'member_' . $category, $updated_materials);

        wp_send_json_success(array(
            'message' => 'Файл успешно загружен!',
            'reload' => true
        ));
    }

    /**
     * Load more members (AJAX handler)
     *
     * @since 5.0.0
     */
    public function load_more_members() {
        // CSRF protection - публичный nonce
        check_ajax_referer('public_members_nonce', 'nonce');

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $type_filter = isset($_POST['member_type']) ? sanitize_text_field($_POST['member_type']) : '';

        $posts_per_page = 12;

        // Если нет фильтра по типу - делаем два отдельных запроса и объединяем
        if (empty($type_filter)) {
            // Запрос для экспертов
            $experts_args = array(
                'post_type' => 'members',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'member_type',
                        'field' => 'slug',
                        'terms' => 'ekspert'
                    )
                )
            );

            if (!empty($search)) {
                $experts_args['s'] = $search;
            }
            if (!empty($city)) {
                $experts_args['meta_query'][] = array(
                    'key' => 'member_city',
                    'value' => $city,
                    'compare' => 'LIKE'
                );
            }
            if (!empty($role)) {
                $experts_args['tax_query'][] = array(
                    'taxonomy' => 'member_role',
                    'field' => 'slug',
                    'terms' => $role
                );
            }

            $experts_query = new WP_Query($experts_args);

            // Запрос для участников
            $members_args = array(
                'post_type' => 'members',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'member_type',
                        'field' => 'slug',
                        'terms' => 'uchastnik'
                    )
                )
            );

            if (!empty($search)) {
                $members_args['s'] = $search;
            }
            if (!empty($city)) {
                $members_args['meta_query'][] = array(
                    'key' => 'member_city',
                    'value' => $city,
                    'compare' => 'LIKE'
                );
            }
            if (!empty($role)) {
                $members_args['tax_query'][] = array(
                    'taxonomy' => 'member_role',
                    'field' => 'slug',
                    'terms' => $role
                );
            }

            $members_query = new WP_Query($members_args);

            // Объединяем результаты
            $all_members = array_merge($experts_query->posts, $members_query->posts);

            // Берем порцию с offset
            $paged_members = array_slice($all_members, $offset, $posts_per_page);

        } else {
            // Если выбран конкретный тип
            $args = array(
                'post_type' => 'members',
                'posts_per_page' => $posts_per_page,
                'offset' => $offset,
                'orderby' => 'title',
                'order' => 'ASC'
            );

            if (!empty($search)) {
                $args['s'] = $search;
            }
            if (!empty($city)) {
                $args['meta_query'][] = array(
                    'key' => 'member_city',
                    'value' => $city,
                    'compare' => 'LIKE'
                );
            }

            $args['tax_query'] = array();
            if (!empty($type_filter)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => $type_filter
                );
            }
            if (!empty($role)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'member_role',
                    'field' => 'slug',
                    'terms' => $role
                );
            }

            $members_query = new WP_Query($args);
            $paged_members = $members_query->posts;
        }

        // Генерируем HTML для карточек
        ob_start();
        foreach ($paged_members as $post) {
            setup_postdata($post);
            $member_id = $post->ID;
            include(plugin_dir_path(__FILE__) . 'templates/member-card.php');
        }
        wp_reset_postdata();
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'count' => count($paged_members)
        ));
    }

    /**
     * Filter members v2 (AJAX handler)
     *
     * @since 5.0.0
     */
    public function filter_members_v2() {
        // CSRF protection - публичный nonce
        check_ajax_referer('public_members_nonce', 'nonce');

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';
        $type_filter = isset($_POST['member_type']) ? sanitize_text_field($_POST['member_type']) : '';

        $posts_per_page = 12;

        // Если нет фильтра по типу - делаем два отдельных запроса и объединяем
        if (empty($type_filter)) {
            // Запрос для экспертов
            $experts_args = array(
                'post_type' => 'members',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'member_type',
                        'field' => 'slug',
                        'terms' => 'ekspert'
                    )
                )
            );

            if (!empty($search)) {
                $experts_args['s'] = $search;
            }
            if (!empty($city)) {
                $experts_args['meta_query'][] = array(
                    'key' => 'member_city',
                    'value' => $city,
                    'compare' => 'LIKE'
                );
            }
            if (!empty($role)) {
                $experts_args['tax_query'][] = array(
                    'taxonomy' => 'member_role',
                    'field' => 'slug',
                    'terms' => $role
                );
            }

            $experts_query = new WP_Query($experts_args);

            // Запрос для участников
            $members_args = array(
                'post_type' => 'members',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'member_type',
                        'field' => 'slug',
                        'terms' => 'uchastnik'
                    )
                )
            );

            if (!empty($search)) {
                $members_args['s'] = $search;
            }
            if (!empty($city)) {
                $members_args['meta_query'][] = array(
                    'key' => 'member_city',
                    'value' => $city,
                    'compare' => 'LIKE'
                );
            }
            if (!empty($role)) {
                $members_args['tax_query'][] = array(
                    'taxonomy' => 'member_role',
                    'field' => 'slug',
                    'terms' => $role
                );
            }

            $members_query = new WP_Query($members_args);

            // Объединяем результаты
            $all_members = array_merge($experts_query->posts, $members_query->posts);
            $total_found = count($all_members);

            // Берем первые N
            $paged_members = array_slice($all_members, 0, $posts_per_page);

        } else {
            // Если выбран конкретный тип
            $args = array(
                'post_type' => 'members',
                'posts_per_page' => $posts_per_page,
                'orderby' => 'title',
                'order' => 'ASC'
            );

            if (!empty($search)) {
                $args['s'] = $search;
            }
            if (!empty($city)) {
                $args['meta_query'][] = array(
                    'key' => 'member_city',
                    'value' => $city,
                    'compare' => 'LIKE'
                );
            }

            $args['tax_query'] = array();
            if (!empty($type_filter)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'member_type',
                    'field' => 'slug',
                    'terms' => $type_filter
                );
            }
            if (!empty($role)) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'member_role',
                    'field' => 'slug',
                    'terms' => $role
                );
            }

            $members_query = new WP_Query($args);
            $paged_members = $members_query->posts;
            $total_found = $members_query->found_posts;
        }

        // Генерируем HTML для карточек
        ob_start();
        foreach ($paged_members as $post) {
            setup_postdata($post);
            $member_id = $post->ID;
            include(plugin_dir_path(__FILE__) . 'templates/member-card.php');
        }
        wp_reset_postdata();
        $html = ob_get_clean();

        error_log('Sending JSON response: shown=' . count($paged_members) . ', total=' . $total_found);

        wp_send_json_success(array(
            'html' => $html,
            'shown' => count($paged_members),
            'total' => $total_found,
            'has_more' => $total_found > count($paged_members)
        ));

        exit; // Принудительно завершаем выполнение
    }

    /**
     * Add portfolio material
     *
     * @since 5.0.0
     */
    public function add_portfolio_material() {
        // Проверка nonce
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        // Проверяем, редактирует ли админ чужой профиль
        $is_admin = current_user_can('administrator');
        $editing_member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : null;

        if ($is_admin && $editing_member_id) {
            $member_post = get_post($editing_member_id);
            if (!$member_post || $member_post->post_type !== 'members') {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
            $member_id = $editing_member_id;
        } else {
            $member_id = Member_User_Link::get_current_user_member_id();
            if (!$member_id) {
                wp_send_json_error(array('message' => 'Участник не найден'));
            }
        }

        if (!$member_id) {
            wp_send_json_error(array('message' => 'Участник не найден'));
        }

        $category = sanitize_text_field($_POST['category']);
        $material_type = sanitize_text_field($_POST['material_type']);

        // Валидируем категорию
        $valid_categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
        if (!in_array($category, $valid_categories)) {
            wp_send_json_error(array('message' => 'Неверная категория'));
        }

        // Получаем текущие данные
        $field_name = 'member_' . $category . '_data';
        $current_data = get_post_meta($member_id, $field_name, true);
        $data_array = $current_data ? json_decode($current_data, true) : array();

        // Собираем новый материал
        $new_material = array(
            'type' => $material_type,
            'title' => sanitize_text_field($_POST['title']),
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
            'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
            'file_id' => 0,
            'author' => isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '',
            'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        );

        // Обработка загрузки файла
        if ($material_type === 'file' && !empty($_FILES['file'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_id = media_handle_upload('file', $member_id);

            if (is_wp_error($file_id)) {
                wp_send_json_error(array('message' => 'Ошибка загрузки файла: ' . $file_id->get_error_message()));
            }

            $new_material['file_id'] = $file_id;
            $new_material['url'] = wp_get_attachment_url($file_id);
        }

        // Добавляем новый материал
        $data_array[] = $new_material;

        // Сохраняем
        update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

        wp_send_json_success(array(
            'message' => 'Материал успешно добавлен!',
            'reload' => true
        ));
    }

    /**
     * Delete portfolio material
     *
     * @since 5.0.0
     */
    public function delete_portfolio_material() {
        // Проверка nonce
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $category = sanitize_text_field($_POST['category']);
        $index = intval($_POST['index']);

        // Валидируем категорию
        $valid_categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
        if (!in_array($category, $valid_categories)) {
            wp_send_json_error(array('message' => 'Неверная категория'));
        }

        // Получаем текущие данные
        $field_name = 'member_' . $category . '_data';
        $current_data = get_post_meta($member_id, $field_name, true);
        $data_array = $current_data ? json_decode($current_data, true) : array();

        // Проверяем что элемент существует
        if (!isset($data_array[$index])) {
            wp_send_json_error(array('message' => 'Материал не найден'));
        }

        // Удаляем файл если это был файл
        if (isset($data_array[$index]['type']) && $data_array[$index]['type'] === 'file' && isset($data_array[$index]['file_id'])) {
            wp_delete_attachment($data_array[$index]['file_id'], true);
        }

        // Удаляем элемент
        unset($data_array[$index]);
        $data_array = array_values($data_array); // Переиндексируем массив

        // Сохраняем
        update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

        wp_send_json_success(array(
            'message' => 'Материал успешно удален!',
            'reload' => true
        ));
    }

    /**
     * Edit portfolio material
     *
     * @since 5.0.0
     */
    public function edit_portfolio_material() {
        // Проверка nonce
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        // SECURITY FIX v3.7.3: Используем единую функцию проверки прав (поддержка admin bypass)
        $member_id = get_editable_member_id();
        if (is_wp_error($member_id)) {
            wp_send_json_error(array('message' => $member_id->get_error_message()));
        }

        $category = sanitize_text_field($_POST['category']);
        $index = intval($_POST['index']);
        $material_type = sanitize_text_field($_POST['material_type']);

        // Валидируем категорию
        $valid_categories = array('testimonials', 'gratitudes', 'interviews', 'videos', 'reviews', 'developments');
        if (!in_array($category, $valid_categories)) {
            wp_send_json_error(array('message' => 'Неверная категория'));
        }

        // Получаем текущие данные
        $field_name = 'member_' . $category . '_data';
        $current_data = get_post_meta($member_id, $field_name, true);
        $data_array = $current_data ? json_decode($current_data, true) : array();

        // Проверяем что элемент существует
        if (!isset($data_array[$index])) {
            wp_send_json_error(array('message' => 'Материал не найден'));
        }

        // Обновляем данные материала (сохраняем file_id если был файл)
        $updated_material = array(
            'type' => $material_type,
            'title' => sanitize_text_field($_POST['title']),
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : '',
            'url' => isset($_POST['url']) ? esc_url_raw($_POST['url']) : '',
            'file_id' => isset($data_array[$index]['file_id']) ? $data_array[$index]['file_id'] : 0,
            'author' => isset($_POST['author']) ? sanitize_text_field($_POST['author']) : '',
            'date' => isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        );

        // Если это файл, сохраняем URL из старых данных
        if ($material_type === 'file' && isset($data_array[$index]['url'])) {
            $updated_material['url'] = $data_array[$index]['url'];
        }

        // Заменяем элемент
        $data_array[$index] = $updated_material;

        // Сохраняем
        update_post_meta($member_id, $field_name, wp_json_encode($data_array, JSON_UNESCAPED_UNICODE));

        wp_send_json_success(array(
            'message' => 'Материал успешно обновлен!',
            'reload' => true
        ));
    }

    /**
     * Create forum topic from dashboard
     *
     * @since 5.0.0
     */
    public function create_forum_topic_dashboard() {
        // Проверка nonce
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо войти в систему'));
        }

        $title = sanitize_text_field($_POST['title']);
        $content = wp_kses_post($_POST['content']);
        $category_id = !empty($_POST['category']) ? intval($_POST['category']) : 0;

        if (empty($title) || empty($content)) {
            wp_send_json_error(array('message' => 'Заполните все обязательные поля'));
        }

        // Создаем новую тему
        $topic_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_type' => 'forum_topic',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        );

        $topic_id = wp_insert_post($topic_data);

        if (is_wp_error($topic_id)) {
            wp_send_json_error(array('message' => 'Ошибка создания темы: ' . $topic_id->get_error_message()));
        }

        // Устанавливаем категорию если указана
        if ($category_id > 0) {
            wp_set_post_terms($topic_id, array($category_id), 'forum_category');
        }

        // Инициализируем счетчики
        update_post_meta($topic_id, 'views_count', 0);

        wp_send_json_success(array(
            'message' => 'Тема успешно создана!',
            'url' => get_permalink($topic_id),
            'reload' => true
        ));
    }

    /**
     * Send member message
     *
     * @since 5.0.0
     */
    public function send_member_message() {
        // Проверка nonce
        check_ajax_referer('send_member_message', 'nonce');

        // Honeypot check (антиспам)
        if (!empty($_POST['website'])) {
            wp_send_json_error(array('message' => 'Обнаружена подозрительная активность'));
        }

        $is_logged_in = is_user_logged_in();
        $recipient_member_id = intval($_POST['recipient_id']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = wp_kses_post($_POST['content']);

        // Данные отправителя
        if ($is_logged_in) {
            $sender_user_id = get_current_user_id();
            $sender_member_id = Member_User_Link::get_current_user_member_id();
            $sender_name = get_the_title($sender_member_id);
            $sender_email = wp_get_current_user()->user_email;
        } else {
            // Для незалогиненных - получаем из формы
            $sender_user_id = 0;
            $sender_member_id = 0;
            $sender_name = sanitize_text_field($_POST['sender_name']);
            $sender_email = sanitize_email($_POST['sender_email']);

            // Валидация для незалогиненных
            if (empty($sender_name) || empty($sender_email)) {
                wp_send_json_error(array('message' => 'Укажите ваше имя и email'));
            }

            if (!is_email($sender_email)) {
                wp_send_json_error(array('message' => 'Укажите корректный email'));
            }
        }

        // Валидация
        if (empty($subject) || empty($content)) {
            wp_send_json_error(array('message' => 'Заполните все обязательные поля'));
        }

        if (empty($recipient_member_id)) {
            wp_send_json_error(array('message' => 'Получатель не указан'));
        }

        // Проверка: нельзя отправить сообщение самому себе (только для залогиненных)
        if ($is_logged_in && $sender_member_id == $recipient_member_id) {
            wp_send_json_error(array('message' => 'Нельзя отправить сообщение самому себе'));
        }

        // === АНТИСПАМ ЗАЩИТА ===

        if ($is_logged_in) {
            // 1. Rate limiting для залогиненных: не более 10 сообщений в день
            $today_start = strtotime('today');
            $messages_today = get_posts(array(
                'post_type' => 'member_message',
                'author' => $sender_user_id,
                'date_query' => array(
                    array(
                        'after' => date('Y-m-d 00:00:00', $today_start),
                    ),
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));

            if (count($messages_today) >= 10) {
                wp_send_json_error(array('message' => 'Вы достигли лимита сообщений на сегодня (10 в день)'));
            }

            // 2. Cooldown: минимум 2 минуты между сообщениями
            $last_message_time = get_user_meta($sender_user_id, 'last_message_sent_time', true);
            if ($last_message_time) {
                $time_diff = time() - intval($last_message_time);
                if ($time_diff < 120) { // 120 секунд = 2 минуты
                    $wait_time = 120 - $time_diff;
                    wp_send_json_error(array('message' => 'Пожалуйста, подождите ' . $wait_time . ' секунд перед отправкой следующего сообщения'));
                }
            }
        } else {
            // Антиспам для незалогиненных - по IP и email
            $sender_ip = $_SERVER['REMOTE_ADDR'];

            // 1. Rate limiting по IP: не более 5 сообщений в день
            $messages_from_ip = get_posts(array(
                'post_type' => 'member_message',
                'meta_query' => array(
                    array(
                        'key' => 'sender_ip',
                        'value' => $sender_ip,
                    ),
                ),
                'date_query' => array(
                    array(
                        'after' => date('Y-m-d 00:00:00', strtotime('today')),
                    ),
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));

            if (count($messages_from_ip) >= 5) {
                wp_send_json_error(array('message' => 'Превышен лимит сообщений на сегодня'));
            }

            // 2. Cooldown по IP: минимум 5 минут между сообщениями
            $last_message_from_ip = get_posts(array(
                'post_type' => 'member_message',
                'meta_query' => array(
                    array(
                        'key' => 'sender_ip',
                        'value' => $sender_ip,
                    ),
                ),
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if (!empty($last_message_from_ip)) {
                $last_time = strtotime($last_message_from_ip[0]->post_date);
                $time_diff = time() - $last_time;
                if ($time_diff < 300) { // 300 секунд = 5 минут
                    $wait_time = ceil((300 - $time_diff) / 60);
                    wp_send_json_error(array('message' => 'Пожалуйста, подождите ' . $wait_time . ' мин. перед отправкой следующего сообщения'));
                }
            }
        }

        // Создаем сообщение
        $message_data = array(
            'post_title' => $subject,
            'post_content' => $content,
            'post_type' => 'member_message',
            'post_status' => 'publish',
            'post_author' => $sender_user_id
        );

        $message_id = wp_insert_post($message_data);

        if (is_wp_error($message_id)) {
            wp_send_json_error(array('message' => 'Ошибка отправки сообщения'));
        }

        // Сохраняем мета-данные
        update_post_meta($message_id, 'recipient_member_id', $recipient_member_id);
        update_post_meta($message_id, 'sender_member_id', $sender_member_id);
        update_post_meta($message_id, 'is_read', 0);
        update_post_meta($message_id, 'sent_at', current_time('mysql'));

        // Для незалогиненных - сохраняем дополнительные данные
        if (!$is_logged_in) {
            update_post_meta($message_id, 'sender_name', $sender_name);
            update_post_meta($message_id, 'sender_email', $sender_email);
            update_post_meta($message_id, 'sender_ip', $_SERVER['REMOTE_ADDR']);
        }

        // Обновляем время последней отправки
        if ($is_logged_in) {
            update_user_meta($sender_user_id, 'last_message_sent_time', time());
        }

        // Отправляем email уведомление получателю
        $recipient_user = get_user_by('ID', get_post_field('post_author', $recipient_member_id));
        if ($recipient_user) {
            $recipient_name = get_the_title($recipient_member_id);

            $email_subject = '[Метода] Новое сообщение от ' . $sender_name;
            $email_body = "Здравствуйте, {$recipient_name}!\n\n";
            $email_body .= "Вам пришло новое личное сообщение от {$sender_name}";

            if (!$is_logged_in) {
                $email_body .= " ({$sender_email})";
            }

            $email_body .= ".\n\nТема: {$subject}\n\n";

            if ($is_logged_in) {
                $email_body .= "Чтобы прочитать сообщение и ответить, войдите в личный кабинет:\n";
                $email_body .= get_permalink(get_option('metoda_dashboard_page_id')) . "\n\n";
            } else {
                $email_body .= "Для ответа напишите на: {$sender_email}\n\n";
                $email_body .= "Или прочитайте сообщение в личном кабинете:\n";
                $email_body .= get_permalink(get_option('metoda_dashboard_page_id')) . "\n\n";
            }

            $email_body .= "---\n";
            $email_body .= "Это сообщение отправлено через форму на сайте Метода.";

            wp_mail($recipient_user->user_email, $email_subject, $email_body);
        }

        wp_send_json_success(array(
            'message' => 'Сообщение успешно отправлено!',
            'message_id' => $message_id
        ));
    }

    /**
     * View member message
     *
     * @since 5.0.0
     */
    public function view_member_message() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходимо войти в систему'));
        }

        $message_id = intval($_POST['message_id']);
        $message = get_post($message_id);

        if (!$message || $message->post_type !== 'member_message') {
            wp_send_json_error(array('message' => 'Сообщение не найдено'));
        }

        $current_member_id = Member_User_Link::get_current_user_member_id();
        $recipient_id = get_post_meta($message_id, 'recipient_member_id', true);
        $sender_id = get_post_meta($message_id, 'sender_member_id', true);

        // Проверка доступа: только отправитель или получатель могут просмотреть
        if ($current_member_id != $recipient_id && $current_member_id != $sender_id) {
            wp_send_json_error(array('message' => 'Доступ запрещен'));
        }

        // Помечаем как прочитанное (если это получатель)
        if ($current_member_id == $recipient_id) {
            update_post_meta($message_id, 'is_read', 1);
            update_post_meta($message_id, 'read_at', current_time('mysql'));
        }

        // Формируем мета информацию
        $meta = '';
        if ($current_member_id == $recipient_id) {
            // Показываем отправителя
            if (empty($sender_id)) {
                // Сообщение от незалогиненного пользователя
                $sender_name = get_post_meta($message_id, 'sender_name', true);
                $sender_email = get_post_meta($message_id, 'sender_email', true);
                $meta .= '<strong>От:</strong> ' . esc_html($sender_name) . ' (' . esc_html($sender_email) . ')<br>';
            } else {
                $meta .= '<strong>От:</strong> ' . get_the_title($sender_id) . '<br>';
            }
        } else {
            $meta .= '<strong>Кому:</strong> ' . get_the_title($recipient_id) . '<br>';
        }
        $meta .= '<strong>Дата:</strong> ' . get_the_date('d.m.Y H:i', $message_id);

        wp_send_json_success(array(
            'title' => $message->post_title,
            'content' => $message->post_content,
            'meta' => $meta
        ));
    }

    /**
     * Save notification settings
     *
     * @return void
     */
    public function save_notification_settings() {
        // Проверка nonce
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        $user_id = get_current_user_id();

        // Каналы доставки
        $channel_email = isset($_POST['channel_email']) ? '1' : '0';
        $channel_telegram = isset($_POST['channel_telegram']) ? '1' : '0';

        update_user_meta($user_id, 'notify_channel_email', $channel_email);
        update_user_meta($user_id, 'notify_channel_telegram', $channel_telegram);

        // Email настройки
        if ($channel_email === '1') {
            $email_destination = sanitize_text_field($_POST['email_destination'] ?? 'account');
            if ($email_destination === 'custom') {
                $custom_email = sanitize_email($_POST['custom_email'] ?? '');
                if (is_email($custom_email)) {
                    update_user_meta($user_id, 'notify_custom_email', $custom_email);
                }
            } else {
                delete_user_meta($user_id, 'notify_custom_email');
            }
        }

        // Типы уведомлений
        $notify_messages = isset($_POST['notify_messages']) ? '1' : '0';
        $notify_forum = isset($_POST['notify_forum']) ? '1' : '0';

        update_user_meta($user_id, 'notify_messages', $notify_messages);
        update_user_meta($user_id, 'notify_forum', $notify_forum);

        // Подтипы уведомлений (если главный тип включен)
        if ($notify_messages === '1') {
            $notify_messages_instant = isset($_POST['notify_messages_instant']) ? '1' : '0';
            update_user_meta($user_id, 'notify_messages_instant', $notify_messages_instant);
        }

        if ($notify_forum === '1') {
            $notify_forum_replies = isset($_POST['notify_forum_replies']) ? '1' : '0';
            $notify_forum_mentions = isset($_POST['notify_forum_mentions']) ? '1' : '0';
            $notify_forum_watching = isset($_POST['notify_forum_watching']) ? '1' : '0';

            update_user_meta($user_id, 'notify_forum_replies', $notify_forum_replies);
            update_user_meta($user_id, 'notify_forum_mentions', $notify_forum_mentions);
            update_user_meta($user_id, 'notify_forum_watching', $notify_forum_watching);
        }

        // Тихие часы
        $quiet_hours_enabled = isset($_POST['quiet_hours_enabled']) ? '1' : '0';
        update_user_meta($user_id, 'quiet_hours_enabled', $quiet_hours_enabled);

        if ($quiet_hours_enabled === '1') {
            $quiet_hours_start = sanitize_text_field($_POST['quiet_hours_start'] ?? '22:00');
            $quiet_hours_end = sanitize_text_field($_POST['quiet_hours_end'] ?? '08:00');

            update_user_meta($user_id, 'quiet_hours_start', $quiet_hours_start);
            update_user_meta($user_id, 'quiet_hours_end', $quiet_hours_end);
        }

        // OTP настройки
        $otp_enabled = isset($_POST['otp_enabled']) ? '1' : '0';
        update_user_meta($user_id, 'otp_enabled', $otp_enabled);

        if ($otp_enabled === '1') {
            $otp_delivery = sanitize_text_field($_POST['otp_delivery'] ?? 'email');
            update_user_meta($user_id, 'otp_delivery', $otp_delivery);
        }

        wp_send_json_success(array('message' => 'Настройки сохранены'));
    }

    /**
     * Check if Telegram is connected
     *
     * @return void
     */
    public function check_telegram_connection() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        $user_id = get_current_user_id();
        $telegram_chat_id = get_user_meta($user_id, 'telegram_chat_id', true);

        if (!empty($telegram_chat_id)) {
            wp_send_json_success(array('connected' => true));
        } else {
            wp_send_json_error(array('connected' => false, 'message' => 'Telegram не подключен'));
        }
    }

    /**
     * Send test notification
     *
     * @return void
     */
    public function send_test_notification() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        $user_id = get_current_user_id();
        $channel = sanitize_text_field($_POST['channel'] ?? '');

        if ($channel === 'telegram') {
            $telegram_chat_id = get_user_meta($user_id, 'telegram_chat_id', true);

            if (empty($telegram_chat_id)) {
                wp_send_json_error(array('message' => 'Telegram не подключен'));
            }

            // Отправка тестового сообщения через Telegram API
            $bot_token = get_option('metoda_telegram_bot_token');
            if (empty($bot_token)) {
                wp_send_json_error(array('message' => 'Telegram бот не настроен'));
            }

            $message = "🔔 Тестовое уведомление\n\nЭто тестовое сообщение из системы уведомлений Metoda Members.";

            $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
            $response = wp_remote_post($url, array(
                'body' => array(
                    'chat_id' => $telegram_chat_id,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                )
            ));

            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => 'Ошибка отправки: ' . $response->get_error_message()));
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['ok']) && $body['ok']) {
                wp_send_json_success(array('message' => 'Тестовое сообщение отправлено'));
            } else {
                wp_send_json_error(array('message' => 'Ошибка API Telegram'));
            }
        } elseif ($channel === 'email') {
            $user = wp_get_current_user();
            $custom_email = get_user_meta($user_id, 'notify_custom_email', true);
            $to = !empty($custom_email) ? $custom_email : $user->user_email;

            $subject = '🔔 Тестовое уведомление - Metoda Members';
            $message = "Это тестовое письмо из системы уведомлений Metoda Members.\n\n";
            $message .= "Если вы получили это письмо, значит уведомления по email настроены правильно.\n\n";
            $message .= "С уважением,\nКоманда Metoda";

            $headers = array('Content-Type: text/plain; charset=UTF-8');

            if (wp_mail($to, $subject, $message, $headers)) {
                wp_send_json_success(array('message' => 'Тестовое письмо отправлено на ' . $to));
            } else {
                wp_send_json_error(array('message' => 'Ошибка отправки email'));
            }
        } else {
            wp_send_json_error(array('message' => 'Неверный канал'));
        }
    }

    /**
     * Disconnect Telegram
     *
     * @return void
     */
    public function disconnect_telegram() {
        check_ajax_referer('member_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Необходима авторизация'));
        }

        $user_id = get_current_user_id();

        // Удаляем связь с Telegram
        delete_user_meta($user_id, 'telegram_chat_id');

        // Отключаем канал Telegram
        update_user_meta($user_id, 'notify_channel_telegram', '0');

        wp_send_json_success(array('message' => 'Telegram отключен'));
    }
}
