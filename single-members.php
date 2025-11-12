<?php
/**
 * Template Name: Modern Single Member Template
 * Template Post Type: members
 *
 * Современный шаблон для отображения персональной страницы участника
 */

get_header();

while (have_posts()) : the_post();
    $member_id = get_the_ID();

    // Получаем все метаданные
    $position = get_post_meta($member_id, 'member_position', true);
    $company = get_post_meta($member_id, 'member_company', true);
    $email = get_post_meta($member_id, 'member_email', true);
    $phone = get_post_meta($member_id, 'member_phone', true);
    $bio = get_post_meta($member_id, 'member_bio', true);
    $specialization = get_post_meta($member_id, 'member_specialization', true);
    $experience = get_post_meta($member_id, 'member_experience', true);
    $interests = get_post_meta($member_id, 'member_interests', true);
    $linkedin = get_post_meta($member_id, 'member_linkedin', true);
    $website = get_post_meta($member_id, 'member_website', true);
    $expectations = get_post_meta($member_id, 'member_expectations', true);
    $gallery_ids = get_post_meta($member_id, 'member_gallery', true);

    // Табы
    $testimonials = get_post_meta($member_id, 'member_testimonials', true);
    $gratitudes = get_post_meta($member_id, 'member_gratitudes', true);
    $interviews = get_post_meta($member_id, 'member_interviews', true);
    $videos = get_post_meta($member_id, 'member_videos', true);
    $reviews = get_post_meta($member_id, 'member_reviews', true);
    $developments = get_post_meta($member_id, 'member_developments', true);

    // Получаем таксономии
    $types = wp_get_post_terms($member_id, 'member_type');
    $roles = wp_get_post_terms($member_id, 'member_role');
    $locations = wp_get_post_terms($member_id, 'member_location');

    // Подготовка галереи
    $gallery_images = array();
    if (has_post_thumbnail()) {
        $gallery_images[] = get_post_thumbnail_id();
    }
    if ($gallery_ids) {
        $additional_ids = explode(',', $gallery_ids);
        $gallery_images = array_merge($gallery_images, $additional_ids);
    }
    $gallery_images = array_unique(array_filter($gallery_images));
?>

