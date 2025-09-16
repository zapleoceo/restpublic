<?php
/**
 * Скрипт миграции структуры событий в MongoDB
 * Обновляет существующие события для соответствия новой структуре ТЗ
 */

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
    
    echo "=== МИГРАЦИЯ СТРУКТУРЫ СОБЫТИЙ ===\n";
    echo "Подключение к MongoDB: $mongodbUrl\n";
    echo "База данных: $dbName\n\n";
    
    // Получаем все события
    $events = $eventsCollection->find([])->toArray();
    echo "Найдено событий для миграции: " . count($events) . "\n\n";
    
    $migratedCount = 0;
    $skippedCount = 0;
    
    foreach ($events as $event) {
        $eventId = (string)$event['_id'];
        echo "Обрабатываем событие ID: $eventId\n";
        
        // Проверяем, нужно ли мигрировать это событие
        $needsMigration = false;
        $updateData = [];
        
        // Проверяем наличие старых полей и создаем новые
        if (isset($event['title']) && !isset($event['title_ru'])) {
            $updateData['title_ru'] = $event['title'];
            $updateData['title_en'] = $event['title']; // Копируем русский как fallback
            $updateData['title_vi'] = $event['title']; // Копируем русский как fallback
            $needsMigration = true;
            echo "  - Мигрируем title -> title_ru/en/vi\n";
        }
        
        if (isset($event['conditions']) && !isset($event['conditions_ru'])) {
            $updateData['conditions_ru'] = $event['conditions'];
            $updateData['conditions_en'] = $event['conditions']; // Копируем русский как fallback
            $updateData['conditions_vi'] = $event['conditions']; // Копируем русский как fallback
            $needsMigration = true;
            echo "  - Мигрируем conditions -> conditions_ru/en/vi\n";
        }
        
        // Добавляем поля описания если их нет
        if (!isset($event['description_ru'])) {
            $updateData['description_ru'] = '';
            $updateData['description_en'] = '';
            $updateData['description_vi'] = '';
            $needsMigration = true;
            echo "  - Добавляем поля description_ru/en/vi\n";
        }
        
        // Обновляем поле ссылки
        if (isset($event['description_link']) && !isset($event['link'])) {
            $updateData['link'] = $event['description_link'];
            $needsMigration = true;
            echo "  - Мигрируем description_link -> link\n";
        }
        
        // Добавляем категорию если её нет
        if (!isset($event['category'])) {
            $updateData['category'] = 'general';
            $needsMigration = true;
            echo "  - Добавляем category: general\n";
        }
        
        if ($needsMigration) {
            try {
                $result = $eventsCollection->updateOne(
                    ['_id' => $event['_id']],
                    ['$set' => $updateData]
                );
                
                if ($result->getModifiedCount() > 0) {
                    echo "  ✅ Событие успешно мигрировано\n";
                    $migratedCount++;
                } else {
                    echo "  ⚠️ Событие не было изменено\n";
                    $skippedCount++;
                }
            } catch (Exception $e) {
                echo "  ❌ Ошибка миграции: " . $e->getMessage() . "\n";
                $skippedCount++;
            }
        } else {
            echo "  ⏭️ Миграция не требуется\n";
            $skippedCount++;
        }
        
        echo "\n";
    }
    
    echo "=== РЕЗУЛЬТАТЫ МИГРАЦИИ ===\n";
    echo "Успешно мигрировано: $migratedCount событий\n";
    echo "Пропущено: $skippedCount событий\n";
    echo "Всего обработано: " . ($migratedCount + $skippedCount) . " событий\n\n";
    
    // Проверяем результат
    echo "=== ПРОВЕРКА РЕЗУЛЬТАТА ===\n";
    $sampleEvents = $eventsCollection->find([], ['limit' => 3])->toArray();
    
    foreach ($sampleEvents as $event) {
        echo "Событие ID: " . (string)$event['_id'] . "\n";
        echo "  title_ru: " . ($event['title_ru'] ?? 'НЕТ') . "\n";
        echo "  title_en: " . ($event['title_en'] ?? 'НЕТ') . "\n";
        echo "  title_vi: " . ($event['title_vi'] ?? 'НЕТ') . "\n";
        echo "  conditions_ru: " . (isset($event['conditions_ru']) ? 'ЕСТЬ' : 'НЕТ') . "\n";
        echo "  conditions_en: " . (isset($event['conditions_en']) ? 'ЕСТЬ' : 'НЕТ') . "\n";
        echo "  conditions_vi: " . (isset($event['conditions_vi']) ? 'ЕСТЬ' : 'НЕТ') . "\n";
        echo "  description_ru: " . (isset($event['description_ru']) ? 'ЕСТЬ' : 'НЕТ') . "\n";
        echo "  link: " . ($event['link'] ?? 'НЕТ') . "\n";
        echo "  category: " . ($event['category'] ?? 'НЕТ') . "\n";
        echo "\n";
    }
    
    echo "✅ Миграция завершена успешно!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка миграции: " . $e->getMessage() . "\n";
    exit(1);
}
?>
