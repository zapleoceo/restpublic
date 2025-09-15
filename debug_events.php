<?php
// Временный скрипт для отладки событий
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

    // Получаем все события
    $events = $eventsCollection->find([])->toArray();

    echo "Всего событий: " . count($events) . "\n\n";

    foreach ($events as $event) {
        echo "ID: " . $event['_id'] . "\n";
        echo "Title: " . ($event['title'] ?? 'NULL') . "\n";
        echo "Date: " . ($event['date'] ?? 'NULL') . "\n";
        echo "Time: " . ($event['time'] ?? 'NULL') . "\n";
        echo "Conditions: " . ($event['conditions'] ?? 'NULL') . "\n";
        echo "Link: " . (isset($event['description_link']) ? substr($event['description_link'], 0, 50) . '...' : 'NULL') . "\n";
        echo "Image: " . ($event['image'] ?? 'NULL') . "\n";
        echo "Comment: " . ($event['comment'] ?? 'NULL') . "\n";
        echo "Active: " . ($event['is_active'] ?? 'NULL') . "\n";
        echo "Created: " . ($event['created_at'] ?? 'NULL') . "\n";
        echo "Updated: " . ($event['updated_at'] ?? 'NULL') . "\n";
        echo "---\n";
    }

} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
