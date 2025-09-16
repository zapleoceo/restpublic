<?php

// Скрипт для миграции существующих изображений событий в GridFS
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/ImageService.php';

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

// Проверяем, что переменные загружены
if (empty($_ENV['MONGODB_URI']) || empty($_ENV['MONGODB_DATABASE'])) {
    echo "ОШИБКА: Не удалось загрузить переменные окружения из .env файла\n";
    echo "MONGODB_URI: " . ($_ENV['MONGODB_URI'] ?? 'НЕ НАЙДЕНО') . "\n";
    echo "MONGODB_DATABASE: " . ($_ENV['MONGODB_DATABASE'] ?? 'НЕ НАЙДЕНО') . "\n";
    exit(1);
}

try {
    echo "Начинаем миграцию изображений в GridFS...\n";
    
    // Подключаемся к MongoDB
    $client = new MongoDB\Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DATABASE']);
    $eventsCollection = $database->selectCollection('events');
    
    $imageService = new ImageService();
    
    // Получаем все события с изображениями
    $events = $eventsCollection->find([
        'image' => ['$exists' => true, '$ne' => null, '$ne' => '']
    ])->toArray();
    
    echo "Найдено событий с изображениями: " . count($events) . "\n";
    
    $migratedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    
    foreach ($events as $event) {
        $eventId = (string)$event['_id'];
        $currentImage = $event['image'];
        
        echo "Обрабатываем событие: " . $event['title'] . " (ID: $eventId)\n";
        echo "Текущее изображение: $currentImage\n";
        
        // Пропускаем, если это уже GridFS file_id
        if (preg_match('/^[a-f\d]{24}$/i', $currentImage)) {
            echo "  -> Пропускаем (уже GridFS file_id)\n";
            $skippedCount++;
            continue;
        }
        
        // Пропускаем дефолтное изображение
        if ($currentImage === '/images/event-default.png') {
            echo "  -> Пропускаем (дефолтное изображение)\n";
            $skippedCount++;
            continue;
        }
        
        // Проверяем, существует ли файл
        $filePath = __DIR__ . $currentImage;
        if (!file_exists($filePath)) {
            echo "  -> ОШИБКА: Файл не найден: $filePath\n";
            $errorCount++;
            continue;
        }
        
        try {
            // Читаем файл
            $fileData = file_get_contents($filePath);
            if ($fileData === false) {
                echo "  -> ОШИБКА: Не удалось прочитать файл\n";
                $errorCount++;
                continue;
            }
            
            // Сохраняем в GridFS
            $imageData = $imageService->saveImage($fileData, basename($currentImage), [
                'event_type' => 'migrated',
                'original_path' => $currentImage,
                'original_event_id' => $eventId
            ]);
            
            // Обновляем событие
            $result = $eventsCollection->updateOne(
                ['_id' => $event['_id']],
                ['$set' => [
                    'image' => $imageData['file_id'],
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            if ($result->getModifiedCount() > 0) {
                echo "  -> УСПЕХ: Мигрировано в GridFS (file_id: " . $imageData['file_id'] . ")\n";
                $migratedCount++;
                
                // Опционально: удаляем старый файл
                if (unlink($filePath)) {
                    echo "  -> Старый файл удален: $filePath\n";
                } else {
                    echo "  -> ПРЕДУПРЕЖДЕНИЕ: Не удалось удалить старый файл: $filePath\n";
                }
            } else {
                echo "  -> ОШИБКА: Не удалось обновить событие в базе\n";
                $errorCount++;
            }
            
        } catch (Exception $e) {
            echo "  -> ОШИБКА: " . $e->getMessage() . "\n";
            $errorCount++;
        }
        
        echo "\n";
    }
    
    echo "=== РЕЗУЛЬТАТЫ МИГРАЦИИ ===\n";
    echo "Успешно мигрировано: $migratedCount\n";
    echo "Пропущено: $skippedCount\n";
    echo "Ошибок: $errorCount\n";
    echo "Всего обработано: " . ($migratedCount + $skippedCount + $errorCount) . "\n";
    
} catch (Exception $e) {
    echo "КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}
