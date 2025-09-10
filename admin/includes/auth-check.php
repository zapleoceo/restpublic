<?php
require_once __DIR__ . '/../config/auth.php';

// Проверка авторизации для админских страниц
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

// Проверка таймаута сессии
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > SESSION_TIMEOUT) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

// Функция логирования действий админа
function logAdminAction($action, $description, $data = []) {
    if (!LOG_ADMIN_ACTIONS) {
        return;
    }
    
    try {
        $logEntry = [
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id(),
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Пытаемся использовать MongoDB
        if (class_exists('MongoDB\Client')) {
            try {
                $client = new MongoDB\Client(DB_CONNECTION_STRING);
                $db = $client->selectDatabase(DB_NAME);
                $logsCollection = $db->selectCollection(LOGS_COLLECTION);
                $logEntry['timestamp'] = new MongoDB\BSON\UTCDateTime();
                $logsCollection->insertOne($logEntry);
                return;
            } catch (Exception $e) {
                // Fallback to file system
            }
        }
        
        // Fallback to file system
        if (FALLBACK_TO_FILES) {
            $logs = [];
            if (file_exists(LOGS_FILE)) {
                $logs = json_decode(file_get_contents(LOGS_FILE), true) ?: [];
            }
            $logs[] = $logEntry;
            
            // Ограничиваем количество логов
            if (count($logs) > 1000) {
                $logs = array_slice($logs, -1000);
            }
            
            file_put_contents(LOGS_FILE, json_encode($logs, JSON_PRETTY_PRINT));
        }
    } catch (Exception $e) {
        error_log("Ошибка логирования: " . $e->getMessage());
    }
}
?>
