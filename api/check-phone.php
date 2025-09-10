<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Загружаем переменные окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Подключаемся к MongoDB
$mongoClient = new MongoDB\Client($_ENV['DB_CONNECTION_STRING'] ?? getenv('DB_CONNECTION_STRING'));
$db = $mongoClient->selectDatabase('northrepublic');

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
    
    // Rate limiting - проверяем количество запросов за последние 60 секунд
    $rateLimitCollection = $db->selectCollection('rate_limits');
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $minuteAgo = new MongoDB\BSON\UTCDateTime((time() - 60) * 1000);
    
    // Удаляем старые записи
    $rateLimitCollection->deleteMany(['timestamp' => ['$lt' => $minuteAgo]]);
    
    // Подсчитываем запросы за последнюю минуту
    $recentRequests = $rateLimitCollection->countDocuments([
        'ip' => $clientIp,
        'endpoint' => 'check-phone',
        'timestamp' => ['$gte' => $minuteAgo]
    ]);
    
    // Лимит: 12 запросов в минуту
    if ($recentRequests >= 12) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please try again later.']);
        exit();
    }
    
    // Записываем текущий запрос
    $rateLimitCollection->insertOne([
        'ip' => $clientIp,
        'endpoint' => 'check-phone',
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);
    
    // Проверяем номер в Poster API через наш backend
    $backendUrl = 'http://localhost:3002/api/poster/clients.getClients';
    $postData = [
        'phone' => $phone
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $backendUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Token: ' . ($_ENV['API_AUTH_TOKEN'] ?? getenv('API_AUTH_TOKEN'))
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Poster API request failed');
    }
    
    $posterResponse = json_decode($response, true);
    
    if (isset($posterResponse['error'])) {
        throw new Exception('Poster API error: ' . $posterResponse['error']);
    }
    
    // Анализируем ответ Poster API
    $clients = $posterResponse['response'] ?? [];
    
    if (empty($clients)) {
        // Клиент не найден
        echo json_encode([
            'found' => false,
            'message' => 'Новый пользователь',
            'groupId' => null
        ]);
    } else {
        $client = $clients[0]; // Берем первого клиента
        $groupId = $client['client_groups_id_client'] ?? null;
        
        // Проверяем, есть ли у клиента заказы
        $hasOrders = false;
        if (isset($client['client_id'])) {
            // Здесь можно добавить проверку заказов через Poster API
            // Пока что считаем, что если клиент найден, то у него могут быть заказы
            $hasOrders = true;
        }
        
        if ($groupId == 3) {
            // Гость
            echo json_encode([
                'found' => true,
                'message' => $hasOrders ? 'Вы уже делали заказы как гость' : 'Новый гость',
                'groupId' => 3,
                'hasOrders' => $hasOrders
            ]);
        } else {
            // Постоянный клиент
            echo json_encode([
                'found' => true,
                'message' => 'Вы уже являетесь нашим постоянным клиентом',
                'groupId' => $groupId,
                'hasOrders' => $hasOrders
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log('Phone check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
