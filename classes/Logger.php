<?php
/**
 * Класс для логирования в MongoDB
 */

class Logger {
    private $db;
    private $logsCollection;
    private $useMongoDB = false;
    
    public function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            if (class_exists('MongoDB\Client')) {
                $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
                $client = new MongoDB\Client($mongodbUrl);
                $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
                $this->db = $client->selectDatabase($dbName);
                $this->logsCollection = $this->db->selectCollection('admin_logs');
                $this->useMongoDB = true;
            } else {
                $this->useMongoDB = false;
                error_log('MongoDB not available, using file system for logging');
            }
        } catch (Exception $e) {
            $this->useMongoDB = false;
            error_log('Database initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Логирование события
     */
    public function log($action, $message, $data = [], $metadata = []) {
        $logEntry = [
            'action' => $action,
            'message' => $message,
            'data' => $data,
            'metadata' => $metadata,
            'timestamp' => $this->getCurrentTimestamp(),
            'level' => $this->getLogLevel($action)
        ];
        
        try {
            if ($this->useMongoDB) {
                $this->logsCollection->insertOne($logEntry);
            } else {
                // Fallback to file system
                $this->logToFile($logEntry);
            }
        } catch (Exception $e) {
            error_log("Logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Получение всех логов
     */
    public function getAllLogs($limit = 1000) {
        try {
            if ($this->useMongoDB) {
                $logs = $this->logsCollection->find(
                    [],
                    [
                        'sort' => ['timestamp' => -1],
                        'limit' => $limit
                    ]
                );
                return iterator_to_array($logs);
            } else {
                return $this->getLogsFromFile($limit);
            }
        } catch (Exception $e) {
            error_log("Error getting logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение логов по действию
     */
    public function getLogsByAction($action, $limit = 100) {
        try {
            if ($this->useMongoDB) {
                $logs = $this->logsCollection->find(
                    ['action' => $action],
                    [
                        'sort' => ['timestamp' => -1],
                        'limit' => $limit
                    ]
                );
                return iterator_to_array($logs);
            } else {
                $allLogs = $this->getLogsFromFile($limit);
                return array_filter($allLogs, function($log) use ($action) {
                    return $log['action'] === $action;
                });
            }
        } catch (Exception $e) {
            error_log("Error getting logs by action: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получение логов по пользователю
     */
    public function getLogsByUser($username, $limit = 100) {
        try {
            if ($this->useMongoDB) {
                $logs = $this->logsCollection->find(
                    ['metadata.username' => $username],
                    [
                        'sort' => ['timestamp' => -1],
                        'limit' => $limit
                    ]
                );
                return iterator_to_array($logs);
            } else {
                $allLogs = $this->getLogsFromFile($limit);
                return array_filter($allLogs, function($log) use ($username) {
                    return isset($log['metadata']['username']) && $log['metadata']['username'] === $username;
                });
            }
        } catch (Exception $e) {
            error_log("Error getting logs by user: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Очистка старых логов
     */
    public function cleanOldLogs($days = 30) {
        try {
            if ($this->useMongoDB) {
                $cutoffDate = new MongoDB\BSON\UTCDateTime((time() - ($days * 24 * 60 * 60)) * 1000);
                $result = $this->logsCollection->deleteMany([
                    'timestamp' => ['$lt' => $cutoffDate]
                ]);
                return $result->getDeletedCount();
            } else {
                // For file system, we'll just truncate the file
                $logsFile = __DIR__ . '/../data/admin_logs.json';
                if (file_exists($logsFile)) {
                    unlink($logsFile);
                    return 1;
                }
                return 0;
            }
        } catch (Exception $e) {
            error_log("Error cleaning old logs: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Получение текущего времени
     */
    private function getCurrentTimestamp() {
        if ($this->useMongoDB) {
            return new MongoDB\BSON\UTCDateTime();
        } else {
            return date('Y-m-d H:i:s');
        }
    }
    
    /**
     * Определение уровня логирования
     */
    private function getLogLevel($action) {
        $errorActions = ['login_failed', 'error', 'security_violation'];
        $warningActions = ['warning', 'rate_limit', 'suspicious_activity'];
        
        if (in_array($action, $errorActions)) {
            return 'error';
        } elseif (in_array($action, $warningActions)) {
            return 'warning';
        } else {
            return 'info';
        }
    }
    
    /**
     * Логирование в файл (fallback)
     */
    private function logToFile($logEntry) {
        $logsFile = __DIR__ . '/../data/admin_logs.json';
        $logsDir = dirname($logsFile);
        
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        $logs = [];
        if (file_exists($logsFile)) {
            $logs = json_decode(file_get_contents($logsFile), true) ?: [];
        }
        
        $logs[] = $logEntry;
        
        // Ограничиваем количество логов
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Получение логов из файла (fallback)
     */
    private function getLogsFromFile($limit = 1000) {
        $logsFile = __DIR__ . '/../data/admin_logs.json';
        
        if (file_exists($logsFile)) {
            $logs = json_decode(file_get_contents($logsFile), true) ?: [];
            return array_slice($logs, -$limit);
        }
        
        return [];
    }
}
?>
