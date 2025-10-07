<?php
require_once 'vendor/autoload.php';

try {
    // Проверяем, что возвращает Poster API напрямую
    $posterToken = '922371:489411264005b482039f38b8ee21f6fb';
    $posterUrl = "https://joinposter.com/api/menu.getProducts?token={$posterToken}";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'method' => 'GET'
        ]
    ]);
    
    echo "Запрашиваем продукты из Poster API...\n";
    $posterResponse = @file_get_contents($posterUrl, false, $context);
    
    if ($posterResponse === false) {
        echo "Ошибка получения данных из Poster API\n";
        exit(1);
    }
    
    $posterData = json_decode($posterResponse, true);
    $products = $posterData['response'] ?? [];
    
    echo "Всего продуктов в Poster API: " . count($products) . "\n";
    
    // Проверяем статистику по видимости
    $visibleCount = 0;
    $hiddenCount = 0;
    $noSpotsCount = 0;
    $visibleSpotsCount = 0;
    
    foreach($products as $product) {
        if (($product['hidden'] ?? '') === '1') {
            $hiddenCount++;
        } else {
            $visibleCount++;
        }
        
        if (isset($product['spots']) && is_array($product['spots'])) {
            $hasVisibleSpot = false;
            foreach($product['spots'] as $spot) {
                if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                    $hasVisibleSpot = true;
                    $visibleSpotsCount++;
                    break;
                }
            }
            if (!$hasVisibleSpot) {
                $noSpotsCount++;
            }
        } else {
            $noSpotsCount++;
        }
    }
    
    echo "\n=== СТАТИСТИКА POSTER API ===\n";
    echo "Видимых продуктов (не скрытых): {$visibleCount}\n";
    echo "Скрытых продуктов: {$hiddenCount}\n";
    echo "Продуктов без spots: {$noSpotsCount}\n";
    echo "Продуктов с видимыми spots: {$visibleSpotsCount}\n";
    
    // Показываем примеры продуктов с видимыми spots
    echo "\n=== ПРИМЕРЫ ПРОДУКТОВ С ВИДИМЫМИ SPOTS ===\n";
    $count = 0;
    foreach($products as $product) {
        if ($count >= 10) break;
        
        if (($product['hidden'] ?? '') !== '1' && isset($product['spots']) && is_array($product['spots'])) {
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
    
    // Показываем примеры продуктов без видимых spots
    echo "\n=== ПРИМЕРЫ ПРОДУКТОВ БЕЗ ВИДИМЫХ SPOTS ===\n";
    $count = 0;
    foreach($products as $product) {
        if ($count >= 10) break;
        
        if (($product['hidden'] ?? '') !== '1') {
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
                $productName = $product['product_name'] ?? 'N/A';
                $productId = $product['product_id'] ?? 'N/A';
                $categoryId = $product['menu_category_id'] ?? 'N/A';
                $spots = $product['spots'] ?? [];
                echo "ID: {$productId}, Название: {$productName}, Категория: {$categoryId}, Spots: " . json_encode($spots) . "\n";
                $count++;
            }
        }
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
