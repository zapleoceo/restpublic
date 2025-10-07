<?php
require_once 'vendor/autoload.php';

try {
    $client = new MongoDB\Client('mongodb://localhost:27017');
    $db = $client->veranda;
    $menuCollection = $db->menu;
    
    // Получаем меню из кеша
    $menu = $menuCollection->findOne(['_id' => 'current_menu']);
    if (!$menu || !isset($menu['products'])) {
        echo "Меню не найдено в кеше\n";
        exit;
    }
    
    $products = $menu['products'];
    echo "Всего продуктов в кеше: " . count($products) . "\n";
    
    // Ищем продукт с ID 126
    $product126 = null;
    foreach($products as $product) {
        if (($product['product_id'] ?? $product['id'] ?? '') == '126') {
            $product126 = $product;
            break;
        }
    }
    
    if ($product126) {
        echo "\n=== ПРОДУКТ ID 126 НАЙДЕН В КЕШЕ ===\n";
        echo "Название: " . ($product126['product_name'] ?? $product126['name'] ?? 'N/A') . "\n";
        echo "ID: " . ($product126['product_id'] ?? $product126['id'] ?? 'N/A') . "\n";
        echo "Скрыт: " . ($product126['hidden'] ?? 'N/A') . "\n";
        echo "Spots: " . json_encode($product126['spots'] ?? []) . "\n";
        echo "Категория: " . ($product126['menu_category_id'] ?? 'N/A') . "\n";
    } else {
        echo "\n❌ ПРОДУКТ ID 126 НЕ НАЙДЕН В КЕШЕ\n";
        
        // Проверим все ID продуктов
        echo "\nВсе ID продуктов в кеше:\n";
        $ids = [];
        foreach($products as $product) {
            $id = $product['product_id'] ?? $product['id'] ?? '';
            if ($id) {
                $ids[] = $id;
            }
        }
        sort($ids);
        echo implode(', ', $ids) . "\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
