<?php
// Тестовый скрипт для проверки API событий
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

    echo "=== ТЕСТ API СОБЫТИЙ ===\n\n";

    // 1. Тест GET - получение всех событий
    echo "1. Тест GET запроса:\n";
    $events = $eventsCollection->find([])->toArray();
    echo "Найдено событий: " . count($events) . "\n";
    
    foreach ($events as $event) {
        echo "  - ID: " . $event['_id'] . " | Title: " . ($event['title'] ?? 'NULL') . " | Date: " . ($event['date'] ?? 'NULL') . "\n";
    }
    echo "\n";

    // 2. Тест POST - создание нового события
    echo "2. Тест POST запроса (создание события):\n";
    $testEvent = [
        'title' => 'Тестовое событие',
        'date' => '2025-01-20',
        'time' => '18:00',
        'conditions' => 'Бесплатно',
        'description_link' => 'https://example.com/test',
        'image' => null,
        'comment' => 'Тестовый комментарий',
        'is_active' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];

    $result = $eventsCollection->insertOne($testEvent);
    if ($result->getInsertedId()) {
        $newEventId = (string)$result->getInsertedId();
        echo "✅ Событие создано успешно. ID: " . $newEventId . "\n";
        
        // 3. Тест PUT - обновление события
        echo "\n3. Тест PUT запроса (обновление события):\n";
        $updateResult = $eventsCollection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($newEventId)],
            ['$set' => [
                'title' => 'Обновленное тестовое событие',
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]]
        );
        
        if ($updateResult->getModifiedCount() > 0) {
            echo "✅ Событие обновлено успешно\n";
        } else {
            echo "❌ Ошибка обновления события\n";
        }
        
        // 4. Тест DELETE - удаление события
        echo "\n4. Тест DELETE запроса (удаление события):\n";
        $deleteResult = $eventsCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($newEventId)]);
        
        if ($deleteResult->getDeletedCount() > 0) {
            echo "✅ Событие удалено успешно\n";
        } else {
            echo "❌ Ошибка удаления события\n";
        }
        
    } else {
        echo "❌ Ошибка создания события\n";
    }

    echo "\n=== ТЕСТ ЗАВЕРШЕН ===\n";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
