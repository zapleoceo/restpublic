<?php
require_once 'vendor/autoload.php';

// Загружаем переменные окружения
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

try {
    // Подключение к MongoDB
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    // Получаем завтрашнюю дату
    $tomorrow = new DateTime();
    $tomorrow->add(new DateInterval('P1D'));
    $tomorrowStr = $tomorrow->format('Y-m-d');
    
    echo "📅 Поиск событий на завтра: $tomorrowStr\n";
    
    // Получаем все события на завтра
    $events = $eventsCollection->find([
        'date' => $tomorrowStr,
        'is_active' => true
    ], [
        'sort' => ['time' => 1]
    ])->toArray();
    
    echo "Найдено событий: " . count($events) . "\n\n";
    
    foreach ($events as $event) {
        echo "=== СОБЫТИЕ ===\n";
        echo "ID: " . ($event['_id'] ?? 'N/A') . "\n";
        echo "Название: " . ($event['title_ru'] ?? $event['title'] ?? 'Без названия') . "\n";
        echo "Время: " . ($event['time'] ?? 'Время не указано') . "\n";
        echo "\n--- ПОЛЯ ОПИСАНИЯ ---\n";
        echo "description_ru: " . (isset($event['description_ru']) ? "'" . $event['description_ru'] . "'" : 'NOT SET') . "\n";
        echo "description: " . (isset($event['description']) ? "'" . $event['description'] . "'" : 'NOT SET') . "\n";
        echo "comment: " . (isset($event['comment']) ? "'" . $event['comment'] . "'" : 'NOT SET') . "\n";
        echo "\n--- ВСЕ ПОЛЯ ---\n";
        foreach ($event as $key => $value) {
            if (is_string($value) && strlen($value) > 0) {
                echo "$key: '$value'\n";
            }
        }
        echo "\n" . str_repeat("=", 50) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
