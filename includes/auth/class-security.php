<?php
/**
 * Security Functions
 *
 * Core security functions for access control
 *
 * @package Metoda_Members
 * @subpackage Auth
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Metoda_Security
 *
 * Handles security and access control for member profiles
 */
class Metoda_Security {

    /**
     * Get editable member ID with security checks
     *
     * SECURITY v3.7.3: Единая функция проверки прав на редактирование member_id
     *
     * Логика:
     * - Админ + member_id в запросе → редактирует чужой профиль (admin bypass)
     * - Обычный юзер → редактирует только свой профиль (игнорируем member_id из запроса)
     *
     * @param array|null $request POST или GET массив с данными
     * @return int|WP_Error member_id или ошибка
     */
    public static function get_editable_member_id($request = null) {
        // Если не передан массив, используем $_POST по умолчанию
        if ($request === null) {
            $request = $_POST;
        }

        $is_admin = current_user_can('administrator');
        $requested_member_id = isset($request['member_id']) ? absint($request['member_id']) : null;

        // СЦЕНАРИЙ 1: Админ редактирует чужой профиль
        if ($is_admin && $requested_member_id) {
            // Проверяем существование member post
            $member_post = get_post($requested_member_id);

            if (!$member_post || $member_post->post_type !== 'members') {
                return new WP_Error(
                    'invalid_member',
                    'Участник не найден или имеет неверный тип',
                    array('member_id' => $requested_member_id)
                );
            }

            // Проверяем что участник не в корзине
            if ($member_post->post_status === 'trash') {
                return new WP_Error(
                    'member_trashed',
                    'Участник находится в корзине',
                    array('member_id' => $requested_member_id)
                );
            }

            return $requested_member_id;
        }

        // СЦЕНАРИЙ 2: Обычный пользователь (или админ без member_id) → редактирует свой профиль
        $current_member_id = Member_User_Link::get_current_user_member_id();

        if (!$current_member_id) {
            return new WP_Error(
                'no_member_linked',
                'Ваш аккаунт не привязан к профилю участника',
                array('user_id' => get_current_user_id())
            );
        }

        return $current_member_id;
    }
}
