<?php
/**
 * API endpoint для обработки callback от Telegram бота
 * POST /api/auth/telegram-callback
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Получаем данные из POST запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Валидация обязательных полей
    $phone = $data['phone'] ?? '';
    $name = $data['name'] ?? '';
    $lastName = $data['lastName'] ?? '';
    $sessionToken = $data['sessionToken'] ?? '';

    if (empty($phone) || empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Phone and name are required'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Логируем попытку авторизации
    error_log("Telegram auth callback: phone={$phone}, name={$name}, sessionToken={$sessionToken}");

    // Проверяем существование клиента в Poster API
    $posterApiUrl = 'https://joinposter.com/api/clients.getClients';
    $posterToken = $_ENV['POSTER_API_TOKEN'] ?? getenv('POSTER_API_TOKEN');
    
    if (!$posterToken) {
        throw new Exception('Poster API token not configured');
    }

    $url = $posterApiUrl . '?token=' . urlencode($posterToken) . '&phone=' . urlencode($phone);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to check client in Poster API');
    }
    
    $clientData = json_decode($response, true);
    
    if (!$clientData || !isset($clientData['response'])) {
        throw new Exception('Invalid response from Poster API');
    }
    
    $clients = $clientData['response'] ?? [];
    $clientId = null;
    
    if (!empty($clients)) {
        // Клиент найден, берем первый
        $clientId = $clients[0]['client_id'] ?? null;
        error_log("Existing client found: client_id={$clientId}");
    } else {
        // Клиент не найден, создаем нового
        error_log("Client not found, creating new one");
        
        $createUrl = 'https://joinposter.com/api/clients.createClient?token=' . urlencode($posterToken);
        
        $clientName = trim($name . ' ' . $lastName);
        $createData = [
            'client_name' => $clientName,
            'client_groups_id_client' => 1, // Группа ID 1 как указано в требованиях
            'phone' => $phone,
            'client_sex' => 0, // Не указан
            'email' => '',
            'birthday' => '',
            'city' => '',
            'country' => '',
            'address' => '',
            'comment' => 'Created via Telegram auth'
        ];
        
        $createContext = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($createData)
            ]
        ]);
        
        $createResponse = @file_get_contents($createUrl, false, $createContext);
        
        if ($createResponse === false) {
            throw new Exception('Failed to create client in Poster API');
        }
        
        $createResult = json_decode($createResponse, true);
        
        if (!$createResult || isset($createResult['error'])) {
            $errorMsg = $createResult['error']['message'] ?? 'Unknown error';
            throw new Exception('Poster API error: ' . $errorMsg);
        }
        
        $clientId = $createResult['response'] ?? null;
        error_log("New client created: client_id={$clientId}");
    }
    
    if (!$clientId) {
        throw new Exception('Failed to get or create client ID');
    }

    // Создаем сессию пользователя
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_authenticated'] = true;
    $_SESSION['user_client_id'] = $clientId;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_authenticated_at'] = time();
    
    // Сохраняем пользователя в MongoDB
    require_once __DIR__ . '/../../classes/UserAuth.php';
    $userAuth = new UserAuth();
    $userAuth->saveUser($clientId, $phone, $name, [
        'last_login' => new MongoDB\BSON\UTCDateTime(),
        'login_count' => 1 // Будет обновлено при повторных входах
    ]);
    
    // Сохраняем сессию
    $userAuth->saveSession($clientId, [
        'auth_method' => 'telegram',
        'session_token' => $sessionToken
    ]);
    
    // Логируем успешную авторизацию
    error_log("User authenticated successfully: client_id={$clientId}, phone={$phone}, name={$name}");

    // Формируем URL для возврата в приложение
    $returnUrl = 'https://northrepublic.me';
    if (!empty($sessionToken)) {
        $returnUrl .= '?auth_success=1&token=' . urlencode($sessionToken);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Authentication successful',
        'client_id' => $clientId,
        'redirectUrl' => $returnUrl
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Telegram callback API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
