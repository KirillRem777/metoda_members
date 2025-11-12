<?php
/**
 * Template: Member Dashboard
 *
 * Personal cabinet for members to edit their profiles
 */

if (!defined('ABSPATH')) {
    exit;
}

$member_id = Member_User_Link::get_current_user_member_id();
$member_data = Member_Dashboard::get_member_data($member_id);
$member_stats = Member_Dashboard::get_member_stats($member_id);
$current_user = wp_get_current_user();
?>

<div class="member-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
                <h1>Личный кабинет</h1>
                <p class="welcome-text">Добро пожаловать, <?php echo esc_html($current_user->display_name); ?>!</p>
            </div>
            <div class="header-right">
                <a href="<?php echo esc_url($member_data['permalink']); ?>" class="btn btn-outline" target="_blank">
                    <span class="icon">👁️</span> Просмотреть профиль
                </a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-outline">
                    <span class="icon">🚪</span> Выход
                </a>
            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($member_stats['profile_views']); ?></div>
                <div class="stat-label">Просмотров профиля</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo esc_html($member_stats['materials_count']); ?></div>
                <div class="stat-label">Материалов</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value">Активен</div>
                <div class="stat-label">Статус профиля</div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content">
        <!-- Sidebar Navigation -->
        <div class="dashboard-sidebar">
            <nav class="dashboard-nav">
                <button class="nav-item active" data-section="profile">
                    <span class="nav-icon">👤</span>
                    <span class="nav-label">Профиль</span>
                </button>
                <button class="nav-item" data-section="gallery">
                    <span class="nav-icon">🖼️</span>
                    <span class="nav-label">Галерея</span>
                </button>
                <button class="nav-item" data-section="materials">
                    <span class="nav-icon">📚</span>
                    <span class="nav-label">Материалы</span>
                </button>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="dashboard-main">
            <!-- Profile Section -->
            <div class="dashboard-section active" id="section-profile">
                <div class="section-header">
                    <h2>Редактирование профиля</h2>
                    <p>Обновите информацию о себе</p>
                </div>

                <form id="profile-form" class="dashboard-form">
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="member_name">ФИО *</label>
                            <input type="text" id="member_name" name="member_name" value="<?php echo esc_attr($member_data['name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_position">Должность</label>
                            <input type="text" id="member_position" name="member_position" value="<?php echo esc_attr($member_data['member_position']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="member_company">Организация</label>
                            <input type="text" id="member_company" name="member_company" value="<?php echo esc_attr($member_data['member_company']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_email">Email</label>
                            <input type="email" id="member_email" name="member_email" value="<?php echo esc_attr($member_data['member_email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="member_phone">Телефон</label>
                            <input type="text" id="member_phone" name="member_phone" value="<?php echo esc_attr($member_data['member_phone']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="member_bio">О себе</label>
                            <textarea id="member_bio" name="member_bio" rows="5"><?php echo esc_textarea($member_data['member_bio']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="member_specialization">Специализация</label>
                            <textarea id="member_specialization" name="member_specialization" rows="3"><?php echo esc_textarea($member_data['member_specialization']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="member_experience">Опыт работы</label>
                            <textarea id="member_experience" name="member_experience" rows="3"><?php echo esc_textarea($member_data['member_experience']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="member_interests">Интересы</label>
                            <textarea id="member_interests" name="member_interests" rows="3"><?php echo esc_textarea($member_data['member_interests']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="member_expectations">Ожидания от сотрудничества</label>
                            <textarea id="member_expectations" name="member_expectations" rows="3"><?php echo esc_textarea($member_data['member_expectations']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="member_linkedin">LinkedIn</label>
                            <input type="url" id="member_linkedin" name="member_linkedin" value="<?php echo esc_url($member_data['member_linkedin']); ?>" placeholder="https://linkedin.com/in/username">
                        </div>
                        <div class="form-group">
                            <label for="member_website">Веб-сайт</label>
                            <input type="url" id="member_website" name="member_website" value="<?php echo esc_url($member_data['member_website']); ?>" placeholder="https://example.com">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">Сохранить изменения</span>
                            <span class="btn-loader" style="display: none;">⏳</span>
                        </button>
                    </div>

                    <div class="form-message" style="display: none;"></div>
                </form>
            </div>

            <!-- Gallery Section -->
            <div class="dashboard-section" id="section-gallery">
                <div class="section-header">
                    <h2>Галерея фотографий</h2>
                    <p>Управляйте фотографиями в вашем профиле</p>
                </div>

                <div class="gallery-manager">
                    <div class="gallery-toolbar">
                        <button type="button" class="btn btn-primary" id="add-gallery-images">
                            <span class="icon">➕</span> Добавить фото
                        </button>
                    </div>

                    <div class="gallery-grid" id="gallery-grid">
                        <?php if (!empty($member_data['gallery_images'])) : ?>
                            <?php foreach ($member_data['gallery_images'] as $image) : ?>
                                <div class="gallery-item" data-id="<?php echo esc_attr($image['id']); ?>">
                                    <img src="<?php echo esc_url($image['thumb']); ?>" alt="">
                                    <button type="button" class="remove-gallery-item" title="Удалить">×</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="gallery-empty">
                                <p>Галерея пуста. Добавьте свои фотографии.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" id="gallery_ids" value="<?php echo esc_attr($member_data['member_gallery']); ?>">

                    <div class="gallery-actions">
                        <button type="button" class="btn btn-primary" id="save-gallery">
                            <span class="btn-text">Сохранить галерею</span>
                            <span class="btn-loader" style="display: none;">⏳</span>
                        </button>
                    </div>

                    <div class="gallery-message" style="display: none;"></div>
                </div>
            </div>

            <!-- Materials Section -->
            <div class="dashboard-section" id="section-materials">
                <div class="section-header">
                    <h2>Управление материалами</h2>
                    <p>Добавляйте файлы или ссылки на ваши работы</p>
                </div>

                <div class="materials-manager">
                    <!-- Material Categories Tabs -->
                    <div class="materials-tabs">
                        <?php
                        $categories = Member_File_Manager::get_categories();
                        $first = true;
                        foreach ($categories as $key => $label) :
                            $active = $first ? 'active' : '';
                            $first = false;
                        ?>
                            <button class="materials-tab <?php echo $active; ?>" data-category="<?php echo esc_attr($key); ?>">
                                <?php echo esc_html($label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Materials Content -->
                    <div class="materials-content">
                        <?php
                        $first = true;
                        foreach ($categories as $key => $label) :
                            $active = $first ? 'active' : '';
                            $first = false;
                            $materials = get_post_meta($member_id, 'member_' . $key, true);
                            $parsed_materials = Member_File_Manager::parse_material_content($materials);
                        ?>
                            <div class="materials-pane <?php echo $active; ?>" id="materials-<?php echo esc_attr($key); ?>" data-category="<?php echo esc_attr($key); ?>">
                                <!-- Add Material Form -->
                                <div class="add-material-form">
                                    <h3>Добавить материал</h3>

                                    <div class="material-type-selector">
                                        <button type="button" class="type-btn active" data-type="link">
                                            <span class="icon">🔗</span> Ссылка
                                        </button>
                                        <button type="button" class="type-btn" data-type="file">
                                            <span class="icon">📎</span> Файл
                                        </button>
                                    </div>

                                    <!-- Link Form -->
                                    <form class="material-form link-form active">
                                        <div class="form-group">
                                            <label>Название *</label>
                                            <input type="text" name="title" required>
                                        </div>
                                        <div class="form-group">
                                            <label>URL *</label>
                                            <input type="url" name="url" required placeholder="https://example.com">
                                        </div>
                                        <div class="form-group">
                                            <label>Описание</label>
                                            <textarea name="description" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-add-link">
                                            <span class="btn-text">Добавить ссылку</span>
                                            <span class="btn-loader" style="display: none;">⏳</span>
                                        </button>
                                    </form>

                                    <!-- File Form -->
                                    <form class="material-form file-form">
                                        <div class="form-group">
                                            <label>Название *</label>
                                            <input type="text" name="title" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Файл *</label>
                                            <input type="file" name="file" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Описание</label>
                                            <textarea name="description" rows="3"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-add-file">
                                            <span class="btn-text">Загрузить файл</span>
                                            <span class="btn-loader" style="display: none;">⏳</span>
                                        </button>
                                    </form>
                                </div>

                                <!-- Materials List -->
                                <div class="materials-list">
                                    <h3>Существующие материалы</h3>

                                    <?php if (!empty($parsed_materials)) : ?>
                                        <div class="materials-grid">
                                            <?php foreach ($parsed_materials as $material) : ?>
                                                <div class="material-card" data-index="<?php echo esc_attr($material['index']); ?>">
                                                    <div class="material-header">
                                                        <span class="material-type"><?php echo $material['type'] === 'file' ? '📎 Файл' : '🔗 Ссылка'; ?></span>
                                                        <button type="button" class="delete-material" data-index="<?php echo esc_attr($material['index']); ?>" title="Удалить">×</button>
                                                    </div>
                                                    <h4><?php echo esc_html($material['title']); ?></h4>
                                                    <?php if (!empty($material['description'])) : ?>
                                                        <p><?php echo esc_html($material['description']); ?></p>
                                                    <?php endif; ?>
                                                    <a href="<?php echo esc_url($material['url']); ?>" target="_blank" class="material-link">Открыть →</a>
                                                    <?php if (!empty($material['formatted_date'])) : ?>
                                                        <div class="material-date"><?php echo esc_html($material['formatted_date']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="materials-empty">
                                            <p>Материалов пока нет. Добавьте первый материал выше.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
