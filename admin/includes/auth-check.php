<?php
// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

require_once __DIR__ . '/../config/auth.php';

// Запускаем сессию если она еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ВРЕМЕННО ОТКЛЮЧЕНА АВТОРИЗАЦИЯ ДЛЯ ТЕСТИРОВАНИЯ
// Проверка авторизации для админских страниц
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: /admin/auth/login.php');
//     exit;
// }

// ВРЕМЕННО ОТКЛЮЧЕН ТАЙМАУТ СЕССИИ ДЛЯ ТЕСТИРОВАНИЯ
// Проверка таймаута сессии
// if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > SESSION_TIMEOUT) {
//     session_destroy();
//     header('Location: /admin/auth/login.php');
//     exit;
// }

// Устанавливаем временные сессионные переменные для тестирования
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'TestUser';
    $_SESSION['admin_user_id'] = 'test_user';
    $_SESSION['admin_login_time'] = time();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Функция логирования действий админа
function logAdminAction($action, $description, $data = []) {
    if (!LOG_ADMIN_ACTIONS) {
        return;
    }
    
    try {
        require_once __DIR__ . '/../../classes/Logger.php';
        $logger = new Logger();
        
        $logger->log($action, $description, $data, [
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id()
        ]);
    } catch (Exception $e) {
        error_log("Ошибка логирования: " . $e->getMessage());
    }
}
?>
