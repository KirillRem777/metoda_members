/**
 * Notification System JavaScript
 * Управление настройками уведомлений
 */

(function() {
    'use strict';

    // Переключение каналов
    const emailChannel = document.getElementById('channel-email');
    if (emailChannel) {
        emailChannel.addEventListener('change', function() {
            document.getElementById('email-channel-settings').style.display =
                this.checked ? 'block' : 'none';
        });
    }

    const telegramChannel = document.getElementById('channel-telegram');
    if (telegramChannel) {
        telegramChannel.addEventListener('change', function() {
            document.getElementById('telegram-channel-settings').style.display =
                this.checked ? 'block' : 'none';
        });
    }

    // Переключение типов уведомлений
    ['messages', 'forum'].forEach(type => {
        const checkbox = document.getElementById(`notify-${type}`);
        const settings = document.getElementById(`${type}-settings`);

        if (checkbox && settings) {
            checkbox.addEventListener('change', function() {
                settings.style.display = this.checked ? 'block' : 'none';
            });
        }
    });

    // Тихие часы
    const quietHoursCheckbox = document.querySelector('input[name="quiet_hours_enabled"]');
    if (quietHoursCheckbox) {
        quietHoursCheckbox.addEventListener('change', function() {
            const settings = document.querySelector('.quiet-hours-settings');
            if (settings) {
                settings.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    // OTP настройки
    const otpEnabledCheckbox = document.getElementById('otp-enabled');
    if (otpEnabledCheckbox) {
        otpEnabledCheckbox.addEventListener('change', function() {
            const settings = document.getElementById('otp-settings');
            if (settings) {
                settings.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    // Инициализация при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        // Показать настройки если каналы включены
        if (emailChannel && emailChannel.checked) {
            document.getElementById('email-channel-settings').style.display = 'block';
        }
        if (telegramChannel && telegramChannel.checked) {
            document.getElementById('telegram-channel-settings').style.display = 'block';
        }

        // Показать настройки типов если включены
        if (document.getElementById('notify-messages')?.checked) {
            const settings = document.getElementById('messages-settings');
            if (settings) settings.style.display = 'block';
        }
        if (document.getElementById('notify-forum')?.checked) {
            const settings = document.getElementById('forum-settings');
            if (settings) settings.style.display = 'block';
        }

        // Показать OTP настройки если включены
        if (document.getElementById('otp-enabled')?.checked) {
            const settings = document.getElementById('otp-settings');
            if (settings) settings.style.display = 'block';
        }
    });

})();

// Глобальные функции (доступны из HTML)

/**
 * Копировать код Telegram
 */
function copyTelegramCode() {
    const code = document.getElementById('telegram-code')?.textContent;
    if (!code) return;

    navigator.clipboard.writeText(code).then(() => {
        alert('✅ Код скопирован! Отправьте его боту в Telegram');
    }).catch(err => {
        console.error('Ошибка копирования:', err);
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea');
        textArea.value = code;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('✅ Код скопирован! Отправьте его боту в Telegram');
    });
}

/**
 * Проверить подключение Telegram
 */
function checkTelegramConnection() {
    if (typeof memberDashboardData === 'undefined') {
        alert('❌ Ошибка: необходимые данные не загружены');
        return;
    }

    fetch(memberDashboardData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'check_telegram_connection',
            nonce: memberDashboardData.nonce
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Telegram подключен!');
            location.reload();
        } else {
            alert('⏳ Telegram ещё не подключен. Следуйте инструкции выше.');
        }
    })
    .catch(err => {
        console.error('Ошибка проверки:', err);
        alert('❌ Ошибка проверки подключения');
    });
}

/**
 * Тестовое уведомление
 */
function sendTestNotification(channel) {
    if (typeof memberDashboardData === 'undefined') {
        alert('❌ Ошибка: необходимые данные не загружены');
        return;
    }

    fetch(memberDashboardData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'send_test_notification',
            channel: channel,
            nonce: memberDashboardData.nonce
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`✅ Тестовое уведомление отправлено в ${channel}!`);
        } else {
            alert('❌ Ошибка: ' + (data.data?.message || 'Неизвестная ошибка'));
        }
    })
    .catch(err => {
        console.error('Ошибка отправки теста:', err);
        alert('❌ Ошибка отправки тестового уведомления');
    });
}

/**
 * Отключить Telegram
 */
function disconnectTelegram() {
    if (!confirm('Отключить Telegram уведомления?')) return;

    if (typeof memberDashboardData === 'undefined') {
        alert('❌ Ошибка: необходимые данные не загружены');
        return;
    }

    fetch(memberDashboardData.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'disconnect_telegram',
            nonce: memberDashboardData.nonce
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Telegram отключен');
            location.reload();
        } else {
            alert('❌ Ошибка отключения');
        }
    })
    .catch(err => {
        console.error('Ошибка отключения:', err);
        alert('❌ Ошибка отключения Telegram');
    });
}

/**
 * Сохранение настроек уведомлений
 */
(function() {
    const form = document.getElementById('notification-settings-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (typeof memberDashboardData === 'undefined') {
            alert('❌ Ошибка: необходимые данные не загружены');
            return;
        }

        const formData = new FormData(this);
        formData.append('action', 'save_notification_settings');
        formData.append('nonce', memberDashboardData.nonce);

        // Показать загрузку
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Сохранение...';

        fetch(memberDashboardData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Настройки сохранены!');
                // Можно перезагрузить или показать success message
            } else {
                alert('❌ Ошибка сохранения: ' + (data.data?.message || 'Неизвестная ошибка'));
            }
        })
        .catch(err => {
            console.error('Ошибка сохранения:', err);
            alert('❌ Ошибка сохранения настроек');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
})();
