# Инструкция: как найти и отключить несанкционированный редирект на `/member-dashboard/`

**Коротко (одним предложением):**
Ты загрузил файлы плагина — на их основе я составил конкретный, пошаговый план действий и набор команд/патчей, которые безопасно отключат и диагностируют редиректы к `/member-dashboard/` и вернут сайт в рабочее состояние.

**Основание инструкции:**
- Эта инструкция составлена на основе анализа присланных тобой файлов: `members-management-pro.php`, `class-member-onboarding.php`, `class-member-template-loader.php`, `class-member-page-templates.php`, `class-member-dashboard.php`, `class-member-access-codes.php`, `class-member-user-link.php`.
- Я не вносил изменений на сервер — все действия выполняешь ты. Если где-то я делаю предположение, я об этом прямо говорю.

---

## План действий (шаг за шагом)

> Перед началом: обязательно сделай полную резервную копию плагина и, если возможно, сайта (файлы + база данных).

### 1) Быстрая проверка — отключить редиректы централизованно

1.1. Открой `wp-config.php` и добавь (если ещё не добавлено):

```php
// временно — диагностика
define('METODA_DISABLE_REDIRECTS', true);
```

1.2. Очисти кэш браузера или открой сайт в режиме инкогнито и проверь — ушёл ли редирект.

- **Если редирект исчез** — проблема в логике плагина (продолжаем к шагу 2 для аккуратного исправления).
- **Если редирект остался** — редирект выполняется не из проанализированных файлов (или из кода, где игнорируется этот флаг). Тогда переходи к шагу 4 (логирование/поиск).

---

### 2) Добавление безопасной обёртки (`metoda_maybe_redirect`) — централизует контроль

Вариант A (рекомендуется): создать mu-plugin (работает независимо от активации плагина). Создай файл `wp-content/mu-plugins/metoda-redirect-guard.php` с этим кодом:

```php
<?php
// wp-content/mu-plugins/metoda-redirect-guard.php
if (!function_exists('metoda_maybe_redirect')) {
    function metoda_maybe_redirect( $location, $status = 302 ) {
        // глобальный выключатель
        if ( defined('METODA_DISABLE_REDIRECTS') && METODA_DISABLE_REDIRECTS ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log("METODA: redirect blocked by METODA_DISABLE_REDIRECTS to: {$location}");
            }
            return;
        }

        // не редиректим в админке, в AJAX или REST-запросах
        if ( wp_doing_ajax() || ( defined('REST_REQUEST') && REST_REQUEST ) || is_admin() ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log("METODA: redirect skipped (admin/ajax/rest) to: {$location}");
            }
            return;
        }

        if ( headers_sent() ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log("METODA: headers already sent, cannot redirect to: {$location}");
            }
            return;
        }

        wp_safe_redirect( $location, (int) $status );
        exit;
    }
}
```

> Почему mu-plugin: он подключается раньше всех обычных плагинов и позволяет перехватить/запретить редиректы централизованно без редактирования основного плагина сразу.

---

### 3) Замена явных вызовов редиректа в файлах плагина

Нужно заменить прямые `wp_redirect(...)`, `wp_safe_redirect(...)` и `header('Location: ...')` в файлах плагина на `metoda_maybe_redirect(...)`.

**Файлы, которые я проверил и где чаще всего встречаются вызовы:**
- `members-management-pro.php`
- `class-member-onboarding.php`
- `class-member-template-loader.php`
- `class-member-page-templates.php`

**Ручная замена (пример):**

```php
// было
wp_redirect( home_url('/member-dashboard/') );
exit;

// стало
metoda_maybe_redirect( home_url('/member-dashboard/') );
```

**Массовая примерная команда (делай бэкапы перед исполнением):**

```bash
# пример для одного конкретного паттерна (GNU perl), делай из корня WP
cp wp-content/plugins/metoda-members/members-management-pro.php /tmp/members-management-pro.php.bak
perl -0777 -pe "s/wp_redirect\(\s*home_url\('\/member-dashboard\/\'\)\);\s*exit\s*;/metoda_maybe_redirect(home_url('/member-dashboard/'));/gs" -i wp-content/plugins/metoda-members/members-management-pro.php
```

> Примечание: шаблон может отличаться (разные пробелы, `wp_safe_redirect`, переменные). Для каждого уникального вхождения лучше править вручную или составить точные регулярные выражения.

