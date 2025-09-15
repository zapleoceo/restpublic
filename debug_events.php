<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Events Widget Debug ===\n";

// Проверяем подключение к MongoDB
echo "1. Checking MongoDB connection...\n";
try {
    require_once 'vendor/autoload.php';
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    echo "✓ MongoDB connected successfully\n";
    echo "Database: $dbName\n";
    echo "Collection: events\n";
    
    // Проверяем количество событий
    $count = $eventsCollection->countDocuments([]);
    echo "Total events in collection: $count\n";
    
    // Проверяем активные события
    $activeCount = $eventsCollection->countDocuments(['is_active' => true]);
    echo "Active events: $activeCount\n";
    
    // Проверяем события начиная с сегодня
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $futureCount = $eventsCollection->countDocuments([
        'is_active' => true,
        'date' => ['$gte' => $today->format('Y-m-d')]
    ]);
    echo "Future active events: $futureCount\n";
    
} catch (Exception $e) {
    echo "✗ MongoDB error: " . $e->getMessage() . "\n";
}

echo "\n2. Checking EventsService...\n";
try {
    require_once 'classes/EventsService.php';
    $service = new EventsService();
    echo "✓ EventsService loaded successfully\n";
    
    $events = $service->getEventsForWidget(8);
    echo "Events returned: " . count($events) . "\n";
    
    if (count($events) > 0) {
        echo "First event: " . $events[0]['title'] . "\n";
        echo "First event date: " . $events[0]['date'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ EventsService error: " . $e->getMessage() . "\n";
}

echo "\n3. Checking environment variables...\n";
echo "MONGODB_URL: " . ($_ENV['MONGODB_URL'] ?? 'not set') . "\n";
echo "MONGODB_DB_NAME: " . ($_ENV['MONGODB_DB_NAME'] ?? 'not set') . "\n";

echo "\n4. Checking .env file...\n";
if (file_exists('.env')) {
    echo "✓ .env file exists\n";
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'MONGODB_URL') !== false) {
        echo "✓ MONGODB_URL found in .env\n";
    } else {
        echo "✗ MONGODB_URL not found in .env\n";
    }
} else {
    echo "✗ .env file not found\n";
}

echo "\n=== Debug Complete ===\n";
?>