<?php
echo "=== Тест админки на продакшн сервере ===\n\n";

// Проверяем доступность разделов
$sections = [
    'pages' => 'admin/pages/index.php',
    'users' => 'admin/users/index.php', 
    'logs' => 'admin/logs/index.php',
    'database' => 'admin/database/index.php'
];

foreach ($sections as $name => $file) {
    echo "Проверка раздела: $name\n";
    
    if (file_exists($file)) {
        echo "✅ Файл существует\n";
        
        // Проверяем, что файл содержит контент
        $content = file_get_contents($file);
        if (strlen($content) > 1000) {
            echo "✅ Файл содержит контент (" . strlen($content) . " байт)\n";
        } else {
            echo "❌ Файл слишком маленький (" . strlen($content) . " байт)\n";
        }
    } else {
        echo "❌ Файл не найден\n";
    }
    
    echo "---\n";
}

// Проверяем классы
$classes = [
    'PageContentService' => 'classes/PageContentService.php',
    'AuthManager' => 'admin/classes/AuthManager.php',
    'Logger' => 'classes/Logger.php'
];

echo "\n=== Проверка классов ===\n";
foreach ($classes as $name => $file) {
    if (file_exists($file)) {
        echo "✅ $name: $file\n";
    } else {
        echo "❌ $name: $file (не найден)\n";
    }
}

echo "\n=== Проверка переменных окружения ===\n";
if (file_exists('.env')) {
    echo "✅ Файл .env существует\n";
} else {
    echo "❌ Файл .env не найден\n";
}

echo "\n=== Тест завершен ===\n";
?>