---

### 4) Если редирект не остановился: включаем логирование и ищем источник

4.1. В `wp-config.php` временно включи:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

4.2. Временная обёртка-логгер (если не сделал mu-plugin) — вставь в начале основного файла плагина (или как mu-plugin) эту функцию и замени прямо в файлах `wp_redirect(...)` на `metoda_debug_wp_redirect(...)` (временно):

```php
if (!function_exists('metoda_debug_wp_redirect')) {
    function metoda_debug_wp_redirect($location, $status = 302) {
        error_log("METODA DEBUG: wp_redirect to: {$location}");
        error_log(print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20), true));
        return; // блокируем редирект для диагностики
    }
}
```

4.3. Зайди на сайт, воспроизведи ситуацию. Проверь `/wp-content/debug.log` — там появится стек вызовов с точным файлом и функцией, где сработал редирект.

---

### 5) Особые случаи — `header('Location: ...')` и редиректы в админке

- Если встречаешь `header('Location: ...')` — замени на `metoda_maybe_redirect(...)`, но учти, что `header()` иногда используется для отправки CSV/файлов; в таких случаях нужно аккуратно проверять контекст (заголовки для скачивания отличны от редиректа).
- Админские редиректы (`is_admin()`) обычно нужны — обёртка их пропускает. Если админ-редиректы критичны, можно добавить в обёртку опцию, позволяющую их выполнять.

---

### 6) Тестирование

6.1. После изменений убери `METODA_DISABLE_REDIRECTS` из `wp-config.php` и протестируй сайт в трёх режимах:
- гость (выйди из аккаунта),
- залогиненный пользователь обычной роли,
- залогиненный админ.

6.2. Проверь страницы, REST-запросы и AJAX. Проверь консоль браузера на 302/301 ответы.

6.3. Отключи `WP_DEBUG` / `WP_DEBUG_LOG`, когда закончишь диагностику.

---

## Команды и примеры (сводно)

**Бэкап файла перед правкой:**
```bash
cp wp-content/plugins/metoda-members/members-management-pro.php /tmp/members-management-pro.php.bak
```

**Создать mu-plugin (пример):**
```bash
mkdir -p wp-content/mu-plugins
cat > wp-content/mu-plugins/metoda-redirect-guard.php <<'PHP'
<?php
// (вставь сюда код метода metoda_maybe_redirect из пункта 2)
PHP
```

**Включить debug лог:**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

**Найти все вхождения wp_redirect в папке плагина:**
```bash
grep -Rn "wp_redirect\|wp_safe_redirect\|header('Location'" wp-content/plugins/metoda-members
```

**Пример замены (ручной/полуавтоматический):**
```bash
perl -0777 -pe "s/wp_redirect\(\s*home_url\('\/member-dashboard\/\'\)\);\s*exit\s*;/metoda_maybe_redirect(home_url('/member-dashboard/'));/gs" -i wp-content/plugins/metoda-members/*.php
```

---

## Что именно я сделал/чего не делал
- **Сделано мной:** проанализированы твои загруженные файлы; составлена инструкция и шаблоны кода; определены подозрительные файлы/функции.
- **Не сделано мной:** я не вносил правок на сервер; не применял патчи; не предполагал содержимое файлов, которого у меня нет.
- **Предположения:** плагин действительно содержит неунифицированные вызовы редиректов (это подтверждается просмотром файлов). Я предполагаю, что один из них срабатывает глобально — наиболее вероятны места, связанные с логином/онбордингом.

---

## Если хочешь, я могу:
- подготовить **патч (diff)**, который вставит `metoda_maybe_redirect()` в начало `members-management-pro.php` и заменит ключевые вхождения в трёх файлах — ты применишь патч на сервере; или
- подготовить **готовые файлы** с внесёнными изменениями (ты скачиваешь и заливаешь); или
- сгенерировать точные фрагменты кода для ручной правки (если ты предпочитаешь править в редакторе хостинга).

---

Если пойдёшь сейчас по инструкции и загрузишь лог `/wp-content/debug.log` или скажешь, какие редиректы нашёл `grep` (вывод), — я прямо тут укажу точную строку/функцию и сгенерирую патч.


*Инструкция подготовлена на основе присланных тобой файлов и текущего содержимого плагина. Если хочешь — подгоню патч под точные строки в файлах (сгенерирую diff).*

