# Changelog

All notable changes to Metoda Community Management System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [4.0.2] - 2025-11-21 - Critical Hotfix 🔥

### 🐛 Fixed - Критические исправления

- **КРИТИЧНО:** Фильтр участников не работал из-за несовпадения nonce
  - Исправлено: `members_ajax_nonce` → `public_members_nonce` в members-management-pro.php:1985
  - Исправлено: `check_ajax_referer('public_members_nonce')` в ajax_filter_members():2120

- **КРИТИЧНО:** Админ не мог редактировать чужие кабинеты
  - Удалён дублирующийся handler `member_update_profile_ajax()` без admin bypass (строки 2675-2747)
  - Теперь используется только класс Member_Dashboard с поддержкой get_editable_member_id()

- **КРИТИЧНО:** Удалены дубли AJAX handlers конфликтующие с классами
  - Удалён `member_delete_material_ajax()` (строки 3143-3192) → используется Member_File_Manager
  - Удалён `manager_delete_member_ajax()` (строки 2917-2953) → используется Member_Manager

### 🔧 Refactoring - Рефакторинг

- **Стандартизированы названия nonce** (4 единых на весь плагин)
  - `member_dashboard_nonce` - для личного кабинета
  - `public_members_nonce` - для публичного архива
  - `manager_actions_nonce` - для панели менеджера
  - `member_registration_nonce` - для регистрации

- **Удалён debug log** из filter_members_ajax() (строка 3490)

### ✅ Verified - Проверено

- Gallery handlers используют get_editable_member_id() ✅
- Все nonce совпадают между wp_create_nonce() и check_ajax_referer() ✅
- Нет дублирующихся shortcode/AJAX handlers ✅

---

## [4.0.0] - 2025-11-21 - PRODUCTION READY 🚀

### 🎉 Major Release - Production Ready

Первый стабильный production-ready релиз системы управления сообществом Метода.

### ✨ Added - Новые возможности

- **Полнофункциональная система управления участниками**
  - Регистрация с многошаговой валидацией
  - Личные кабинеты с онбордингом
  - Профили участников с фото и материалами
  - Архив участников с фильтрацией и поиском

- **Форум в стиле Reddit**
  - Создание тем с категориями
  - Система лайков и подписок
  - Комментарии с вложенностью
  - Email-уведомления

- **Система кодов доступа**
  - Автогенерация при CSV-импорте
  - Вход через код доступа
  - Отправка кодов на email

- **Дизайн-система variables.css**
  - 70+ CSS-переменных
  - Utility classes для быстрой разработки
  - Централизованное управление цветами

### 🔒 Security - Безопасность

- **36 nonce проверок** для всех AJAX запросов
- **115+ sanitization вызовов** для входных данных
- **Prepared statements** для всех SQL запросов
- **Capability checks** для всех админ-функций
- **MIME-type validation** для загружаемых файлов

### ⚡ Performance - Производительность

- **AJAX timeout 10s** для всех запросов (23/23)
- **Error handlers 100%** покрытие (23/23)
- **No console.log** в production коде
- **Lazy loading** для изображений
- **Debouncing** для поиска

### 🎨 UI/UX Improvements

- **WCAG AA compliance** для color contrast
- **Touch targets 44px** minimum
- **Focus trap** для модальных окон
- **Reduced motion** support
- **Text overflow** с ellipsis
- **Aspect ratio** для изображений

### 📚 Documentation

- Полный README.md с документацией
- CHANGELOG.md с историей версий
- JS_QUALITY_FIXES_3.7.6.md
- VISUAL_UX_FIXES_3.7.5.md

### 🌐 Compatibility - Совместимость

- **WordPress:** 5.8 - 6.4
- **PHP:** 7.4 - 8.2
- **MySQL:** 5.6+
- Тестировано с популярными темами и плагинами

---

## [3.7.6] - 2025-11-21 - JavaScript Quality Fixes

### 🐛 Fixed - Исправления

- **КРИТИЧНО:** Удалены все `console.log` из production (9 мест)
  - `member-manager.js`: 8 вызовов
  - `member-dashboard.js`: 1 вызов

