<?php
/**
 * PHP обработчик телеграм авторизации
 * Перенаправляет запрос к Node.js backend
 */

// Разрешаем CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обрабатываем preflight OPTIONS запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Получаем данные из POST запроса
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON data'
    ]);
    exit;
}

// Извлекаем данные
$phone = $data['phone'] ?? '';
$name = $data['name'] ?? '';
$lastName = $data['lastName'] ?? '';
$sessionToken = $data['sessionToken'] ?? '';

if (!$phone || !$sessionToken) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: phone, sessionToken'
    ]);
    exit;
}

// Определяем backend URL
$backend_url = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/auth/telegram-callback';

// Подготавливаем данные для backend
$backend_data = [
    'phone' => $phone,
    'name' => $name,
    'lastName' => $lastName,
    'sessionToken' => $sessionToken
];

// Получаем токен авторизации
$api_token = $_ENV['API_AUTH_TOKEN'] ?? '';

// Отправляем запрос к backend
$ch = curl_init($backend_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($backend_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Token: ' . $api_token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    error_log('cURL error in telegram-callback.php: ' . curl_error($ch));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Backend connection failed'
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Перенаправляем ответ от backend
http_response_code($http_code);
echo $response;
?>
