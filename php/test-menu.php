<?php
/**
 * Тестовый скрипт для проверки работы системы меню
 * Показывает статус всех компонентов
 */

echo "<h1>🔍 Тест системы меню North Republic</h1>\n";
echo "<style>body{font-family:monospace;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>\n";

// 1. Проверка MongoDB подключения
echo "<h2>1. Проверка MongoDB</h2>\n";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        echo "<span class='ok'>✅ MongoDB PHP драйвер установлен</span><br>\n";
        
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $collection = $db->menu;
        
        // Проверяем подключение
        $result = $collection->findOne(['_id' => 'current_menu']);
        if ($result) {
            echo "<span class='ok'>✅ MongoDB подключение работает</span><br>\n";
            echo "<span class='ok'>✅ Кэш меню найден</span><br>\n";
            echo "📊 Категории: " . count($result['categories'] ?? []) . "<br>\n";
            echo "📊 Продукты: " . count($result['products'] ?? []) . "<br>\n";
            echo "📅 Обновлено: " . ($result['updated_at'] ?? 'неизвестно') . "<br>\n";
        } else {
            echo "<span class='warning'>⚠️ Кэш меню пуст</span><br>\n";
        }
    } else {
        echo "<span class='error'>❌ MongoDB PHP драйвер не установлен</span><br>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Ошибка MongoDB: " . $e->getMessage() . "</span><br>\n";
}

// 2. Проверка MenuCache класса
echo "<h2>2. Проверка MenuCache</h2>\n";
try {
    require_once __DIR__ . '/classes/MenuCache.php';
    $menuCache = new MenuCache();
    echo "<span class='ok'>✅ MenuCache класс загружен</span><br>\n";
    
    $menuData = $menuCache->getMenu();
    if ($menuData) {
        echo "<span class='ok'>✅ MenuCache.getMenu() работает</span><br>\n";
        echo "📊 Категории: " . count($menuData['categories']) . "<br>\n";
        echo "📊 Продукты: " . count($menuData['products']) . "<br>\n";
    } else {
        echo "<span class='warning'>⚠️ MenuCache.getMenu() вернул null</span><br>\n";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Ошибка MenuCache: " . $e->getMessage() . "</span><br>\n";
}

// 3. Проверка API
echo "<h2>3. Проверка Backend API</h2>\n";
$api_url = 'https://northrepublic.me:3002/api/health';
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($api_url, false, $context);
if ($response !== false) {
    echo "<span class='ok'>✅ Backend API доступен</span><br>\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "📊 Статус: " . ($data['status'] ?? 'неизвестно') . "<br>\n";
        echo "📊 Uptime: " . ($data['uptime'] ?? 'неизвестно') . " сек<br>\n";
    }
} else {
    echo "<span class='error'>❌ Backend API недоступен</span><br>\n";
}

// 4. Проверка API меню
echo "<h2>4. Проверка API меню</h2>\n";
$menu_api_url = 'https://northrepublic.me:3002/api/menu';
$response = @file_get_contents($menu_api_url, false, $context);
if ($response !== false) {
    echo "<span class='ok'>✅ API меню доступен</span><br>\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "📊 Категории: " . count($data['categories'] ?? []) . "<br>\n";
        echo "📊 Продукты: " . count($data['products'] ?? []) . "<br>\n";
    }
} else {
    echo "<span class='error'>❌ API меню недоступен</span><br>\n";
}

// 5. Проверка файлов
echo "<h2>5. Проверка файлов</h2>\n";
$files_to_check = [
    'menu.php' => 'Основная страница меню',
    'classes/MenuCache.php' => 'Класс кэширования',
    'init-cache.php' => 'Скрипт инициализации кэша',
    'components/header.php' => 'Компонент заголовка',
    'components/footer.php' => 'Компонент подвала',
    'components/cart.php' => 'Компонент корзины'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span class='ok'>✅ $description ($file)</span><br>\n";
    } else {
        echo "<span class='error'>❌ $description ($file) не найден</span><br>\n";
    }
}

// 6. Проверка путей к ресурсам
echo "<h2>6. Проверка ресурсов</h2>\n";
$resources_to_check = [
    'template/css/styles.css' => 'Основные стили',
    'template/css/vendor.css' => 'Vendor стили',
    'template/js/main.js' => 'Основной JavaScript',
    'template/js/plugins.js' => 'JavaScript плагины',
    'images/logo.png' => 'Логотип'
];

foreach ($resources_to_check as $resource => $description) {
    if (file_exists(__DIR__ . '/../' . $resource)) {
        echo "<span class='ok'>✅ $description ($resource)</span><br>\n";
    } else {
        echo "<span class='error'>❌ $description ($resource) не найден</span><br>\n";
    }
}

echo "<h2>🎯 Рекомендации</h2>\n";
echo "<ul>\n";
echo "<li>Если MongoDB недоступен - запустите: <code>sudo systemctl start mongodb</code></li>\n";
echo "<li>Если API недоступен - перезапустите: <code>pm2 restart northrepublic-backend</code></li>\n";
echo "<li>Если кэш пуст - запустите: <code>php php/init-cache.php</code></li>\n";
echo "<li>Если файлы не найдены - выполните: <code>./deploy.sh</code></li>\n";
echo "</ul>\n";

echo "<p><a href='menu.php'>🔗 Перейти к странице меню</a></p>\n";
?>
