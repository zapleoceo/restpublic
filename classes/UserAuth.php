<?php

/**
 * Класс для работы с авторизацией пользователей
 */
class UserAuth {
    private $db;
    private $usersCollection;
    private $sessionsCollection;
    private $useMongoDB = false;
    
    public function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            if (class_exists('MongoDB\Client')) {
                // Загружаем переменные окружения
                if (file_exists(__DIR__ . '/../.env')) {
                    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
                    $dotenv->load();
                }
                
                $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
                $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
                
                $client = new MongoDB\Client($mongodbUrl);
                $this->db = $client->selectDatabase($dbName);
                $this->usersCollection = $this->db->selectCollection('users');
                $this->sessionsCollection = $this->db->selectCollection('user_sessions');
                $this->useMongoDB = true;
            } else {
                $this->useMongoDB = false;
                error_log('MongoDB not available, using file system');
            }
        } catch (Exception $e) {
            $this->useMongoDB = false;
            error_log('Database initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Сохранить пользователя в MongoDB
     */
    public function saveUser($clientId, $phone, $name, $additionalData = []) {
        try {
            if (!$this->useMongoDB) {
                return false;
            }
            
            $userData = [
                'client_id' => $clientId,
                'phone' => $phone,
                'name' => $name,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                ...$additionalData
            ];
            
            // Проверяем, существует ли уже пользователь
            $existingUser = $this->usersCollection->findOne(['client_id' => $clientId]);
            
            if ($existingUser) {
                // Обновляем существующего пользователя
                $userData['updated_at'] = new MongoDB\BSON\UTCDateTime();
                $result = $this->usersCollection->updateOne(
                    ['client_id' => $clientId],
                    ['$set' => $userData]
                );
                return $result->getModifiedCount() > 0;
            } else {
                // Создаем нового пользователя
                $result = $this->usersCollection->insertOne($userData);
                return $result->getInsertedCount() > 0;
            }
        } catch (Exception $e) {
            error_log('Error saving user: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить пользователя по client_id
     */
    public function getUserByClientId($clientId) {
        try {
            if (!$this->useMongoDB) {
                return null;
            }
            
            return $this->usersCollection->findOne(['client_id' => $clientId]);
        } catch (Exception $e) {
            error_log('Error getting user: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Сохранить сессию пользователя
     */
    public function saveSession($clientId, $sessionData = []) {
        try {
            if (!$this->useMongoDB) {
                return false;
            }
            
            $sessionInfo = [
                'client_id' => $clientId,
                'session_id' => session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'expires_at' => new MongoDB\BSON\UTCDateTime((time() + 86400) * 1000), // 24 часа
                ...$sessionData
            ];
            
            $result = $this->sessionsCollection->insertOne($sessionInfo);
            return $result->getInsertedCount() > 0;
        } catch (Exception $e) {
            error_log('Error saving session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Удалить сессию пользователя
     */
    public function deleteSession($clientId = null) {
        try {
            if (!$this->useMongoDB) {
                return false;
            }
            
            $filter = [];
            if ($clientId) {
                $filter['client_id'] = $clientId;
            } else {
                $filter['session_id'] = session_id();
            }
            
            $result = $this->sessionsCollection->deleteMany($filter);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log('Error deleting session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверить валидность сессии
     */
    public function validateSession($clientId) {
        try {
            if (!$this->useMongoDB) {
                return false;
            }
            
            $session = $this->sessionsCollection->findOne([
                'client_id' => $clientId,
                'session_id' => session_id(),
                'expires_at' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
            ]);
            
            return $session !== null;
        } catch (Exception $e) {
            error_log('Error validating session: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Очистить истекшие сессии
     */
    public function cleanupExpiredSessions() {
        try {
            if (!$this->useMongoDB) {
                return false;
            }
            
            $result = $this->sessionsCollection->deleteMany([
                'expires_at' => ['$lt' => new MongoDB\BSON\UTCDateTime()]
            ]);
            
            return $result->getDeletedCount();
        } catch (Exception $e) {
            error_log('Error cleaning up sessions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить статистику пользователей
     */
    public function getUserStats() {
        try {
            if (!$this->useMongoDB) {
                return null;
            }
            
            $totalUsers = $this->usersCollection->countDocuments();
            $activeSessions = $this->sessionsCollection->countDocuments([
                'expires_at' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
            ]);
            
            return [
                'total_users' => $totalUsers,
                'active_sessions' => $activeSessions
            ];
        } catch (Exception $e) {
            error_log('Error getting user stats: ' . $e->getMessage());
            return null;
        }
    }
}
?>
