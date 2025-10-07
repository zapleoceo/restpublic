<?php
require_once 'vendor/autoload.php';

try {
    // Проверяем, что возвращает Poster API напрямую для продукта 126
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
    
    // Ищем продукт с ID 126
    $product126 = null;
    foreach($products as $product) {
        if (($product['product_id'] ?? $product['id'] ?? '') == '126') {
            $product126 = $product;
            break;
        }
    }
    
    if ($product126) {
        echo "\n=== ПРОДУКТ ID 126 В POSTER API ===\n";
        echo "Название: " . ($product126['product_name'] ?? $product126['name'] ?? 'N/A') . "\n";
        echo "ID: " . ($product126['product_id'] ?? $product126['id'] ?? 'N/A') . "\n";
        echo "Скрыт: " . ($product126['hidden'] ?? 'N/A') . "\n";
        echo "Spots: " . json_encode($product126['spots'] ?? []) . "\n";
        echo "Категория: " . ($product126['menu_category_id'] ?? 'N/A') . "\n";
        
        // Проверяем фильтрацию
        echo "\n=== АНАЛИЗ ФИЛЬТРАЦИИ ===\n";
        $hidden = $product126['hidden'] ?? '0';
        $spots = $product126['spots'] ?? [];
        
        echo "Hidden: $hidden\n";
        echo "Spots: " . json_encode($spots) . "\n";
        
        if ($hidden === "1") {
            echo "❌ Продукт скрыт (hidden = 1)\n";
        } else {
            echo "✅ Продукт не скрыт (hidden = 0)\n";
        }
        
        if (is_array($spots) && !empty($spots)) {
            $hasVisibleSpot = false;
            foreach($spots as $spot) {
                if (($spot['visible'] ?? '0') === "1" || ($spot['visible'] ?? 0) === 1) {
                    $hasVisibleSpot = true;
                    break;
                }
            }
            if ($hasVisibleSpot) {
                echo "✅ Есть видимый spot\n";
            } else {
                echo "❌ Нет видимых spots\n";
            }
        } else {
            echo "❌ Нет spots\n";
        }
        
    } else {
        echo "\n❌ ПРОДУКТ ID 126 НЕ НАЙДЕН В POSTER API\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
