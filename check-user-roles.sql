-- ДИАГНОСТИКА: Проверка ролей и мета-полей пользователя
-- Выполни в phpMyAdmin для своего пользователя

-- 1. Проверь свои роли (замени YOUR_USERNAME на свой логин)
SELECT u.ID, u.user_login, u.user_email, m.meta_value as roles
FROM wp_users u
JOIN wp_usermeta m ON u.ID = m.user_id
WHERE u.user_login = 'YOUR_USERNAME'
AND m.meta_key = 'wp_capabilities';

-- 2. Проверь все мета-поля онбординга для своего пользователя (замени USER_ID на свой ID)
SELECT meta_key, meta_value
FROM wp_usermeta
WHERE user_id = USER_ID
AND (meta_key LIKE '%onboarding%' OR meta_key LIKE '%member%');

-- 3. УДАЛИТЬ ВСЕ флаги онбординга (замени USER_ID на свой ID)
DELETE FROM wp_usermeta
WHERE user_id = USER_ID
AND meta_key IN ('_member_needs_onboarding', '_member_first_login', 'metoda_onboarding_seen');

-- 4. Если у тебя случайно есть роль 'member' или 'expert' - удали её (замени USER_ID)
UPDATE wp_usermeta
SET meta_value = 'a:1:{s:13:"administrator";b:1;}'
WHERE user_id = USER_ID
AND meta_key = 'wp_capabilities';
