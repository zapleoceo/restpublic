<?php
// Временный файл для диагностики меню
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Menu Debug</h1>";

// Проверяем MongoDB
echo "<h2>MongoDB Connection Test</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        echo "✅ MongoDB Client доступен<br>";
        
        require_once __DIR__ . '/php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        echo "✅ MenuCache создан<br>";
        
        $menuData = $menuCache->getMenu();
        if ($menuData) {
            echo "✅ Меню загружено из кэша<br>";
            echo "Категории: " . count($menuData['categories']) . "<br>";
            echo "Продукты: " . count($menuData['products']) . "<br>";
            
            echo "<h3>Категории:</h3>";
            foreach ($menuData['categories'] as $cat) {
                echo "- " . ($cat['category_name'] ?? $cat['name'] ?? 'Без названия') . " (ID: " . $cat['category_id'] . ")<br>";
            }
        } else {
            echo "⚠️ Меню не загружено из кэша<br>";
        }
    } else {
        echo "❌ MongoDB Client недоступен<br>";
    }
} catch (Exception $e) {
    echo "❌ Ошибка MongoDB: " . $e->getMessage() . "<br>";
}

// Проверяем API
echo "<h2>API Test</h2>";
$api_url = 'https://northrepublic.me/api/menu';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = @file_get_contents($api_url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data) {
        echo "✅ API доступен<br>";
        echo "Категории из API: " . count($data['categories'] ?? []) . "<br>";
        echo "Продукты из API: " . count($data['products'] ?? []) . "<br>";
    } else {
        echo "⚠️ API вернул некорректные данные<br>";
    }
} else {
    echo "❌ API недоступен<br>";
}

echo "<h2>Test Complete</h2>";
?>
