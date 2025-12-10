<?php
/**
 * Конфигурация авторизации
 * Секреты и настройки безопасности
 */

// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

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

// Настройки базы данных (используется DatabaseConfig с fallback)
require_once __DIR__ . '/../../classes/DatabaseConfig.php';
$primaryConfig = DatabaseConfig::getPrimaryConfig();
$fallbackConfig = DatabaseConfig::getFallbackConfig();
define('DB_CONNECTION_STRING', $primaryConfig['url']);
define('DB_NAME', $primaryConfig['name']);
define('DB_FALLBACK_URL', $fallbackConfig['url']);
define('DB_FALLBACK_NAME', $fallbackConfig['name']);
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
