<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $menuCollection = $db->menu;
    
    $menu = $menuCollection->findOne(['_id' => 'current_menu']);
    
    if ($menu) {
        echo "=== МЕНЮ ИЗ КЭША ===\n";
        echo "Время обновления: " . ($menu['updated_at'] ?? 'неизвестно') . "\n";
        echo "Количество категорий: " . count($menu['categories'] ?? []) . "\n";
        echo "Количество продуктов: " . count($menu['products'] ?? []) . "\n\n";
        
        echo "=== СПИСОК ПРОДУКТОВ ===\n";
        $products = $menu['products'] ?? [];
        foreach ($products as $index => $product) {
            echo ($index + 1) . ". " . ($product['product_name'] ?? $product['name'] ?? 'Без названия') . "\n";
            if ($index >= 49) { // Показываем первые 50
                echo "... и еще " . (count($products) - 50) . " продуктов\n";
                break;
            }
        }
    } else {
        echo "Кэш меню не найден\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
