<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing index.php logic...\n";

// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "Autoload loaded\n";
    
    if (class_exists('MongoDB\Client')) {
        echo "MongoDB\Client class exists\n";
        require_once __DIR__ . '/php/classes/MenuCache.php';
        echo "MenuCache.php loaded\n";
        
        $menuCache = new MenuCache();
        echo "MenuCache instance created\n";
        
        $menuData = $menuCache->getMenu();
        echo "Menu data retrieved: " . ($menuData ? 'SUCCESS' : 'NULL') . "\n";
        
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        echo "Categories count: " . count($categories) . "\n";
        echo "Products count: " . count($products) . "\n";
        
        // Group products by category for quick access
        if ($products) {
            foreach ($products as $product) {
                $categoryId = String($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                if (!isset($productsByCategory[$categoryId])) {
                    $productsByCategory[$categoryId] = [];
                }
                $productsByCategory[$categoryId][] = $product;
            }
        }
        
        echo "Products grouped by category: " . count($productsByCategory) . " categories\n";
    } else {
        echo "MongoDB\Client class does not exist\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed\n";
?>
