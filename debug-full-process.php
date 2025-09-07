<?php
// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

echo "=== DEBUGGING MINI-MENU PROCESS ===\n";

try {
    echo "1. Loading autoload.php...\n";
    require_once __DIR__ . '/vendor/autoload.php';
    
    echo "2. Checking MongoDB\Client class...\n";
    if (class_exists('MongoDB\Client')) {
        echo "   ✓ MongoDB\Client class exists\n";
        
        echo "3. Loading MenuCache class...\n";
        require_once __DIR__ . '/php/classes/MenuCache.php';
        
        echo "4. Creating MenuCache instance...\n";
        $menuCache = new MenuCache();
        
        echo "5. Getting menu data...\n";
        $menuData = $menuCache->getMenu();
        
        if ($menuData) {
            echo "   ✓ Menu data loaded successfully\n";
            $categories = $menuData['categories'] ?? [];
            $products = $menuData['products'] ?? [];
            
            echo "6. Categories count: " . count($categories) . "\n";
            echo "7. Products count: " . count($products) . "\n";
            echo "8. Categories type: " . gettype($categories) . "\n";
            echo "9. Products type: " . gettype($products) . "\n";
            
            if (count($categories) > 0) {
                echo "10. First category: " . ($categories[0]['category_name'] ?? 'No name') . "\n";
            }
            
            // Group products by category for quick access and sort by popularity
            if ($products) {
                echo "11. Processing products...\n";
                foreach ($products as $product) {
                    $categoryId = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                    if (!isset($productsByCategory[$categoryId])) {
                        $productsByCategory[$categoryId] = [];
                    }
                    
                    // Check if product is visible
                    $isVisible = true;
                    if (isset($product['spots']) && is_array($product['spots'])) {
                        foreach ($product['spots'] as $spot) {
                            if (isset($spot['visible']) && $spot['visible'] == '0') {
                                $isVisible = false;
                                break;
                            }
                        }
                    }
                    
                    // Only add visible products
                    if ($isVisible) {
                        $productsByCategory[$categoryId][] = $product;
                    }
                }
                
                echo "12. Products grouped by category:\n";
                foreach ($productsByCategory as $catId => $catProducts) {
                    echo "    Category $catId: " . count($catProducts) . " products\n";
                }
            }
        } else {
            echo "   ✗ No menu data returned\n";
        }
    } else {
        echo "   ✗ MongoDB\Client class not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FINAL STATE ===\n";
echo "Categories count: " . count($categories) . "\n";
echo "Products count: " . count($products) . "\n";
echo "ProductsByCategory count: " . count($productsByCategory) . "\n";
echo "Categories empty: " . (empty($categories) ? 'YES' : 'NO') . "\n";

if (empty($categories)) {
    echo "❌ PROBLEM: Categories is empty - this will show error message\n";
} else {
    echo "✅ SUCCESS: Categories loaded - mini-menu should work\n";
}
?>
