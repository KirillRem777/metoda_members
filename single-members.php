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
    $telegram = get_post_meta($member_id, 'member_telegram', true);
    $website = get_post_meta($member_id, 'member_website', true);

    // Новые поля
    $specialization_experience = get_post_meta($member_id, 'member_specialization_experience', true);
    $professional_interests = get_post_meta($member_id, 'member_professional_interests', true);
    $expectations = get_post_meta($member_id, 'member_expectations', true);
    $bio = get_post_meta($member_id, 'member_bio', true);

    // Получаем материалы (repeater поля)
    $testimonials_data = get_post_meta($member_id, 'member_testimonials_data', true);
    $gratitudes_data = get_post_meta($member_id, 'member_gratitudes_data', true);
    $interviews_data = get_post_meta($member_id, 'member_interviews_data', true);
    $videos_data = get_post_meta($member_id, 'member_videos_data', true);
    $reviews_data = get_post_meta($member_id, 'member_reviews_data', true);
    $developments_data = get_post_meta($member_id, 'member_developments_data', true);

    $testimonials_data = $testimonials_data ? json_decode($testimonials_data, true) : array();
    $gratitudes_data = $gratitudes_data ? json_decode($gratitudes_data, true) : array();
    $interviews_data = $interviews_data ? json_decode($interviews_data, true) : array();
    $videos_data = $videos_data ? json_decode($videos_data, true) : array();
    $reviews_data = $reviews_data ? json_decode($reviews_data, true) : array();
    $developments_data = $developments_data ? json_decode($developments_data, true) : array();

    // Подсчитываем общее количество материалов
    $total_materials = count($testimonials_data) + count($gratitudes_data) + count($interviews_data) +
                       count($videos_data) + count($reviews_data) + count($developments_data);

    // Получаем таксономии
    $roles = wp_get_post_terms($member_id, 'member_role');
    $locations = wp_get_post_terms($member_id, 'member_location');
    $member_types = wp_get_post_terms($member_id, 'member_type'); // Эксперт или Участник

    // Получаем галерею фотографий из мета-поля
    $gallery_ids_string = get_post_meta($member_id, 'member_gallery', true);
    $gallery_ids = !empty($gallery_ids_string) ? explode(',', $gallery_ids_string) : array();

    // Также получаем все прикрепленные изображения
    $attached_images = get_attached_media('image', $member_id);
    if (!empty($attached_images)) {
        foreach ($attached_images as $image) {
            // Добавляем только те изображения, которых еще нет в галерее
            if (!in_array($image->ID, $gallery_ids)) {
                $gallery_ids[] = $image->ID;
            }
        }
    }

    // Убираем миниатюру поста из галереи
    $thumbnail_id = get_post_thumbnail_id($member_id);
    if ($thumbnail_id) {
        $gallery_ids = array_diff($gallery_ids, array($thumbnail_id));
    }

    // Обработка буллетов для специализации
    $specialization_items = array();
    if ($specialization_experience) {
        // Разделяем по символу | (данные из CSV) или по \n (legacy)
        $delimiter = (strpos($specialization_experience, '|') !== false) ? '|' : "\n";
        $lines = explode($delimiter, $specialization_experience);

        foreach ($lines as $line) {
            $line = trim($line);

            if (!empty($line)) {
                // Убираем символ буллета если он есть
                $first_char = mb_substr($line, 0, 1);
                if (in_array($first_char, ['•', '●', '○', '·', '▪', '▫', '■', '□', '◆', '◇', '-', '*', '»', '›'])) {
                    $line = mb_substr($line, 1);
                    $line = ltrim($line);
                }

                if (!empty($line)) {
                    $specialization_items[] = $line;
                }
            }
        }
    }

    // Обработка буллетов для интересов
    $interest_items = array();
    if ($professional_interests) {
        // Разделяем по символу | (данные из CSV) или по \n (legacy)
        $delimiter = (strpos($professional_interests, '|') !== false) ? '|' : "\n";
        $lines = explode($delimiter, $professional_interests);

        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Убираем символ буллета если он есть
                $first_char = mb_substr($line, 0, 1);
                if (in_array($first_char, ['•', '●', '○', '·', '▪', '▫', '■', '□', '◆', '◇', '-', '*', '»', '›'])) {
                    $line = mb_substr($line, 1);
                    $line = ltrim($line);
                }

                if (!empty($line)) {
                    $interest_items[] = $line;
                }
            }
        }
    }

    // Цвета "Метода" - синий и красный
    $primary_color = '#2e466f'; // Темно-синий
    $accent_color = '#ef4e4c';  // Красный
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php the_title(); ?> - <?php bloginfo('name'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { display: none; }
        * { font-family: 'Montserrat', sans-serif; }

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
        <section class="bg-white rounded-xl shadow-sm border p-4 md:p-8 mb-8">
            <div class="flex items-start space-x-0 md:space-x-8 space-y-6 md:space-y-0 flex-col md:flex-row">
                <!-- Photo -->
                <div class="flex-shrink-0 mx-auto md:mx-0">
                    <div class="w-32 h-32 md:w-48 md:h-48 rounded-2xl overflow-hidden bg-gray-100">
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
                <div class="flex-1 text-center md:text-left">
                    <div class="mb-4">
                        <div class="flex flex-wrap gap-2 mb-3 justify-center md:justify-start">
                            <?php if ($member_types && !is_wp_error($member_types)): ?>
                                <?php foreach ($member_types as $type): ?>
                                    <span class="inline-block metoda-accent-bg text-white px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo esc_html($type->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($roles && !is_wp_error($roles)): ?>
                                <?php foreach ($roles as $role): ?>
                                    <span class="inline-block metoda-primary-bg text-white px-3 py-1 rounded-full text-sm font-medium">
                                        <?php echo esc_html($role->name); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <h1 class="text-2xl md:text-4xl font-bold text-gray-900 mb-2"><?php the_title(); ?></h1>

                        <?php if ($position): ?>
                            <h2 class="text-base md:text-lg font-medium text-gray-600 mb-3"><?php echo esc_html($position); ?></h2>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <?php if ($company): ?>
                        <div class="flex items-center text-gray-600 justify-center md:justify-start">
                            <i class="fa-solid fa-building metoda-primary mr-3"></i>
                            <span class="font-medium"><?php echo esc_html($company); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($city): ?>
                        <div class="flex items-center text-gray-600 justify-center md:justify-start">
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
                <section class="bg-white rounded-xl shadow-sm border p-4 md:p-8">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Профессиональная информация</h3>

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
                <div class="bg-white rounded-xl shadow-sm border p-4 md:p-8">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">О себе</h3>
                    <div class="text-gray-700" style="line-height: 1.7;">
                        <?php echo wpautop($bio); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Collaboration Expectations -->
                <?php if ($expectations): ?>
                <div class="bg-white rounded-xl shadow-sm border p-4 md:p-8">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">Ожидания от сотрудничества</h3>
                    <div class="text-gray-700" style="line-height: 1.7;">
                        <?php echo wpautop($expectations); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Photo Gallery -->
                <?php if (!empty($gallery_ids) && count($gallery_ids) > 0): ?>
                <div class="bg-white rounded-xl shadow-sm border p-4 md:p-8">
                    <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-6">
                        <i class="fa-solid fa-images metoda-primary mr-2"></i>
                        Фотогалерея
                        <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo count($gallery_ids); ?>)</span>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php
                        $index = 0;
                        foreach ($gallery_ids as $attachment_id):
                            $attachment_id = intval(trim($attachment_id));
                            if (!$attachment_id) continue;
                            $image_url = wp_get_attachment_image_url($attachment_id, 'medium');
                            $image_full = wp_get_attachment_image_url($attachment_id, 'full');
                            if (!$image_url) continue;
                        ?>
                        <a href="<?php echo esc_url($image_full); ?>"
                           class="gallery-item group relative overflow-hidden rounded-lg aspect-square bg-gray-100 hover:opacity-90 transition-opacity cursor-pointer"
                           data-image-index="<?php echo $index; ?>"
                           data-lightbox="member-gallery">
                            <img src="<?php echo esc_url($image_url); ?>"
                                 alt="<?php the_title(); ?>"
                                 class="w-full h-full object-cover object-top">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all flex items-center justify-center">
                                <i class="fa-solid fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            </div>
                        </a>
                        <?php $index++; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Portfolio & Achievements Section -->
                <?php if ($total_materials > 0): ?>
                    <?php include(METODA_PLUGIN_DIR . 'templates/materials-section.php'); ?>
                <?php endif; ?>
            </div>

            <!-- Right Column (Sidebar) -->
            <aside class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">

                    <!-- Contact Card -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Контактная информация</h4>
                        <div class="space-y-3 mb-4">
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

                            <?php if ($telegram): ?>
                            <a href="https://t.me/<?php echo esc_attr(ltrim($telegram, '@')); ?>" target="_blank" rel="noopener" class="flex items-center text-gray-600 hover:text-primary transition-colors">
                                <i class="fa-brands fa-telegram metoda-primary mr-3"></i>
                                <span class="text-sm">Telegram: @<?php echo esc_html(ltrim($telegram, '@')); ?></span>
                            </a>
                            <?php endif; ?>

                            <?php if ($website): ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" class="flex items-center text-gray-600 hover:text-primary transition-colors">
                                <i class="fa-solid fa-globe metoda-primary mr-3"></i>
                                <span class="text-sm">Вебсайт</span>
                            </a>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Проверяем, не является ли это профилем текущего пользователя
                        $current_user_member_id = Member_User_Link::get_current_user_member_id();
                        $is_own_profile = ($current_user_member_id && $current_user_member_id == $member_id);

                        if (!$is_own_profile):
                        ?>
                        <button onclick="openMessageModal(<?php echo $member_id; ?>, '<?php echo esc_js(get_the_title()); ?>')" class="w-full metoda-primary-bg text-white text-center py-3 px-4 rounded-lg hover:opacity-90 transition-opacity font-medium">
                            <i class="fa-solid fa-paper-plane mr-2"></i>
                            Отправить сообщение
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="space-y-3">
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

    <!-- Enhanced Lightbox for Gallery with Navigation -->
    <script>
    (function() {
        const galleryItems = document.querySelectorAll('.gallery-item');
        if (galleryItems.length === 0) return;

        let currentIndex = 0;
        const images = Array.from(galleryItems).map(item => item.href);

        // Create lightbox overlay with navigation
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black bg-opacity-95 z-50 hidden items-center justify-center p-4';
        overlay.innerHTML = `
            <button class="close-btn absolute top-4 right-4 text-white text-4xl hover:text-gray-300 transition-colors z-10">&times;</button>
            <button class="prev-btn absolute left-4 top-1/2 -translate-y-1/2 text-white text-5xl hover:text-gray-300 transition-colors z-10 bg-black bg-opacity-50 w-14 h-14 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button class="next-btn absolute right-4 top-1/2 -translate-y-1/2 text-white text-5xl hover:text-gray-300 transition-colors z-10 bg-black bg-opacity-50 w-14 h-14 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <div class="counter absolute top-4 left-4 text-white text-lg bg-black bg-opacity-50 px-4 py-2 rounded-lg"></div>
            <img src="" alt="" class="max-w-full max-h-full object-contain" style="object-position: top center;">
        `;
        document.body.appendChild(overlay);

        const overlayImg = overlay.querySelector('img');
        const closeBtn = overlay.querySelector('.close-btn');
        const prevBtn = overlay.querySelector('.prev-btn');
        const nextBtn = overlay.querySelector('.next-btn');
        const counter = overlay.querySelector('.counter');

        function showImage(index) {
            currentIndex = index;
            overlayImg.src = images[currentIndex];
            counter.textContent = `${currentIndex + 1} / ${images.length}`;

            // Show/hide navigation buttons
            prevBtn.style.display = currentIndex > 0 ? 'flex' : 'none';
            nextBtn.style.display = currentIndex < images.length - 1 ? 'flex' : 'none';
        }

        function openLightbox(index) {
            showImage(index);
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function nextImage() {
            if (currentIndex < images.length - 1) {
                showImage(currentIndex + 1);
            }
        }

        function prevImage() {
            if (currentIndex > 0) {
                showImage(currentIndex - 1);
            }
        }

        // Open lightbox on gallery item click
        galleryItems.forEach((item, index) => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                openLightbox(index);
            });
        });

        // Close button
        closeBtn.addEventListener('click', closeLightbox);

        // Navigation buttons
        prevBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            prevImage();
        });

        nextBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            nextImage();
        });

        // Close on overlay click (but not on image or buttons)
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (overlay.classList.contains('hidden')) return;

            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                prevImage();
            } else if (e.key === 'ArrowRight') {
                nextImage();
            }
        });
    })();

    // === МОДАЛЬНЫЕ ОКНА ДЛЯ МАТЕРИАЛОВ ===
    function openMaterialModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMaterialModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    }

    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.material-modal.flex');
            openModals.forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            });
        }
    });

    // === ЛИЧНЫЕ СООБЩЕНИЯ ===
    function openMessageModal(recipientId, recipientName) {
        // Проверяем авторизацию
        <?php if (!is_user_logged_in()): ?>
        if (confirm('Для отправки сообщений необходимо войти в систему. Перейти на страницу входа?')) {
            window.location.href = '<?php echo wp_login_url(get_permalink()); ?>';
        }
        return;
        <?php endif; ?>

        document.getElementById('message_recipient_id').value = recipientId;
        document.getElementById('message_recipient_name').textContent = recipientName;
        document.getElementById('send-message-modal').classList.remove('hidden');
        document.getElementById('send-message-modal').classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Clear form
        document.getElementById('message_subject').value = '';
        if (window.messageQuill) {
            window.messageQuill.setContents([]);
        }
    }

    function closeMessageModal() {
        document.getElementById('send-message-modal').classList.add('hidden');
        document.getElementById('send-message-modal').classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
    </script>

    <!-- Send Message Modal -->
    <div id="send-message-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 rounded-t-2xl flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Новое сообщение</h3>
                    <p class="text-sm text-gray-500 mt-1">Для: <span id="message_recipient_name" class="font-semibold"></span></p>
                </div>
                <button type="button" onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="send-message-form" class="p-6">
                <input type="hidden" name="recipient_id" id="message_recipient_id">

                <!-- Honeypot (антиспам) -->
                <div style="position: absolute; left: -5000px;">
                    <input type="text" name="website" id="message_website" tabindex="-1" autocomplete="off">
                </div>

                <div class="space-y-4">
                    <?php if (!is_user_logged_in()): ?>
                    <!-- Ваше имя (для незалогиненных) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ваше имя *</label>
                        <input type="text" name="sender_name" id="message_sender_name" required maxlength="100" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none" placeholder="Как вас зовут?">
                    </div>

                    <!-- Email для ответа (для незалогиненных) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email для ответа *</label>
                        <input type="email" name="sender_email" id="message_sender_email" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none" placeholder="your@email.com">
                        <p class="text-xs text-gray-500 mt-1">Участник сможет ответить вам на этот email</p>
                    </div>
                    <?php endif; ?>

                    <!-- Тема -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Тема сообщения *</label>
                        <input type="text" name="subject" id="message_subject" required maxlength="200" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none" placeholder="О чем вы хотите написать?">
                    </div>

                    <!-- Текст сообщения -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Сообщение *</label>
                        <div class="quill-editor-wrapper">
                            <div id="message-editor" class="quill-editor" style="min-height: 250px;"></div>
                        </div>
                        <textarea name="content" id="message_content_hidden" style="display: none;"></textarea>
                        <p class="text-xs text-gray-500 mt-2">✨ Используйте панель инструментов для форматирования</p>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 px-6 py-3 text-white rounded-lg font-medium hover:opacity-90 transition-opacity metoda-primary-bg">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span class="btn-text">Отправить сообщение</span>
                            <span class="btn-loader hidden">
                                <i class="fas fa-spinner fa-spin"></i> Отправка...
                            </span>
                        </button>
                        <button type="button" onclick="closeMessageModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors">
                            Отмена
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quill.js для сообщений -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
    // Initialize Quill editor for messages
    window.messageQuill = new Quill('#message-editor', {
        theme: 'snow',
        placeholder: 'Напишите ваше сообщение...',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['blockquote', 'link'],
                ['clean']
            ]
        }
    });

    // Handle form submission
    document.getElementById('send-message-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Check honeypot
        if (document.getElementById('message_website').value !== '') {
            alert('Обнаружена подозрительная активность');
            return;
        }

        // Get content from Quill
        var content = window.messageQuill.root.innerHTML;
        document.getElementById('message_content_hidden').value = content;

        var formData = new FormData(this);
        formData.append('action', 'send_member_message');
        formData.append('nonce', '<?php echo wp_create_nonce("send_member_message"); ?>');

        var $form = jQuery(this);
        var $btn = $form.find('button[type="submit"]');

        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $btn.prop('disabled', true);
                $btn.find('.btn-text').hide();
                $btn.find('.btn-loader').removeClass('hidden').show();
            },
            success: function(response) {
                if (response.success) {
                    // Success notification
                    var notification = jQuery('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3">' +
                        '<i class="fas fa-check-circle text-xl"></i>' +
                        '<span>Сообщение отправлено!</span>' +
                    '</div>');
                    jQuery('body').append(notification);

                    setTimeout(function() {
                        notification.fadeOut(function() {
                            jQuery(this).remove();
                        });
                        closeMessageModal();
                    }, 2000);
                } else {
                    alert('Ошибка: ' + response.data.message);
                }
            },
            error: function() {
                alert('Произошла ошибка при отправке');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.btn-text').show();
                $btn.find('.btn-loader').hide();
            }
        });
    });
    </script>
</body>
</html>

<?php
endwhile;
?>
