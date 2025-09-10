<?php
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "🧪 Тестирование логики меню\n\n";

// Тест 1: Проверка загрузки меню из MongoDB
echo "1. Тест загрузки меню из MongoDB:\n";
try {
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        
        if ($menuData) {
            $categories = $menuData['categories'] ?? [];
            $products = $menuData['products'] ?? [];
            echo "✅ MongoDB: Загружено категорий: " . count($categories) . ", товаров: " . count($products) . "\n";
        } else {
            echo "❌ MongoDB: Данные не загружены\n";
        }
    } else {
        echo "❌ MongoDB: Класс не найден\n";
    }
} catch (Exception $e) {
    echo "❌ MongoDB: Ошибка - " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 2: Проверка API fallback
echo "2. Тест API fallback:\n";
$api_base_url = 'https://northrepublic.me:3002/api';

function fetchFromAPI($endpoint) {
    global $api_base_url;
    $url = $api_base_url . $endpoint;
    
    // Добавляем токен авторизации
    $authToken = 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
    $url .= (strpos($url, '?') !== false ? '&' : '?') . 'token=' . urlencode($authToken);
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

$menu_data = fetchFromAPI('/menu');
if ($menu_data) {
    $categories = $menu_data['categories'] ?? [];
    $products = $menu_data['products'] ?? [];
    echo "✅ API: Загружено категорий: " . count($categories) . ", товаров: " . count($products) . "\n";
} else {
    echo "❌ API: Данные не загружены\n";
}

echo "\n";

// Тест 3: Проверка популярных товаров
echo "3. Тест популярных товаров:\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$authToken = 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
$popularUrl = $api_base_url . '/menu/categories/2/popular?limit=5&token=' . urlencode($authToken);
$popularResponse = @file_get_contents($popularUrl, false, $context);

if ($popularResponse !== false) {
    $popularData = json_decode($popularResponse, true);
    if ($popularData && isset($popularData['popular_products'])) {
        echo "✅ Популярные товары: Загружено " . count($popularData['popular_products']) . " товаров\n";
    } else {
        echo "❌ Популярные товары: Неверный формат данных\n";
    }
} else {
    echo "❌ Популярные товары: Запрос не выполнен\n";
}

echo "\n✅ Тестирование завершено\n";
?>
