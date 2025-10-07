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
    $categories = $menu['categories'] ?? [];
    
    echo "=== СТАТИСТИКА КЕША ===\n";
    echo "Всего продуктов в кеше: " . count($products) . "\n";
    echo "Всего категорий в кеше: " . count($categories) . "\n";
    
    // Проверяем категории
    echo "\n=== КАТЕГОРИИ ===\n";
    foreach($categories as $category) {
        $categoryId = $category['category_id'] ?? $category['id'] ?? 'N/A';
        $categoryName = $category['category_name'] ?? $category['name'] ?? 'N/A';
        echo "ID: {$categoryId}, Название: {$categoryName}\n";
    }
    
    // Проверяем продукты по категориям
    echo "\n=== ПРОДУКТЫ ПО КАТЕГОРИЯМ ===\n";
    $productsByCategory = [];
    foreach($products as $product) {
        $categoryId = $product['menu_category_id'] ?? '';
        if (!isset($productsByCategory[$categoryId])) {
            $productsByCategory[$categoryId] = [];
        }
        $productsByCategory[$categoryId][] = $product;
    }
    
    foreach($productsByCategory as $categoryId => $categoryProducts) {
        $categoryName = 'Неизвестная категория';
        foreach($categories as $category) {
            if (($category['category_id'] ?? $category['id'] ?? '') == $categoryId) {
                $categoryName = $category['category_name'] ?? $category['name'] ?? 'N/A';
                break;
            }
        }
        echo "\nКатегория {$categoryId} ({$categoryName}): " . count($categoryProducts) . " продуктов\n";
        
        // Показываем первые 3 продукта из каждой категории
        $count = 0;
        foreach($categoryProducts as $product) {
            if ($count >= 3) break;
            $productName = $product['product_name'] ?? $product['name'] ?? 'N/A';
            $productId = $product['product_id'] ?? $product['id'] ?? 'N/A';
            $hidden = $product['hidden'] ?? 'N/A';
            $visible = 'N/A';
            if (isset($product['spots']) && is_array($product['spots'])) {
                foreach($product['spots'] as $spot) {
                    if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                        $visible = 'Да';
                        break;
                    }
                }
            }
            echo "  - ID: {$productId}, Название: {$productName}, Скрыт: {$hidden}, Видимый: {$visible}\n";
            $count++;
        }
        if (count($categoryProducts) > 3) {
            echo "  ... и еще " . (count($categoryProducts) - 3) . " продуктов\n";
        }
    }
    
    // Проверяем скрытые продукты
    echo "\n=== СКРЫТЫЕ ПРОДУКТЫ ===\n";
    $hiddenProducts = [];
    foreach($products as $product) {
        if (($product['hidden'] ?? '') === '1') {
            $hiddenProducts[] = $product;
        }
    }
    echo "Скрытых продуктов: " . count($hiddenProducts) . "\n";
    
    // Проверяем продукты без видимых spots
    echo "\n=== ПРОДУКТЫ БЕЗ ВИДИМЫХ SPOTS ===\n";
    $invisibleProducts = [];
    foreach($products as $product) {
        $hasVisibleSpot = false;
        if (isset($product['spots']) && is_array($product['spots'])) {
            foreach($product['spots'] as $spot) {
                if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                    $hasVisibleSpot = true;
                    break;
                }
            }
        }
        if (!$hasVisibleSpot) {
            $invisibleProducts[] = $product;
        }
    }
    echo "Продуктов без видимых spots: " . count($invisibleProducts) . "\n";
    
    if (count($invisibleProducts) > 0) {
        echo "Примеры:\n";
        $count = 0;
        foreach($invisibleProducts as $product) {
            if ($count >= 5) break;
            $productName = $product['product_name'] ?? $product['name'] ?? 'N/A';
            $productId = $product['product_id'] ?? $product['id'] ?? 'N/A';
            echo "  - ID: {$productId}, Название: {$productName}\n";
            $count++;
        }
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
