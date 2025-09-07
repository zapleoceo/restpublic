<?php
// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        echo "DEBUG: Categories count: " . count($categories) . "\n";
        echo "DEBUG: Products count: " . count($products) . "\n";
        echo "DEBUG: Categories type: " . gettype($categories) . "\n";
        echo "DEBUG: Products type: " . gettype($products) . "\n";
        
        if (count($categories) > 0) {
            echo "DEBUG: First category: " . ($categories[0]['category_name'] ?? 'No name') . "\n";
        }
        
        // Check if categories is empty
        if (empty($categories)) {
            echo "ERROR: Categories is empty!\n";
        } else {
            echo "SUCCESS: Categories loaded successfully\n";
        }
    } else {
        echo "ERROR: MongoDB\Client not found\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
