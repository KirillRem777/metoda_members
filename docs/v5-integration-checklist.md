# Metoda Community MGMT v5.0 - Integration Test Checklist

## Pre-Test Setup
- [ ] Backup database before testing
- [ ] Clear all caches (browser, WordPress, any caching plugins)
- [ ] Check PHP error log is accessible
- [ ] Verify METODA_DISABLE_REDIRECTS is NOT set (normal mode)

---

## 1. AJAX Handlers (Metoda_Ajax_* classes)

### 1.1 Members Directory (Metoda_Ajax_Members)
- [ ] Filter members by city works
- [ ] Filter members by role works
- [ ] Search by name works
- [ ] Filter by member type (expert/member) works
- [ ] Combined filters work together
- [ ] "Load More" pagination works
- [ ] Experts appear before regular members
- [ ] Total count displays correctly

### 1.2 Gallery (Metoda_Ajax_Gallery)
- [ ] Upload photo to gallery works
- [ ] Save gallery order works
- [ ] Delete photo from gallery works
- [ ] File validation rejects non-images
- [ ] File size limit (5MB) enforced
- [ ] Admin can edit member gallery

### 1.3 Materials (Metoda_Ajax_Materials)
- [ ] Add material link works
- [ ] Add material file works
- [ ] Delete material works
- [ ] Edit material works
- [ ] All 6 categories work (testimonials, gratitudes, interviews, videos, reviews, developments)
- [ ] Old string-based materials still display
- [ ] New JSON-based materials work

### 1.4 Messages (Metoda_Ajax_Messages)
- [ ] Send message (logged in) works
- [ ] Send message (guest) works
- [ ] View message works
- [ ] Honeypot spam protection works
- [ ] Rate limiting works (10/day logged in, 5/day guest)
- [ ] Cooldown works (2 min logged in, 5 min guest)
- [ ] Email notification sent

### 1.5 Manager (Metoda_Ajax_Manager)
- [ ] Change member status to publish
- [ ] Change member status to pending
- [ ] Change member status to draft
- [ ] Non-managers cannot access

---

## 2. Shortcodes (Metoda_Shortcodes)

### 2.1 Members Directory [members_directory]
- [ ] Renders member grid
- [ ] Filters panel displays
- [ ] Pagination works
- [ ] Custom attributes work (columns, show_filters, show_search)

### 2.2 Member Registration [member_registration]
- [ ] Form displays correctly
- [ ] Form submission works
- [ ] Validation errors display
- [ ] Success message displays

### 2.3 Manager Panel [manager_panel]
- [ ] Displays for managers
- [ ] Displays for administrators
- [ ] Access denied for regular members
- [ ] Member list loads

### 2.4 Custom Login [custom_login]
- [ ] Login form displays for guests
- [ ] Logged-in admins see admin link
- [ ] Logged-in managers redirect to manager panel
- [ ] Logged-in members redirect to dashboard

---

## 3. Redirects (Metoda_Redirects)

### 3.1 Forum Access
- [ ] Logged out users redirected from forum
- [ ] Logged in users can access forum

### 3.2 Dashboard Access
- [ ] Non-members redirected from dashboard
- [ ] Members can access dashboard
- [ ] Admins can access dashboard

### 3.3 Manager Panel Access
- [ ] Non-managers redirected
- [ ] Managers can access
- [ ] Admins can access

### 3.4 Kill Switch
- [ ] METODA_DISABLE_REDIRECTS=true disables all redirects
- [ ] Warning message shown on login page

---

## 4. Auth Classes (Metoda_Auth_* classes)

### 4.1 Login (Metoda_Login)
- [ ] Password login works
- [ ] Admin redirects to admin panel
- [ ] Manager redirects to manager panel
- [ ] Member redirects to dashboard (or onboarding)
- [ ] Admin bar hidden for members
- [ ] Custom login page styles work

### 4.2 OTP (Metoda_Otp)
- [ ] OTP generation works
- [ ] OTP verification works
- [ ] OTP expiry (10 min) works
- [ ] Static helpers accessible

### 4.3 Onboarding (Metoda_Onboarding)
- [ ] New members redirected to onboarding
- [ ] Onboarding step tracking works
- [ ] Onboarding completion works
- [ ] Admins bypass onboarding

---

## 5. Admin Classes (existing from Phase 2)

### 5.1 Meta Boxes
- [ ] Member details meta box saves
- [ ] Member gallery meta box saves
- [ ] Expert-specific fields work
- [ ] Admin bypass for member editing works

### 5.2 Dashboard Widget
- [ ] Stats display on admin dashboard
- [ ] Links work correctly

### 5.3 Admin Menus
- [ ] All menu items appear
- [ ] Settings page works
- [ ] Activity log works

---

## 6. General Checks

### 6.1 No Duplicate Hooks
- [ ] No PHP warnings about duplicate hooks
- [ ] No "already registered" errors
- [ ] Each AJAX action fires only once

### 6.2 Performance
- [ ] Page load times acceptable
- [ ] No memory exhaustion errors
- [ ] No infinite loops

### 6.3 Security
- [ ] All AJAX actions verify nonces
- [ ] File uploads validated properly
- [ ] SQL injection prevention (parameterized queries)
- [ ] XSS prevention (proper escaping)

### 6.4 Error Handling
- [ ] Graceful error messages
- [ ] No PHP fatal errors
- [ ] No white screens

---

## Post-Test

- [ ] Check PHP error log for warnings/errors
- [ ] Document any issues found
- [ ] Verify all critical paths work

---

## Notes

Test Date: _______________
Tested By: _______________
WordPress Version: _______________
PHP Version: _______________

Issues Found:
1.
2.
3.

Sign-off: _______________
