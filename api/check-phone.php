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
    
    // Отладка
    error_log('Raw input: ' . $rawInput);
    error_log('Parsed input: ' . print_r($input, true));
    
    if (!isset($input['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Phone number is required', 'debug' => ['raw' => $rawInput, 'parsed' => $input]]);
        exit();
    }
    
    $phone = $input['phone'];
    
    // Валидация номера телефона
    if (!preg_match('/^\+[0-9]{10,12}$/', $phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid phone number format']);
        exit();
    }
    
    // Возвращаем тестовый ответ
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