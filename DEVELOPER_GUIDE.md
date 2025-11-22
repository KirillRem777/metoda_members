# Developer Guide - Metoda Members Plugin v4.2.0

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [Class Reference](#class-reference)
4. [Extending the Plugin](#extending-the-plugin)
5. [Best Practices](#best-practices)
6. [Security Guidelines](#security-guidelines)
7. [Testing and Debugging](#testing-and-debugging)

---

## Architecture Overview

### Design Philosophy

The Metoda Members plugin follows a **hybrid architecture** combining:
- **Modular OOP Classes** - New functionality organized into focused classes
- **Legacy Layer** - Backward-compatible function layer for external integrations
- **100% Compatibility** - All legacy functions remain callable

### Architecture Pattern

```
WordPress Hooks
     ↓
Modular Classes (New) ← Main execution path
     ↓
Legacy Functions (Preserved) ← Backward compatibility layer
```

**Key Principle:** Hooks execute through classes, but legacy functions remain available for templates and external plugins.

---

## Directory Structure

```
metoda_members/
├── members-management-pro.php    # Main plugin file, bootstrap
├── includes/
│   ├── core/                     # Core functionality classes
│   │   ├── class-post-types.php  # CPT registration (members, messages)
│   │   ├── class-taxonomies.php  # Taxonomy registration
│   │   └── class-assets.php      # Frontend CSS/JS management
│   ├── admin/                    # Admin interface classes
│   │   └── class-meta-boxes.php  # Edit screen meta boxes
│   ├── ajax/                     # AJAX handlers
│   │   └── class-ajax-members.php # All member-related AJAX
│   ├── auth/                     # Authentication & security
│   │   └── class-security.php    # Access control utilities
│   └── legacy/                   # ⚠️ DO NOT MODIFY
│       ├── functions.php         # 62 legacy functions (read-only)
│       └── hooks.php             # Hook registrations (managed)
├── templates/                    # Frontend templates
├── assets/                       # CSS, JS, images
└── docs/                         # Documentation
```

### File Modification Rules

**✅ SAFE TO MODIFY:**
- `includes/core/class-*.php`
- `includes/admin/class-*.php`
- `includes/ajax/class-*.php`
- `includes/auth/class-*.php`
- `templates/*.php`
- `assets/**/*`

**⚠️ MANAGED (require careful review):**
- `members-management-pro.php` (bootstrap logic)
- `includes/legacy/hooks.php` (hook migrations only)

**❌ DO NOT MODIFY:**
- `includes/legacy/functions.php` (preserved for compatibility)

---

## Class Reference

### Core Classes

#### Metoda_Post_Types
**File:** `includes/core/class-post-types.php`
**Purpose:** Registers custom post types and image sizes

**Methods:**
- `register_members_post_type()` - Registers 'members' CPT
- `register_member_messages_post_type()` - Registers 'member_message' CPT
- `register_member_image_sizes()` - Defines custom image sizes

**Hook Registration:**
```php
add_action('init', array($this, 'register_members_post_type'));
add_action('init', array($this, 'register_member_messages_post_type'));
add_action('after_setup_theme', array($this, 'register_member_image_sizes'));
```

---

#### Metoda_Taxonomies
**File:** `includes/core/class-taxonomies.php`
**Purpose:** Registers custom taxonomies

**Methods:**
- `register_member_type_taxonomy()` - Member types (студент, ментор, etc.)
- `register_member_role_taxonomy()` - Member roles
- `register_member_location_taxonomy()` - Member locations

**Hook Registration:**
```php
add_action('init', array($this, 'register_member_type_taxonomy'));
add_action('init', array($this, 'register_member_role_taxonomy'));
add_action('init', array($this, 'register_member_location_taxonomy'));
```

---

#### Metoda_Assets
**File:** `includes/core/class-assets.php`
**Purpose:** Manages frontend CSS/JS loading with smart conditional loading

**Methods:**
- `register_tailwind_styles()` - Registers Tailwind CSS
- `enqueue_scripts()` - Conditionally loads scripts based on page slug

**Features:**
- Page-specific script loading (only loads where needed)
- Tailwind CSS integration
- Font Awesome support
- AJAX localization

**Hook Registration:**
```php
add_action('init', array($this, 'register_tailwind_styles'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
```

---

### Admin Classes

#### Metoda_Meta_Boxes
**File:** `includes/admin/class-meta-boxes.php`
**Purpose:** Handles member edit screen interface (669 lines, 36 KB)

**Methods:**
- `add_member_meta_boxes()` - Registers meta boxes
- `render_member_details_meta_box()` - Renders edit UI (includes inline CSS/JS)
- `save_member_details()` - Processes form submissions with nonce verification

**Security Features:**
- Nonce verification on save
- Capability checks (edit_post)
- Data sanitization for all fields

**Hook Registration:**
```php
add_action('add_meta_boxes', array($this, 'add_member_meta_boxes'));
add_action('save_post_members', array($this, 'save_member_details'));
```

---

### AJAX Classes

#### Metoda_Ajax_Members
**File:** `includes/ajax/class-ajax-members.php` (1,508 lines, 61 KB)
**Purpose:** Handles all member-related AJAX operations

**Public AJAX Endpoints** (wp_ajax_* + wp_ajax_nopriv_*):
- `member_register` - New member registration
- `load_more_members` - Infinite scroll pagination
- `filter_members` - Member filtering/search
- `send_member_message` - Send private messages

**Private AJAX Endpoints** (wp_ajax_* only):
- `dismiss_image_crop_notice` - Dismiss admin notices
- `manager_change_member_status` - Change member status (managers only)
- `member_save_gallery` - Save gallery order
- `member_upload_gallery_photo` - Upload photos
- `member_add_material_link` - Add portfolio link
- `member_add_material_file` - Add portfolio file
- `add_portfolio_material` - Add material (JSON API)
- `delete_portfolio_material` - Delete material (JSON API)
- `edit_portfolio_material` - Edit material (JSON API)
- `create_forum_topic_dashboard` - Create forum topic from dashboard
- `view_member_message` - Mark message as viewed

**Security Pattern:**
```php
// Nonce verification (required for all AJAX)
if (!wp_verify_nonce($_POST['nonce'], 'action_name_nonce')) {
    wp_send_json_error('Недействительный nonce.');
}

// Capability check (for privileged actions)
if (!current_user_can('edit_posts')) {
    wp_send_json_error('Недостаточно прав.');
}

// Member ID validation
$member_id = Metoda_Security::get_editable_member_id($_POST);
if (is_wp_error($member_id)) {
    wp_send_json_error($member_id->get_error_message());
}
```

---

### Auth/Security Classes

#### Metoda_Security
**File:** `includes/auth/class-security.php`
**Purpose:** Core security and access control utilities

**Static Methods:**
- `get_editable_member_id($request)` - Returns member ID user can edit, or WP_Error

**Usage Example:**
```php
$member_id = Metoda_Security::get_editable_member_id($_POST);
if (is_wp_error($member_id)) {
    wp_send_json_error($member_id->get_error_message());
    return;
}
// Proceed with validated member ID
```

**Access Control Logic:**
1. If user has `manage_options` → can edit any member
2. If user is logged in → returns their member post ID
3. Otherwise → returns WP_Error

---

## Extending the Plugin

### Adding a New AJAX Handler

**1. Add method to Metoda_Ajax_Members:**

```php
// File: includes/ajax/class-ajax-members.php

public function __construct() {
    // Add to constructor
    add_action('wp_ajax_your_new_action', array($this, 'handle_your_action'));
}

public function handle_your_action() {
    // 1. Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'your_action_nonce')) {
        wp_send_json_error('Недействительный nonce.');
    }

    // 2. Validate member ID (if needed)
    $member_id = Metoda_Security::get_editable_member_id($_POST);
    if (is_wp_error($member_id)) {
        wp_send_json_error($member_id->get_error_message());
    }

    // 3. Process request
    $result = $this->process_your_logic($member_id, $_POST);

    // 4. Return JSON response
    if ($result) {
        wp_send_json_success(array('message' => 'Success!'));
    } else {
        wp_send_json_error('Failed to process.');
    }
}
```

**2. Enqueue AJAX script (if needed):**

```php
// File: includes/core/class-assets.php

wp_localize_script('your-script', 'yourAjax', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('your_action_nonce')
));
```

---

### Creating a New Admin Class

**Example: Creating class-admin-notices.php**

```php
<?php
/**
 * Admin Notices Handler
 *
 * @package Metoda_Members
 * @since 4.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Metoda_Admin_Notices {

    public function __construct() {
        add_action('admin_notices', array($this, 'display_notices'));
    }

    public function display_notices() {
        // Your notice logic here
    }
}
```

**Register in members-management-pro.php:**

```php
// Load new class
require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-notices.php';

// Initialize (in plugin bootstrap section)
if (is_admin()) {
    new Metoda_Admin_Notices();
}
```

---

### Adding a New Meta Box

**1. Add to Metoda_Meta_Boxes class:**

```php
// File: includes/admin/class-meta-boxes.php

public function add_member_meta_boxes() {
    // Existing meta boxes...

    // Add your new meta box
    add_meta_box(
        'your_meta_box_id',
        'Your Meta Box Title',
        array($this, 'render_your_meta_box'),
        'members',
        'normal',
        'high'
    );
}

public function render_your_meta_box($post) {
    wp_nonce_field('save_your_meta', 'your_meta_nonce');

    $your_data = get_post_meta($post->ID, '_your_meta_key', true);
    ?>
    <input type="text" name="your_field" value="<?php echo esc_attr($your_data); ?>" />
    <?php
}

public function save_member_details($post_id) {
    // Add to existing save logic
    if (isset($_POST['your_meta_nonce']) &&
        wp_verify_nonce($_POST['your_meta_nonce'], 'save_your_meta')) {

        if (isset($_POST['your_field'])) {
            update_post_meta($post_id, '_your_meta_key', sanitize_text_field($_POST['your_field']));
        }
    }
}
```

---

## Best Practices

### 1. Never Modify Legacy Code

**❌ WRONG:**
```php
// Modifying includes/legacy/functions.php
function register_members_post_type() {
    // Changed implementation...
}
```

**✅ CORRECT:**
```php
// Create new class method or extend existing class
// Legacy function remains untouched for compatibility
```

---

### 2. Always Use Nonce Verification

**❌ WRONG:**
```php
public function handle_ajax() {
    $data = $_POST['data']; // No verification!
    update_post_meta($post_id, '_key', $data);
}
```

**✅ CORRECT:**
```php
public function handle_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'action_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    $data = sanitize_text_field($_POST['data']);
    update_post_meta($post_id, '_key', $data);
}
```

---

### 3. Use Metoda_Security for Access Control

**❌ WRONG:**
```php
$member_id = intval($_POST['member_id']); // No validation!
update_post_meta($member_id, '_key', $value);
```

**✅ CORRECT:**
```php
$member_id = Metoda_Security::get_editable_member_id($_POST);
if (is_wp_error($member_id)) {
    wp_send_json_error($member_id->get_error_message());
    return;
}
update_post_meta($member_id, '_key', $value);
```

---

### 4. Follow WordPress Coding Standards

**Class Naming:** `Metoda_Feature_Name`
**File Naming:** `class-feature-name.php`
**Method Visibility:** Declare explicitly (public/private/protected)
**Hooks in Constructor:** Use `array($this, 'method_name')`

---

### 5. Document Your Code

```php
/**
 * Processes member gallery upload
 *
 * Validates file type, size, and permissions before uploading.
 * Logs activity for security audit trail.
 *
 * @since 4.2.0
 * @param int $member_id Member post ID
 * @param array $file Uploaded file from $_FILES
 * @return int|WP_Error Attachment ID on success, WP_Error on failure
 */
public function process_gallery_upload($member_id, $file) {
    // Implementation...
}
```

---

## Security Guidelines

### OWASP Top 10 Prevention

#### 1. SQL Injection Prevention
**Always use:** `$wpdb->prepare()`, never direct SQL

```php
// ✅ CORRECT
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_author = %d",
    $user_id
));

// ❌ WRONG
$results = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_author = {$user_id}");
```

#### 2. XSS Prevention
**Always escape output:**

```php
echo esc_html($user_input);        // Plain text
echo esc_attr($user_input);        // HTML attributes
echo esc_url($url);                // URLs
echo wp_kses_post($rich_content);  // Rich content (allowed HTML)
```

#### 3. CSRF Prevention
**Use nonces for all forms and AJAX:**

```php
// Create nonce
wp_nonce_field('action_name', 'nonce_field_name');

// Verify nonce
if (!wp_verify_nonce($_POST['nonce_field_name'], 'action_name')) {
    wp_die('Security check failed');
}
```

#### 4. Access Control
**Check capabilities:**

```php
if (!current_user_can('edit_posts')) {
    wp_die('Insufficient permissions');
}
```

#### 5. File Upload Security
**Validate file types:**

```php
$allowed_types = array('image/jpeg', 'image/png', 'image/gif');
if (!in_array($file['type'], $allowed_types)) {
    return new WP_Error('invalid_type', 'Invalid file type');
}
```

---

## Testing and Debugging

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Logging for Debugging

```php
// Log to wp-content/debug.log
error_log('Debug info: ' . print_r($variable, true));

// Or use WordPress function
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Metoda Members: Processing member ID ' . $member_id);
}
```

### Testing AJAX Endpoints

**Using Browser Console:**
```javascript
jQuery.post(ajaxurl, {
    action: 'your_action',
    nonce: yourNonce,
    data: 'test'
}, function(response) {
    console.log(response);
});
```

### PHP Syntax Check

```bash
# Check single file
php -l includes/core/class-assets.php

# Check all PHP files
find includes -name "*.php" -exec php -l {} \;
```

---

## Migration Notes (Phase 3 Complete)

### What Was Migrated

**Phase 3 (Complete) - 28 hooks migrated:**
- ✅ Post Types → `Metoda_Post_Types`
- ✅ Taxonomies → `Metoda_Taxonomies`
- ✅ Assets → `Metoda_Assets`
- ✅ Meta Boxes → `Metoda_Meta_Boxes`
- ✅ AJAX Handlers → `Metoda_Ajax_Members`

### What Remains in Legacy Layer

**19 active hooks in includes/legacy/hooks.php:**
- Activation/deactivation hooks (3)
- Theme/frontend hooks (2)
- Admin columns (6)
- Admin menus/notices (6)
- Dashboard widgets (1)
- Page creation hooks (1)

**See:** `includes/legacy/hooks.php` for TODO comments with future migration plans.

---

## Support and Resources

**Documentation:**
- This guide (DEVELOPER_GUIDE.md)
- MIGRATION_GUIDE.md - For upgrading from older versions
- CHANGELOG_REFACTORING.md - Complete refactoring history

**Code Reference:**
- `includes/legacy/functions.php` - All available legacy functions
- `includes/legacy/hooks.php` - Hook registration reference

**For Questions:**
Contact the Metoda development team or refer to the plugin documentation.

---

**Last Updated:** 2025-11-22
**Plugin Version:** 4.2.0-refactor
**Refactoring Phase:** 4 (Final Cleanup)
