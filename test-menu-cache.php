<?php
require_once 'vendor/autoload.php';
require_once 'php/classes/MenuCache.php';

echo "Testing MenuCache...\n";

try {
    $cache = new MenuCache();
    echo "✅ MenuCache created\n";
    
    $menu = $cache->getMenu();
    echo "✅ Menu retrieved\n";
    
    echo "Categories: " . count($menu['categories'] ?? []) . "\n";
    echo "Products: " . count($menu['products'] ?? []) . "\n";
    
    if (!empty($menu['categories'])) {
        echo "First category: " . ($menu['categories'][0]['category_name'] ?? 'No name') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
