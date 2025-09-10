<?php
// Настройки CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    // Получаем данные из запроса
    $rawInput = file_get_contents('php://input');
    
    // Исправляем экранированные кавычки
    $rawInput = str_replace(['\\"', '\\:'], ['"', ':'], $rawInput);
    
    $input = json_decode($rawInput, true);
    
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
    
    // Проверяем номер в Poster API через наш backend
    $backendUrl = 'http://localhost:3002/api/poster/clients.getClients';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $backendUrl . '?phone=' . urlencode($phone));
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Token: ' . ($_ENV['API_AUTH_TOKEN'] ?? getenv('API_AUTH_TOKEN'))
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        // Если API недоступен, возвращаем как нового пользователя
        echo json_encode([
            'found' => false,
            'message' => 'Новый пользователь',
            'groupId' => null
        ]);
        exit();
    }
    
    $posterResponse = json_decode($response, true);
    
    if (isset($posterResponse['error'])) {
        // Если ошибка API, возвращаем как нового пользователя
        echo json_encode([
            'found' => false,
            'message' => 'Новый пользователь',
            'groupId' => null
        ]);
        exit();
    }
    
    // Анализируем ответ Poster API
    // Backend возвращает массив клиентов напрямую, а не в поле 'response'
    $clients = is_array($posterResponse) ? $posterResponse : ($posterResponse['response'] ?? []);
    
    if (empty($clients)) {
        // Клиент не найден
        echo json_encode([
            'found' => false,
            'message' => 'Новый пользователь',
            'groupId' => null
        ]);
    } else {
        $client = $clients[0]; // Берем первого клиента
        $groupId = $client['client_groups_id'] ?? null;
        
        if ($groupId == 3) {
            // Гость
            echo json_encode([
                'found' => true,
                'message' => 'Вы уже делали заказы как гость',
                'groupId' => 3,
                'hasOrders' => true
            ]);
        } else {
            // Постоянный клиент
            echo json_encode([
                'found' => true,
                'message' => 'Вы уже являетесь нашим постоянным клиентом',
                'groupId' => $groupId,
                'hasOrders' => true
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log('Phone check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>