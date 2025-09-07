<?php
/**
 * Принудительное обновление кэша меню
 */

echo "🔄 Принудительное обновление кэша меню...\n";

// 1. Обновляем через API
echo "1. Запуск обновления через API...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://northrepublic.me:3002/api/cache/update-menu');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ API обновление успешно (HTTP $httpCode)\n";
} else {
    echo "❌ API обновление неудачно (HTTP $httpCode)\n";
}

// 2. Проверяем результат
echo "2. Проверка результата...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/php/classes/MenuCache.php';
    
    $menuCache = new MenuCache();
    $menuData = $menuCache->getMenu();
    
    if ($menuData) {
        echo "✅ Кэш обновлен успешно\n";
        echo "📊 Категории: " . count($menuData['categories']) . "\n";
        echo "📊 Продукты: " . count($menuData['products']) . "\n";
        
        // Показываем первые категории
        echo "\n📋 Категории:\n";
        foreach (array_slice($menuData['categories'], 0, 5) as $category) {
            echo "- " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . "\n";
        }
    } else {
        echo "❌ Кэш пуст после обновления\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка проверки: " . $e->getMessage() . "\n";
}

echo "\n🎉 Обновление завершено!\n";
?>
