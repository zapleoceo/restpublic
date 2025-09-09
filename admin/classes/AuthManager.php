<?php
require_once __DIR__ . '/../config/auth.php';

class AuthManager {
    private $db;
    private $usersCollection;
    private $logsCollection;
    private $sessionsCollection;
    private $useMongoDB = false;
    
    public function __construct() {
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        try {
            if (class_exists('MongoDB\Client')) {
                $client = new MongoDB\Client(DB_CONNECTION_STRING);
                $this->db = $client->selectDatabase(DB_NAME);
                $this->usersCollection = $this->db->selectCollection(USERS_COLLECTION);
                $this->logsCollection = $this->db->selectCollection(LOGS_COLLECTION);
                $this->sessionsCollection = $this->db->selectCollection(SESSIONS_COLLECTION);
                $this->useMongoDB = true;
            } else {
                $this->useMongoDB = false;
                $this->log('info', 'MongoDB not available, using file system');
            }
        } catch (Exception $e) {
            $this->useMongoDB = false;
            $this->log('error', 'Database initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Аутентификация пользователя
     */
    public function authenticate($username, $password) {
        // Проверяем rate limiting
        if (!$this->checkRateLimit($username)) {
            $this->log('warning', 'Rate limit exceeded for user: ' . $username);
            return ['success' => false, 'error' => 'Слишком много попыток входа. Попробуйте позже.'];
        }
        
        // Валидация входных данных
        $validation = $this->validateCredentials($username, $password);
        if (!$validation['valid']) {
            $this->log('warning', 'Invalid credentials format for user: ' . $username);
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Поиск пользователя
        $user = $this->findUser($username);
        if (!$user) {
            $this->logFailedAttempt($username, 'User not found');
            return ['success' => false, 'error' => 'Неверные данные для входа'];
        }
        
        // Проверка блокировки
        if ($this->isUserLocked($user)) {
            $this->log('warning', 'Login attempt for locked user: ' . $username);
            return ['success' => false, 'error' => 'Аккаунт заблокирован. Обратитесь к администратору.'];
        }
        
        // Проверка пароля
        if (!password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($username, 'Invalid password');
            $this->incrementFailedAttempts($user);
            return ['success' => false, 'error' => 'Неверные данные для входа'];
        }
        
        // Успешная авторизация
        $this->logSuccessfulLogin($username);
        $this->resetFailedAttempts($user);
        $this->updateLastLogin($user);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['_id'] ?? $user['username'],
                'username' => $user['username'],
                'email' => $user['email'] ?? '',
                'role' => $user['role'] ?? 'admin'
            ]
        ];
    }
    
    /**
     * Создание нового пользователя
     */
    public function createUser($username, $password, $email = '', $role = 'admin') {
        // Проверяем, есть ли уже пользователи
        if ($this->getUsersCount() > 0) {
            return ['success' => false, 'error' => 'Пользователи уже существуют в системе'];
        }
        
        // Валидация
        $validation = $this->validateNewUser($username, $password, $email);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Создание пользователя
        $userData = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
            'role' => $role,
            'active' => true,
            'created_at' => $this->getCurrentTimestamp(),
            'last_login' => null,
            'failed_attempts' => 0,
            'locked_until' => null
        ];
        
        try {
            if ($this->useMongoDB) {
                $result = $this->usersCollection->insertOne($userData);
                $userData['_id'] = $result->getInsertedId();
            } else {
                $users = $this->getUsersFromFile();
                $userData['_id'] = uniqid();
                $users[] = $userData;
                $this->saveUsersToFile($users);
            }
            
            $this->log('info', 'User created: ' . $username);
            return ['success' => true, 'user' => $userData];
            
        } catch (Exception $e) {
            $this->log('error', 'Failed to create user: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Ошибка создания пользователя'];
        }
    }
    
    /**
     * Поиск пользователя
     */
    private function findUser($username) {
        try {
            if ($this->useMongoDB) {
                return $this->usersCollection->findOne([
                    'username' => $username,
                    'active' => true
                ]);
            } else {
                $users = $this->getUsersFromFile();
                foreach ($users as $user) {
                    if ($user['username'] === $username && $user['active'] === true) {
                        return $user;
                    }
                }
                return null;
            }
        } catch (Exception $e) {
            $this->log('error', 'Error finding user: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получение количества пользователей
     */
    public function getUsersCount() {
        try {
            if ($this->useMongoDB) {
                return $this->usersCollection->countDocuments();
            } else {
                $users = $this->getUsersFromFile();
                return count($users);
            }
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Валидация учетных данных
     */
    private function validateCredentials($username, $password) {
        if (empty($username) || empty($password)) {
            return ['valid' => false, 'error' => 'Заполните все поля'];
        }
        
        if (strlen($username) > 50 || strlen($password) > 100) {
            return ['valid' => false, 'error' => 'Слишком длинные данные'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'error' => 'Имя пользователя может содержать только буквы, цифры и подчеркивания'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Валидация нового пользователя
     */
    private function validateNewUser($username, $password, $email) {
        $credValidation = $this->validateCredentials($username, $password);
        if (!$credValidation['valid']) {
            return $credValidation;
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return ['valid' => false, 'error' => 'Пароль должен быть не менее ' . PASSWORD_MIN_LENGTH . ' символов'];
        }
        
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return ['valid' => false, 'error' => 'Пароль должен содержать специальные символы'];
        }
        
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'error' => 'Пароль должен содержать цифры'];
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return ['valid' => false, 'error' => 'Пароль должен содержать заглавные буквы'];
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Неверный формат email'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Проверка rate limiting
     */
    private function checkRateLimit($username) {
        // Простая проверка - можно улучшить
        return true;
    }
    
    /**
     * Проверка блокировки пользователя
     */
    private function isUserLocked($user) {
        if (isset($user['locked_until']) && $user['locked_until']) {
            $lockTime = is_string($user['locked_until']) ? strtotime($user['locked_until']) : $user['locked_until'];
            if ($lockTime > time()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Логирование неудачной попытки
     */
    private function logFailedAttempt($username, $reason) {
        $this->log('warning', 'Failed login attempt: ' . $username . ' - ' . $reason);
    }
    
    /**
     * Логирование успешного входа
     */
    private function logSuccessfulLogin($username) {
        $this->log('info', 'Successful login: ' . $username);
    }
    
    /**
     * Увеличение счетчика неудачных попыток
     */
    private function incrementFailedAttempts($user) {
        $failedAttempts = ($user['failed_attempts'] ?? 0) + 1;
        
        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
            $this->lockUser($user, LOCKOUT_TIME);
        } else {
            $this->updateUserField($user, 'failed_attempts', $failedAttempts);
        }
    }
    
    /**
     * Сброс счетчика неудачных попыток
     */
    private function resetFailedAttempts($user) {
        $this->updateUserField($user, 'failed_attempts', 0);
        $this->updateUserField($user, 'locked_until', null);
    }
    
    /**
     * Блокировка пользователя
     */
    private function lockUser($user, $lockTime) {
        $lockedUntil = time() + $lockTime;
        $this->updateUserField($user, 'locked_until', $lockedUntil);
        $this->log('warning', 'User locked: ' . $user['username'] . ' until ' . date('Y-m-d H:i:s', $lockedUntil));
    }
    
    /**
     * Обновление времени последнего входа
     */
    private function updateLastLogin($user) {
        $this->updateUserField($user, 'last_login', $this->getCurrentTimestamp());
    }
    
    /**
     * Обновление поля пользователя
     */
    private function updateUserField($user, $field, $value) {
        try {
            if ($this->useMongoDB) {
                $this->usersCollection->updateOne(
                    ['_id' => $user['_id']],
                    ['$set' => [$field => $value]]
                );
            } else {
                $users = $this->getUsersFromFile();
                foreach ($users as &$u) {
                    if (($u['_id'] ?? $u['username']) === ($user['_id'] ?? $user['username'])) {
                        $u[$field] = $value;
                        break;
                    }
                }
                $this->saveUsersToFile($users);
            }
        } catch (Exception $e) {
            $this->log('error', 'Failed to update user field: ' . $e->getMessage());
        }
    }
    
    /**
     * Работа с файлами (fallback)
     */
    private function getUsersFromFile() {
        if (file_exists(USERS_FILE)) {
            $content = file_get_contents(USERS_FILE);
            return json_decode($content, true) ?: [];
        }
        return [];
    }
    
    private function saveUsersToFile($users) {
        return file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
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
     * Логирование
     */
    private function log($level, $message) {
        $logEntry = [
            'level' => $level,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        try {
            if ($this->useMongoDB) {
                $this->logsCollection->insertOne($logEntry);
            } else {
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
            error_log("Auth log error: " . $e->getMessage());
        }
    }
}
?>
