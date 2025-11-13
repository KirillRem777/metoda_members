<?php
/**
 * Template: Member Manager Panel
 * Frontend admin panel for managers
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();

// Get taxonomies for filters
$member_types = get_terms(array('taxonomy' => 'member_type', 'hide_empty' => false));
$member_roles = get_terms(array('taxonomy' => 'member_role', 'hide_empty' => false));
$member_locations = get_terms(array('taxonomy' => 'member_location', 'hide_empty' => false));
?>

<div class="member-manager-panel">
    <!-- Header -->
    <div class="manager-header">
        <div class="header-content">
            <div class="header-left">
                <h1>Панель управления участниками</h1>
                <p>Добро пожаловать, <?php echo esc_html($current_user->display_name); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-primary" id="add-member-btn">
                    <span class="icon">➕</span> Добавить участника
                </button>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline">Выход</a>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="manager-toolbar">
        <div class="search-box">
            <input type="text" id="member-search" placeholder="Поиск участников...">
            <button class="btn btn-primary" id="search-btn">🔍 Найти</button>
        </div>
        <div class="toolbar-actions">
            <span class="results-count">Найдено: <strong id="total-count">0</strong></span>
        </div>
    </div>

    <!-- Members Table -->
    <div class="members-table-container">
        <table class="members-table" id="members-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Фото</th>
                    <th>ФИО</th>
                    <th>Должность</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th style="width: 200px;">Действия</th>
                </tr>
            </thead>
            <tbody id="members-tbody">
                <tr>
                    <td colspan="6" class="loading">
                        <div class="loader">⏳ Загрузка...</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="pagination"></div>

    <!-- Add/Edit Member Modal -->
    <div class="modal" id="member-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Добавить участника</h2>
                <button class="modal-close" id="modal-close">×</button>
            </div>
            <form id="member-form" class="modal-body">
                <input type="hidden" id="member-id" name="member_id">

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>ФИО *</label>
                        <input type="text" name="title" id="member-title" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Должность</label>
                        <input type="text" name="member_position" id="member-position">
                    </div>
                    <div class="form-group">
                        <label>Организация</label>
                        <input type="text" name="member_company" id="member-company">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="member_email" id="member-email">
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="member_phone" id="member-phone">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Фото</label>
                        <div class="photo-upload">
                            <input type="hidden" name="thumbnail_id" id="thumbnail-id">
                            <div id="photo-preview" class="photo-preview"></div>
                            <button type="button" class="btn btn-secondary" id="upload-photo-btn">Загрузить фото</button>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>О себе</label>
                        <textarea name="member_bio" id="member-bio" rows="4"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Специализация</label>
                        <textarea name="member_specialization" id="member-specialization" rows="2"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Опыт работы</label>
                        <textarea name="member_experience" id="member-experience" rows="3"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Интересы</label>
                        <textarea name="member_interests" id="member-interests" rows="2"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>LinkedIn</label>
                        <input type="url" name="member_linkedin" id="member-linkedin" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div class="form-group">
                        <label>Веб-сайт</label>
                        <input type="url" name="member_website" id="member-website" placeholder="https://...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Ожидания от участия</label>
                        <textarea name="member_expectations" id="member-expectations" rows="3"></textarea>
                    </div>
                </div>

                <!-- Taxonomies Section -->
                <h3 class="form-section-title">Категории участника</h3>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Тип участника</label>
                        <div class="checkbox-group" id="member-types-group">
                            <?php
                            if (!empty($member_types)) {
                                foreach ($member_types as $term) {
                                    echo '<label class="checkbox-label">';
                                    echo '<input type="checkbox" name="member_types[]" value="' . $term->term_id . '">';
                                    echo '<span>' . esc_html($term->name) . '</span>';
                                    echo '</label>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Роль участника</label>
                        <div class="checkbox-group" id="member-roles-group">
                            <?php
                            if (!empty($member_roles)) {
                                foreach ($member_roles as $term) {
                                    echo '<label class="checkbox-label">';
                                    echo '<input type="checkbox" name="member_roles[]" value="' . $term->term_id . '">';
                                    echo '<span>' . esc_html($term->name) . '</span>';
                                    echo '</label>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Локация</label>
                        <div class="checkbox-group" id="member-locations-group">
                            <?php
                            if (!empty($member_locations)) {
                                foreach ($member_locations as $term) {
                                    echo '<label class="checkbox-label">';
                                    echo '<input type="checkbox" name="member_locations[]" value="' . $term->term_id . '">';
                                    echo '<span>' . esc_html($term->name) . '</span>';
                                    echo '</label>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Materials Section -->
                <h3 class="form-section-title">Материалы участника</h3>

                <?php
                $material_categories = array(
                    'testimonials' => 'Отзывы',
                    'gratitudes' => 'Благодарности',
                    'interviews' => 'Интервью',
                    'videos' => 'Видео',
                    'reviews' => 'Рецензии',
                    'developments' => 'Разработки'
                );

                foreach ($material_categories as $key => $label):
                ?>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label><?php echo esc_html($label); ?></label>
                        <div id="materials-<?php echo $key; ?>-container" class="materials-container">
                            <!-- Materials will be added here dynamically -->
                        </div>
                        <button type="button" class="add-material-btn" data-category="<?php echo $key; ?>">
                            ➕ Добавить <?php echo mb_strtolower($label); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Gallery Section -->
                <h3 class="form-section-title">Галерея фотографий</h3>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Дополнительные фотографии</label>
                        <div id="gallery-preview" class="gallery-preview">
                            <!-- Gallery images will be displayed here -->
                        </div>
                        <input type="hidden" id="gallery-ids" name="gallery_ids">
                        <button type="button" class="btn btn-secondary" id="upload-gallery-btn">📷 Добавить фотографии</button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancel-btn">Отмена</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-text">Сохранить</span>
                        <span class="btn-loader" style="display: none;">⏳</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="delete-modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2>Подтверждение удаления</h2>
                <button class="modal-close" id="delete-modal-close">×</button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите удалить участника <strong id="delete-member-name"></strong>?</p>
                <p style="color: #ef4444;">Это действие нельзя отменить.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="delete-cancel-btn">Отмена</button>
                <button type="button" class="btn btn-danger" id="delete-confirm-btn">
                    <span class="btn-text">Удалить</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
            </div>
        </div>
    </div>
</div>
