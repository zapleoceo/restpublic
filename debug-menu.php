<?php
/**
 * Отладочный скрипт для проверки данных меню
 */

echo "<h1>🔍 Отладка данных меню</h1>\n";
echo "<style>body{font-family:monospace;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>\n";

// 1. Проверка MongoDB напрямую
echo "<h2>1. Прямая проверка MongoDB</h2>\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        echo "<span class='ok'>✅ MongoDB драйвер доступен</span><br>\n";
        
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $collection = $db->menu;
        
        $result = $collection->findOne(['_id' => 'current_menu']);
        if ($result) {
            echo "<span class='ok'>✅ Кэш найден в MongoDB</span><br>\n";
            echo "📊 Категории: " . count($result['categories'] ?? []) . "<br>\n";
            echo "📊 Продукты: " . count($result['products'] ?? []) . "<br>\n";
            
            // Показываем первые категории
            if (!empty($result['categories'])) {
                echo "<h3>Категории в кэше:</h3>\n";
                foreach (array_slice($result['categories'], 0, 5) as $category) {
                    echo "- " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . " (ID: " . ($category['category_id'] ?? 'нет') . ")<br>\n";
                }
            }
        } else {
            echo "<span class='warning'>⚠️ Кэш пуст в MongoDB</span><br>\n";
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
        echo "<span class='ok'>✅ MenuCache.getMenu() работает</span><br>\n";
        echo "📊 Категории: " . count($menuData['categories']) . "<br>\n";
        echo "📊 Продукты: " . count($menuData['products']) . "<br>\n";
    } else {
        echo "<span class='warning'>⚠️ MenuCache.getMenu() вернул null</span><br>\n";
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
        
        // Показываем первые категории
        if (!empty($data['categories'])) {
            echo "<h3>Категории из API:</h3>\n";
            foreach (array_slice($data['categories'], 0, 5) as $category) {
                echo "- " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . " (ID: " . ($category['category_id'] ?? 'нет') . ")<br>\n";
            }
        }
    }
} else {
    echo "<span class='error'>❌ API недоступен</span><br>\n";
}

// 4. Тест загрузки данных как в index.php
echo "<h2>4. Тест загрузки данных (как в index.php)</h2>\n";
$categories = [];
$products = [];
$productsByCategory = [];

try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        // Group products by category
        if ($products) {
            foreach ($products as $product) {
                $categoryId = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                if (!isset($productsByCategory[$categoryId])) {
                    $productsByCategory[$categoryId] = [];
                }
                $productsByCategory[$categoryId][] = $product;
            }
        }
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Ошибка загрузки: " . $e->getMessage() . "</span><br>\n";
}

echo "📊 Результат загрузки:<br>\n";
echo "- Категории: " . count($categories) . "<br>\n";
echo "- Продукты: " . count($products) . "<br>\n";
echo "- Группировка по категориям: " . count($productsByCategory) . " категорий<br>\n";

if (!empty($categories)) {
    echo "<h3>Первые 3 категории:</h3>\n";
    foreach (array_slice($categories, 0, 3) as $category) {
        $categoryId = $category['category_id'];
        $productCount = count($productsByCategory[$categoryId] ?? []);
        echo "- " . ($category['category_name'] ?? $category['name'] ?? 'Без названия') . " (ID: $categoryId, продуктов: $productCount)<br>\n";
    }
}

echo "<h2>🎯 Рекомендации</h2>\n";
echo "<ul>\n";
echo "<li>Если MongoDB пуст - запустите: <code>php force-update-cache.php</code></li>\n";
echo "<li>Если API недоступен - проверьте: <code>pm2 status</code></li>\n";
echo "<li>Если MongoDB недоступен - запустите: <code>sudo systemctl start mongodb</code></li>\n";
echo "</ul>\n";

echo "<p><a href='index.php'>🔗 Главная страница</a> | <a href='php/menu.php'>🔗 Страница меню</a></p>\n";
?>
