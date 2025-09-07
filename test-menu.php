<?php
require_once 'vendor/autoload.php';
require_once 'php/classes/MenuCache.php';

$cache = new MenuCache();
$data = $cache->getMenu();

if ($data) {
    echo "Categories: " . count($data['categories']) . "\n";
    echo "Products: " . count($data['products']) . "\n";
    
    // Check first category
    if (count($data['categories']) > 0) {
        $firstCategory = $data['categories'][0];
        echo "First category: " . ($firstCategory['category_name'] ?? 'No name') . "\n";
        echo "Category ID: " . ($firstCategory['category_id'] ?? 'No ID') . "\n";
    }
    
    // Check food products
    $foodProducts = 0;
    foreach ($data['products'] as $product) {
        $categoryId = $product['menu_category_id'] ?? $product['category_id'] ?? '';
        if ($categoryId == '2') {
            $foodProducts++;
        }
    }
    echo "Food products: " . $foodProducts . "\n";
} else {
    echo "No data found\n";
}
?>
