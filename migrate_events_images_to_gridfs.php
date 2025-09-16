<?php

// Скрипт для миграции изображений событий в GridFS
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

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/ImageService.php';

try {
    // Подключение к MongoDB
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    $imageService = new ImageService();
    
    echo "Начинаем миграцию изображений событий в GridFS...\n";
    
    // Получаем все события
    $events = $eventsCollection->find([])->toArray();
    $totalEvents = count($events);
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    
    echo "Найдено событий: $totalEvents\n";
    
    foreach ($events as $event) {
        $eventId = (string)$event['_id'];
        $currentImage = $event['image'] ?? null;
        
        echo "Обрабатываем событие: {$event['title']} (ID: $eventId)\n";
        
        // Пропускаем если уже GridFS ID
        if ($currentImage && preg_match('/^[a-f0-9]{24}$/i', $currentImage)) {
            echo "  - Уже GridFS ID, пропускаем\n";
            $skippedCount++;
            continue;
        }
        
        // Пропускаем если нет изображения
        if (!$currentImage || $currentImage === '/images/event-default.png') {
            echo "  - Нет изображения, пропускаем\n";
            $skippedCount++;
            continue;
        }
        
        // Проверяем, существует ли файл
        $fullPath = __DIR__ . $currentImage;
        if (!file_exists($fullPath)) {
            echo "  - Файл не найден: $fullPath, устанавливаем дефолтное изображение\n";
            
            // Устанавливаем дефолтное изображение
            $eventsCollection->updateOne(
                ['_id' => $event['_id']],
                ['$set' => ['image' => '/images/event-default.png']]
            );
            $skippedCount++;
            continue;
        }
        
        try {
            // Читаем файл
            $fileData = file_get_contents($fullPath);
            if ($fileData === false) {
                echo "  - Ошибка чтения файла: $fullPath\n";
                $errorCount++;
                continue;
            }
            
            // Получаем имя файла
            $filename = basename($currentImage);
            
            // Сохраняем в GridFS
            $result = $imageService->saveImage($fileData, $filename, [
                'event_id' => $eventId,
                'original_path' => $currentImage,
                'migrated_at' => new MongoDB\BSON\UTCDateTime()
            ]);
            
            if ($result) {
                // Обновляем событие с новым GridFS ID
                $eventsCollection->updateOne(
                    ['_id' => $event['_id']],
                    ['$set' => ['image' => $result['file_id']]]
                );
                
                echo "  - Мигрировано в GridFS: {$result['file_id']}\n";
                $migratedCount++;
                
                // Удаляем старый файл
                if (unlink($fullPath)) {
                    echo "  - Старый файл удален: $fullPath\n";
                } else {
                    echo "  - Предупреждение: не удалось удалить старый файл: $fullPath\n";
                }
            } else {
                echo "  - Ошибка сохранения в GridFS\n";
                $errorCount++;
            }
            
        } catch (Exception $e) {
            echo "  - Ошибка: " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "\nМиграция завершена!\n";
    echo "Всего событий: $totalEvents\n";
    echo "Мигрировано: $migratedCount\n";
    echo "Пропущено: $skippedCount\n";
    echo "Ошибок: $errorCount\n";
    
} catch (Exception $e) {
    echo "Критическая ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
