<?php
require_once 'vendor/autoload.php';

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    echo "=== All Events in Database ===\n";
    
    $events = $eventsCollection->find([], ['sort' => ['date' => 1]])->toArray();
    
    foreach ($events as $event) {
        echo "Date: " . $event['date'] . " | ";
        echo "Title: " . $event['title'] . " | ";
        echo "Active: " . ($event['is_active'] ? 'Yes' : 'No') . "\n";
    }
    
    echo "\n=== Today's Date ===\n";
    $today = new DateTime();
    echo "Today: " . $today->format('Y-m-d') . "\n";
    
    echo "\n=== Events from today onwards ===\n";
    $today->setTime(0, 0, 0);
    $futureEvents = $eventsCollection->find([
        'is_active' => true,
        'date' => ['$gte' => $today->format('Y-m-d')]
    ])->toArray();
    
    foreach ($futureEvents as $event) {
        echo "Date: " . $event['date'] . " | ";
        echo "Title: " . $event['title'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
