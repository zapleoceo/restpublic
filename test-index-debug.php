<?php
// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

echo "=== DEBUGGING INDEX.PHP ===\n";

try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        echo "1. MongoDB\Client class exists\n";
        require_once __DIR__ . '/php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        echo "2. Categories count: " . count($categories) . "\n";
        echo "3. Products count: " . count($products) . "\n";
        
        // Group products by category for quick access and sort by popularity
        if ($products) {
            echo "4. Processing products...\n";
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
            
            echo "5. Products grouped by category:\n";
            foreach ($productsByCategory as $catId => $catProducts) {
                echo "    Category $catId: " . count($catProducts) . " products\n";
            }
            
            // Sort products by popularity (visible first, then by sort_order, then by price)
            foreach ($productsByCategory as $categoryId => $categoryProducts) {
                usort($categoryProducts, function($a, $b) {
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
                    
                    // Third: by price (lower price is more popular for basic items)
                    $aPrice = (int)($a['price_normalized'] ?? 0);
                    $bPrice = (int)($b['price_normalized'] ?? 0);
                    
                    return $aPrice <=> $bPrice;
                });
                
                // Take only top 5 most popular products
                $productsByCategory[$categoryId] = array_slice($categoryProducts, 0, 5);
            }
            
            echo "6. After sorting and limiting to top 5:\n";
            foreach ($productsByCategory as $catId => $catProducts) {
                echo "    Category $catId: " . count($catProducts) . " products\n";
                if (count($catProducts) > 0) {
                    echo "      First product: " . ($catProducts[0]['product_name'] ?? 'No name') . "\n";
                }
            }
        }
    } else {
        echo "1. MongoDB\Client class NOT found\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
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

// Check if we have food products
if (isset($productsByCategory['2'])) {
    echo "Food products (category 2): " . count($productsByCategory['2']) . "\n";
    if (count($productsByCategory['2']) > 0) {
        echo "First food product: " . ($productsByCategory['2'][0]['product_name'] ?? 'No name') . "\n";
    }
} else {
    echo "No food products found in category 2\n";
}
?>
