<?php
session_start();

// Логируем выход
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $logsCollection = $db->admin_logs;
        
        $logEntry = [
            'action' => 'logout',
            'description' => 'Выход из админки',
            'data' => [
                'username' => $_SESSION['admin_username'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'session_duration' => time() - ($_SESSION['admin_login_time'] ?? time())
            ],
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'session_id' => session_id()
        ];
        
        $logsCollection->insertOne($logEntry);
    } catch (Exception $e) {
        error_log("Ошибка логирования выхода: " . $e->getMessage());
    }
}

// Очищаем сессию
session_destroy();

// Перенаправляем на страницу входа
header('Location: /admin/auth/telegram.php');
exit;
?>
