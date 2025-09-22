<?php
/**
 * API endpoint для получения профиля пользователя
 * GET /api/user/profile
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

    // Получаем данные пользователя из Poster API
    $posterApiUrl = 'https://joinposter.com/api/clients.getClient';
    $posterToken = $_ENV['POSTER_API_TOKEN'] ?? getenv('POSTER_API_TOKEN');
    
    if (!$posterToken) {
        throw new Exception('Poster API token not configured');
    }

    $url = $posterApiUrl . '?token=' . urlencode($posterToken) . '&client_id=' . urlencode($clientId);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch user data from Poster API');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['response']) || empty($data['response'])) {
        throw new Exception('Invalid response from Poster API');
    }
    
    $userData = $data['response'][0]; // Первый элемент массива
    
    // Форматируем данные для фронтенда
    $formattedUser = [
        'client_id' => $userData['client_id'] ?? null,
        'firstname' => $userData['firstname'] ?? null,
        'lastname' => $userData['lastname'] ?? null,
        'phone' => $userData['phone'] ?? null,
        'email' => $userData['email'] ?? null,
        'discount_per' => $userData['discount_per'] ?? 0,
        'bonus' => $userData['bonus'] ?? 0,
        'total_payed_sum' => $userData['total_payed_sum'] ?? 0,
        'date_activale' => $userData['date_activale'] ?? null,
        'client_groups_name' => $userData['client_groups_name'] ?? null,
        'client_groups_discount' => $userData['client_groups_discount'] ?? 0
    ];

    echo json_encode([
        'success' => true,
        'user' => $formattedUser
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("User profile API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
