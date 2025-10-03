<?php
/**
 * PHP обработчик статуса авторизации
 * Проверяет сессию пользователя и возвращает статус авторизации
 */

// Разрешаем CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Token');

// Обрабатываем preflight OPTIONS запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Определяем backend URL
$backend_url = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/auth/status';

// Получаем токен сессии из заголовка или query параметра
$session_token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? $_GET['sessionToken'] ?? '';

// Подготавливаем данные для backend
$backend_data = [];
if ($session_token) {
    $backend_data['sessionToken'] = $session_token;
}

// Получаем токен авторизации
$api_token = $_ENV['API_AUTH_TOKEN'] ?? '';

// Отправляем запрос к backend
$ch = curl_init($backend_url . ($session_token ? '?sessionToken=' . urlencode($session_token) : ''));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Token: ' . $api_token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    error_log('cURL error in auth-status.php: ' . curl_error($ch));
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
