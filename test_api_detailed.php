<?php
// Детальный тест API для событий
require_once 'vendor/autoload.php';

echo "=== Детальный тест API событий ===\n";

// Подключаемся к MongoDB
$client = new MongoDB\Client('mongodb://localhost:27017');
$database = $client->selectDatabase('northrepublic');
$eventsCollection = $database->selectCollection('events');

// Тест 1: Проверяем последнее созданное событие
echo "\n1. Последнее созданное событие:\n";
$lastEvent = $eventsCollection->findOne([], ['sort' => ['created_at' => -1]]);
if ($lastEvent) {
    echo "ID: " . $lastEvent['_id'] . "\n";
    echo "Title: " . $lastEvent['title'] . "\n";
    echo "Date: " . $lastEvent['date'] . "\n";
    echo "Time: " . $lastEvent['time'] . "\n";
    echo "Image: " . ($lastEvent['image'] ?? 'null') . "\n";
    echo "Is Active: " . ($lastEvent['is_active'] ? 'true' : 'false') . "\n";
    echo "Created: " . $lastEvent['created_at']->toDateTime()->format('Y-m-d H:i:s') . "\n";
} else {
    echo "События не найдены\n";
}

// Тест 2: Проверяем все события с изображениями
echo "\n2. События с изображениями:\n";
$eventsWithImages = $eventsCollection->find(['image' => ['$ne' => null]]);
$count = 0;
foreach ($eventsWithImages as $event) {
    $count++;
    echo "  $count. " . $event['title'] . " - " . $event['image'] . "\n";
}
echo "Всего событий с изображениями: $count\n";

// Тест 3: Проверяем события без изображений
echo "\n3. События без изображений:\n";
$eventsWithoutImages = $eventsCollection->find(['image' => null]);
$count = 0;
foreach ($eventsWithoutImages as $event) {
    $count++;
    echo "  $count. " . $event['title'] . " - " . ($event['image'] ?? 'null') . "\n";
}
echo "Всего событий без изображений: $count\n";

// Тест 4: Проверяем файлы в папке images/events
echo "\n4. Файлы в папке images/events:\n";
$uploadDir = '/var/www/northrepubli_usr/data/www/northrepublic.me/images/events/';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $imageFiles = array_filter($files, function($file) {
        return $file !== '.' && $file !== '..' && $file !== '.htaccess';
    });
    
    if (empty($imageFiles)) {
        echo "  Папка пуста (кроме .htaccess)\n";
    } else {
        foreach ($imageFiles as $file) {
            $filePath = $uploadDir . $file;
            $fileSize = filesize($filePath);
            echo "  - $file (" . round($fileSize/1024, 2) . " KB)\n";
        }
    }
} else {
    echo "  Папка не существует\n";
}

echo "\n=== Тест завершен ===\n";
?>
