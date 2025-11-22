# Metoda Community MGMT v5.1.1

## Что нового в 5.1.1

✅ **Telegram интеграция в онбординг**
- Добавлен опциональный Шаг 4 "Мгновенные уведомления"
- Пользователь может подключить Telegram после создания аккаунта
- Polling механизм для автоматической проверки подключения (каждые 2 сек, макс 2 мин)
- OTP коды приходят за 1 секунду вместо 30 через email

## Требования

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+

## Установка

1. **Загрузить архив**
   ```bash
   # Скачайте metoda-community-mgmt-v5.1.1.zip
   ```

2. **Установить через WordPress**
   - Админка → Плагины → Добавить новый → Загрузить плагин
   - Выбрать `metoda-community-mgmt-v5.1.1.zip`
   - Нажать "Установить" → "Активировать"

3. **Настроить Telegram Bot** (опционально)
   - Создать бота через @BotFather
   - Получить токен бота
   - Админка → Настройки → Metoda Community
   - Вкладка "Telegram Integration"
   - Вставить токен и установить webhook

## Поток онбординга с Telegram

```
Шаг 1: Access Code (проверка кода доступа)
   ↓
Шаг 2: Фото профиля
   ↓
Шаг 3: Основная информация (ФИО, email, password)
   ↓
→ Создание WordPress аккаунта ←
   ↓
Шаг 4: Telegram (опционально)
   ├─ Пропустить → Шаг 5
   └─ Подключить → Открывается deep link → Polling статуса → Шаг 5
   ↓
Шаг 5: Завершение (redirect на /member-dashboard/)
```

## Техническиедетали

**Новые файлы/изменения:**
- `templates/member-onboarding.php` - добавлен Step 4 (Telegram)
- `includes/class-member-onboarding.php` - генерация Telegram link после создания юзера
- `members-management-pro.php` - версия 5.1.1

**API эндпоинты:**
- `POST /wp-admin/admin-ajax.php?action=metoda_complete_access_code_onboarding`
  - Параметр: `enable_telegram=true/false`
  - Возвращает: `telegram_link` (если включен)

- `POST /wp-admin/admin-ajax.php?action=metoda_check_telegram_status`
  - Polling каждые 2 секунды
  - Возвращает: `{ success: true, linked: true/false, username: "..." }`

**User Meta:**
- `telegram_chat_id` - ID чата с ботом
- `telegram_username` - @username пользователя
- `telegram_linked_at` - timestamp подключения

## Changelog

**5.1.1** (2025-11-22)
- Добавлена Telegram интеграция в онбординг (опциональный шаг 4)
- Генерация deep link с токеном после создания аккаунта
- JavaScript polling для проверки статуса подключения
- UI для трёх состояний: не подключен / подключение / подключен

**5.1.0** (2025-11-21)
- Telegram интеграция для моментальной доставки OTP (1 сек vs 30 сек email)
- Классы `Metoda\Auth\Telegram` и `Metoda\Auth\OTP`
- Webhook handler для Telegram Bot API

**5.0.0** (2025-11-20)
- Современная WordPress архитектура с namespaces и PSR-4
- Полный рефакторинг кодовой базы

## Troubleshooting

**Telegram не подключается:**
1. Проверить токен бота в настройках
2. Убедиться что webhook установлен: `https://api.telegram.org/bot<TOKEN>/getWebhookInfo`
3. Проверить логи: Админка → Tools → Error Logs

**OTP не приходит:**
1. Если Telegram подключен - проверить что бот не заблокирован пользователем
2. Fallback на email автоматический
3. Проверить SMTP настройки для email

**Polling не останавливается:**
- Максимум 60 попыток (2 минуты)
- После успеха или timeout автоматически останавливается

## Поддержка

Вопросы и баги: https://github.com/KirillRem777/metoda_members/issues
