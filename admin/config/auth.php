<?php
/**
 * Конфигурация авторизации
 * Секреты и настройки безопасности
 */

// Настройки сессии
define('SESSION_TIMEOUT', 6 * 60 * 60); // 6 часов в секундах
define('MAX_LOGIN_ATTEMPTS', 5); // Максимум попыток входа
define('LOCKOUT_TIME', 15 * 60); // 15 минут блокировки

// Настройки безопасности
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_UPPERCASE', true);

// Настройки логирования
define('LOG_FAILED_ATTEMPTS', true);
define('LOG_SUCCESSFUL_LOGINS', true);
define('LOG_ADMIN_ACTIONS', true);

// Настройки CSRF
define('CSRF_TOKEN_LIFETIME', 3600); // 1 час

// Настройки базы данных
define('DB_CONNECTION_STRING', 'mongodb://localhost:27017');
define('DB_NAME', 'northrepublic');
define('USERS_COLLECTION', 'admin_users');
define('LOGS_COLLECTION', 'admin_logs');
define('SESSIONS_COLLECTION', 'admin_sessions');

// Настройки файлов (fallback если MongoDB недоступна)
define('FALLBACK_TO_FILES', true);
define('DATA_DIR', __DIR__ . '/../../data');
define('USERS_FILE', DATA_DIR . '/admin_users.json');
define('LOGS_FILE', DATA_DIR . '/admin_logs.json');
define('SESSIONS_FILE', DATA_DIR . '/admin_sessions.json');

// Создаем директорию для данных если не существует
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
?>
