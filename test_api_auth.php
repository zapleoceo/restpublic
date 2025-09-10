<?php
// Тестовый скрипт для проверки авторизации API

echo "🧪 Тестирование авторизации API\n\n";

$authToken = $_ENV['API_AUTH_TOKEN'] ?? getenv('API_AUTH_TOKEN') ?? 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
$baseUrl = 'http://localhost:3002/api';

// Тест 1: Запрос без токена (должен вернуть 401)
echo "1. Тест без токена:\n";
$url = $baseUrl . '/poster/menu.getCategories';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "❌ Запрос не выполнен\n";
} else {
    $data = json_decode($response, true);
    if (isset($data['error']) && $data['error'] === 'Unauthorized') {
        echo "✅ Получена ошибка авторизации (ожидаемо)\n";
    } else {
        echo "❌ Неожиданный ответ: " . $response . "\n";
    }
}

echo "\n";

// Тест 2: Запрос с токеном (должен работать)
echo "2. Тест с токеном:\n";
$url = $baseUrl . '/poster/menu.getCategories?token=' . urlencode($authToken);
$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "❌ Запрос не выполнен\n";
} else {
    $data = json_decode($response, true);
    if (isset($data['response']) || isset($data['categories'])) {
        echo "✅ Запрос выполнен успешно\n";
    } else {
        echo "❌ Неожиданный ответ: " . $response . "\n";
    }
}

echo "\n";

// Тест 3: Запрос к /api/menu (должен работать без токена)
echo "3. Тест /api/menu (без токена):\n";
$url = $baseUrl . '/menu';
$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "❌ Запрос не выполнен\n";
} else {
    $data = json_decode($response, true);
    if (isset($data['categories']) && isset($data['products'])) {
        echo "✅ Запрос к /api/menu выполнен успешно\n";
    } else {
        echo "❌ Неожиданный ответ: " . $response . "\n";
    }
}

echo "\n✅ Тестирование завершено\n";
?>
