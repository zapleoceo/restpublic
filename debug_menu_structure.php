<?php
require_once 'classes/MenuCache.php';

$menuCache = new MenuCache();

echo "=== DEBUG MENU STRUCTURE ===\n";

$menu = $menuCache->getMenu(30);
if ($menu) {
    echo "Структура кэша меню:\n";
    foreach ($menu as $key => $value) {
        if ($key === 'categories' && is_array($value)) {
            echo "\n📁 КАТЕГОРИИ:\n";
            foreach ($value as $category) {
                echo "   - " . ($category['name'] ?? 'Без названия') . " (ID: " . ($category['id'] ?? 'N/A') . ")\n";
            }
        } elseif ($key === 'products' && is_array($value)) {
            echo "\n🍽️ ПРОДУКТЫ (первые 5):\n";
            $count = 0;
            foreach ($value as $product) {
                if ($count >= 5) break;
                echo "   - " . ($product['product_name'] ?? $product['name'] ?? 'Без названия') . 
                     " (Категория: " . ($product['category_name'] ?? 'N/A') . ")\n";
                $count++;
            }
        }
    }
} else {
    echo "❌ Кэш не найден\n";
}
?>
