<?php
// Файл для подключения к базе данных MongoDB
// Используется в админ-панели

require_once __DIR__ . '/../../vendor/autoload.php';

function get_db_connection() {
    static $client = null;
    static $db = null;
    
    if ($client === null) {
        try {
            $client = new MongoDB\Client("mongodb://localhost:27017");
            $db = $client->northrepublic; // Используем название базы данных из проекта
        } catch (Exception $e) {
            error_log("MongoDB connection error: " . $e->getMessage());
            die("Ошибка подключения к базе данных.");
        }
    }
    
    return $db;
}

// Функция для логирования действий админа
function logAdminAction($actionType, $description, $details = []) {
    try {
        $db = get_db_connection();
        $logsCollection = $db->admin_logs;
        
        $logEntry = [
            'action_type' => $actionType,
            'description' => $description,
            'details' => $details,
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'telegram_id' => $_SESSION['admin_telegram_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ];
        
        $logsCollection->insertOne($logEntry);
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
    }
}

// Функция для получения настроек системы
function getSystemSettings() {
    try {
        $db = get_db_connection();
        $settingsCollection = $db->admin_settings;
        
        $settings = $settingsCollection->findOne(['_id' => 'main_settings']);
        
        if (!$settings) {
            // Возвращаем настройки по умолчанию
            return [
                'site_name' => 'North Republic',
                'site_description' => 'Ресторан в Нячанге',
                'default_language' => 'ru',
                'session_timeout' => 6,
                'max_upload_size' => 10,
                'webp_quality' => 85,
                'enable_logging' => true,
                'log_retention_days' => 30,
                'backup_enabled' => false,
                'backup_frequency' => 'daily'
            ];
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log("Error getting system settings: " . $e->getMessage());
        return [];
    }
}

// Функция для проверки прав доступа
function checkAdminPermissions($requiredPermission = null) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    // Пока что все админы имеют все права
    // В будущем можно добавить систему ролей
    return true;
}

// Функция для очистки старых логов
function cleanupOldLogs($days = 30) {
    try {
        $db = get_db_connection();
        $logsCollection = $db->admin_logs;
        
        $cutoffDate = new MongoDB\BSON\UTCDateTime(strtotime("-{$days} days") * 1000);
        
        $result = $logsCollection->deleteMany([
            'timestamp' => ['$lt' => $cutoffDate]
        ]);
        
        return $result->getDeletedCount();
    } catch (Exception $e) {
        error_log("Error cleaning up old logs: " . $e->getMessage());
        return 0;
    }
}

// Функция для получения статистики системы
function getSystemStats() {
    try {
        $db = get_db_connection();
        
        $stats = [
            'texts' => $db->admin_texts->countDocuments(),
            'images' => $db->admin_images->countDocuments(),
            'users' => $db->admin_users->countDocuments(),
            'logs' => $db->admin_logs->countDocuments(),
        ];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting system stats: " . $e->getMessage());
        return [];
    }
}
?>
