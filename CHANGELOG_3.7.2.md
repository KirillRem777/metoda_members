# 🔧 Dashboard Admin View Fix v3.7.2 (2025-11-21)

## КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Админский просмотр кабинетов

### ❌ Проблема
Админы не могли корректно редактировать профили других участников через `/member-dashboard/?member_id=XXX`:
- JavaScript не знал, какой профиль редактируется
- AJAX запросы отправлялись без `member_id`
- Изменения сохранялись в профиль админа вместо нужного участника
- Галерея и материалы редактировались неправильно

---

## ✅ ИСПРАВЛЕНИЯ

### 1. **PHP: class-member-dashboard.php**

#### Добавлено в `enqueue_dashboard_assets()`:

```php
// FIXED: Определяем member_id для JS (критично для админского просмотра)
$is_admin = current_user_can('administrator');
$viewing_member_id = isset($_GET['member_id']) ? absint($_GET['member_id']) : null;

if ($is_admin && $viewing_member_id) {
    // Админ смотрит чужой кабинет
    $member_id_for_js = $viewing_member_id;
    $is_admin_view = true;
} else {
    // Обычный пользователь или админ без параметра
    $member_id_for_js = Member_User_Link::get_current_user_member_id();
    $is_admin_view = false;
}

wp_localize_script('member-dashboard', 'memberDashboard', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('member_dashboard_nonce'),
    'memberId' => $member_id_for_js,        // ADDED ✅
    'isAdminView' => $is_admin_view,        // ADDED ✅
));
```

#### Улучшения безопасности:
- ✅ Заменил `intval()` → `absint()` (best practice для ID)
- ✅ Добавил `esc_url()` и `esc_html()` где не хватало

#### Добавлена админ-панель:
При просмотре чужого кабинета сверху появляется панель с кнопками:
- 🔙 **К списку участников** → `/manager-panel/`
- 👤 **Публичный профиль** → `/members/{slug}/`
- ⚙️ **Редактировать в админке** → `/wp-admin/post.php?post={id}&action=edit`

```html
<!-- Admin View Notice Bar -->
<div class="admin-view-notice">
    <div class="notice-content">
        <span class="notice-icon">👤</span>
        <span class="notice-text">
            Вы просматриваете кабинет участника: <strong><?php echo esc_html($member_data['name']); ?></strong>
        </span>
    </div>
    <div class="notice-actions">
        <a href="<?php echo esc_url(home_url('/manager-panel/')); ?>" class="btn-notice">
            <i class="fas fa-arrow-left"></i> К списку
        </a>
        <a href="<?php echo esc_url(get_permalink($member_id)); ?>" class="btn-notice" target="_blank">
            <i class="fas fa-external-link-alt"></i> Публичный профиль
        </a>
        <a href="<?php echo esc_url(admin_url('post.php?post=' . $member_id . '&action=edit')); ?>" class="btn-notice">
            <i class="fas fa-cog"></i> В админке
        </a>
    </div>
</div>
```

---

### 2. **JS: member-dashboard.js**

#### Добавлена функция `getMemberId()`:
```javascript
/**
 * ADDED: Helper function to get member_id for AJAX requests
 * Возвращает ID участника из локализованных данных
 */
function getMemberId() {
    return memberDashboard.memberId || null;
}
```

#### Исправлен `initProfileForm()`:
```javascript
// FIXED: Добавляем member_id в запрос
const memberId = getMemberId();
const memberIdParam = memberId ? '&member_id=' + memberId : '';

$.ajax({
    url: memberDashboard.ajaxUrl,
    type: 'POST',
    data: formData + '&action=member_update_profile&nonce=' + memberDashboard.nonce + memberIdParam,
    // ...
});
```

#### Исправлен `uploadGalleryPhoto()`:
```javascript
// FIXED: Исправлено memberDashboardData → memberDashboard
const memberId = getMemberId();

// ...

formData.append('action', 'member_upload_gallery_photo');
formData.append('nonce', memberDashboard.nonce);       // FIXED
formData.append('member_id', memberId);                 // ADDED
```

#### Исправлен `initMaterialsManager()`:

**Добавление ссылки:**
```javascript
const memberId = getMemberId();

$.ajax({
    url: memberDashboard.ajaxUrl,
    type: 'POST',
    data: {
        action: 'member_add_material_link',
        nonce: memberDashboard.nonce,
        member_id: memberId,  // ADDED ✅
        category: category,
        title: title,
        url: url,
        description: description
    },
    // ...
});
```

