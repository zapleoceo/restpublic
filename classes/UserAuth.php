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
     * Сохранить пользователя в MongoDB (только client_id)
     * Сначала проверяем Poster API на наличие пользователя
     */
    public function saveUser($phone, $name, $lastName = '', $additionalData = []) {
        try {
            if (!$this->useMongoDB) {
                return false;
            }
            
            // Сначала проверяем, есть ли пользователь в Poster API
            $existingClientId = $this->findClientInPoster($phone);
            
            if ($existingClientId) {
                // Пользователь уже существует в Poster API - используем его
                error_log("User already exists in Poster API with client_id: $existingClientId");
                return $this->saveUserToMongoDB($existingClientId);
            } else {
                // Создаем нового пользователя в Poster API
                $clientId = $this->createClientInPoster($phone, $name, $lastName, $additionalData);
                
                if ($clientId) {
                    // Сохраняем связь в MongoDB
                    return $this->saveUserToMongoDB($clientId);
                } else {
                    error_log('Failed to create client in Poster API');
                    return false;
                }
            }
        } catch (Exception $e) {
            error_log('Error saving user: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Найти клиента в Poster API по телефону
     */
    private function findClientInPoster($phone) {
        try {
            $apiUrl = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3002') . '/api/poster/clients.getClients';
            $authToken = $_ENV['API_AUTH_TOKEN'] ?? '';
            
            $url = $apiUrl . '?phone=' . urlencode($phone) . '&token=' . urlencode($authToken);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log('Failed to fetch clients from Poster API');
                return null;
            }
            
            $clients = json_decode($response, true);
            
            if (is_array($clients) && count($clients) > 0) {
                return $clients[0]['client_id'] ?? null;
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Error finding client in Poster API: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Создать нового клиента в Poster API
     */
    private function createClientInPoster($phone, $name, $lastName, $additionalData) {
        try {
            $apiUrl = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3002') . '/api/poster/clients.createClient';
            $authToken = $_ENV['API_AUTH_TOKEN'] ?? '';
            
            $clientData = [
                'firstname' => $lastName ?: 'Пользователь',
                'lastname' => $name ?: '',
                'client_groups_id_client' => 1, // Default group
                'phone' => $phone,
                'email' => $additionalData['email'] ?? '',
                'comment' => $additionalData['comment'] ?? ''
            ];
            
            $postData = json_encode($clientData);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'X-API-Token: ' . $authToken
                    ],
                    'content' => $postData
                ]
            ]);
            
            $response = @file_get_contents($apiUrl . '?token=' . urlencode($authToken), false, $context);
            
            if ($response === false) {
                error_log('Failed to create client in Poster API');
                return null;
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['response'])) {
                return $result['response'];
            }
            
            error_log('Invalid response from Poster API: ' . $response);
            return null;
        } catch (Exception $e) {
            error_log('Error creating client in Poster API: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Сохранить пользователя в MongoDB (только client_id)
     */
    private function saveUserToMongoDB($clientId) {
        try {
            // Проверяем, существует ли уже пользователь
            $existingUser = $this->usersCollection->findOne(['client_id' => $clientId]);
            
            if ($existingUser) {
                // Пользователь уже существует - ничего не делаем
                return true;
            } else {
                // Создаем нового пользователя только с client_id
                $userData = [
                    'client_id' => $clientId
                ];
                
                $result = $this->usersCollection->insertOne($userData);
                return $result->getInsertedCount() > 0;
            }
        } catch (Exception $e) {
            error_log('Error saving user to MongoDB: ' . $e->getMessage());
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
