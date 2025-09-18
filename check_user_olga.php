<?php
require_once __DIR__ . '/vendor/autoload.php';

// Загружаем переменные окружения
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $usersCollection = $db->admin_users;
    
    echo "🔍 Поиск пользователя 'olga'...\n";
    
    $user = $usersCollection->findOne(['username' => 'olga']);
    
    if ($user) {
        echo "✅ Пользователь найден:\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . ($user['email'] ?? 'не указан') . "\n";
        echo "Role: " . ($user['role'] ?? 'admin') . "\n";
        echo "Active: " . ($user['active'] ? 'да' : 'нет') . "\n";
        echo "Created: " . ($user['created_at'] ? $user['created_at']->toDateTime()->format('Y-m-d H:i:s') : 'не указано') . "\n";
        echo "Last login: " . ($user['last_login'] ? $user['last_login']->toDateTime()->format('Y-m-d H:i:s') : 'никогда') . "\n";
        echo "Failed attempts: " . ($user['failed_attempts'] ?? 0) . "\n";
        echo "Locked until: " . ($user['locked_until'] ?? 'не заблокирован') . "\n";
        
        // Проверяем пароль
        echo "\n🔐 Проверка пароля 'olgaolegovaya'...\n";
        if (password_verify('olgaolegovaya', $user['password_hash'])) {
            echo "✅ Пароль верный\n";
        } else {
            echo "❌ Пароль неверный\n";
        }
        
    } else {
        echo "❌ Пользователь 'olga' не найден\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
