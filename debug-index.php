<?php
require_once 'vendor/autoload.php';

echo "=== DEBUG INDEX.PHP ===\n";

// Проверяем MongoDB расширение
if (class_exists('MongoDB\Client')) {
    echo "✅ MongoDB extension is loaded\n";
} else {
    echo "❌ MongoDB extension is NOT loaded\n";
    exit;
}

// Проверяем MenuCache класс
try {
    require_once 'php/classes/MenuCache.php';
    echo "✅ MenuCache class loaded\n";
} catch (Exception $e) {
    echo "❌ MenuCache class error: " . $e->getMessage() . "\n";
    exit;
}

// Проверяем подключение к MongoDB
try {
    $cache = new MenuCache();
    echo "✅ MenuCache created\n";
} catch (Exception $e) {
    echo "❌ MenuCache creation error: " . $e->getMessage() . "\n";
    exit;
}

// Проверяем получение данных
try {
    $menu = $cache->getMenu();
    echo "✅ Menu data retrieved\n";
    echo "Categories: " . count($menu['categories'] ?? []) . "\n";
    echo "Products: " . count($menu['products'] ?? []) . "\n";
    
    if (!empty($menu['categories'])) {
        echo "First category: " . ($menu['categories'][0]['category_name'] ?? 'No name') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Menu data error: " . $e->getMessage() . "\n";
}

echo "=== END DEBUG ===\n";
?>
