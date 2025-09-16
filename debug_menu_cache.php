<?php
require_once 'classes/MenuCache.php';

$menuCache = new MenuCache();

echo "=== DEBUG MENU CACHE ===\n";

// Проверяем, есть ли кэш
echo "1. Проверяем кэш меню:\n";
$menu = $menuCache->getMenu(30);
if ($menu) {
    echo "   ✅ Кэш найден\n";
    echo "   📊 Количество категорий: " . count($menu) . "\n";
    
    foreach ($menu as $categoryId => $products) {
        if (is_array($products) || $products instanceof Countable) {
            echo "   📁 Категория $categoryId: " . count($products) . " продуктов\n";
            if (!empty($products) && is_array($products)) {
                $firstProduct = $products[0];
                echo "      🍽️ Первый продукт: " . ($firstProduct['product_name'] ?? $firstProduct['name'] ?? 'Без названия') . "\n";
            }
        } else {
            echo "   📁 Категория $categoryId: " . gettype($products) . " (не массив)\n";
        }
    }
} else {
    echo "   ❌ Кэш не найден\n";
}

echo "\n2. Проверяем продукты для категории 'Еда':\n";
$foodProducts = $menuCache->getProductsByCategory('Еда', 5, 'ru');
echo "   📊 Найдено продуктов: " . count($foodProducts) . "\n";

if (!empty($foodProducts)) {
    foreach ($foodProducts as $product) {
        echo "   🍽️ " . ($product['product_name'] ?? $product['name'] ?? 'Без названия') . "\n";
    }
} else {
    echo "   ❌ Продукты не найдены\n";
}

echo "\n3. Проверяем время последнего обновления:\n";
$lastUpdate = $menuCache->getLastUpdateTimeFormatted();
echo "   🕐 Последнее обновление: " . ($lastUpdate ?? 'Неизвестно') . "\n";

echo "\n4. Проверяем, нужно ли обновление:\n";
$needsUpdate = $menuCache->needsUpdate(30);
echo "   🔄 Нужно обновление: " . ($needsUpdate ? 'ДА' : 'НЕТ') . "\n";
?>
