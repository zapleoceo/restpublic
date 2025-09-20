<?php
// Скрипт для обновления всех событий мафии с одинаковым изображением
// Берет изображение из события "Мафия" от 2025-09-15 и применяет ко всем остальным событиям мафии
// Запускать на сервере: php update_mafia_events.php

require_once __DIR__ . '/vendor/autoload.php';

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
    
    echo "🔍 Поиск события 'Мафия' от 2025-09-15...\n";
    
    // Ищем событие мафии от 2025-09-15
    $mafiaEvent = $eventsCollection->findOne([
        'date' => '2025-09-15',
        'time' => '19:00',
        '$or' => [
            ['title_ru' => ['$regex' => 'мафия', '$options' => 'i']],
            ['title' => ['$regex' => 'мафия', '$options' => 'i']]
        ]
    ]);
    
    if (!$mafiaEvent) {
        echo "❌ Событие 'Мафия' от 2025-09-15 не найдено!\n";
        echo "Проверьте правильность даты и времени.\n";
        exit(1);
    }
    
    echo "✅ Найдено событие: " . ($mafiaEvent['title_ru'] ?? $mafiaEvent['title'] ?? 'Без названия') . "\n";
    echo "📅 Дата: " . $mafiaEvent['date'] . " " . $mafiaEvent['time'] . "\n";
    
    if (empty($mafiaEvent['image'])) {
        echo "❌ У события нет изображения!\n";
        exit(1);
    }
    
    $sourceImageId = $mafiaEvent['image'];
    echo "🖼️ ID изображения: " . $sourceImageId . "\n";
    
    // Ищем все остальные события мафии
    echo "\n🔍 Поиск всех остальных событий мафии...\n";
    
    $mafiaEvents = $eventsCollection->find([
        '_id' => ['$ne' => $mafiaEvent['_id']], // Исключаем исходное событие
        '$or' => [
            ['title_ru' => ['$regex' => 'мафия', '$options' => 'i']],
            ['title' => ['$regex' => 'мафия', '$options' => 'i']]
        ]
    ])->toArray();
    
    echo "📊 Найдено событий мафии для обновления: " . count($mafiaEvents) . "\n";
    
    if (count($mafiaEvents) === 0) {
        echo "ℹ️ Нет других событий мафии для обновления.\n";
        exit(0);
    }
    
    // Показываем список событий, которые будут обновлены
    echo "\n📋 События для обновления:\n";
    foreach ($mafiaEvents as $event) {
        $title = $event['title_ru'] ?? $event['title'] ?? 'Без названия';
        $date = $event['date'] . ' ' . $event['time'];
        $currentImage = $event['image'] ?? 'Нет изображения';
        echo "  - $title ($date) - текущее изображение: $currentImage\n";
    }
    
    // Обновляем все события мафии
    echo "\n🔄 Обновление событий мафии...\n";
    
    $updatedCount = 0;
    $errorCount = 0;
    
    foreach ($mafiaEvents as $event) {
        try {
            $result = $eventsCollection->updateOne(
                ['_id' => $event['_id']],
                [
                    '$set' => [
                        'image' => $sourceImageId,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                $title = $event['title_ru'] ?? $event['title'] ?? 'Без названия';
                echo "  ✅ Обновлено: $title\n";
                $updatedCount++;
            } else {
                echo "  ⚠️ Не изменено: " . ($event['title_ru'] ?? $event['title'] ?? 'Без названия') . "\n";
            }
        } catch (Exception $e) {
            echo "  ❌ Ошибка обновления: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "\n📊 Результат обновления:\n";
    echo "  ✅ Успешно обновлено: $updatedCount событий\n";
    echo "  ❌ Ошибок: $errorCount\n";
    echo "  🖼️ Использовано изображение: $sourceImageId\n";
    
    if ($updatedCount > 0) {
        echo "\n🎉 Все события мафии теперь имеют одинаковое изображение!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
?>
