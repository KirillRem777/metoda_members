<?php
/**
 * Template Name: New Member Profile Template
 * Template Post Type: members
 *
 * Современный шаблон профиля участника на базе Tailwind CSS
 */

get_header();

while (have_posts()) : the_post();
    $member_id = get_the_ID();

    // Получаем все метаданные
    $position = get_post_meta($member_id, 'member_position', true);
    $company = get_post_meta($member_id, 'member_company', true);
    $city = get_post_meta($member_id, 'member_city', true);
    $email = get_post_meta($member_id, 'member_email', true);
    $phone = get_post_meta($member_id, 'member_phone', true);
    $linkedin = get_post_meta($member_id, 'member_linkedin', true);
    $website = get_post_meta($member_id, 'member_website', true);

    // Новые поля
    $specialization_experience = get_post_meta($member_id, 'member_specialization_experience', true);
    $professional_interests = get_post_meta($member_id, 'member_professional_interests', true);
    $expectations = get_post_meta($member_id, 'member_expectations', true);
    $bio = get_post_meta($member_id, 'member_bio', true);

    // Получаем таксономии
    $roles = wp_get_post_terms($member_id, 'member_role');
    $locations = wp_get_post_terms($member_id, 'member_location');

    // Получаем галерею фотографий
    $gallery_ids_string = get_post_meta($member_id, 'member_gallery', true);
    $gallery_ids = !empty($gallery_ids_string) ? explode(',', $gallery_ids_string) : array();

    // Обработка буллетов для специализации
    $specialization_items = array();
    if ($specialization_experience) {
        // Разделяем по символу | (данные из CSV)
        $lines = explode('|', $specialization_experience);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Убираем символ буллета если он есть
                $line = preg_replace('/^[•\-\*]\s*/', '', $line);
                if (!empty($line)) {
                    $specialization_items[] = $line;
                }
            }
        }
    }

    // Обработка буллетов для интересов
    $interest_items = array();
    if ($professional_interests) {
        // Разделяем по символу | (данные из CSV)
        $lines = explode('|', $professional_interests);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Убираем символ буллета если он есть
                $line = preg_replace('/^[•\-\*]\s*/', '', $line);
                if (!empty($line)) {
                    $interest_items[] = $line;
                }
            }
        }
    }

    // Цвета "Метода" - синий и оранжевый
    $primary_color = '#0066cc'; // Синий
    $accent_color = '#ff6600';  // Оранжевый
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        * { font-family: 'Inter', sans-serif; }

        .metoda-primary { color: <?php echo $primary_color; ?>; }
        .metoda-primary-bg { background-color: <?php echo $primary_color; ?>; }
        .metoda-accent { color: <?php echo $accent_color; ?>; }
        .metoda-accent-bg { background-color: <?php echo $accent_color; ?>; }

        .member-content h1, .member-content h2, .member-content h3,
        .member-content h4, .member-content h5, .member-content h6 {
            margin-top: 1.5em;
            margin-bottom: 0.75em;
            font-weight: 600;
        }

        .member-content p { margin-bottom: 1em; line-height: 1.7; }
        .member-content ul, .member-content ol {
            margin-left: 1.5em;
            margin-bottom: 1em;
        }
        .member-content ul { list-style-type: disc; }
        .member-content ol { list-style-type: decimal; }
        .member-content li { margin-bottom: 0.5em; }
        .member-content strong { font-weight: 600; }
        .member-content em { font-style: italic; }
        .member-content br { display: block; content: ""; margin-top: 0.5em; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $primary_color; ?>',
                        accent: '<?php echo $accent_color; ?>',
                        secondary: '#64748b'
                    }
                }
            }
        }
    </script>
    <?php wp_head(); ?>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="<?php echo get_post_type_archive_link('members'); ?>" class="text-gray-600 hover:text-primary transition-colors">
                        <i class="fa-solid fa-arrow-left text-lg"></i>
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900">Профиль участника</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="metoda-primary-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                        <i class="fa-solid fa-envelope mr-2"></i>
                        Написать
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="text-gray-600 hover:text-primary transition-colors">
                        <i class="fa-solid fa-print text-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">

        <!-- Hero Section -->
        <section class="bg-white rounded-xl shadow-sm border p-8 mb-8">
            <div class="flex items-start space-x-8 flex-col md:flex-row">
                <!-- Photo -->
                <div class="flex-shrink-0">
                    <div class="w-48 h-48 rounded-2xl overflow-hidden bg-gray-100">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover')); ?>
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-6xl font-bold text-gray-300">
                                <?php echo mb_substr(get_the_title(), 0, 1); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info -->
                <div class="flex-1">
                    <div class="mb-4">
                        <?php if ($roles && !is_wp_error($roles)): ?>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php foreach ($roles as $role): ?>
                                    <span class="inline-block metoda-primary-bg text-white px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo esc_html($role->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h1 class="text-4xl font-bold text-gray-900 mb-2"><?php the_title(); ?></h1>

                        <?php if ($position): ?>
                            <h2 class="text-2xl font-semibold text-gray-700 mb-3"><?php echo esc_html($position); ?></h2>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <?php if ($company): ?>
                        <div class="flex items-center text-gray-600">
                            <i class="fa-solid fa-building metoda-primary mr-3"></i>
                            <span class="font-medium"><?php echo esc_html($company); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($city): ?>
                        <div class="flex items-center text-gray-600">
                            <i class="fa-solid fa-location-dot metoda-primary mr-3"></i>
                            <span><?php echo esc_html($city); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column (Main Content) -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Professional Information -->
                <section class="bg-white rounded-xl shadow-sm border p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Профессиональная информация</h3>

                    <div class="space-y-8">

                        <!-- Specialization and Experience -->
                        <?php if (!empty($specialization_items)): ?>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Специализация и стаж</h4>
                            <ul class="space-y-3">
                                <?php foreach ($specialization_items as $item): ?>
                                <li class="flex items-start">
                                    <div class="w-2 h-2 metoda-primary-bg rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                    <span class="text-gray-700"><?php echo esc_html($item); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Professional Interests -->
                        <?php if (!empty($interest_items)): ?>
                        <div>
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Сфера профессиональных интересов</h4>
                            <ul class="space-y-3">
                                <?php foreach ($interest_items as $item): ?>
                                <li class="flex items-start">
                                    <div class="w-2 h-2 metoda-accent-bg rounded-full mt-2 mr-3 flex-shrink-0"></div>
                                    <span class="text-gray-700"><?php echo esc_html($item); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- About Me -->
                <?php if ($bio): ?>
                <div class="bg-white rounded-xl shadow-sm border p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">О себе</h3>
                    <div class="member-content prose prose-gray max-w-none text-gray-700">
                        <?php echo wpautop($bio); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Collaboration Expectations -->
                <?php if ($expectations): ?>
                <div class="bg-white rounded-xl shadow-sm border p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Ожидания от сотрудничества</h3>
                    <div class="member-content prose prose-gray max-w-none text-gray-700">
                        <?php echo wpautop($expectations); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Photo Gallery -->
                <?php if (!empty($gallery_ids) && count($gallery_ids) > 1): ?>
                <div class="bg-white rounded-xl shadow-sm border p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">
                        <i class="fa-solid fa-images metoda-primary mr-2"></i>
                        Фотогалерея
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($gallery_ids as $attachment_id):
                            $attachment_id = intval(trim($attachment_id));
                            if (!$attachment_id) continue;
                            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
                            $image_full = wp_get_attachment_image_url($attachment_id, 'full');
                            if (!$image_url) continue;
                        ?>
                        <a href="<?php echo esc_url($image_full); ?>"
                           class="gallery-item group relative overflow-hidden rounded-lg aspect-square bg-gray-100 hover:opacity-90 transition-opacity"
                           data-lightbox="member-gallery">
                            <img src="<?php echo esc_url($image_url); ?>"
                                 alt="<?php the_title(); ?>"
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                                <i class="fa-solid fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column (Sidebar) -->
            <aside class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">

                    <!-- Contact Card -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Контактная информация</h4>
                        <div class="space-y-3">
                            <?php if ($email): ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="flex items-center text-gray-600 hover:text-primary transition-colors">
                                <i class="fa-solid fa-envelope metoda-primary mr-3"></i>
                                <span class="text-sm break-all"><?php echo esc_html($email); ?></span>
                            </a>
                            <?php endif; ?>

                            <?php if ($phone): ?>
                            <a href="tel:<?php echo esc_attr($phone); ?>" class="flex items-center text-gray-600 hover:text-primary transition-colors">
                                <i class="fa-solid fa-phone metoda-primary mr-3"></i>
                                <span class="text-sm"><?php echo esc_html($phone); ?></span>
                            </a>
                            <?php endif; ?>

                            <?php if ($linkedin): ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener" class="flex items-center text-gray-600 hover:text-primary transition-colors">
                                <i class="fa-brands fa-linkedin metoda-primary mr-3"></i>
                                <span class="text-sm">LinkedIn</span>
                            </a>
                            <?php endif; ?>

                            <?php if ($website): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="flex items-center text-gray-600 hover:text-primary transition-colors">
                                <i class="fa-solid fa-globe metoda-primary mr-3"></i>
                                <span class="text-sm">Вебсайт</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="space-y-3">
                            <?php if ($email): ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>" class="block w-full metoda-primary-bg text-white text-center py-3 px-4 rounded-lg hover:opacity-90 transition-opacity font-medium">
                                <i class="fa-solid fa-envelope mr-2"></i>
                                Отправить сообщение
                            </a>
                            <?php endif; ?>

                            <a href="<?php echo get_post_type_archive_link('members'); ?>" class="block w-full border border-gray-300 text-gray-700 text-center py-3 px-4 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                <i class="fa-solid fa-arrow-left mr-2"></i>
                                К списку участников
                            </a>

                            <button onclick="window.print()" class="w-full border border-gray-300 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                                <i class="fa-solid fa-print mr-2"></i>
                                Распечатать
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <?php wp_footer(); ?>

    <!-- Simple Lightbox for Gallery -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const galleryItems = document.querySelectorAll('.gallery-item');
        if (galleryItems.length === 0) return;

        // Create lightbox overlay
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4';
        overlay.innerHTML = `
            <button class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300 transition-colors" onclick="this.parentElement.classList.add('hidden')">&times;</button>
            <img src="" alt="" class="max-w-full max-h-full object-contain">
        `;
        document.body.appendChild(overlay);

        const overlayImg = overlay.querySelector('img');

        galleryItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                overlayImg.src = this.href;
                overlay.classList.remove('hidden');
            });
        });

        // Close on overlay click (but not on image)
        overlay.addEventListener('click', function(e) {
            if (e.target === this || e.target.tagName === 'BUTTON') {
                this.classList.add('hidden');
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
                overlay.classList.add('hidden');
            }
        });
    });
    </script>
</body>
</html>

<?php
endwhile;
?>
