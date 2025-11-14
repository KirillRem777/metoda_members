<?php
/**
 * Завершение создания страниц после активации
 *
 * Запусти этот файл ОДИН РАЗ в браузере чтобы завершить создание страниц
 * https://ваш-сайт.ru/wp-content/plugins/metoda_members/finish-page-creation.php
 *
 * УДАЛИ файл после использования!
 */

// Поднимаемся на 3 уровня вверх чтобы найти wp-load.php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');

if (!current_user_can('manage_options')) {
    die('У тебя нет прав для выполнения этой операции!');
}

echo '<h1>Завершение создания страниц</h1>';
echo '<pre>';

// Проверяем флаг
$needs_creation = get_option('metoda_needs_page_creation');
echo "Статус флага metoda_needs_page_creation: " . ($needs_creation === '1' ? 'НУЖНО СОЗДАТЬ' : 'УЖЕ СОЗДАНО') . "\n\n";

if ($needs_creation === '1') {
    echo "Начинаем создание страниц...\n\n";

    // Массив страниц для создания
    $pages = array(
        array(
            'title' => 'Личный кабинет участника',
            'slug' => 'member-dashboard',
            'shortcode' => '[member_dashboard]',
        ),
        array(
            'title' => 'Добро пожаловать',
            'slug' => 'member-onboarding',
            'shortcode' => '[member_onboarding]',
        ),
        array(
            'title' => 'Панель управления',
            'slug' => 'manager-panel',
            'shortcode' => '[member_manager_dashboard]',
        ),
        array(
            'title' => 'Форум участников',
            'slug' => 'member-forum',
            'shortcode' => '[member_forum]',
        ),
    );

    foreach ($pages as $page_data) {
        $existing_page = get_page_by_path($page_data['slug']);

        if (!$existing_page) {
            $page_id = wp_insert_post(array(
                'post_title' => $page_data['title'],
                'post_name' => $page_data['slug'],
                'post_content' => $page_data['shortcode'],
                'post_status' => 'publish',
                'post_type' => 'page',
            ));

            if ($page_id) {
                echo "✅ Создана страница: {$page_data['title']} (ID: {$page_id})\n";
            } else {
                echo "❌ ОШИБКА создания страницы: {$page_data['title']}\n";
            }
        } else {
            echo "ℹ️  Страница уже существует: {$page_data['title']} (ID: {$existing_page->ID})\n";
        }
    }

    // Удаляем флаг
    delete_option('metoda_needs_page_creation');
    echo "\n✅ Флаг metoda_needs_page_creation удалён!\n";

} else {
    echo "ℹ️  Страницы уже созданы, ничего делать не нужно.\n";
}

echo "\n";
echo "===========================================\n";
echo "ГОТОВО! Теперь можно удалить этот файл.\n";
echo "===========================================\n";
echo '</pre>';

// Показываем список всех страниц
echo '<h2>Текущие страницы плагина:</h2>';
echo '<ul>';
$slugs = array('member-dashboard', 'member-onboarding', 'manager-panel', 'member-forum');
foreach ($slugs as $slug) {
    $page = get_page_by_path($slug);
    if ($page) {
        echo '<li><strong>' . $page->post_title . '</strong> - <a href="' . get_permalink($page) . '">' . get_permalink($page) . '</a></li>';
    }
}
echo '</ul>';