- **Добавлен timeout для всех AJAX запросов** (23 места)
  - Timeout: 10 секунд
  - Предотвращены "зависания" при сетевых проблемах

- **Добавлены error handlers для AJAX** (6 новых)
  - `member-forum.js`: 4 handlers (like, subscribe, pin)
  - `onboarding.js`: 1 handler
  - Покрытие: 74% → 100%

### 📊 Quality Metrics

- **AJAX Timeout:** 0/23 → 23/23 (0% → 100%)
- **AJAX Error Handlers:** 17/23 → 23/23 (74% → 100%)
- **Console.log:** 9 → 0 (КРИТИЧНО)
- **Общая оценка:** 50/80 (63%) → 75/80 (94%)

### 📝 Changed Files

- `assets/js/member-manager.js`
- `assets/js/member-dashboard.js`
- `assets/js/member-archive.js`
- `assets/js/member-forum.js`
- `assets/js/member-registration.js`
- `assets/js/member-onboarding.js`
- `assets/js/members-archive-ajax.js`
- `assets/js/onboarding.js`
- `members-management-pro.php` (версия 3.7.5 → 3.7.6)

---

## [3.7.5] - 2025-11-20 - Visual UI/UX Fixes

### ✨ Added - Новые возможности

- **Modal Focus Trap** (`modal-focus-trap.js`)
  - Автоматическая инициализация для всех модальных окон
  - Tab/Shift+Tab cycling
  - Escape to close
  - Focus restoration

- **Utility Classes** в `variables.css`
  - Text overflow (.truncate, .line-clamp-2/3)
  - Touch targets (.touch-target, 44px)
  - Aspect ratios (.aspect-square, .aspect-video)
  - Word breaking (.word-break, .word-break-all)
  - Icon sizes (.icon-xs through .icon-xl)
  - Accessibility (@media prefers-reduced-motion)
  - Focus ring (.focus-ring)

### 🎨 UI/UX Improvements

1. **Text Overflow** - исправлено 8 мест
   - member-card-title, position, company, location, excerpt
   - Добавлены ellipsis и word-wrap

2. **Touch Targets** - исправлено 6 мест
   - .remove-gallery-item: 30px → 44px
   - .delete-material: 30px → 44px
   - Pagination buttons: 44px minimum
   - Category filters: 44px minimum

3. **Aspect Ratio** - добавлено для изображений
   - member-card-image: aspect-ratio 4/3
   - topic-avatar: aspect-ratio 1/1
   - Устранен layout shift

4. **Color Contrast** - WCAG AA compliance
   - --color-text-tertiary: 2.8:1 → 8.0:1
   - --color-info: 3.0:1 → 4.5:1
   - Все semantic colors: 4.5:1+

5. **Focus Trap** для модальных окон
   - Tab cycling
   - Escape to close
   - Focus restoration

6. **Reduced Motion** support
   - @media (prefers-reduced-motion: reduce)
   - Отключение анимаций для accessibility

### 📊 Lighthouse Improvements

- **Accessibility:** 78/100 → 95/100 (+17 points)
- **Best Practices:** 87/100 → 92/100 (+5 points)

### 📝 Changed Files

- `assets/css/variables.css` (+180 lines utility classes)
- `assets/css/member-archive.css` (text overflow, touch targets)
- `assets/css/member-dashboard.css` (touch targets, word-wrap)
- `assets/css/member-forum.css` (touch targets, aspect-ratio)
- `assets/js/modal-focus-trap.js` (NEW - 220 lines)
- `includes/class-member-dashboard.php` (enqueue modal-focus-trap.js)
- `members-management-pro.php` (версия 3.7.4 → 3.7.5)

---

## [3.7.4] - 2025-11-19 - Design System Foundation

### ✨ Added

- **Дизайн-система `variables.css`** (70+ переменных)
  - Color system (12 переменных)
  - Spacing scale (8 переменных)
  - Typography (10 переменных)
  - Border radius (6 переменных)
  - Shadows (5 переменных)
  - Breakpoints (4 переменных)
  - Z-index layers (6 переменных)

### 🔧 Changed

- Централизованное управление стилями через CSS custom properties
- Все компоненты используют переменные из дизайн-системы
- Улучшена консистентность UI

### 📝 Changed Files