**Загрузка файла:**
```javascript
const memberId = getMemberId();

formData.append('action', 'member_add_material_file');
formData.append('nonce', memberDashboard.nonce);
formData.append('member_id', memberId);  // ADDED ✅
formData.append('category', category);
// ...
```

**Удаление материала:**
```javascript
const memberId = getMemberId();

$.ajax({
    url: memberDashboard.ajaxUrl,
    type: 'POST',
    data: {
        action: 'member_delete_material',
        nonce: memberDashboard.nonce,
        member_id: memberId,  // ADDED ✅
        category: category,
        index: index
    },
    // ...
});
```

#### Добавлена консольная отладка:
```javascript
if (memberDashboard.isAdminView) {
    console.log('Admin view mode: editing member ID ' + memberDashboard.memberId);
}
```

---

## 📊 РЕЗУЛЬТАТЫ

| Функция | До | После |
|---------|-----|-------|
| **Редактирование профиля** | ❌ Сохранялось в профиль админа | ✅ Сохраняется в нужный профиль |
| **Загрузка в галерею** | ❌ Фото добавлялись админу | ✅ Фото добавляются участнику |
| **Добавление ссылки** | ❌ Сохранялось админу | ✅ Сохраняется участнику |
| **Загрузка файла** | ❌ Файл шел админу | ✅ Файл идет участнику |
| **Удаление материала** | ❌ Не работало | ✅ Удаляется корректно |
| **Админ-панель** | ❌ Отсутствовала | ✅ Красивая панель сверху |

---

## 🧪 ТЕСТИРОВАНИЕ

### Сценарий 1: Админ редактирует чужой профиль
1. Войти как администратор
2. Перейти на `/manager-panel/`
3. Нажать "Edit" у любого участника
4. Изменить имя, должность, загрузить фото
5. Сохранить → **Проверить, что изменения сохранились в профиль участника, а НЕ админа**

### Сценарий 2: Добавление материалов
1. В кабинете участника (через `?member_id=XXX`)
2. Перейти во вкладку "Материалы"
3. Добавить ссылку/загрузить файл
4. **Проверить, что материал появился у участника, а НЕ у админа**

### Сценарий 3: Работа галереи
1. Загрузить фото в галерею участника
2. **Проверить, что фото появилось в галерее участника**
3. Удалить фото → должно удалиться корректно

### Сценарий 4: Админ-панель
1. Проверить, что сверху появилась желтая панель "Вы просматриваете кабинет..."
2. Нажать "К списку" → должно вернуть в `/manager-panel/`
3. Нажать "Публичный профиль" → открывается профиль в новой вкладке
4. Нажать "В админке" → открывается админка редактирования

---

## ⚠️ ВАЖНО

### Обновление кэша
После обновления обязательно:
```bash
# Очистить кэш браузера
Ctrl+Shift+Del (Chrome/Firefox)

# Очистить кэш WordPress (если есть плагин кэширования)
wp cache flush
```

### Версии файлов обновлены
- `class-member-dashboard.php` → версия **1.0.1**
- `member-dashboard.js` → версия **1.0.1**

WordPress автоматически подгрузит новые версии благодаря:
```php
wp_enqueue_script('member-dashboard', ..., array('jquery'), '1.0.1', true);
```

---

## 📂 ИЗМЕНЕННЫЕ ФАЙЛЫ

1. `includes/class-member-dashboard.php` - полная замена
2. `assets/js/member-dashboard.js` - полная замена
3. `members-management-pro.php` - версия 3.7.1 → 3.7.2

---

## 🔗 СВЯЗАННЫЕ ИСПРАВЛЕНИЯ

Эти изменения работают в паре с исправлениями из **v3.7.1**:
- Админ bypass в AJAX handlers (все 7 endpoints)
- Унификация `_linked_user_id`
- Security fixes

---

**Версия:** 3.7.2
**Дата:** 2025-11-21
**Статус:** ✅ КРИТИЧНО - ОБЯЗАТЕЛЬНО К УСТАНОВКЕ
**Приоритет:** 🔥 ВЫСОКИЙ

**Без этого фикса админы НЕ МОГУТ редактировать профили участников!**
