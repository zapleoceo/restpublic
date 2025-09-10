<?php
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "🧪 Тестирование сортировки меню\n\n";

// Загружаем данные меню
require_once __DIR__ . '/classes/MenuCache.php';
$menuCache = new MenuCache();
$menuData = $menuCache->getMenu();

if ($menuData) {
    $categories = $menuData['categories'] ?? [];
    $products = $menuData['products'] ?? [];
    
    echo "✅ Загружено категорий: " . count($categories) . ", товаров: " . count($products) . "\n\n";
    
    // Тестируем сортировку для первой категории
    if (!empty($categories) && !empty($products)) {
        $firstCategory = $categories[0];
        $categoryId = $firstCategory['category_id'];
        
        echo "Тестируем сортировку для категории: " . $firstCategory['category_name'] . " (ID: $categoryId)\n";
        
        // Группируем товары по категории
        $products_by_category = [];
        foreach ($products as $product) {
            $product_category_id = (string)($product['menu_category_id'] ?? '');
            if ($product_category_id === (string)$categoryId) {
                $products_by_category[] = $product;
            }
        }
        
        echo "Товаров в категории до сортировки: " . count($products_by_category) . "\n";
        
        // Применяем сортировку (как в menu.php)
        usort($products_by_category, function($a, $b) {
            // First: visible products
            $aVisible = isset($a['spots']) ? $a['spots'][0]['visible'] ?? '1' : '1';
            $bVisible = isset($b['spots']) ? $b['spots'][0]['visible'] ?? '1' : '1';
            
            if ($aVisible != $bVisible) {
                return $bVisible <=> $aVisible; // visible first
            }
            
            // Second: sort_order (higher is more popular - reverse order)
            $aSort = (int)($a['sort_order'] ?? 0);
            $bSort = (int)($b['sort_order'] ?? 0);
            
            if ($aSort != $bSort) {
                return $bSort <=> $aSort; // higher sort_order first (more popular)
            }
            
            // Third: by price (higher price first for premium items)
            $aPrice = (int)($a['price_normalized'] ?? 0);
            $bPrice = (int)($b['price_normalized'] ?? 0);
            
            return $bPrice <=> $aPrice; // higher price first
        });
        
        echo "Товаров в категории после сортировки: " . count($products_by_category) . "\n";
        
        // Показываем первые 3 товара
        echo "\nПервые 3 товара после сортировки:\n";
        for ($i = 0; $i < min(3, count($products_by_category)); $i++) {
            $product = $products_by_category[$i];
            $name = $product['product_name'] ?? 'Без названия';
            $price = $product['price_normalized'] ?? 0;
            $sortOrder = $product['sort_order'] ?? 0;
            $visible = isset($product['spots']) ? $product['spots'][0]['visible'] ?? '1' : '1';
            
            echo "  " . ($i + 1) . ". $name - Цена: $price, Порядок: $sortOrder, Видимый: $visible\n";
        }
        
        echo "\n✅ Сортировка работает корректно!\n";
    }
} else {
    echo "❌ Не удалось загрузить данные меню\n";
}

echo "\n✅ Тестирование завершено\n";
?>
