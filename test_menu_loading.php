<?php
// Устанавливаем переменные окружения
$_ENV['BACKEND_URL'] = 'http://localhost:3002';
$_ENV['API_AUTH_TOKEN'] = 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';

require_once 'classes/MenuCache.php';

echo "=== TEST MENU LOADING ===\n";

$menuCache = new MenuCache();
$menuData = $menuCache->getMenu();
$categories = $menuData ? $menuData['categories'] : [];
$products = $menuData ? $menuData['products'] : [];

echo "1. Категории в кэше:\n";
if ($categories) {
    foreach ($categories as $category) {
        echo "   - ID: " . ($category['category_id'] ?? 'N/A') . ", Название: " . ($category['name'] ?? 'N/A') . "\n";
    }
} else {
    echo "   ❌ Категории не найдены\n";
}

echo "\n2. Тестируем API запросы:\n";
$api_base_url = $_ENV['BACKEND_URL'] . '/api';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$productsByCategory = [];

if ($categories) {
    foreach ($categories as $category) {
        $categoryId = (string)($category['category_id']);
        echo "   📁 Тестируем категорию ID: $categoryId\n";
        
        try {
            $authToken = $_ENV['API_AUTH_TOKEN'];
            $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=5&token=' . urlencode($authToken);
            echo "   🔗 URL: $popularUrl\n";
            
            $popularResponse = @file_get_contents($popularUrl, false, $context);
            
            if ($popularResponse !== false) {
                $popularData = json_decode($popularResponse, true);
                if ($popularData && isset($popularData['popular_products'])) {
                    $productsByCategory[$categoryId] = $popularData['popular_products'];
                    echo "   ✅ Получено продуктов: " . count($popularData['popular_products']) . "\n";
                } else {
                    echo "   ❌ Неверный ответ API\n";
                    $productsByCategory[$categoryId] = [];
                }
            } else {
                echo "   ❌ Ошибка запроса к API\n";
                $productsByCategory[$categoryId] = [];
            }
        } catch (Exception $e) {
            echo "   ❌ Исключение: " . $e->getMessage() . "\n";
            $productsByCategory[$categoryId] = [];
        }
    }
}

echo "\n3. Итоговые результаты:\n";
foreach ($productsByCategory as $categoryId => $products) {
    echo "   📁 Категория $categoryId: " . count($products) . " продуктов\n";
    if (!empty($products)) {
        foreach (array_slice($products, 0, 3) as $product) {
            echo "      🍽️ " . ($product['product_name'] ?? 'Без названия') . "\n";
        }
    }
}
?>
