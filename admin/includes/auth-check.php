<?php
// Проверка авторизации для админских страниц
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: auth/telegram.php');
    exit;
}

// Проверка таймаута сессии (6 часов)
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > 21600) {
    session_destroy();
    header('Location: auth/telegram.php');
    exit;
}

// Функция логирования действий админа
function logAdminAction($action, $description, $data = []) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $logsCollection = $db->admin_logs;
        
        $logEntry = [
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'session_id' => session_id(),
            'username' => $_SESSION['admin_username'] ?? 'unknown'
        ];
        
        $logsCollection->insertOne($logEntry);
    } catch (Exception $e) {
        error_log("Ошибка логирования: " . $e->getMessage());
    }
}
?>
