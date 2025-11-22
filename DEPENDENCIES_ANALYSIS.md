# Анализ зависимостей плагина v4.2.0

## Общая статистика

- **Общее количество строк:** 4463
- **Глобальных функций:** 62
- **Хуков (add_action/add_filter):** 55
- **Инициализируемых классов:** 9

---

## Структура главного файла (members-management-pro.php)

### 1. Заголовок плагина (строки 1-10)
```php
Plugin Name: Metoda Community MGMT
Version: 4.2.0
```

### 2. Константы (строки 11-25)
- `METODA_PLUGIN_DIR`
- `METODA_DISABLE_PLUGIN` (kill switch)

### 3. Загрузка классов (строки 27-50)
```php
require_once 'includes/class-member-user-link.php';
require_once 'includes/class-member-page-templates.php';
require_once 'includes/class-member-csv-importer.php';
require_once 'includes/class-member-email-templates.php';
require_once 'includes/class-member-access-codes.php';
require_once 'includes/class-member-otp.php';
require_once 'includes/class-member-bulk-users.php';
require_once 'includes/class-member-dashboard.php';
require_once 'includes/class-member-file-manager.php';
require_once 'includes/class-member-manager.php';
require_once 'includes/class-member-archive.php';
require_once 'includes/class-member-forum.php';
require_once 'includes/class-member-onboarding.php';
require_once 'includes/class-member-template-loader.php';
```

### 4. Инициализация классов (строки 53-61)
```php
new Member_Dashboard();
new Member_File_Manager();
new Member_Manager();
new Member_Archive();
new Member_Forum();
new Member_Onboarding();
new Member_Template_Loader();
new Member_Access_Codes();
new Member_OTP();
```

### 5. Глобальные функции (62 штуки)

#### Activation/Deactivation hooks
1. `metoda_plugin_activation()` - line 66
2. `metoda_members_activate()` - line 137
3. `metoda_members_deactivate()` - line 345

#### Security
4. `get_editable_member_id($request)` - line 82 ⚠️ ВАЖНАЯ

#### Custom Roles & Capabilities
5. `metoda_create_custom_roles()` - line 205

#### Page Creation
6. `metoda_create_template_pages()` - line 245
7. `metoda_create_pages_deferred()` - line 320

#### Post Types
8. `register_members_post_type()` - line 359
9. `register_member_messages_post_type()` - line 402
10. `register_forum_topic_post_type()` - line 450
11. `register_forum_category_post_type()` - line 488

#### Taxonomies
12. `register_member_type_taxonomy()` - line 538
13. `register_member_role_taxonomy()` - line 567
14. `register_member_location_taxonomy()` - line 596

#### Image Sizes
15. `register_member_image_sizes()` - line 440
16. `add_image_crop_help_notice()` - line 453
17. `dismiss_image_crop_notice_ajax()` - line 527

#### Meta Boxes
18. `add_member_meta_boxes()` - line 625
19. `render_member_details_meta_box($post)` - line 638
20. `save_member_details($post_id)` - line 1173

#### Shortcodes
21. `members_directory_shortcode($atts)` - line 1272
22. `member_registration_shortcode()` - line 1824
23. `custom_login_shortcode()` - line 2222
24. `forgot_password_shortcode()` - line 2489
25. `reset_password_shortcode()` - line 2635
26. `member_onboarding_shortcode()` - line 2840
27. `manager_panel_shortcode()` - line 3163
28. `forum_archive_shortcode()` - line 3434
29. `forum_topic_shortcode()` - line 3541

#### AJAX Handlers (массив)
30-55. Около 25+ AJAX функций для обработки форм

---

## Зависимости между модулями

### Core Dependencies
```
members-management-pro.php
  ↓
  ├── Member_User_Link (связь WP User ↔ Member)
  ├── Member_Page_Templates (создание страниц)
  ├── Member_Template_Loader (загрузка шаблонов)
  └── get_editable_member_id() (security function)
```

### Auth Module Dependencies
```
Member_Access_Codes
Member_OTP
  ↓
  └── Member_Email_Templates (отправка писем)
```

### Frontend Module Dependencies
```
Member_Dashboard
Member_Archive
Member_Forum
Member_Onboarding
  ↓
  └── Member_Template_Loader (шаблоны)
```

### Admin Module Dependencies
```
Member_Manager
Member_CSV_Importer
Member_Bulk_Users
Member_File_Manager
  ↓
  └── Member_Email_Templates (уведомления)
```

---

## Критически важные функции (нельзя ломать!)

1. **get_editable_member_id($request)** - line 82
   - Используется везде для проверки прав доступа
   - ДОЛЖНА оставаться глобальной или быть доступной везде

2. **metoda_create_template_pages()** - line 245
   - Создает критичные страницы плагина
   - Вызывается при активации

3. **register_members_post_type()** - line 359
   - Регистрирует главный post type
   - ДОЛЖЕН выполниться на `init` hook

4. **save_member_details($post_id)** - line 1173
   - Сохраняет метаданные участника
   - Критична для работы плагина

---

## План извлечения в legacy/

### legacy/functions.php должен содержать:
- ✅ Все 62 глобальные функции
- ✅ В том же порядке как в оригинале
- ✅ С комментариями о назначении

### legacy/hooks.php должен содержать:
- ✅ Все add_action() и add_filter()
- ✅ В том же порядке регистрации
- ✅ Комментарии какой hook к чему привязан

### Что НЕ трогаем:
- ❌ Константы (остаются в главном файле)
- ❌ require_once классов (остаются в главном файле)
- ❌ new Class() инициализации (остаются в главном файле)

---

**Дата анализа:** 22 ноября 2025
**Проанализировано строк:** 4463
**Найдено зависимостей:** Критических - 4, Средних - 15, Слабых - 43