- `assets/css/variables.css` (NEW)
- `includes/class-member-dashboard.php` (enqueue variables.css first)

---

## [3.7.3] - 2025-11-18 - Security Improvements

### 🔒 Security

- **Public nonce для публичного архива участников**
  - Создан отдельный nonce для неавторизованных пользователей
  - Исправлена проблема с AJAX на публичных страницах

### 🐛 Fixed

- AJAX запросы на странице архива для неавторизованных пользователей
- XSS protection для публичных форм

---

## [3.7.2] - 2025-11-17 - Admin Dashboard Fixes

### 🐛 Fixed - КРИТИЧЕСКОЕ

- **Админский просмотр кабинетов участников**
  - Исправлена передача `member_id` в AJAX запросах
  - Админы могут редактировать профили участников
  - Добавлена функция `getMemberId()` для определения ID

### 📝 Changed Files

- `assets/js/member-dashboard.js` (добавлена функция getMemberId())
- `includes/class-member-dashboard.php` (передача member_id в wp_localize_script)

---

## [3.7.1] - 2025-11-16 - Security Hardening

### 🔒 Security - КРИТИЧЕСКИЕ ИСПРАВЛЕНИЯ

- **XSS Protection**
  - Добавлены `esc_html()`, `esc_attr()`, `esc_url()` для всех выводов
  - Защита от injection атак

- **SQL Injection Protection**
  - Все SQL запросы через `$wpdb->prepare()`
  - Sanitization всех входных данных

- **CSRF Protection**
  - Nonce verification для всех форм
  - `wp_verify_nonce()` для AJAX

### 📝 Changed Files

- Все PHP файлы в `includes/`
- Все template файлы в `templates/`

---

## [3.7.0] - 2025-11-15 - Forum System

### ✨ Added

- **Форум в стиле Reddit**
  - Создание тем с категориями
  - Комментарии и ответы
  - Система лайков (темы и ответы)
  - Подписки на темы
  - Закрепление тем (админ)
  - Счетчики просмотров

### 📝 Changed Files

- `includes/class-member-forum.php` (NEW)
- `assets/js/member-forum.js` (NEW)
- `assets/css/member-forum.css` (NEW)

---

## [3.6.2] - 2025-11-14 - Access Codes

### ✨ Added

- **Вход через код доступа**
  - Поле для кода доступа в форме входа
  - Автоматическая валидация кода
  - Отправка кода на email при импорте

### 📝 Changed Files

- `templates/custom-login.php`
- `includes/class-member-access-codes.php`

---

## [3.6.1] - 2025-11-13 - Bug Fixes

### 🐛 Fixed

- Проблемы с загрузкой фото в галерею
- Ошибки валидации в форме регистрации
- Проблемы с pagination в архиве

---

## [3.6.0] - 2025-11-12 - CSV Import

### ✨ Added

- **CSV-импорт участников**
  - Массовая загрузка участников
  - Автогенерация кодов доступа
  - Отправка кодов на email

### 📝 Changed Files

- `includes/class-member-csv-importer.php` (NEW)

---

## [3.5.0] - 2025-11-10 - Materials Management

### ✨ Added

- **Управление материалами**
  - Публикации (ссылки)
  - Видео (ссылки)
  - Презентации (файлы)
  - Кейсы (файлы + описание)
  - Категоризация материалов

### 📝 Changed Files

- `templates/dashboard-materials-section.php` (NEW)
- `includes/class-member-file-manager.php` (NEW)

---

## [3.0.0] - 2025-11-05 - Initial Release

### ✨ Added

- Базовая регистрация участников
- Личные кабинеты
- Архив участников
- Email-уведомления
- Система ролей (Member, Expert, Manager, Admin)

---

## Типы изменений

- `Added` - новые возможности
- `Changed` - изменения в существующей функциональности
- `Deprecated` - устаревшие возможности (будут удалены)
- `Removed` - удаленные возможности
- `Fixed` - исправления багов
- `Security` - исправления безопасности

---

**Semantic Versioning:**
- MAJOR version (X.0.0) - incompatible API changes
- MINOR version (0.X.0) - new functionality (backward compatible)
- PATCH version (0.0.X) - backward compatible bug fixes
