<?php
require_once 'vendor/autoload.php';

try {
    echo "=== ПРОВЕРКА НАШЕГО API ===\n";
    echo "Запрашиваем продукты из нашего API...\n";
    
    $apiUrl = "http://localhost:3003/api/menu";
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'method' => 'GET'
        ]
    ]);
    
    $apiResponse = @file_get_contents($apiUrl, false, $context);
    
    if ($apiResponse === false) {
        echo "Ошибка получения данных из нашего API\n";
        exit(1);
    }
    
    $apiData = json_decode($apiResponse, true);
    $products = $apiData['products'] ?? [];
    
    echo "Всего продуктов в нашем API: " . count($products) . "\n";
    
    // Проверяем статистику по видимости
    $visibleCount = 0;
    $hiddenCount = 0;
    $noSpotsCount = 0;
    $visibleSpotsCount = 0;
    $hiddenSpotsCount = 0;
    
    foreach($products as $product) {
        if (($product['hidden'] ?? '') === '1') {
            $hiddenCount++;
        } else {
            $visibleCount++;
        }
        
        if (isset($product['spots']) && is_array($product['spots']) && count($product['spots']) > 0) {
            $hasVisibleSpot = false;
            $hasHiddenSpot = false;
            foreach($product['spots'] as $spot) {
                if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                    $hasVisibleSpot = true;
                } else {
                    $hasHiddenSpot = true;
                }
            }
            if ($hasVisibleSpot) {
                $visibleSpotsCount++;
            }
            if ($hasHiddenSpot) {
                $hiddenSpotsCount++;
            }
        } else {
            $noSpotsCount++;
        }
    }
    
    echo "\n=== СТАТИСТИКА НАШЕГО API ===\n";
    echo "Видимых продуктов (не скрытых): {$visibleCount}\n";
    echo "Скрытых продуктов: {$hiddenCount}\n";
    echo "Продуктов без spots: {$noSpotsCount}\n";
    echo "Продуктов с видимыми spots: {$visibleSpotsCount}\n";
    echo "Продуктов с скрытыми spots: {$hiddenSpotsCount}\n";
    
    // Показываем примеры скрытых продуктов
    echo "\n=== ПРИМЕРЫ СКРЫТЫХ ПРОДУКТОВ ===\n";
    $count = 0;
    foreach($products as $product) {
        if ($count >= 5) break;
        if (($product['hidden'] ?? '') === '1') {
            $productName = $product['product_name'] ?? 'N/A';
            $productId = $product['product_id'] ?? 'N/A';
            $categoryId = $product['menu_category_id'] ?? 'N/A';
            echo "ID: {$productId}, Название: {$productName}, Категория: {$categoryId}\n";
            $count++;
        }
    }
    
    // Показываем примеры продуктов с видимыми spots
    echo "\n=== ПРИМЕРЫ ПРОДУКТОВ С ВИДИМЫМИ SPOTS ===\n";
    $count = 0;
    foreach($products as $product) {
        if ($count >= 10) break;
        if (($product['hidden'] ?? '') !== '1' && isset($product['spots']) && is_array($product['spots']) && count($product['spots']) > 0) {
            $hasVisibleSpot = false;
            foreach($product['spots'] as $spot) {
                if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                    $hasVisibleSpot = true;
                    break;
                }
            }
            
            if ($hasVisibleSpot) {
                $productName = $product['product_name'] ?? 'N/A';
                $productId = $product['product_id'] ?? 'N/A';
                $categoryId = $product['menu_category_id'] ?? 'N/A';
                echo "ID: {$productId}, Название: {$productName}, Категория: {$categoryId}\n";
                $count++;
            }
        }
    }
    
    // Показываем примеры продуктов с скрытыми spots
    echo "\n=== ПРИМЕРЫ ПРОДУКТОВ С СКРЫТЫМИ SPOTS ===\n";
    $count = 0;
    foreach($products as $product) {
        if ($count >= 10) break;
        if (($product['hidden'] ?? '') !== '1' && isset($product['spots']) && is_array($product['spots']) && count($product['spots']) > 0) {
            $hasVisibleSpot = false;
            $hasHiddenSpot = false;
            foreach($product['spots'] as $spot) {
                if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                    $hasVisibleSpot = true;
                } else {
                    $hasHiddenSpot = true;
                }
            }
            
            if (!$hasVisibleSpot && $hasHiddenSpot) {
                $productName = $product['product_name'] ?? 'N/A';
                $productId = $product['product_id'] ?? 'N/A';
                $categoryId = $product['menu_category_id'] ?? 'N/A';
                $spots = $product['spots'] ?? [];
                echo "ID: {$productId}, Название: {$productName}, Категория: {$categoryId}, Spots: " . json_encode($spots) . "\n";
                $count++;
            }
        }
    }
    
    // Показываем примеры продуктов без spots
    echo "\n=== ПРИМЕРЫ ПРОДУКТОВ БЕЗ SPOTS ===\n";
    $count = 0;
    foreach($products as $product) {
        if ($count >= 10) break;
        if (($product['hidden'] ?? '') !== '1' && (!isset($product['spots']) || !is_array($product['spots']) || count($product['spots']) === 0)) {
            $productName = $product['product_name'] ?? 'N/A';
            $productId = $product['product_id'] ?? 'N/A';
            $categoryId = $product['menu_category_id'] ?? 'N/A';
            echo "ID: {$productId}, Название: {$productName}, Категория: {$categoryId}\n";
            $count++;
        }
    }
    
    // Ищем продукт ID 126
    echo "\n=== ПОИСК ПРОДУКТА ID 126 ===\n";
    $product126 = null;
    foreach($products as $product) {
        if (($product['product_id'] ?? $product['id'] ?? '') == '126') {
            $product126 = $product;
            break;
        }
    }
    
    if ($product126) {
        echo "✅ Продукт ID 126 найден в нашем API\n";
        echo "Название: " . ($product126['product_name'] ?? 'N/A') . "\n";
        echo "ID: " . ($product126['product_id'] ?? 'N/A') . "\n";
        echo "Скрыт: " . ($product126['hidden'] ?? 'N/A') . "\n";
        echo "Spots: " . json_encode($product126['spots'] ?? 'N/A') . "\n";
        echo "Категория: " . ($product126['menu_category_id'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Продукт ID 126 не найден в нашем API\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
