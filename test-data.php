<?php
/**
 * Простой тест загрузки данных меню
 */

echo "<h1>🔍 Тест загрузки данных меню</h1>\n";
echo "<style>body{font-family:monospace;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>\n";

// 1. Проверка MongoDB
echo "<h2>1. Проверка MongoDB</h2>\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        echo "<span class='ok'>✅ MongoDB PHP драйвер установлен</span><br>\n";
        
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $collection = $db->menu;
        
        $result = $collection->findOne(['_id' => 'current_menu']);
        if ($result) {
            echo "<span class='ok'>✅ Кэш найден</span><br>\n";
            echo "📊 Категории: " . count($result['categories'] ?? []) . "<br>\n";
            echo "📊 Продукты: " . count($result['products'] ?? []) . "<br>\n";
        } else {
            echo "<span class='warning'>⚠️ Кэш пуст</span><br>\n";
        }
    } else {
        echo "<span class='error'>❌ MongoDB драйвер не установлен</span><br>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ MongoDB ошибка: " . $e->getMessage() . "</span><br>\n";
}

// 2. Проверка MenuCache
echo "<h2>2. Проверка MenuCache</h2>\n";
try {
    require_once __DIR__ . '/php/classes/MenuCache.php';
    $menuCache = new MenuCache();
    echo "<span class='ok'>✅ MenuCache создан</span><br>\n";
    
    $menuData = $menuCache->getMenu();
    if ($menuData) {
        echo "<span class='ok'>✅ Данные получены</span><br>\n";
        echo "📊 Категории: " . count($menuData['categories']) . "<br>\n";
        echo "📊 Продукты: " . count($menuData['products']) . "<br>\n";
        
        // Показываем первые 3 категории
        echo "<h3>Первые категории:</h3>\n";
        foreach (array_slice($menuData['categories'], 0, 3) as $category) {
            echo "- " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . "<br>\n";
        }
    } else {
        echo "<span class='warning'>⚠️ Данные не получены</span><br>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ MenuCache ошибка: " . $e->getMessage() . "</span><br>\n";
}

// 3. Проверка API
echo "<h2>3. Проверка API</h2>\n";
$api_url = 'https://northrepublic.me:3002/api/menu';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($api_url, false, $context);
if ($response !== false) {
    echo "<span class='ok'>✅ API доступен</span><br>\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "📊 Категории: " . count($data['categories'] ?? []) . "<br>\n";
        echo "📊 Продукты: " . count($data['products'] ?? []) . "<br>\n";
    }
} else {
    echo "<span class='error'>❌ API недоступен</span><br>\n";
}

echo "<h2>🎯 Следующие шаги</h2>\n";
echo "<ul>\n";
echo "<li><a href='index.php'>Главная страница</a></li>\n";
echo "<li><a href='php/menu.php'>Страница меню</a></li>\n";
echo "<li><a href='php/init-cache.php'>Инициализация кэша</a></li>\n";
echo "</ul>\n";
?>
