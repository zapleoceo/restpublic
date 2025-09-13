<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

// Обработка preflight запросов
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Проверяем метод запроса
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Загружаем переменные окружения
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    // Получаем данные заказа
    $input = file_get_contents('php://input');
    $orderData = json_decode($input, true);
    
    if (!$orderData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        exit();
    }
    
    // Валидация обязательных полей
    if (!isset($orderData['products']) || !is_array($orderData['products']) || empty($orderData['products'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Products array is required and cannot be empty']);
        exit();
    }
    
    // Подключаем Poster API Service
    require_once __DIR__ . '/../classes/SePayApiService.php';
    $posterService = new SePayApiService();
    
    // Создаем заказ через Poster API
    $result = $posterService->createIncomingOrder($orderData);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'order' => $result,
            'message' => 'Order created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create order'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create order',
        'message' => $e->getMessage()
    ]);
}
?>
