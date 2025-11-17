<?php
/**
 * Member Card Template
 * Карточка участника для архива (используется в AJAX и обычной загрузке)
 */

if (!isset($member_id)) {
    $member_id = get_the_ID();
}

$position = get_post_meta($member_id, 'member_position', true);
$company = get_post_meta($member_id, 'member_company', true);
$city = get_post_meta($member_id, 'member_city', true);
$roles = wp_get_post_terms($member_id, 'member_role');
$member_types = wp_get_post_terms($member_id, 'member_type');

// Определяем тип участника для плашки
$is_expert = false;
if ($member_types && !is_wp_error($member_types)) {
    foreach ($member_types as $type) {
        if ($type->slug === 'ekspert' || $type->name === 'Эксперт') {
            $is_expert = true;
            break;
        }
    }
}

// Обработка имени: только Имя Фамилия (без отчества)
$full_name = get_the_title($member_id);
$name_parts = explode(' ', $full_name);
$short_name = '';
if (count($name_parts) >= 2) {
    // Предполагаем формат: Фамилия Имя Отчество
    $short_name = $name_parts[0] . ' ' . $name_parts[1];
} else {
    $short_name = $full_name;
}
?>

<article class="member-card bg-white rounded-xl shadow-sm border p-6">
    <a href="<?php echo get_permalink($member_id); ?>" class="flex items-start gap-4" style="border: none !important;">
        <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 flex-shrink-0">
            <?php if (has_post_thumbnail($member_id)): ?>
                <?php echo get_the_post_thumbnail($member_id, 'thumbnail', array('class' => 'w-full h-full object-cover object-top')); ?>
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-300">
                    <?php echo mb_substr($short_name, 0, 1); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex-1 min-w-0">
            <!-- Member Type Badge -->
            <?php if ($member_types && !is_wp_error($member_types) && !empty($member_types)): ?>
            <div class="mb-2">
                <?php if ($is_expert): ?>
                    <span class="inline-block px-3 py-1 metoda-accent-bg text-white text-xs font-semibold rounded-full">Эксперт</span>
                <?php else: ?>
                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Участник</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <h3 class="text-lg font-semibold text-gray-900 mb-1 truncate"><?php echo esc_html($short_name); ?></h3>

            <?php if ($position): ?>
            <p class="text-xs text-gray-600 mb-1 line-clamp-2"><?php echo esc_html($position); ?></p>
            <?php endif; ?>

            <?php if ($company): ?>
            <p class="text-xs font-medium text-gray-500 mb-3 line-clamp-1"><?php echo esc_html($company); ?></p>
            <?php endif; ?>

            <?php if ($city): ?>
            <div class="flex items-center text-xs text-gray-500 mb-3">
                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                <span><?php echo esc_html($city); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($roles && !is_wp_error($roles)): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach (array_slice($roles, 0, 3) as $role): ?>
                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                    <?php echo esc_html($role->name); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </a>
</article>
