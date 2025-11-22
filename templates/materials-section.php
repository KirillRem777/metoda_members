<?php
/**
 * Секция материалов с табами (LinkedIn style)
 * Отображает: Отзывы, Благодарности, Интервью, Видео, Рецензии, Разработки
 */

if (!defined('ABSPATH')) {
    exit;
}

// Проверяем есть ли хоть одна категория материалов
if ($total_materials > 0):
?>

<!-- Materials Section with Tabs -->
<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <!-- Tabs Header -->
    <div class="border-b border-gray-200 bg-gray-50">
        <div class="flex flex-wrap gap-2 p-4">
            <?php if (count($testimonials_data) > 0): ?>
            <button class="material-tab active px-6 py-3 text-sm font-medium rounded-lg transition-all" data-tab="testimonials">
                💬 Отзывы <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($testimonials_data); ?></span>
            </button>
            <?php endif; ?>

            <?php if (count($gratitudes_data) > 0): ?>
            <button class="material-tab px-6 py-3 text-sm font-medium rounded-lg transition-all" data-tab="gratitudes">
                🏆 Благодарности <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($gratitudes_data); ?></span>
            </button>
            <?php endif; ?>

            <?php if (count($interviews_data) > 0): ?>
            <button class="material-tab px-6 py-3 text-sm font-medium rounded-lg transition-all" data-tab="interviews">
                🎤 Интервью <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($interviews_data); ?></span>
            </button>
            <?php endif; ?>

            <?php if (count($videos_data) > 0): ?>
            <button class="material-tab px-6 py-3 text-sm font-medium rounded-lg transition-all" data-tab="videos">
                🎥 Видео <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($videos_data); ?></span>
            </button>
            <?php endif; ?>

            <?php if (count($reviews_data) > 0): ?>
            <button class="material-tab px-6 py-3 text-sm font-medium rounded-lg transition-all" data-tab="reviews">
                📝 Рецензии <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($reviews_data); ?></span>
            </button>
            <?php endif; ?>

            <?php if (count($developments_data) > 0): ?>
            <button class="material-tab px-6 py-3 text-sm font-medium rounded-lg transition-all" data-tab="developments">
                💾 Разработки <span class="ml-2 px-2 py-0.5 bg-gray-200 rounded-full text-xs"><?php echo count($developments_data); ?></span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs Content -->
    <div class="p-6">
        <!-- Отзывы -->
        <?php if (count($testimonials_data) > 0): ?>
        <div class="material-content active" data-content="testimonials">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($testimonials_data as $item): ?>
                    <?php include plugin_dir_path(__DIR__) . 'templates/material-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Благодарности -->
        <?php if (count($gratitudes_data) > 0): ?>
        <div class="material-content" data-content="gratitudes">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($gratitudes_data as $item): ?>
                    <?php include plugin_dir_path(__DIR__) . 'templates/material-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Интервью -->
        <?php if (count($interviews_data) > 0): ?>
        <div class="material-content" data-content="interviews">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($interviews_data as $item): ?>
                    <?php include plugin_dir_path(__DIR__) . 'templates/material-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Видео -->
        <?php if (count($videos_data) > 0): ?>
        <div class="material-content" data-content="videos">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($videos_data as $item): ?>
                    <?php include plugin_dir_path(__DIR__) . 'templates/material-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Рецензии -->
        <?php if (count($reviews_data) > 0): ?>
        <div class="material-content" data-content="reviews">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($reviews_data as $item): ?>
                    <?php include plugin_dir_path(__DIR__) . 'templates/material-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Разработки -->
        <?php if (count($developments_data) > 0): ?>
        <div class="material-content" data-content="developments">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($developments_data as $item): ?>
                    <?php include plugin_dir_path(__DIR__) . 'templates/material-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Tab Styles */
    .material-tab {
        background: white;
        color: #6b7280;
        border: 1px solid transparent;
    }

    .material-tab:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .material-tab.active {
        background: #0066cc;
        color: white;
        border-color: #0066cc;
    }

    .material-tab.active span {
        background: rgba(255,255,255,0.2);
        color: white;
    }

    .material-tab:focus {
        outline: 2px solid #0066cc;
        outline-offset: 2px;
    }

    /* Content visibility */
    .material-content {
        display: none;
    }

    .material-content.active {
        display: block;
    }
</style>

<script>
(function($) {
    if (typeof $ === 'undefined') {
        $ = jQuery;
    }

    $(document).ready(function() {
        // Tab switching
        $('.material-tab').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');

            // Update active tab
            $('.material-tab').removeClass('active');
            $(this).addClass('active');

            // Show corresponding content
            $('.material-content').removeClass('active');
            $('.material-content[data-content="' + tab + '"]').addClass('active');
        });
    });
})(jQuery);
</script>

<?php endif; ?>
