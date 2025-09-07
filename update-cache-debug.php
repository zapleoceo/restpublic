<?php
/**
 * Принудительное обновление кэша с диагностикой
 */

echo "🔄 Принудительное обновление кэша меню с диагностикой...\n\n";

// 1. Проверяем API health
echo "1. Проверка API health...\n";
$health_url = 'https://northrepublic.me:3002/api/health';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($health_url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "✅ API доступен\n";
    echo "   Статус: " . ($data['status'] ?? 'неизвестно') . "\n";
    echo "   Uptime: " . ($data['uptime'] ?? 'неизвестно') . " сек\n";
} else {
    echo "❌ API недоступен\n";
    echo "   Проверьте: pm2 status\n";
    echo "   Перезапустите: pm2 restart northrepublic-backend\n";
    exit(1);
}

// 2. Проверяем API меню
echo "\n2. Проверка API меню...\n";
$menu_url = 'https://northrepublic.me:3002/api/menu';
$response = @file_get_contents($menu_url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    echo "✅ API меню доступен\n";
    echo "   Категории: " . count($data['categories'] ?? []) . "\n";
    echo "   Продукты: " . count($data['products'] ?? []) . "\n";
    
    if (!empty($data['categories'])) {
        echo "   Первые категории:\n";
        foreach (array_slice($data['categories'], 0, 3) as $category) {
            echo "   - " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . "\n";
        }
    }
} else {
    echo "❌ API меню недоступен\n";
    exit(1);
}

// 3. Обновляем кэш
echo "\n3. Обновление кэша...\n";
$update_url = 'https://northrepublic.me:3002/api/cache/update-menu';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $update_url);
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
    echo "✅ Кэш обновлен успешно (HTTP $httpCode)\n";
    $resultData = json_decode($result, true);
    if ($resultData) {
        echo "   Модифицировано записей: " . ($resultData['modifiedCount'] ?? 'неизвестно') . "\n";
    }
} else {
    echo "❌ Ошибка обновления кэша (HTTP $httpCode)\n";
    echo "   Ответ: " . $result . "\n";
}

// 4. Проверяем результат
echo "\n4. Проверка результата...\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/php/classes/MenuCache.php';
    
    $menuCache = new MenuCache();
    $menuData = $menuCache->getMenu();
    
    if ($menuData) {
        echo "✅ Кэш проверен успешно\n";
        echo "   Категории: " . count($menuData['categories']) . "\n";
        echo "   Продукты: " . count($menuData['products']) . "\n";
        
        if (!empty($menuData['categories'])) {
            echo "   Категории в кэше:\n";
            foreach (array_slice($menuData['categories'], 0, 5) as $category) {
                echo "   - " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . "\n";
            }
        }
    } else {
        echo "❌ Кэш пуст после обновления\n";
    }
} catch (Exception $e) {
    echo "❌ Ошибка проверки кэша: " . $e->getMessage() . "\n";
}

// 5. Тест загрузки данных
echo "\n5. Тест загрузки данных...\n";
$categories = [];
$products = [];

try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
    }
} catch (Exception $e) {
    echo "❌ Ошибка загрузки данных: " . $e->getMessage() . "\n";
}

echo "   Категории загружены: " . count($categories) . "\n";
echo "   Продукты загружены: " . count($products) . "\n";

if (count($categories) > 0 && count($products) > 0) {
    echo "✅ Данные загружаются корректно\n";
} else {
    echo "❌ Проблема с загрузкой данных\n";
}

echo "\n🎉 Обновление завершено!\n";
echo "Теперь проверьте:\n";
echo "- Главная страница: https://northrepublic.me/\n";
echo "- Страница меню: https://northrepublic.me/menu.php\n";
echo "- Отладка: https://northrepublic.me/debug-menu.php\n";
?>
