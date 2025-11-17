<?php
/**
 * Template: Member Onboarding
 *
 * Welcome screen and password change for new members
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$member_id = Member_User_Link::get_current_user_member_id();
$current_step = Member_Onboarding::get_user_step($user_id);
$member_data = $member_id ? Member_Dashboard::get_member_data($member_id) : null;
?>

<div class="member-onboarding">
    <!-- Background Decoration -->
    <div class="onboarding-bg">
        <div class="bg-circle circle-1"></div>
        <div class="bg-circle circle-2"></div>
        <div class="bg-circle circle-3"></div>
    </div>

    <!-- Onboarding Container -->
    <div class="onboarding-container">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo $current_step === 'password' ? 'active' : 'completed'; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Смена пароля</div>
            </div>
            <div class="step-line <?php echo $current_step === 'welcome' ? 'active' : ''; ?>"></div>
            <div class="step <?php echo $current_step === 'welcome' ? 'active' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Добро пожаловать</div>
            </div>
        </div>

        <!-- Password Change Step -->
        <div class="onboarding-step <?php echo $current_step === 'password' ? 'active' : 'hidden'; ?>" id="step-password">
            <div class="step-content">
                <div class="step-icon">🔐</div>
                <h1>Добро пожаловать, <?php echo esc_html($current_user->display_name); ?>!</h1>
                <p class="step-description">
                    Для безопасности вашей учетной записи, пожалуйста, смените временный пароль на собственный.
                </p>

                <form id="password-change-form" class="onboarding-form">
                    <div class="form-group">
                        <label for="current_password">
                            <span class="icon">🔑</span>
                            Текущий пароль (временный)
                        </label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            <span class="icon">🆕</span>
                            Новый пароль
                        </label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                        <small class="form-hint">Минимум 8 символов</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <span class="icon">✅</span>
                            Подтвердите новый пароль
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                        <div class="strength-text">Введите пароль</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-large">
                        <span class="btn-text">Сменить пароль</span>
                        <span class="btn-loader" style="display: none;">⏳</span>
                    </button>

                    <div class="form-message" style="display: none;"></div>
                </form>
            </div>
        </div>

        <!-- Welcome Step -->
        <div class="onboarding-step <?php echo $current_step === 'welcome' ? 'active' : 'hidden'; ?>" id="step-welcome">
            <div class="step-content">
                <div class="welcome-hero">
                    <div class="hero-icon">🎉</div>
                    <h1>Добро пожаловать в сообщество!</h1>
                    <p class="hero-subtitle">Мы рады видеть вас среди участников нашей ассоциации</p>
                </div>

                <?php if ($member_data) : ?>
                    <div class="member-preview">
                        <div class="preview-photo">
                            <?php if ($member_data['thumbnail_url']) : ?>
                                <img src="<?php echo esc_url($member_data['thumbnail_url']); ?>" alt="<?php echo esc_attr($member_data['name']); ?>">
                            <?php else : ?>
                                <div class="preview-placeholder">
                                    <?php echo mb_substr($member_data['name'], 0, 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="preview-info">
                            <h3><?php echo esc_html($member_data['name']); ?></h3>
                            <?php if (!empty($member_data['member_position'])) : ?>
                                <p><?php echo esc_html($member_data['member_position']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($member_data['member_company'])) : ?>
                                <p class="company"><?php echo esc_html($member_data['member_company']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👤</div>
                        <h3>Ваш профиль</h3>
                        <p>Редактируйте информацию о себе, добавляйте фотографии и контакты</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🖼️</div>
                        <h3>Галерея</h3>
                        <p>Загружайте фотографии и создавайте привлекательную галерею</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📚</div>
                        <h3>Материалы</h3>
                        <p>Делитесь своими работами, отзывами, интервью и достижениями</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🤝</div>
                        <h3>Сообщество</h3>
                        <p>Общайтесь с коллегами и участвуйте в жизни ассоциации</p>
                    </div>
                </div>

                <div class="welcome-actions">
                    <button type="button" class="btn btn-primary btn-large" id="complete-onboarding">
                        <span class="btn-text">Перейти в личный кабинет</span>
                        <span class="btn-icon">→</span>
                    </button>

                    <?php if ($member_data) : ?>
                        <a href="<?php echo esc_url($member_data['permalink']); ?>" class="btn btn-outline" target="_blank">
                            Просмотреть мой профиль
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Text -->
    <div class="onboarding-footer">
        <p>Нужна помощь? <a href="mailto:support@example.com">Свяжитесь с администратором</a></p>
    </div>
</div>
