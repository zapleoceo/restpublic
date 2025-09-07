<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/php/classes/MenuCache.php';

$cache = new MenuCache();
$data = $cache->getMenu();

if ($data) {
    echo "Categories: " . count($data['categories']) . "\n";
    echo "Products: " . count($data['products']) . "\n";
    
    // Check if we have food category
    $foodCategory = null;
    foreach ($data['categories'] as $category) {
        if (($category['category_id'] ?? '') == '2') {
            $foodCategory = $category;
            break;
        }
    }
    
    if ($foodCategory) {
        echo "Food category found: " . ($foodCategory['category_name'] ?? 'No name') . "\n";
        
        // Count food products
        $foodProducts = 0;
        foreach ($data['products'] as $product) {
            if (($product['menu_category_id'] ?? $product['category_id'] ?? '') == '2') {
                $foodProducts++;
            }
        }
        echo "Food products: " . $foodProducts . "\n";
    } else {
        echo "Food category not found\n";
    }
} else {
    echo "No data found\n";
}
?>
