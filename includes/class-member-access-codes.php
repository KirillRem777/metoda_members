<?php
/**
 * Member Access Codes Class
 *
 * Handles access codes for imported members
 * Allows existing members to claim their profiles via unique codes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Member_Access_Codes {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_access_codes', array($this, 'ajax_generate_codes'));
        add_action('wp_ajax_regenerate_single_code', array($this, 'ajax_regenerate_single_code'));
        add_action('wp_ajax_export_access_codes', array($this, 'ajax_export_codes'));
        add_action('wp_ajax_validate_access_code', array($this, 'ajax_validate_code'));
        add_action('wp_ajax_nopriv_validate_access_code', array($this, 'ajax_validate_code'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=member',
            'Коды доступа',
            'Коды доступа',
            'manage_options',
            'member-access-codes',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Коды доступа для участников</h1>
            <p>Генерируйте уникальные коды доступа для импортированных участников. Коды позволяют участникам зарегистрироваться и получить доступ к своим профилям.</p>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Генерация кодов</h2>
                <p>Создайте коды для всех участников без кодов доступа или пересоздайте существующие коды.</p>

                <p>
                    <button type="button" class="button button-primary" id="generate-codes-new">
                        <span class="dashicons dashicons-admin-network" style="margin-top: 3px;"></span>
                        Создать коды для участников без кодов
                    </button>

                    <button type="button" class="button" id="generate-codes-all">
                        <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                        Пересоздать все коды
                    </button>
                </p>

                <div id="generation-result" style="margin-top: 15px;"></div>
            </div>

            <div class="card" style="max-width: none; margin-top: 20px;">
                <h2>Список участников и их коды</h2>

                <p>
                    <button type="button" class="button" id="export-codes">
                        <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                        Экспортировать коды в CSV
                    </button>
                </p>

                <table class="wp-list-table widefat fixed striped" id="codes-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Имя участника</th>
                            <th>Email</th>
                            <th style="width: 200px;">Код доступа</th>
                            <th style="width: 120px;">Статус</th>
                            <th style="width: 150px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $this->render_members_table(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .card {
                background: white;
                border: 1px solid #ccd0d4;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                padding: 20px;
                margin-bottom: 20px;
            }
            .card h2 {
                margin-top: 0;
                font-size: 18px;
            }
            .access-code {
                font-family: 'Courier New', monospace;
                font-weight: bold;
                color: #0073aa;
                font-size: 14px;
            }
            .status-unclaimed {
                color: #d63638;
            }
            .status-claimed {
                color: #00a32a;
            }
            .generation-success {
                padding: 10px;
                background: #d7f9e6;
                border-left: 4px solid #00a32a;
                margin-top: 10px;
            }
            .generation-error {
                padding: 10px;
                background: #fcf0f1;
                border-left: 4px solid #d63638;
                margin-top: 10px;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Generate codes for members without codes
            $('#generate-codes-new').on('click', function() {
                if (!confirm('Создать коды доступа для всех участников без кодов?')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('Генерация...');
                $('#generation-result').html('');

                $.post(ajaxurl, {
                    action: 'generate_access_codes',
                    mode: 'new',
                    nonce: '<?php echo wp_create_nonce('generate_access_codes'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#generation-result').html('<div class="generation-success">' + response.data.message + '</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $('#generation-result').html('<div class="generation-error">' + response.data.message + '</div>');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-network" style="margin-top: 3px;"></span> Создать коды для участников без кодов');
                    }
                });
            });

            // Regenerate all codes
            $('#generate-codes-all').on('click', function() {
                if (!confirm('ВНИМАНИЕ! Это пересоздаст все коды доступа. Старые коды перестанут работать. Продолжить?')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('Генерация...');
                $('#generation-result').html('');

                $.post(ajaxurl, {
                    action: 'generate_access_codes',
                    mode: 'all',
                    nonce: '<?php echo wp_create_nonce('generate_access_codes'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#generation-result').html('<div class="generation-success">' + response.data.message + '</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $('#generation-result').html('<div class="generation-error">' + response.data.message + '</div>');
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="margin-top: 3px;"></span> Пересоздать все коды');
                    }
                });
            });

            // Regenerate single code
            $(document).on('click', '.regenerate-code', function(e) {
                e.preventDefault();
                var memberId = $(this).data('member-id');
                var $btn = $(this);

                if (!confirm('Пересоздать код доступа для этого участника?')) {
                    return;
                }

                $btn.prop('disabled', true).text('...');

                $.post(ajaxurl, {
                    action: 'regenerate_single_code',
                    member_id: memberId,
                    nonce: '<?php echo wp_create_nonce('regenerate_code'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Пересоздать');
                    }
                });
            });

            // Copy code to clipboard
            $(document).on('click', '.copy-code', function(e) {
                e.preventDefault();
                var code = $(this).data('code');
                var $btn = $(this);

                navigator.clipboard.writeText(code).then(function() {
                    var originalText = $btn.text();
                    $btn.text('Скопировано!');
                    setTimeout(function() {
                        $btn.text(originalText);
                    }, 2000);
                });
            });

            // Export codes
            $('#export-codes').on('click', function() {
                window.location.href = ajaxurl + '?action=export_access_codes&nonce=<?php echo wp_create_nonce('export_codes'); ?>';
            });
        });
        </script>
        <?php
    }

    /**
     * Render members table
     */
    private function render_members_table() {
        $members = get_posts(array(
            'post_type' => 'member',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft'),
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        if (empty($members)) {
            echo '<tr><td colspan="6">Участники не найдены</td></tr>';
            return;
        }

        foreach ($members as $member) {
            $access_code = get_post_meta($member->ID, 'member_access_code', true);
            $linked_user_id = get_post_meta($member->ID, 'member_user_id', true);
            $email = get_post_meta($member->ID, 'member_email', true);

            $status = $linked_user_id ? 'claimed' : 'unclaimed';
            $status_text = $linked_user_id ? 'Активирован' : 'Не активирован';
            $status_class = $linked_user_id ? 'status-claimed' : 'status-unclaimed';

            echo '<tr>';
            echo '<td>' . $member->ID . '</td>';
            echo '<td><strong>' . esc_html($member->post_title) . '</strong></td>';
            echo '<td>' . esc_html($email) . '</td>';
            echo '<td>';
            if ($access_code) {
                echo '<span class="access-code">' . esc_html($access_code) . '</span>';
            } else {
                echo '<em style="color: #999;">Нет кода</em>';
            }
            echo '</td>';
            echo '<td class="' . $status_class . '"><strong>' . $status_text . '</strong></td>';
            echo '<td>';
            echo '<button class="button button-small copy-code" data-code="' . esc_attr($access_code) . '" ' . ($access_code ? '' : 'disabled') . '>Копировать</button> ';
            echo '<button class="button button-small regenerate-code" data-member-id="' . $member->ID . '">Пересоздать</button>';
            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Generate access codes via AJAX
     */
    public function ajax_generate_codes() {
        check_ajax_referer('generate_access_codes', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }

        $mode = isset($_POST['mode']) ? $_POST['mode'] : 'new';

        $members = get_posts(array(
            'post_type' => 'member',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));

        $generated_count = 0;

        foreach ($members as $member) {
            $existing_code = get_post_meta($member->ID, 'member_access_code', true);

            // Skip if mode is 'new' and code already exists
            if ($mode === 'new' && !empty($existing_code)) {
                continue;
            }

            // Generate new code
            $code = $this->generate_unique_code();
            update_post_meta($member->ID, 'member_access_code', $code);
            $generated_count++;
        }

        if ($generated_count > 0) {
            wp_send_json_success(array(
                'message' => "Успешно создано кодов: {$generated_count}"
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Нет участников для генерации кодов'
            ));
        }
    }

    /**
     * Regenerate single code via AJAX
     */
    public function ajax_regenerate_single_code() {
        check_ajax_referer('regenerate_code', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }

        $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;

        if (!$member_id) {
            wp_send_json_error(array('message' => 'Неверный ID участника'));
        }

        $code = $this->generate_unique_code();
        update_post_meta($member_id, 'member_access_code', $code);

        wp_send_json_success(array(
            'message' => 'Код успешно пересоздан',
            'code' => $code
        ));
    }

    /**
     * Export codes to CSV
     */
    public function ajax_export_codes() {
        check_ajax_referer('export_codes', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }

        $members = get_posts(array(
            'post_type' => 'member',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="access_codes_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header
        fputcsv($output, array('ID', 'Имя', 'Email', 'Код доступа', 'Статус'), ';');

        foreach ($members as $member) {
            $access_code = get_post_meta($member->ID, 'member_access_code', true);
            $linked_user_id = get_post_meta($member->ID, 'member_user_id', true);
            $email = get_post_meta($member->ID, 'member_email', true);
            $status = $linked_user_id ? 'Активирован' : 'Не активирован';

            fputcsv($output, array(
                $member->ID,
                $member->post_title,
                $email,
                $access_code,
                $status
            ), ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Generate unique access code
     */
    public function generate_unique_code() {
        do {
            $code = 'METODA-' . date('Y') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $existing = $this->find_member_by_code($code);
        } while ($existing);

        return $code;
    }

    /**
     * Find member by access code
     */
    public static function find_member_by_code($code) {
        $members = get_posts(array(
            'post_type' => 'member',
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => 'member_access_code',
                    'value' => $code,
                    'compare' => '='
                )
            )
        ));

        return !empty($members) ? $members[0] : null;
    }

    /**
     * Check if code is valid and unclaimed
     */
    public static function validate_code($code) {
        if (empty($code)) {
            return false;
        }

        $member = self::find_member_by_code($code);

        if (!$member) {
            return false;
        }

        // Check if already claimed
        $linked_user_id = get_post_meta($member->ID, 'member_user_id', true);

        if ($linked_user_id) {
            return false; // Already claimed
        }

        return $member->ID;
    }

    /**
     * Claim member profile with access code
     */
    public static function claim_profile($code, $user_id) {
        $member_id = self::validate_code($code);

        if (!$member_id) {
            return false;
        }

        // Link user to member
        update_post_meta($member_id, 'member_user_id', $user_id);
        update_user_meta($user_id, 'member_id', $member_id);

        return $member_id;
    }

    /**
     * Validate access code via AJAX
     */
    public function ajax_validate_code() {
        $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';

        if (empty($code)) {
            wp_send_json_error(array('message' => 'Код доступа не указан'));
        }

        $member = self::find_member_by_code($code);

        if (!$member) {
            wp_send_json_error(array('message' => 'Неверный код доступа'));
        }

        // Check if already claimed
        $linked_user_id = get_post_meta($member->ID, 'member_user_id', true);

        if ($linked_user_id) {
            wp_send_json_error(array('message' => 'Этот код уже активирован'));
        }

        // Code is valid and available
        wp_send_json_success(array(
            'message' => 'Код подтвержден! Профиль "' . esc_html($member->post_title) . '" будет активирован при регистрации',
            'member_name' => $member->post_title,
            'member_id' => $member->ID
        ));
    }
}

// Initialize the class
new Member_Access_Codes();
