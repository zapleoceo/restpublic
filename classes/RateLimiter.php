<?php

class RateLimiter
{
    private $db;
    private $collection;
    
    public function __construct()
    {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            // Загружаем переменные окружения
            if (file_exists(__DIR__ . '/../.env')) {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
                $dotenv->load();
            }
            
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $client = new MongoDB\Client($mongodbUrl);
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
            $this->db = $client->$dbName;
            $this->collection = $this->db->rate_limits;
        } catch (Exception $e) {
            error_log("RateLimiter connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Проверка лимита запросов
     */
    public function checkLimit($identifier, $maxRequests = 10, $windowMinutes = 15)
    {
        try {
            $windowStart = new MongoDB\BSON\UTCDateTime((time() - ($windowMinutes * 60)) * 1000);
            
            // Удаляем старые записи
            $this->collection->deleteMany([
                'timestamp' => ['$lt' => $windowStart]
            ]);
            
            // Подсчитываем запросы в окне
            $requestCount = $this->collection->countDocuments([
                'identifier' => $identifier,
                'timestamp' => ['$gte' => $windowStart]
            ]);
            
            if ($requestCount >= $maxRequests) {
                return false; // Лимит превышен
            }
            
            // Записываем текущий запрос
            $this->collection->insertOne([
                'identifier' => $identifier,
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            return true; // Лимит не превышен
            
        } catch (Exception $e) {
            error_log("RateLimiter error: " . $e->getMessage());
            return true; // В случае ошибки разрешаем запрос
        }
    }
    
    /**
     * Проверка лимита для админ-панели
     */
    public function checkAdminLimit($username = null)
    {
        $identifier = $username ? "admin_{$username}" : "admin_ip_" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $this->checkLimit($identifier, 30, 15); // 30 запросов за 15 минут
    }
    
    /**
     * Проверка лимита для авторизации
     */
    public function checkAuthLimit()
    {
        $identifier = "auth_" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $this->checkLimit($identifier, 5, 15); // 5 попыток за 15 минут
    }
    
    /**
     * Проверка лимита для загрузки файлов
     */
    public function checkUploadLimit($username = null)
    {
        $identifier = $username ? "upload_{$username}" : "upload_ip_" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $this->checkLimit($identifier, 10, 60); // 10 загрузок за час
    }
    
    /**
     * Получение информации о лимитах
     */
    public function getLimitInfo($identifier, $windowMinutes = 15)
    {
        try {
            $windowStart = new MongoDB\BSON\UTCDateTime((time() - ($windowMinutes * 60)) * 1000);
            
            $requestCount = $this->collection->countDocuments([
                'identifier' => $identifier,
                'timestamp' => ['$gte' => $windowStart]
            ]);
            
            return [
                'count' => $requestCount,
                'window_start' => $windowStart->toDateTime(),
                'window_minutes' => $windowMinutes
            ];
            
        } catch (Exception $e) {
            error_log("RateLimiter getLimitInfo error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Очистка старых записей
     */
    public function cleanup($olderThanHours = 24)
    {
        try {
            $cutoffTime = new MongoDB\BSON\UTCDateTime((time() - ($olderThanHours * 60 * 60)) * 1000);
            
            $result = $this->collection->deleteMany([
                'timestamp' => ['$lt' => $cutoffTime]
            ]);
            
            return $result->getDeletedCount();
            
        } catch (Exception $e) {
            error_log("RateLimiter cleanup error: " . $e->getMessage());
            return 0;
        }
    }
}
?>
