<?php
/**
 * Dashboard Notifications Section
 * Секция настроек уведомлений в личном кабинете
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Получаем текущие настройки
$notify_channel_email = get_user_meta($user_id, 'notify_channel_email', true);
$notify_channel_telegram = get_user_meta($user_id, 'notify_channel_telegram', true);
$notify_messages = get_user_meta($user_id, 'notify_messages', true) ?: '1';
$notify_forum = get_user_meta($user_id, 'notify_forum', true);
$custom_email = get_user_meta($user_id, 'notify_custom_email', true);
$telegram_chat_id = get_user_meta($user_id, 'telegram_chat_id', true);
$telegram_bot_username = get_option('metoda_telegram_bot_username', 'MetodaBot');
$otp_enabled = get_user_meta($user_id, 'otp_enabled', true);
$otp_delivery = get_user_meta($user_id, 'otp_delivery', true) ?: 'email';
?>

<!-- Notifications Section -->
<section id="notifications-section" class="section-content hidden">
    <div class="member-cabinet-header px-8 py-6">
        <div class="max-w-5xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-900">Настройки уведомлений</h2>
            <p class="text-sm text-gray-500 mt-1">Настройте как вы хотите получать уведомления о важных событиях на платформе</p>
        </div>
    </div>

    <div class="p-8">
        <div class="max-w-5xl mx-auto">
            <form id="notification-settings-form" class="space-y-8">

                <!-- БЛОК: Каналы доставки -->
                <div class="settings-section">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Каналы доставки</h3>
                    <p class="text-sm text-gray-500 mb-6">Выберите как вы хотите получать уведомления</p>

                    <!-- Email -->
                    <div class="notification-channel mb-4">
                        <div class="channel-header">
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="channel_email"
                                       id="channel-email"
                                       value="1"
                                       <?php checked($notify_channel_email, '1'); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="channel-info">
                                <h4 class="text-base font-semibold text-gray-900">📧 Email уведомления</h4>
                                <p class="text-sm text-gray-600">Получайте полные сообщения на email с возможностью ответа</p>
                            </div>
                        </div>

                        <div class="channel-settings" id="email-channel-settings" style="display: none;">
                            <div class="setting-group">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Отправлять на:</label>
                                <div class="radio-group">
                                    <label class="flex items-center gap-2">
                                        <input type="radio"
                                               name="email_destination"
                                               value="account"
                                               <?php checked(empty($custom_email)); ?>>
                                        <span>Email аккаунта: <strong><?php echo esc_html($current_user->user_email); ?></strong></span>
                                    </label>
                                    <label class="flex items-start gap-2">
                                        <input type="radio"
                                               name="email_destination"
                                               value="custom"
                                               <?php checked(!empty($custom_email)); ?>>
                                        <span class="flex flex-col gap-2">
                                            <span>Другой email:</span>
                                            <input type="email"
                                                   name="custom_email"
                                                   value="<?php echo esc_attr($custom_email); ?>"
                                                   placeholder="your@email.com"
                                                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Telegram -->
                    <div class="notification-channel">
                        <div class="channel-header">
                            <label class="toggle-switch">
                                <input type="checkbox"
                                       name="channel_telegram"
                                       id="channel-telegram"
                                       value="1"
                                       <?php checked($notify_channel_telegram, '1'); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="channel-info">
                                <h4 class="text-base font-semibold text-gray-900">📱 Telegram</h4>
                                <p class="text-sm text-gray-600">Получайте сообщения в Telegram с возможностью ответить через бота</p>
                            </div>
                        </div>

                        <div class="channel-settings" id="telegram-channel-settings" style="display: none;">
                            <?php if (empty($telegram_chat_id)): ?>
                                <!-- Подключение Telegram -->
                                <div class="telegram-connect">
                                    <div class="steps">
                                        <h5 class="text-base font-semibold text-blue-900 mb-3">Как подключить:</h5>
                                        <ol class="space-y-2 text-sm">
                                            <li>Откройте Telegram и найдите бота <a href="https://t.me/<?php echo esc_attr($telegram_bot_username); ?>" target="_blank" class="text-blue-600 font-semibold hover:underline">@<?php echo esc_html($telegram_bot_username); ?></a></li>
                                            <li>Нажмите <strong>START</strong></li>
                                            <li>Отправьте боту этот код:
                                                <div class="code-box my-2">
                                                    <code id="telegram-code"><?php echo esc_html($user_id . '-' . wp_create_nonce('telegram_verify_' . $user_id)); ?></code>
                                                    <button type="button" class="copy-btn" onclick="copyTelegramCode()">📋 Копировать</button>
                                                </div>
                                            </li>
                                            <li>Бот подтвердит подключение ✅</li>
                                        </ol>
                                    </div>
                                    <button type="button" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors" onclick="checkTelegramConnection()">
                                        Проверить подключение
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Telegram подключен -->
                                <div class="telegram-connected">
                                    <p class="success-message">
                                        <span class="icon">✅</span>
                                        <span>Telegram подключен! Вы будете получать уведомления.</span>
                                    </p>
                                    <div class="telegram-actions">
                                        <button type="button" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors" onclick="sendTestNotification('telegram')">
                                            Отправить тест
                                        </button>
                                        <button type="button" class="button-link danger" onclick="disconnectTelegram()">
                                            Отключить
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200">

                <!-- БЛОК: Типы уведомлений -->
                <div class="settings-section">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Типы уведомлений</h3>
                    <p class="text-sm text-gray-500 mb-6">Выберите о каких событиях вы хотите получать уведомления</p>

                    <!-- Входящие сообщения -->
                    <div class="notification-type mb-3">
                        <div class="type-header">
                            <input type="checkbox"
                                   name="notify_messages"
                                   id="notify-messages"
                                   value="1"
                                   <?php checked($notify_messages, '1'); ?>>
                            <label for="notify-messages" class="cursor-pointer">
                                <strong class="block text-base text-gray-900">📬 Входящие сообщения</strong>
                                <span class="description text-sm text-gray-600">Когда вам присылают личное сообщение</span>
                            </label>
                        </div>
                        <div class="type-settings" id="messages-settings" style="display: none;">
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="notify_messages_instant" value="1" checked>
                                <span>Мгновенно (сразу при получении)</span>
                            </label>
                            <p class="note text-sm">💡 Вы получите полное сообщение и сможете ответить прямо из email/Telegram</p>
                        </div>
                    </div>

                    <!-- Форум -->
                    <div class="notification-type mb-3">
                        <div class="type-header">
                            <input type="checkbox"
                                   name="notify_forum"
                                   id="notify-forum"
                                   value="1"
                                   <?php checked($notify_forum, '1'); ?>>
                            <label for="notify-forum" class="cursor-pointer">
                                <strong class="block text-base text-gray-900">💬 Форум</strong>
                                <span class="description text-sm text-gray-600">Активность в темах форума</span>
                            </label>
                        </div>
                        <div class="type-settings" id="forum-settings" style="display: none;">
                            <label class="flex items-center gap-2 text-sm cursor-pointer mb-2">
                                <input type="checkbox" name="notify_forum_replies" value="1" checked>
                                <span>Ответы на мои темы</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm cursor-pointer mb-2">
                                <input type="checkbox" name="notify_forum_mentions" value="1" checked>
                                <span>Когда меня упоминают (@username)</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="notify_forum_watching" value="1">
                                <span>Новые сообщения в отслеживаемых темах</span>
                            </label>
                        </div>
                    </div>

                    <!-- Проекты (пока неактивно) -->
                    <div class="notification-type disabled mb-3">
                        <div class="type-header">
                            <input type="checkbox"
                                   name="notify_projects"
                                   id="notify-projects"
                                   value="1"
                                   disabled>
                            <label for="notify-projects" class="cursor-not-allowed">
                                <strong class="block text-base text-gray-900">📁 Проекты</strong>
                                <span class="description text-sm text-gray-600">Скоро появится</span>
                            </label>
                        </div>
                    </div>

                    <!-- Обучение (пока неактивно) -->
                    <div class="notification-type disabled">
                        <div class="type-header">
                            <input type="checkbox"
                                   name="notify_learning"
                                   id="notify-learning"
                                   value="1"
                                   disabled>
                            <label for="notify-learning" class="cursor-not-allowed">
                                <strong class="block text-base text-gray-900">📚 Обучающая платформа</strong>
                                <span class="description text-sm text-gray-600">Скоро появится</span>
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-200">

                <!-- БЛОК: Режим "Не беспокоить" -->
                <div class="settings-section">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">⏰ Режим "Не беспокоить"</h3>
                    <label class="flex items-center gap-2 text-sm cursor-pointer mb-4">
                        <input type="checkbox" name="quiet_hours_enabled" value="1">
                        <span>Не отправлять уведомления в определенное время</span>
                    </label>
                    <div class="quiet-hours-settings" style="display: none;">
                        <div class="time-range">
                            <label class="text-sm">
                                С <input type="time" name="quiet_hours_start" value="22:00" class="px-3 py-2 border border-gray-300 rounded-lg mx-2">
                                до <input type="time" name="quiet_hours_end" value="08:00" class="px-3 py-2 border border-gray-300 rounded-lg mx-2">
                            </label>
                        </div>
                        <p class="note text-sm mt-3">Уведомления будут накапливаться и придут утром одной сводкой</p>
                    </div>
                </div>

                <hr class="border-gray-200">

                <!-- БЛОК: OTP Настройки -->
                <div class="settings-section">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">🔐 Вход по OTP (одноразовый пароль)</h3>
                    <p class="text-sm text-gray-500 mb-6">Настройте вход в личный кабинет через одноразовые коды вместо постоянного пароля</p>

                    <label class="flex items-center gap-2 text-sm cursor-pointer mb-4">
                        <input type="checkbox"
                               name="otp_enabled"
                               id="otp-enabled"
                               value="1"
                               <?php checked($otp_enabled, '1'); ?>>
                        <span>Включить вход по одноразовому паролю (OTP)</span>
                    </label>

                    <div class="otp-settings" id="otp-settings" style="display: none;">
                        <div class="setting-group mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Получать OTP код на:</label>
                            <div class="radio-group">
                                <label class="flex items-center gap-2 mb-2">
                                    <input type="radio"
                                           name="otp_delivery"
                                           value="email"
                                           <?php checked($otp_delivery, 'email'); ?>>
                                    <span>📧 Email</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio"
                                           name="otp_delivery"
                                           value="telegram"
                                           <?php checked($otp_delivery, 'telegram'); ?>>
                                    <span>📱 Telegram</span>
                                </label>
                            </div>
                        </div>
                        <p class="note text-sm">💡 При входе вам будет отправлен одноразовый код на выбранный канал. Код действителен 5 минут.</p>
                        <p class="note text-sm mt-2">⚠️ Для получения OTP в Telegram необходимо подключить бота в разделе "Каналы доставки" выше</p>
                    </div>
                </div>

                <!-- Кнопка сохранения -->
                <div class="form-actions">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm">
                        Сохранить настройки
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/js/notification-system.js'; ?>"></script>
