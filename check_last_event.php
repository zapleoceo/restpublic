<?php
require_once 'vendor/autoload.php';

$mongodbUrl = 'mongodb://localhost:27017';
$dbName = 'northrepublic';
$client = new MongoDB\Client($mongodbUrl);
$db = $client->$dbName;
$eventsCollection = $db->events;

// Получаем последнее событие
$events = $eventsCollection->find([], ['sort' => ['_id' => -1], 'limit' => 1])->toArray();

if (!empty($events)) {
    $event = $events[0];
    echo "=== ПОСЛЕДНЕЕ СОБЫТИЕ ===\n";
    echo "ID: " . $event['_id'] . "\n";
    echo "Title: " . ($event['title'] ?? 'NULL') . "\n";
    echo "Date: " . ($event['date'] ?? 'NULL') . "\n";
    echo "Time: " . ($event['time'] ?? 'NULL') . "\n";
    echo "Conditions: " . ($event['conditions'] ?? 'NULL') . "\n";
    echo "is_active type: " . gettype($event['is_active'] ?? 'NULL') . "\n";
    echo "is_active value: " . var_export($event['is_active'] ?? 'NULL', true) . "\n";
    echo "image type: " . gettype($event['image'] ?? 'NULL') . "\n";
    echo "image value: " . var_export($event['image'] ?? 'NULL', true) . "\n";
    echo "description_link: " . ($event['description_link'] ?? 'NULL') . "\n";
    echo "comment: " . ($event['comment'] ?? 'NULL') . "\n";
    
    // Проверяем проблемы
    $problems = [];
    if (!isset($event['is_active']) || !is_bool($event['is_active'])) {
        $problems[] = "is_active имеет неверный тип: " . gettype($event['is_active'] ?? 'NULL');
    }
    if (isset($event['image']) && !is_string($event['image']) && !is_null($event['image'])) {
        $problems[] = "image имеет неверный тип: " . gettype($event['image']);
    }
    
    if (!empty($problems)) {
        echo "\n🚨 ПРОБЛЕМЫ:\n";
        foreach ($problems as $problem) {
            echo "- $problem\n";
        }
    } else {
        echo "\n✅ Событие в порядке\n";
    }
} else {
    echo "События не найдены\n";
}
?>
