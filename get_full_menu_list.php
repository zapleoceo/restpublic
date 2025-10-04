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
        echo "=== ПОЛНЫЙ СПИСОК БЛЮД ===\n";
        $products = $menu['products'] ?? [];
        
        foreach ($products as $index => $product) {
            $name = $product['product_name'] ?? $product['name'] ?? 'Без названия';
            $id = $product['product_id'] ?? $product['id'] ?? 'нет ID';
            echo ($index + 1) . ". ID: $id | Название: $name\n";
        }
        
        echo "\nВсего блюд: " . count($products) . "\n";
    } else {
        echo "Кэш меню не найден\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
