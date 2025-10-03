<?php
/**
 * PHP прокси для API запросов
 * Перенаправляет запросы к Node.js backend без использования Apache mod_proxy
 */

// Разрешаем CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');

// Обрабатываем preflight OPTIONS запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Получаем путь API запроса
$path = $_GET['path'] ?? '';
if (empty($path)) {
    http_response_code(400);
    echo json_encode(['error' => 'Path parameter is required']);
    exit;
}

// Определяем backend URL
$backend_url = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/api/' . $path;

// Получаем токен авторизации из заголовка запроса или из окружения
$api_token = $_SERVER['HTTP_X_API_TOKEN'] ?? ($_ENV['API_AUTH_TOKEN'] ?? '');

// Определяем метод запроса
$method = $_SERVER['REQUEST_METHOD'];

// Получаем входные данные
$input_data = file_get_contents('php://input');

// Настраиваем cURL
$ch = curl_init($backend_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Token: ' . $api_token,
    'Content-Length: ' . strlen($input_data)
]);

// Выполняем запрос
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Обрабатываем ошибки cURL
if (curl_errno($ch)) {
    error_log('cURL error in proxy.php: ' . curl_error($ch));
    http_response_code(500);
    echo json_encode(['error' => 'Backend connection failed']);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Устанавливаем код ответа и заголовки
http_response_code($http_code);

// Перенаправляем заголовки ответа
if ($content_type) {
    header('Content-Type: ' . $content_type);
}

// Выводим ответ
echo $response;
?>
