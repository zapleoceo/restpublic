<?php
/**
 * API endpoint для получения заказов пользователя
 * GET /api/user/orders
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Загружаем переменные окружения
    require_once __DIR__ . '/../../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    }

    // Запускаем сессию если она еще не запущена
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Проверяем авторизацию
    if (!isset($_SESSION['user_authenticated']) || $_SESSION['user_authenticated'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'User not authenticated'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $clientId = $_SESSION['user_client_id'] ?? null;
    if (!$clientId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Bad request',
            'message' => 'Client ID not found in session'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Получаем заказы пользователя из Poster API
    $posterApiUrl = 'https://joinposter.com/api/incomingOrders.getIncomingOrders';
    $posterToken = $_ENV['POSTER_API_TOKEN'] ?? getenv('POSTER_API_TOKEN');
    
    if (!$posterToken) {
        throw new Exception('Poster API token not configured');
    }

    // Получаем заказы за последние 30 дней
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo = date('Y-m-d');
    
    $url = $posterApiUrl . '?token=' . urlencode($posterToken) . 
           '&date_from=' . urlencode($dateFrom) . 
           '&date_to=' . urlencode($dateTo) . 
           '&client_id=' . urlencode($clientId);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch orders from Poster API');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['response'])) {
        throw new Exception('Invalid response from Poster API');
    }
    
    $orders = $data['response'] ?? [];
    
    // Фильтруем только неоплаченные заказы
    $unpaidOrders = array_filter($orders, function($order) {
        // Проверяем статус заказа (предполагаем, что статус 0 или 1 означает неоплаченный)
        $status = $order['status'] ?? 0;
        return $status == 0 || $status == 1;
    });
    
    // Форматируем данные для фронтенда
    $formattedOrders = array_map(function($order) {
        return [
            'id' => $order['incoming_order_id'] ?? $order['id'] ?? null,
            'date' => $order['date'] ?? null,
            'total' => $order['total'] ?? 0,
            'status' => $this->getOrderStatusText($order['status'] ?? 0),
            'comment' => $order['comment'] ?? null,
            'products' => $order['products'] ?? []
        ];
    }, $unpaidOrders);

    echo json_encode([
        'success' => true,
        'orders' => array_values($formattedOrders)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("User orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Получить текстовое описание статуса заказа
 */
function getOrderStatusText($status) {
    switch ($status) {
        case 0:
            return 'Новый';
        case 1:
            return 'В обработке';
        case 2:
            return 'Готов';
        case 3:
            return 'Выполнен';
        case 4:
            return 'Отменен';
        default:
            return 'Неизвестно';
    }
}
?>