<!-- Подключаем Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<div class="metoda-member-single">
    <!-- Hero секция с фото и основной информацией -->
    <div class="member-hero-section">
        <div class="member-hero-overlay"></div>
        <div class="container-metoda">
            <div class="member-hero-grid">
                <!-- Фото / Слайдер -->
                <div class="member-photo-container">
                    <?php if (count($gallery_images) > 1) : ?>
                        <div class="swiper member-photo-swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($gallery_images as $img_id) : ?>
                                    <div class="swiper-slide">
                                        <?php echo wp_get_attachment_image($img_id, 'large', false, array('class' => 'member-photo-slide')); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-pagination"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    <?php elseif (count($gallery_images) === 1) : ?>
                        <div class="member-photo-single">
                            <?php echo wp_get_attachment_image($gallery_images[0], 'large'); ?>
                        </div>
                    <?php else : ?>
                        <div class="member-avatar-placeholder">
                            <?php echo mb_substr(get_the_title(), 0, 1); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Информация -->
                <div class="member-hero-info">
                    <h1 class="member-name"><?php the_title(); ?></h1>

                    <?php if ($position) : ?>
                        <div class="member-position"><?php echo esc_html($position); ?></div>
                    <?php endif; ?>

                    <?php if ($company) : ?>
                        <div class="member-company"><?php echo esc_html($company); ?></div>
                    <?php endif; ?>

                    <?php if ($locations) : ?>
                        <div class="member-location">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M8 0C4.687 0 2 2.687 2 6c0 3.854 5.328 9.49 5.547 9.73a.75.75 0 0 0 .906 0C8.672 15.49 14 9.854 14 6c0-3.313-2.687-6-6-6zm0 8.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                            </svg>
                            <?php echo esc_html(wp_list_pluck($locations, 'name')[0]); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Роли в ассоциации -->
                    <?php if (!empty($roles)) : ?>
                        <div class="member-roles-section">
                            <h3>Роль в ассоциации</h3>
                            <div class="member-roles">
                                <?php foreach ($roles as $role) : ?>
                                    <span class="role-badge"><?php echo esc_html($role->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Типы участника -->
                    <?php if (!empty($types)) : ?>
                        <div class="member-types">
                            <?php foreach ($types as $type) : ?>
                                <span class="type-badge"><?php echo esc_html($type->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Контакты -->
                    <div class="member-contacts">
                        <?php if ($email) : ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="contact-btn">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4zm2 0v.217l6 3.429 6-3.429V4H4zm12 2.017L10.7 9.31a1.5 1.5 0 0 1-1.4 0L4 6.017V16h12V6.017z"/>
                                </svg>
                                <span>Email</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($phone) : ?>
                            <a href="tel:<?php echo esc_attr($phone); ?>" class="contact-btn">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/>
                                </svg>
                                <span>Позвонить</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="contact-btn">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M16 0H4a4 4 0 0 0-4 4v12a4 4 0 0 0 4 4h12a4 4 0 0 0 4-4V4a4 4 0 0 0-4-4zM7 15H5V8h2v7zm-1-8.2A1.2 1.2 0 1 1 6 4.4a1.2 1.2 0 0 1 0 2.4zM15 15h-2v-3.5c0-2.5-3-2.3-3 0V15H8V8h2v1.1c1-1.9 5-2 5 1.8V15z"/>
                                </svg>
                                <span>LinkedIn</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($website) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" class="contact-btn">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm6.93 6h-2.95a15.65 15.65 0 0 0-1.38-3.56A8.03 8.03 0 0 1 16.93 6zM10 2.04c.83 1.2 1.48 2.53 1.91 3.96H8.09c.43-1.43 1.08-2.76 1.91-3.96zM2.26 12C2.1 11.36 2 10.69 2 10s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H2.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 0 1 3.08 14zm2.95-8H3.08a7.987 7.987 0 0 1 4.33-3.56A15.65 15.65 0 0 0 6.03 6zM10 17.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM12.34 12H7.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56zM14.36 12c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/>
                                </svg>
                                <span>Вебсайт</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="member-content-section">
        <div class="container-metoda">
            <div class="member-content-grid">
                <!-- Основная информация -->
                <div class="member-main-content">
                    <?php if ($specialization || $experience) : ?>
                        <div class="info-card">
                            <h2 class="section-title">Специализация и стаж</h2>
                            <?php if ($specialization) : ?>
                                <div class="info-block">
                                    <h4>Специализация</h4>
                                    <p><?php echo nl2br(esc_html($specialization)); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($experience) : ?>
                                <div class="info-block">
                                    <h4>Опыт работы</h4>
                                    <p><?php echo esc_html($experience); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($interests) : ?>
                        <div class="info-card">
                            <h2 class="section-title">Сфера профессиональных интересов</h2>
                            <p><?php echo nl2br(esc_html($interests)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($expectations) : ?>
                        <div class="info-card">
                            <h2 class="section-title">Ожидания от сотрудничества с ассоциацией</h2>
                            <p><?php echo nl2br(esc_html($expectations)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($bio) : ?>
                        <div class="info-card bio-card">
                            <h2 class="section-title">О себе</h2>
                            <div class="bio-content">
                                <?php echo nl2br(esc_html($bio)); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Табы с дополнительными материалами -->
                    <?php if ($testimonials || $gratitudes || $interviews || $videos || $reviews || $developments) : ?>
                        <div class="info-card tabs-card">
                            <div class="tabs-container">
                                <div class="tabs-header">
                                    <?php if ($testimonials) : ?><button class="tab-btn active" data-tab="testimonials">Отзывы</button><?php endif; ?>
                                    <?php if ($gratitudes) : ?><button class="tab-btn" data-tab="gratitudes">Благодарности</button><?php endif; ?>
                                    <?php if ($interviews) : ?><button class="tab-btn" data-tab="interviews">Интервью</button><?php endif; ?>
                                    <?php if ($videos) : ?><button class="tab-btn" data-tab="videos">Видео</button><?php endif; ?>
                                    <?php if ($reviews) : ?><button class="tab-btn" data-tab="reviews">Рецензии</button><?php endif; ?>
                                    <?php if ($developments) : ?><button class="tab-btn" data-tab="developments">Разработки</button><?php endif; ?>
                                </div>

                                <div class="tabs-content">
                                    <?php if ($testimonials) : ?>
                                        <div class="tab-pane active" id="testimonials">
                                            <?php echo nl2br(esc_html($testimonials)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($gratitudes) : ?>
                                        <div class="tab-pane" id="gratitudes">
                                            <?php echo nl2br(esc_html($gratitudes)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($interviews) : ?>
                                        <div class="tab-pane" id="interviews">
                                            <?php
                                            $interview_links = array_filter(array_map('trim', explode(',', $interviews)));
                                            foreach ($interview_links as $link) {
                                                echo '<a href="' . esc_url($link) . '" target="_blank" class="resource-link">' . esc_html($link) . '</a>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($videos) : ?>
                                        <div class="tab-pane" id="videos">
                                            <?php
                                            $video_links = array_filter(array_map('trim', explode(',', $videos)));
                                            foreach ($video_links as $link) {
                                                // Проверяем, это YouTube или Vimeo
                                                if (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false) {
                                                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $link, $match);
                                                    if (isset($match[1])) {
                                                        echo '<div class="video-embed"><iframe width="100%" height="400" src="https://www.youtube.com/embed/' . $match[1] . '" frameborder="0" allowfullscreen></iframe></div>';
                                                    }
                                                } else {
                                                    echo '<a href="' . esc_url($link) . '" target="_blank" class="resource-link">' . esc_html($link) . '</a>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($reviews) : ?>
                                        <div class="tab-pane" id="reviews">
                                            <?php echo nl2br(esc_html($reviews)); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($developments) : ?>
                                        <div class="tab-pane" id="developments">
                                            <?php
                                            $dev_links = array_filter(array_map('trim', explode(',', $developments)));
                                            foreach ($dev_links as $link) {
                                                echo '<a href="' . esc_url($link) . '" target="_blank" class="resource-link">' . esc_html($link) . '</a>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Сайдбар -->
                <aside class="member-sidebar">
                    <div class="sidebar-card">
                        <h3>Другие участники</h3>
                        <?php
                        $related_args = array(
                            'post_type' => 'members',
                            'posts_per_page' => 5,
                            'post__not_in' => array($member_id),
                            'orderby' => 'rand',
                        );

                        if ($roles) {
                            $role_slugs = wp_list_pluck($roles, 'slug');
                            $related_args['tax_query'] = array(
                                array(
                                    'taxonomy' => 'member_role',
                                    'field' => 'slug',
                                    'terms' => $role_slugs,
                                ),
                            );
                        }

                        $related_query = new WP_Query($related_args);

                        if ($related_query->have_posts()) : ?>
                            <div class="related-members">
                                <?php while ($related_query->have_posts()) : $related_query->the_post(); ?>
                                    <a href="<?php the_permalink(); ?>" class="related-member">
                                        <div class="related-photo">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php the_post_thumbnail('thumbnail'); ?>
                                            <?php else : ?>
                                                <div class="related-avatar">
                                                    <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-info">
                                            <h4><?php the_title(); ?></h4>
                                            <?php
                                            $rel_position = get_post_meta(get_the_ID(), 'member_position', true);
                                            if ($rel_position) : ?>
                                                <p><?php echo esc_html($rel_position); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        <?php endif;
                        wp_reset_postdata();
                        ?>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
/* ===== ОСНОВНЫЕ СТИЛИ ===== */
.metoda-member-single {
    background: #f8f9fb;
    min-height: 100vh;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

.container-metoda {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ===== HERO СЕКЦИЯ ===== */
.member-hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}

.member-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.08) 0%, transparent 50%);
    pointer-events: none;
}

.member-hero-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 60px;
    align-items: start;
    position: relative;
    z-index: 1;
}

/* ===== ФОТО / СЛАЙДЕР ===== */
.member-photo-container {
    position: relative;
}

.member-photo-swiper {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.member-photo-slide img {
    width: 100%;
    height: 500px;
    object-fit: cover;
}

.member-photo-single {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.member-photo-single img {
    width: 100%;
    height: 500px;
    object-fit: cover;
}

.member-avatar-placeholder {
    width: 100%;
    height: 500px;
    border-radius: 20px;
    background: rgba(255,255,255,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 120px;
    font-weight: 700;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.swiper-button-prev,
.swiper-button-next {
    color: white;
    background: rgba(0,0,0,0.3);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    backdrop-filter: blur(10px);
}

.swiper-button-prev:after,
.swiper-button-next:after {
    font-size: 20px;
}

.swiper-pagination-bullet {
    background: white;
    opacity: 0.5;
}

.swiper-pagination-bullet-active {
    opacity: 1;
}

/* ===== ИНФОРМАЦИЯ В HERO ===== */
.member-hero-info {
    padding: 20px 0;
}

.member-name {
    font-size: 48px;
    font-weight: 700;
    margin: 0 0 16px 0;
    line-height: 1.2;
}

.member-position {
    font-size: 24px;
    opacity: 0.95;
    margin-bottom: 8px;
    font-weight: 500;
}

.member-company {
    font-size: 18px;
    opacity: 0.85;
    margin-bottom: 20px;
}

.member-location {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 32px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.15);
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

/* ===== РОЛИ И ТИПЫ ===== */
.member-roles-section {
    margin: 32px 0;
}

.member-roles-section h3 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.8;
    margin: 0 0 16px 0;
    font-weight: 600;
}

.member-roles {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.role-badge {
    padding: 10px 20px;
    background: rgba(255,255,255,0.25);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.role-badge:hover {
    background: rgba(255,255,255,0.35);
    transform: translateY(-2px);
}

.member-types {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 32px;
}

.type-badge {
    padding: 8px 16px;
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

/* ===== КОНТАКТЫ ===== */
.member-contacts {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
}

.contact-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 30px;
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.contact-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.contact-btn svg {
    width: 20px;
    height: 20px;
}

/* ===== ОСНОВНОЙ КОНТЕНТ ===== */
.member-content-section {
    padding: 60px 0;
}

.member-content-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 40px;
}

.info-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}

.info-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 24px 0;
    padding-bottom: 16px;
    border-bottom: 3px solid #667eea;
}

.info-block {
    margin-bottom: 24px;
}

.info-block:last-child {
    margin-bottom: 0;
}

.info-block h4 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #667eea;
    margin: 0 0 12px 0;
    font-weight: 700;
}

.info-block p {
    font-size: 16px;
    line-height: 1.7;
    color: #4a5568;
    margin: 0;
}

.bio-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.bio-content {
    font-size: 17px;
    line-height: 1.8;
    color: #2d3748;
    font-style: italic;
}

/* ===== ТАБЫ ===== */
.tabs-card {
    padding: 0;
    overflow: hidden;
}

.tabs-header {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 24px 24px 0 24px;
    background: #f8f9fb;
    border-bottom: 2px solid #e2e8f0;
}

.tab-btn {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.tab-btn:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.tab-btn.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

.tabs-content {
    padding: 40px;
}

.tab-pane {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.resource-link {
    display: block;
    padding: 16px 20px;
    margin-bottom: 12px;
    background: #f8f9fb;
    border-radius: 12px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border-left: 4px solid #667eea;
}

.resource-link:hover {
    background: #667eea;
    color: white;
    transform: translateX(8px);
}

.video-embed {
    margin-bottom: 24px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* ===== САЙДБАР ===== */
.sidebar-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    position: sticky;
    top: 24px;
}

.sidebar-card h3 {
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    margin: 0 0 24px 0;
}

.related-members {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.related-member {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.related-member:hover {
    background: #f8f9fb;
    transform: translateX(8px);
}

.related-photo {
    flex-shrink: 0;
}

.related-photo img {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    object-fit: cover;
}

.related-avatar {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.related-info h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0 0 4px 0;
}

.related-info p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
}

/* ===== АДАПТИВНОСТЬ ===== */
@media (max-width: 1024px) {
    .member-hero-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .member-photo-container {
        max-width: 500px;
        margin: 0 auto;
    }

    .member-content-grid {
        grid-template-columns: 1fr;
    }

    .sidebar-card {
        position: relative;
        top: 0;
    }
}

@media (max-width: 768px) {
    .member-hero-section {
        padding: 40px 0;
    }

    .member-name {
        font-size: 32px;
    }

    .member-position {
        font-size: 18px;
    }

    .info-card {
        padding: 24px;
    }

    .section-title {
        font-size: 22px;
    }

    .tabs-header {
        padding: 16px 16px 0 16px;
    }

    .tab-btn {
        padding: 10px 16px;
        font-size: 13px;
    }

    .tabs-content {
        padding: 24px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Swiper
    <?php if (count($gallery_images) > 1) : ?>
    new Swiper('.member-photo-swiper', {
        loop: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
    });
    <?php endif; ?>

    // Табы
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            // Убираем активные классы
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Добавляем активные классы
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
});
</script>

<?php endwhile; ?>

<?php get_footer(); ?>
