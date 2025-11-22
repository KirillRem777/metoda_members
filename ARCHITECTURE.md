# Архитектура плагина Metoda Community MGMT

## 📋 Оглавление
1. [Общий обзор](#общий-обзор)
2. [Файловая структура](#файловая-структура)
3. [Архитектура базы данных](#архитектура-базы-данных)
4. [Классы и их назначение](#классы-и-их-назначение)
5. [AJAX Endpoints](#ajax-endpoints)
6. [Система шаблонов](#система-шаблонов)
7. [Кастомные типы постов и таксономии](#кастомные-типы-постов-и-таксономии)
8. [Технический стек](#технический-стек)
9. [Важные концепции](#важные-концепции)

---

## Общий обзор

**Metoda Community MGMT** — WordPress плагин для управления частным сообществом методистов и экспертов.

### Основные функции:
- ✅ Управление участниками и экспертами (кастомный post type)
- ✅ Личные кабинеты с онбордингом
- ✅ Портфолио материалов с WYSIWYG редактором
- ✅ Форум в стиле Reddit с категориями
- ✅ Система личных сообщений
- ✅ CSV импорт участников
- ✅ Фото-кроппер и галерея
- ✅ Поиск и фильтрация
- ✅ Админ-панель мониторинга

### Версия: 3.4.4
### Автор: Kirill Rem

---

## Файловая структура

```
metoda_members/
├── members-management-pro.php          # Главный файл плагина
├── single-members.php                  # Шаблон профиля участника
├── ARCHITECTURE.md                     # Эта документация
├── DOCUMENTATION.md                    # Пользовательская документация
│
├── includes/                           # PHP классы
│   ├── class-member-user-link.php      # Связь WP User ↔ Member Post
│   ├── class-member-page-templates.php # Регистрация страниц
│   ├── class-member-csv-importer.php   # CSV импорт
│   ├── class-member-email-templates.php# Email уведомления
│   ├── class-member-access-codes.php   # Коды доступа
│   ├── class-member-bulk-users.php     # Массовые операции
│   ├── class-member-dashboard.php      # Личный кабинет
│   ├── class-member-file-manager.php   # Загрузка материалов
│   ├── class-member-manager.php        # CRUD операции
│   ├── class-member-archive.php        # Архив участников
│   ├── class-member-forum.php          # Форум
│   ├── class-member-onboarding.php     # Онбординг
│   └── class-member-template-loader.php# Загрузка шаблонов
│
├── templates/                          # Шаблоны страниц
│   ├── archive-members.php             # Список всех участников
│   ├── member-card.php                 # Карточка участника
│   ├── member-dashboard.php            # Личный кабинет
│   ├── member-registration.php         # Регистрация
│   ├── custom-login.php                # Вход
│   ├── forgot-password.php             # Восстановление пароля
│   ├── reset-password.php              # Сброс пароля
│   ├── forum-archive.php               # Главная форума
│   ├── forum-topic.php                 # Одна тема форума
│   ├── forum-listing.php               # Компонент списка тем
│   ├── materials-section.php           # Секция материалов
│   ├── material-card.php               # Карточка материала
│   ├── dashboard-materials-section.php # Материалы в кабинете
│   ├── dashboard-material-item.php     # Элемент материала
│   ├── dashboard-messages-section.php  # Сообщения в кабинете
│   └── dashboard-forum-section.php     # Форум в кабинете
│
├── assets/
│   ├── css/                            # Стили
│   │   ├── member-archive.css
│   │   ├── member-dashboard.css
│   │   ├── member-forum.css
│   │   ├── member-onboarding.css
│   │   └── ...
│   └── js/                             # JavaScript
│       ├── member-archive.js
│       ├── member-dashboard.js
│       ├── member-forum.js
│       └── ...
│
└── Photos/                             # Демо фотографии
```

---

## Архитектура базы данных

### Используемые таблицы WordPress

#### 1. `wp_posts` - Основное хранилище

```sql
-- Участники сообщества
post_type = 'members'
post_title = "Имя Фамилия"
post_status = 'publish'
post_author = ID создателя

-- Топики форума
post_type = 'forum_topic'
post_title = "Название темы"
post_content = "Текст первого сообщения"

-- Личные сообщения
post_type = 'member_message'
post_title = "Тема сообщения"
post_content = "Текст сообщения (HTML)"
```

#### 2. `wp_postmeta` - Метаданные

**Для участников (post_type='members'):**
```sql
_linked_user_id         → ID связанного WordPress пользователя
member_position         → Должность
member_company          → Компания/организация
member_email            → Email
member_phone            → Телефон
member_bio              → О себе
member_specialization   → Специализация
member_experience       → Опыт работы
member_interests        → Интересы
member_linkedin         → LinkedIn URL
member_website          → Вебсайт
member_expectations     → Ожидания от сотрудничества
member_gallery          → ID фотографий (comma-separated)
member_articles         → JSON массив статей
member_gratitudes       → JSON массив благодарностей
member_interviews       → JSON массив интервью
member_videos           → JSON массив видео
member_reviews          → JSON массив рецензий
member_developments     → JSON массив разработок
_profile_views          → Счетчик просмотров профиля
```

**Для топиков форума (post_type='forum_topic'):**
```sql
author_member_id        → ID участника-автора
likes_count             → Количество лайков
liked_by_users          → JSON массив ID пользователей
```

**Для сообщений (post_type='member_message'):**
```sql
sender_member_id        → ID отправителя (Member post ID)
recipient_member_id     → ID получателя (Member post ID)
sender_name             → Имя отправителя (для админов)
message_read            → 0/1 прочитано ли
message_read_date       → Дата прочтения
```

#### 3. `wp_terms` + `wp_term_taxonomy` - Таксономии

```sql
-- Типы участников
taxonomy = 'member_type'
terms = ['uchastnik', 'ekspert']

-- Роли участников
taxonomy = 'member_role'
terms = ['Методист', 'Директор', 'Координатор', ...]

-- Города
taxonomy = 'member_location'
terms = ['Москва', 'Санкт-Петербург', ...]

-- Категории форума
taxonomy = 'forum_category'
terms = ['Общие вопросы', 'Методология', ...]
```

#### 4. `wp_users` - WordPress пользователи

```sql
ID                      → Используется для авторизации
user_login              → Логин
user_email              → Email
user_pass               → Хеш пароля
```

#### 5. `wp_usermeta` - Метаданные пользователей

```sql
metoda_onboarding_seen  → 1 если онбординг пройден
metoda_access_code      → Код доступа для регистрации
```

#### 6. `wp_comments` - Комментарии к форуму

```sql
comment_post_ID         → ID топика форума
comment_author          → Имя автора
comment_content         → Текст комментария
user_id                 → ID WordPress пользователя
```

### Связи между таблицами

```
wp_users (ID=5)
    ↓ (через _linked_user_id)
wp_postmeta → post_id=123 (Member)
    ↓
wp_posts (ID=123, post_type='members')
    ↓
wp_term_relationships → связь с таксономиями
    ↓
wp_terms (member_type='ekspert', member_location='Москва')
```

---

## Классы и их назначение

### 1. `Member_User_Link` (class-member-user-link.php)

**Назначение:** Связывает WordPress пользователей с постами типа `members`

**Ключевые методы:**
```php
get_current_user_member_id()        // Получить ID поста участника для текущего юзера
link_user_to_member($user_id, $member_id)  // Создать связь
can_user_edit_member($member_id)    // Проверка прав редактирования
get_member_by_user_id($user_id)     // Найти Member по User ID
```

**SQL запрос для поиска:**
```sql
SELECT post_id FROM wp_postmeta
WHERE meta_key = '_linked_user_id'
AND meta_value = '{current_user_id}'
```

---

### 2. `Member_Dashboard` (class-member-dashboard.php)

**Назначение:** Личный кабинет участника

**Ключевые методы:**
```php
get_member_data($member_id)         // Получить все данные участника
get_member_stats($member_id)        // Статистика (просмотры, материалы)
ajax_update_profile()               // AJAX обновление профиля
ajax_update_gallery()               // AJAX обновление галереи
```

**Важно:**
- Админы могут просматривать чужие кабинеты через `?member_id=XXX`
- Проверка: `current_user_can('administrator')`

---

### 3. `Member_Template_Loader` (class-member-template-loader.php)

**Назначение:** Автоматическая загрузка кастомных шаблонов из плагина

**Логика работы:**
```php
is_singular('members')              → single-members.php
is_post_type_archive('members')     → templates/archive-members.php
is_singular('forum_topic')          → templates/forum-topic.php
is_post_type_archive('forum_topic') → templates/forum-archive.php
```

**Приоритет:** theme > plugin

---

### 4. `Member_Archive` (class-member-archive.php)

**Назначение:** Архив участников с фильтрацией и пагинацией

**Ключевые методы:**
```php
ajax_filter_members()               // AJAX фильтрация
ajax_load_more_members()            // AJAX "Показать еще"
```

**Параметры фильтрации:**
- `search` - поиск по имени
- `city` - фильтр по городу
- `role` - фильтр по роли
- `member_type` - тип (все/участники/эксперты)

---

### 5. `Member_Forum` (class-member-forum.php)

**Назначение:** Reddit-style форум

**Ключевые методы:**
```php
ajax_create_topic()                 // Создать новую тему
ajax_like_topic()                   // Лайкнуть тему
ajax_submit_comment()               // Добавить комментарий
```

**Особенности:**
- Лайки хранятся в `likes_count` + `liked_by_users` (JSON)
- Комментарии через стандартную систему WordPress
- Доступ только для авторизованных (`auth_redirect()`)

---

### 6. `Member_File_Manager` (class-member-file-manager.php)

**Назначение:** Управление материалами участника

**Категории материалов:**
```php
'articles'      => 'Статьи'
'gratitudes'    => 'Благодарности'
'interviews'    => 'Интервью'
'videos'        => 'Видео'
'reviews'       => 'Рецензии'
'developments'  => 'Разработки'
```

**Формат хранения:**
```json
[
  {
    "title": "Название",
    "description": "Описание",
    "link": "https://...",
    "file_url": "https://...",
    "video_url": "https://...",
    "image_url": "https://..."
  }
]
```

---

### 7. `Member_CSV_Importer` (class-member-csv-importer.php)

**Назначение:** Массовый импорт участников из CSV

**Формат CSV:**
```csv
full_name,email,phone,position,company,city,type,role
Иван Иванов,ivan@test.ru,+7999...,Методист,Школа №1,Москва,uchastnik,Методист
```

**Процесс:**
1. Создает пост типа `members`
2. Создает WordPress пользователя (если нужно)
3. Связывает через `_linked_user_id`
4. Генерирует код доступа

---

## AJAX Endpoints

### Формат вызова
```javascript
$.ajax({
    url: '/wp-admin/admin-ajax.php',
    type: 'POST',
    data: {
        action: 'имя_экшена',
        nonce: nonce_value,
        param1: value1
    }
});
```

### Список всех endpoints

#### Архив участников
```php
add_action('wp_ajax_filter_members', ...)        // Фильтрация участников
add_action('wp_ajax_nopriv_filter_members', ...) // Для неавторизованных

add_action('wp_ajax_load_more_members', ...)     // Подгрузка участников
add_action('wp_ajax_nopriv_load_more_members', ...)
```

#### Личный кабинет
```php
add_action('wp_ajax_member_update_profile', ...)    // Обновить профиль
add_action('wp_ajax_member_update_gallery', ...)    // Обновить галерею
add_action('wp_ajax_mark_onboarding_seen', ...)     // Отметить онбординг

add_action('wp_ajax_add_material', ...)             // Добавить материал
add_action('wp_ajax_delete_material', ...)          // Удалить материал
add_action('wp_ajax_upload_material_file', ...)     // Загрузить файл
```

#### Форум
```php
add_action('wp_ajax_create_topic', ...)             // Создать тему
add_action('wp_ajax_like_topic', ...)               // Лайкнуть тему
add_action('wp_ajax_submit_comment', ...)           // Добавить комментарий
```

#### Сообщения
```php
add_action('wp_ajax_send_member_message', ...)      // Отправить сообщение
add_action('wp_ajax_mark_message_read', ...)        // Отметить прочитанным
add_action('wp_ajax_delete_message', ...)           // Удалить сообщение
```

---

## Система шаблонов

### Загрузка шаблонов

**Приоритет:**
1. Тема: `/wp-content/themes/current-theme/single-members.php`
2. Плагин: `/wp-content/plugins/metoda_members/single-members.php`

**Класс:** `Member_Template_Loader`

### Основные шаблоны

#### 1. `single-members.php` - Профиль участника
**Секции:**
- Шапка с кнопкой "Назад"
- Основная информация (фото, имя, должность)
- О себе
- Ожидания от сотрудничества
- Фотогалерея (с lightbox)
- Портфолио материалов (табы)
- Контактная информация (сайдбар)
- Кнопка "Отправить сообщение"

**Особенности:**
- Кнопка сообщения скрыта для собственного профиля
- Lightbox для галереи (стрелки, ESC)
- Модальные окна для материалов
- Quill editor для сообщений

#### 2. `templates/archive-members.php` - Список участников
**Секции:**
- Шапка с поиском
- Фильтры (тип, город, роль)
- Сетка карточек участников
- Кнопка "Показать еще"

**Фильтры работают через AJAX:**
```javascript
// При изменении фильтра
action: 'filter_members'
// Результат: обновляется сетка карточек
```

#### 3. `templates/member-dashboard.php` - Личный кабинет
**Секции:**
- Сайдбар с навигацией
- Профиль (редактирование)
- Материалы (CRUD)
- Форум (мои темы)
- Сообщения (входящие/исходящие)

**Режим просмотра админом:**
```php
if ($is_admin && $_GET['member_id']) {
    // Админ просматривает чужой кабинет
    // Показывается желтый баннер
}
```

#### 4. `templates/forum-archive.php` - Главная форума
**Секции:**
- Категории (pills)
- Список топиков с аватарами
- Метаинформация (автор, время, лайки, комментарии)
- Пагинация

**Дизайн:**
- Tailwind CSS
- Современный card-based layout
- Hover эффекты

---

## Кастомные типы постов и таксономии

### Регистрация в `members-management-pro.php`

#### 1. Post Type: `members`
```php
register_post_type('members', [
    'label' => 'Участники',
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'show_in_rest' => true,
]);
```

#### 2. Post Type: `forum_topic`
```php
register_post_type('forum_topic', [
    'label' => 'Топики форума',
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'comments'],
]);
```

#### 3. Post Type: `member_message`
```php
register_post_type('member_message', [
    'label' => 'Сообщения',
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
]);
```

#### 4. Taxonomy: `member_type`
```php
register_taxonomy('member_type', 'members', [
    'label' => 'Тип участника',
    'hierarchical' => false,
]);
// Terms: 'uchastnik', 'ekspert'
```

#### 5. Taxonomy: `member_role`
```php
register_taxonomy('member_role', 'members', [
    'label' => 'Роль',
    'hierarchical' => false,
]);
```

#### 6. Taxonomy: `member_location`
```php
register_taxonomy('member_location', 'members', [
    'label' => 'Город',
    'hierarchical' => false,
]);
```

#### 7. Taxonomy: `forum_category`
```php
register_taxonomy('forum_category', 'forum_topic', [
    'label' => 'Категория форума',
    'hierarchical' => true,
]);
```

---

## Технический стек

### Frontend
- **Tailwind CSS** - utility-first CSS (через CDN)
- **Font Awesome 6.4** - иконки
- **jQuery** - AJAX, DOM манипуляции
- **Quill.js** - WYSIWYG редактор для сообщений

### Backend
- **WordPress 5.0+**
- **PHP 7.4+**
- **MySQL** (через WordPress DB API)

### Библиотеки
- **WordPress REST API** - частично используется
- **WordPress Media Library** - для загрузки файлов
- **WordPress Comments API** - для комментариев форума

### CDN ресурсы
```html
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
```

---

## Важные концепции

### 1. Связь User ↔ Member

**Проблема:** WordPress имеет `wp_users`, но нужна дополнительная информация

**Решение:** Создаем `members` post type и связываем через мета-поле

```php
// Найти Member для текущего пользователя
$user_id = get_current_user_id();

$query = new WP_Query([
    'post_type' => 'members',
    'meta_query' => [
        [
            'key' => '_linked_user_id',
            'value' => $user_id,
            'compare' => '='
        ]
    ]
]);

$member_id = $query->posts[0]->ID;
```

### 2. Админ-просмотр чужих кабинетов

**URL:** `/member-dashboard/?member_id=123`

**Логика:**
```php
if (current_user_can('administrator') && isset($_GET['member_id'])) {
    $member_id = intval($_GET['member_id']);
    $is_viewing_other = true;
} else {
    $member_id = Member_User_Link::get_current_user_member_id();
    $is_viewing_other = false;
}
```

### 3. AJAX без авторизации

**Для публичных страниц используем оба хука:**
```php
add_action('wp_ajax_filter_members', 'callback');           // Для авторизованных
add_action('wp_ajax_nopriv_filter_members', 'callback');    // Для неавторизованных
```

### 4. Безопасность

**Nonce для AJAX:**
```php
// В PHP
wp_localize_script('script-handle', 'ajaxData', [
    'nonce' => wp_create_nonce('my_action_nonce')
]);

// В JS
$.ajax({
    data: {
        action: 'my_action',
        nonce: ajaxData.nonce
    }
});

// В PHP callback
check_ajax_referer('my_action_nonce', 'nonce');
```

**Проверка прав:**
```php
if (!current_user_can('administrator')) {
    wp_die('Нет доступа');
}
```

### 5. Материалы в JSON

**Почему JSON?**
- Гибкая структура (разные типы материалов)
- Нет необходимости в отдельной таблице
- Легко сериализуется/десериализуется

**Структура:**
```json
[
  {
    "title": "Статья о методологии",
    "description": "Краткое описание...",
    "link": "https://example.com/article",
    "image_url": "https://...",
    "file_url": null,
    "video_url": null
  }
]
```

### 6. Форум: лайки через meta

**Почему не отдельная таблица?**
- Простота
- Не нужны сложные запросы
- WordPress нативно кеширует postmeta

**Реализация:**
```php
// Получаем массив ID юзеров, которые лайкнули
$liked_by = json_decode(get_post_meta($topic_id, 'liked_by_users', true), true);

// Проверяем
if (in_array($user_id, $liked_by)) {
    // Уже лайкнул - удаляем
    $liked_by = array_diff($liked_by, [$user_id]);
} else {
    // Еще не лайкнул - добавляем
    $liked_by[] = $user_id;
}

// Сохраняем
update_post_meta($topic_id, 'liked_by_users', json_encode($liked_by));
update_post_meta($topic_id, 'likes_count', count($liked_by));
```

### 7. Template Loader Pattern

**Зачем?**
- Плагин не должен требовать изменения темы
- Автоматическая подстановка шаблонов
- Возможность переопределения в теме

**Реализация:**
```php
add_filter('template_include', function($template) {
    if (is_singular('members')) {
        $theme_template = locate_template(['single-members.php']);
        if (!$theme_template) {
            return plugin_dir_path(__FILE__) . 'single-members.php';
        }
    }
    return $template;
}, 99);
```

### 8. Сообщения: отправитель может быть админ

**Проблема:** Админ отправляет от своего имени, но у него нет Member post

**Решение:**
```php
$sender_id = get_post_meta($message_id, 'sender_member_id', true);
if ($sender_id) {
    $sender_name = get_the_title($sender_id);
} else {
    // Проверяем, админ ли автор поста
    $post_author_id = get_post_field('post_author', $message_id);
    if (user_can($post_author_id, 'administrator')) {
        $sender_name = '👑 Администратор';
    } else {
        $sender_name = 'Неизвестно';
    }
}
```

---

## Частые задачи

### Добавить новое поле к участнику

1. **В админке** - добавить metabox в `members-management-pro.php`:
```php
add_meta_box('member_new_field', 'Новое поле', function($post) {
    $value = get_post_meta($post->ID, 'member_new_field', true);
    echo '<input name="member_new_field" value="' . esc_attr($value) . '">';
}, 'members');

// Сохранение
add_action('save_post_members', function($post_id) {
    update_post_meta($post_id, 'member_new_field', $_POST['member_new_field']);
});
```

2. **В шаблоне** - использовать:
```php
$new_field = get_post_meta($member_id, 'member_new_field', true);
echo esc_html($new_field);
```

### Добавить новый фильтр в архив

1. **В `templates/archive-members.php`** - добавить HTML:
```html
<select id="new-filter">
    <option value="">Все</option>
    <option value="value1">Вариант 1</option>
</select>
```

2. **В JavaScript** - добавить в `filterData`:
```javascript
const filterData = {
    action: 'filter_members',
    new_filter: $('#new-filter').val()
};
```

3. **В PHP** - обработать в `filter_members_ajax()`:
```php
$new_filter = isset($_POST['new_filter']) ? sanitize_text_field($_POST['new_filter']) : '';
if ($new_filter) {
    $args['meta_query'][] = [
        'key' => 'member_new_field',
        'value' => $new_filter,
        'compare' => '='
    ];
}
```

### Добавить новый AJAX endpoint

1. **Зарегистрировать хук:**
```php
add_action('wp_ajax_my_new_action', 'my_new_action_callback');
```

2. **Написать callback:**
```php
function my_new_action_callback() {
    check_ajax_referer('my_nonce', 'nonce');

    $param = sanitize_text_field($_POST['param']);

    // Логика

    wp_send_json_success(['result' => 'OK']);
}
```

3. **Вызвать из JS:**
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'my_new_action',
        nonce: nonce_value,
        param: value
    },
    success: function(response) {
        console.log(response.data.result);
    }
});
```

---

## Отладка

### Включить debug режим

В `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Логи в: `/wp-content/debug.log`

### Логирование в PHP

```php
error_log('Значение переменной: ' . print_r($var, true));
```

### Логирование в JS

```javascript
console.log('Filter data:', filterData);
console.error('AJAX error:', xhr.responseText);
```

### Проверка AJAX запросов

**Chrome DevTools:**
1. F12 → Network
2. Фильтр: XHR
3. Кликнуть на запрос
4. Response tab - ответ сервера

---

## Производительность

### Кеширование WordPress

**Transients API:**
```php
// Сохранить на 1 час
set_transient('my_key', $data, HOUR_IN_SECONDS);

// Получить
$data = get_transient('my_key');
if ($data === false) {
    // Нет в кеше - вычислить
}
```

### Оптимизация запросов

**Плохо:**
```php
foreach ($members as $member) {
    $email = get_post_meta($member->ID, 'member_email', true); // N+1 запросов
}
```

**Хорошо:**
```php
update_post_meta_cache($member_ids); // 1 запрос
foreach ($members as $member) {
    $email = get_post_meta($member->ID, 'member_email', true); // Из кеша
}
```

---

## Версионирование

### Формат версий: MAJOR.MINOR.PATCH

- **MAJOR** - несовместимые изменения API
- **MINOR** - новые функции (обратно совместимые)
- **PATCH** - исправления багов

### Текущая версия: **3.4.4**

**Changelog:**
- 3.4.4 - Форум архив, фиксы админ идентификации
- 3.4.3 - Колонки сообщений в админке
- 3.4.2 - Критические багфиксы (redirect, filter, dashboard)
- 3.4.1 - Контроль доступа к форуму
- 3.4.0 - Админ логи активности

---

## Контакты и поддержка

**GitHub:** https://github.com/KirillRem777/metoda_members
**Ветка:** `claude/review-archive-solution-01BDVM9hSxbr8rj538dBC3X1`

**Для быстрого старта в новом чате:**
1. Прочитай этот файл (`ARCHITECTURE.md`)
2. Прочитай `DOCUMENTATION.md` для пользовательских инструкций
3. Изучи `members-management-pro.php` - главный файл
4. Проверь структуру БД в разделе "Архитектура базы данных"

---

**Документация актуальна для версии 3.4.4**
**Дата обновления: 2025-11-20**
