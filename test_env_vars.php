<?php
// Загружаем .env файл
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "=== TEST ENV VARIABLES ===\n";
echo "BACKEND_URL: " . ($_ENV['BACKEND_URL'] ?? 'NOT SET') . "\n";
echo "API_AUTH_TOKEN: " . ($_ENV['API_AUTH_TOKEN'] ?? 'NOT SET') . "\n";

// Тестируем API запрос
$api_base_url = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3002') . '/api';
$authToken = $_ENV['API_AUTH_TOKEN'] ?? 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';

echo "\nAPI Base URL: $api_base_url\n";
echo "Auth Token: " . substr($authToken, 0, 20) . "...\n";

// Тестируем запрос к API
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$popularUrl = $api_base_url . '/menu/categories/2/popular?limit=5&token=' . urlencode($authToken);
echo "\nTesting URL: $popularUrl\n";

$popularResponse = @file_get_contents($popularUrl, false, $context);

if ($popularResponse !== false) {
    $popularData = json_decode($popularResponse, true);
    if ($popularData && isset($popularData['popular_products'])) {
        echo "✅ API работает! Получено продуктов: " . count($popularData['popular_products']) . "\n";
        foreach (array_slice($popularData['popular_products'], 0, 3) as $product) {
            echo "   🍽️ " . ($product['product_name'] ?? 'Без названия') . "\n";
        }
    } else {
        echo "❌ Неверный ответ API\n";
    }
} else {
    echo "❌ Ошибка запроса к API\n";
}
?>
