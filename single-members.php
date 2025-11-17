<?php
/**
 * Template Name: Single Member Template
 * Template Post Type: members
 * 
 * Шаблон для отображения персональной страницы участника
 * Поместите этот файл в папку вашей темы: /wp-content/themes/your-theme/single-members.php
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
    $specialization = get_post_meta($member_id, 'member_specialization_experience', true);
    $experience = get_post_meta($member_id, 'member_experience', true);
    $interests = get_post_meta($member_id, 'member_professional_interests', true);
    $linkedin = get_post_meta($member_id, 'member_linkedin', true);
    $website = get_post_meta($member_id, 'member_website', true);
    
    // Получаем таксономии
    $types = wp_get_post_terms($member_id, 'member_type');
    $roles = wp_get_post_terms($member_id, 'member_role');
    $locations = wp_get_post_terms($member_id, 'member_location');
?>

<div class="member-single-wrapper">
    <div class="member-hero">
        <div class="container">
            <div class="member-hero-content">
                <div class="member-hero-photo">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large'); ?>
                    <?php else : ?>
                        <div class="member-avatar-large">
                            <?php echo mb_substr(get_the_title(), 0, 1); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="member-hero-info">
                    <h1 class="member-title"><?php the_title(); ?></h1>
                    
                    <?php if ($position) : ?>
                        <div class="member-position-large"><?php echo esc_html($position); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($company) : ?>
                        <div class="member-company-large"><?php echo esc_html($company); ?></div>
                    <?php endif; ?>
                    
                    <div class="member-badges">
                        <?php foreach ($types as $type) : ?>
                            <span class="badge badge-type"><?php echo esc_html($type->name); ?></span>
                        <?php endforeach; ?>
                        
                        <?php foreach ($roles as $role) : ?>
                            <span class="badge badge-role"><?php echo esc_html($role->name); ?></span>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($locations) : ?>
                        <div class="member-location">
                            <svg class="icon-location" width="16" height="16" fill="currentColor">
                                <path d="M8 0C4.687 0 2 2.687 2 6c0 3.854 5.328 9.49 5.547 9.73a.75.75 0 0 0 .906 0C8.672 15.49 14 9.854 14 6c0-3.313-2.687-6-6-6zm0 8.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                            </svg>
                            <?php 
                            $location_names = array();
                            foreach ($locations as $location) {
                                $location_names[] = $location->name;
                            }
                            echo esc_html(implode(', ', $location_names));
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="member-contacts">
                        <?php if ($email) : ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="contact-link">
                                <svg class="icon" width="20" height="20" fill="currentColor">
                                    <path d="M2 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4zm2 0v.217l6 3.429 6-3.429V4H4zm12 2.017L10.7 9.31a1.5 1.5 0 0 1-1.4 0L4 6.017V16h12V6.017z"/>
                                </svg>
                                Email
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($phone) : ?>
                            <a href="tel:<?php echo esc_attr($phone); ?>" class="contact-link">
                                <svg class="icon" width="20" height="20" fill="currentColor">
                                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328z"/>
                                </svg>
                                Позвонить
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" class="contact-link">
                                <svg class="icon" width="20" height="20" fill="currentColor">
                                    <path d="M16 0H4a4 4 0 0 0-4 4v12a4 4 0 0 0 4 4h12a4 4 0 0 0 4-4V4a4 4 0 0 0-4-4zM7 15H5V8h2v7zm-1-8.2A1.2 1.2 0 1 1 6 4.4a1.2 1.2 0 0 1 0 2.4zM15 15h-2v-3.5c0-2.5-3-2.3-3 0V15H8V8h2v1.1c1-1.9 5-2 5 1.8V15z"/>
                                </svg>
                                LinkedIn
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($website) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" class="contact-link">
                                <svg class="icon" width="20" height="20" fill="currentColor">
                                    <path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm6.93 6h-2.95a15.65 15.65 0 0 0-1.38-3.56A8.03 8.03 0 0 1 16.93 6zM10 2.04c.83 1.2 1.48 2.53 1.91 3.96H8.09c.43-1.43 1.08-2.76 1.91-3.96zM2.26 12C2.1 11.36 2 10.69 2 10s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H2.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.987 7.987 0 0 1 3.08 14zm2.95-8H3.08a7.987 7.987 0 0 1 4.33-3.56A15.65 15.65 0 0 0 6.03 6zM10 17.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM12.34 12H7.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56zM14.36 12c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/>
                                </svg>
                                Вебсайт
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="member-content">
        <div class="container">
            <div class="member-main">
                <?php if ($experience || $specialization) : ?>
                <div class="info-section">
                    <h2>Профессиональный опыт</h2>
                    
                    <?php if ($experience) : ?>
                        <div class="info-item">
                            <h3>Стаж работы</h3>
                            <p><?php echo esc_html($experience); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($specialization) : ?>
                        <div class="info-item">
                            <h3>Специализация и стаж</h3>
                            <?php
                            // Парсим специализацию - разделяем по |
                            $spec_items = explode('|', $specialization);
                            if (count($spec_items) > 1) {
                                echo '<ul class="member-bullet-list">';
                                foreach ($spec_items as $item) {
                                    $item = trim($item);
                                    // Убираем символ • в начале если есть
                                    $item = preg_replace('/^[•·●]\s*/', '', $item);
                                    if (!empty($item)) {
                                        echo '<li>' . esc_html($item) . '</li>';
                                    }
                                }
                                echo '</ul>';
                            } else {
                                echo '<p>' . nl2br(esc_html($specialization)) . '</p>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($interests) : ?>
                <div class="info-section">
                    <h2>Сфера профессиональных интересов</h2>
                    <?php
                    // Парсим интересы - разделяем по |
                    $interest_items = explode('|', $interests);
                    if (count($interest_items) > 1) {
                        echo '<ul class="member-bullet-list">';
                        foreach ($interest_items as $item) {
                            $item = trim($item);
                            // Убираем символ • в начале если есть
                            $item = preg_replace('/^[•·●]\s*/', '', $item);
                            if (!empty($item)) {
                                echo '<li>' . esc_html($item) . '</li>';
                            }
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>' . nl2br(esc_html($interests)) . '</p>';
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <?php if (get_the_content()) : ?>
                <div class="info-section">
                    <h2>Подробная информация</h2>
                    <div class="content-formatted">
                        <?php the_content(); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($bio) : ?>
                <div class="info-section">
                    <h2>О себе</h2>
                    <div class="bio-content">
                        <?php echo nl2br(esc_html($bio)); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="member-sidebar">
                <div class="sidebar-widget">
                    <h3>Другие участники</h3>
                    <?php
                    $related_args = array(
                        'post_type' => 'members',
                        'posts_per_page' => 5,
                        'post__not_in' => array($member_id),
                        'orderby' => 'rand',
                    );
                    
                    // Если есть роли, ищем похожих
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
                                <div class="related-member">
                                    <a href="<?php the_permalink(); ?>">
                                        <div class="related-member-photo">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <?php the_post_thumbnail('thumbnail'); ?>
                                            <?php else : ?>
                                                <div class="avatar-small">
                                                    <?php echo mb_substr(get_the_title(), 0, 1); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="related-member-info">
                                            <h4><?php the_title(); ?></h4>
                                            <?php 
                                            $rel_position = get_post_meta(get_the_ID(), 'member_position', true);
                                            if ($rel_position) : ?>
                                                <p><?php echo esc_html($rel_position); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.member-single-wrapper {
    background: #f5f5f5;
    min-height: 100vh;
}

.member-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    color: white;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.member-hero-content {
    display: flex;
    align-items: center;
    gap: 60px;
}

.member-hero-photo {
    flex-shrink: 0;
}

.member-hero-photo img {
    width: 250px;
    height: 250px;
    border-radius: 50%;
    border: 5px solid rgba(255, 255, 255, 0.2);
    object-fit: cover;
}

.member-avatar-large {
    width: 250px;
    height: 250px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 100px;
    font-weight: bold;
    border: 5px solid rgba(255, 255, 255, 0.2);
}

.member-hero-info {
    flex-grow: 1;
}

.member-title {
    font-size: 48px;
    margin: 0 0 15px 0;
}

.member-position-large {
    font-size: 24px;
    opacity: 0.95;
    margin-bottom: 10px;
}

.member-company-large {
    font-size: 18px;
    opacity: 0.85;
    margin-bottom: 20px;
}

.member-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.badge {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-type {
    background: rgba(255, 255, 255, 0.25);
}

.badge-role {
    background: rgba(255, 255, 255, 0.15);
}

.member-location {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 25px;
}

.icon-location {
    fill: currentColor;
}

.member-contacts {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.contact-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 25px;
    color: white;
    text-decoration: none;
    transition: background 0.3s;
}

.contact-link:hover {
    background: rgba(255, 255, 255, 0.3);
}

.icon {
    fill: currentColor;
}

.member-content {
    padding: 60px 0;
}

.member-content .container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 60px;
}

.info-section {
    background: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.info-section h2 {
    font-size: 28px;
    margin: 0 0 25px 0;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
}

.info-item {
    margin-bottom: 25px;
}

.info-item h3 {
    font-size: 16px;
    font-weight: 600;
    color: #666;
    margin: 0 0 10px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item p {
    font-size: 16px;
    line-height: 1.6;
    color: #444;
    margin: 0;
}

.content-formatted {
    font-size: 16px;
    line-height: 1.8;
    color: #444;
}

.bio-content {
    font-size: 16px;
    line-height: 1.8;
    color: #444;
    font-style: italic;
}

.sidebar-widget {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: sticky;
    top: 30px;
}

.sidebar-widget h3 {
    font-size: 20px;
    margin: 0 0 20px 0;
    color: #333;
}

.related-members {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.related-member a {
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s;
}

.related-member a:hover {
    transform: translateX(5px);
}

.related-member-photo img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.avatar-small {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
}

.related-member-info h4 {
    font-size: 16px;
    margin: 0 0 5px 0;
    color: #333;
}

.related-member-info p {
    font-size: 13px;
    color: #666;
    margin: 0;
}

@media (max-width: 992px) {
    .member-hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .member-content .container {
        grid-template-columns: 1fr;
    }
    
    .sidebar-widget {
        position: relative;
        top: 0;
    }
}

@media (max-width: 768px) {
    .member-title {
        font-size: 32px;
    }
    
    .member-position-large {
        font-size: 18px;
    }
    
    .member-hero-photo img,
    .member-avatar-large {
        width: 180px;
        height: 180px;
    }
    
    .info-section {
        padding: 25px;
    }
}
</style>

<?php endwhile; ?>

<?php get_footer(); ?>