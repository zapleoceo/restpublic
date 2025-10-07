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
        
        // Проверяем, что происходит с фильтрацией
        echo "\n=== ПРОВЕРКА ФИЛЬТРАЦИИ ===\n";
        
        // Проверяем, не скрыт ли продукт
        if (($product126['hidden'] ?? '') === '1') {
            echo "❌ Продукт скрыт (hidden = 1)\n";
        } else {
            echo "✅ Продукт не скрыт (hidden = " . ($product126['hidden'] ?? '0') . ")\n";
        }
        
        // Проверяем видимость в spots
        if (isset($product126['spots']) && is_array($product126['spots'])) {
            $hasVisibleSpot = false;
            foreach($product126['spots'] as $spot) {
                if (($spot['visible'] ?? '') === '1' || ($spot['visible'] ?? '') === 1) {
                    $hasVisibleSpot = true;
                    break;
                }
            }
            
            if ($hasVisibleSpot) {
                echo "✅ Продукт имеет видимый spot\n";
            } else {
                echo "❌ Продукт не имеет видимых spots\n";
            }
        } else {
            echo "⚠️ У продукта нет spots\n";
        }
        
        // Проверяем категорию
        $categoryId = $product126['menu_category_id'] ?? '';
        echo "Категория ID: " . $categoryId . "\n";
        
        // Проверяем, есть ли категория в кеше
        if (isset($menu['categories'])) {
            $categoryFound = false;
            foreach($menu['categories'] as $category) {
                if (($category['category_id'] ?? $category['id'] ?? '') == $categoryId) {
                    $categoryFound = true;
                    echo "✅ Категория найдена: " . ($category['category_name'] ?? $category['name'] ?? 'N/A') . "\n";
                    break;
                }
            }
            
            if (!$categoryFound) {
                echo "❌ Категория не найдена в кеше\n";
            }
        } else {
            echo "⚠️ Категории не найдены в кеше\n";
        }
        
    } else {
        echo "\n❌ ПРОДУКТ ID 126 НЕ НАЙДЕН В КЕШЕ\n";
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
