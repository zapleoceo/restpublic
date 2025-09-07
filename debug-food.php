<?php
require_once 'vendor/autoload.php';
require_once 'php/classes/MenuCache.php';

$cache = new MenuCache();
$data = $cache->getMenu();

if ($data && isset($data['products'])) {
    $foodProducts = array_filter($data['products'], function($p) { 
        return ($p['menu_category_id'] ?? $p['category_id'] ?? '') == '2'; 
    });
    
    echo "=== FOOD PRODUCTS (Category ID: 2) ===\n";
    foreach (array_slice($foodProducts, 0, 15) as $product) {
        echo $product['product_name'] . ' - sort_order: ' . ($product['sort_order'] ?? 'null') . ' - price: ' . ($product['price_normalized'] ?? 'null') . "\n";
    }
    
    echo "\n=== SORTING BY POPULARITY ===\n";
    usort($foodProducts, function($a, $b) {
        // First: visible products
        $aVisible = isset($a['spots']) ? $a['spots'][0]['visible'] ?? '1' : '1';
        $bVisible = isset($b['spots']) ? $b['spots'][0]['visible'] ?? '1' : '1';
        
        if ($aVisible != $bVisible) {
            return $bVisible <=> $aVisible; // visible first
        }
        
        // Second: sort_order (lower is more popular)
        $aSort = (int)($a['sort_order'] ?? 999);
        $bSort = (int)($b['sort_order'] ?? 999);
        
        if ($aSort != $bSort) {
            return $aSort <=> $bSort;
        }
        
        // Third: by price (lower price is more popular for basic items)
        $aPrice = (int)($a['price_normalized'] ?? 0);
        $bPrice = (int)($b['price_normalized'] ?? 0);
        
        return $aPrice <=> $bPrice;
    });
    
    echo "TOP 5 POPULAR FOOD ITEMS:\n";
    foreach (array_slice($foodProducts, 0, 5) as $product) {
        echo $product['product_name'] . ' - sort_order: ' . ($product['sort_order'] ?? 'null') . ' - price: ' . ($product['price_normalized'] ?? 'null') . "\n";
    }
}
?>
