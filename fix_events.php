<?php
// Скрипт для исправления событий
require_once 'vendor/autoload.php';

// Загружаем переменные окружения
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';

    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;

    // Удаляем событие с поврежденными данными
    $result = $eventsCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId('68c85d364f85077b5f0dfc62')]);
    
    if ($result->getDeletedCount() > 0) {
        echo "✅ Удалено поврежденное событие\n";
    } else {
        echo "❌ Событие не найдено\n";
    }

    // Проверяем оставшиеся события
    $events = $eventsCollection->find([])->toArray();
    echo "Осталось событий: " . count($events) . "\n";

    foreach ($events as $event) {
        echo "ID: " . $event['_id'] . " | Title: " . ($event['title'] ?? 'NULL') . " | Date: " . ($event['date'] ?? 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
