<?php
// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

require_once __DIR__ . '/../config/auth.php';

// Проверка авторизации для админских страниц
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /admin/auth/login.php');
    exit;
}

// Проверка таймаута сессии
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: /admin/auth/login.php');
    exit;
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
