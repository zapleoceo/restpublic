<?php
// Простая загрузка переменных окружения без Composer
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Временно отключаем MongoDB для тестирования
// $connectionString = $_ENV['DB_CONNECTION_STRING'] ?? getenv('DB_CONNECTION_STRING') ?? 'mongodb://localhost:27017';
// $mongoClient = new MongoDB\Driver\Manager($connectionString);

// Настройки CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Получаем данные из запроса
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Phone number is required']);
        exit();
    }
    
    $phone = $input['phone'];
    
    // Валидация номера телефона
    if (!preg_match('/^\+[0-9]{10,12}$/', $phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid phone number format']);
        exit();
    }
    
    // Rate limiting - временно отключен для тестирования
    // $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // $minuteAgo = new MongoDB\BSON\UTCDateTime((time() - 60) * 1000);
    
    // Временно возвращаем тестовый ответ
    echo json_encode([
        'found' => false,
        'message' => 'Новый пользователь (тест)',
        'groupId' => null
    ]);
    
} catch (Exception $e) {
    error_log('Phone check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
