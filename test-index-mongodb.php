<?php
require_once 'vendor/autoload.php';

echo "Testing MongoDB connection from index.php context...\n";

try {
    if (class_exists('MongoDB\Client')) {
        echo "✅ MongoDB extension is loaded\n";
        
        require_once __DIR__ . '/php/classes/MenuCache.php';
        echo "✅ MenuCache class loaded\n";
        
        $menuCache = new MenuCache();
        echo "✅ MenuCache created\n";
        
        $menuData = $menuCache->getMenu();
        echo "✅ Menu data retrieved\n";
        
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        echo "Categories: " . count($categories) . "\n";
        echo "Products: " . count($products) . "\n";
        
        if (!empty($categories)) {
            echo "First category: " . ($categories[0]['category_name'] ?? 'No name') . "\n";
        }
        
    } else {
        echo "❌ MongoDB extension is NOT loaded\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
