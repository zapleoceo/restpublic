<?php
require_once 'php/classes/MenuCache.php';

try {
    $cache = new MenuCache();
    $data = $cache->getMenu();
    
    echo "MenuCache result: " . ($data ? 'SUCCESS' : 'NULL') . "\n";
    echo "Categories: " . count($data['categories'] ?? []) . "\n";
    echo "Products: " . count($data['products'] ?? []) . "\n";
    
    if ($data && !empty($data['categories'])) {
        echo "First category: " . ($data['categories'][0]['category_name'] ?? $data['categories'][0]['name'] ?? 'no name') . "\n";
        echo "First category ID: " . ($data['categories'][0]['category_id'] ?? 'no id') . "\n";
    }
    
    if ($data && !empty($data['products'])) {
        echo "First product: " . ($data['products'][0]['product_name'] ?? $data['products'][0]['name'] ?? 'no name') . "\n";
        echo "First product category ID: " . ($data['products'][0]['menu_category_id'] ?? $data['products'][0]['category_id'] ?? 'no id') . "\n";
    }
    
} catch (Exception $e) {
    echo "MenuCache error: " . $e->getMessage() . "\n";
}
?>
