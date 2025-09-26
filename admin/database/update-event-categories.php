<?php
/**
 * Скрипт для обновления категорий событий в MongoDB
 * Заменяет старые категории на новые
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
    
    echo "🔄 Начинаем обновление категорий событий...\n\n";
    
    // Маппинг старых категорий на новые
    $categoryMapping = [
        'general' => 'Музыкальное',
        'entertainment' => 'Игровое', 
        'food' => 'Детское',
        'music' => 'Музыкальное',
        'sports' => 'Настольные игры',
        'cultural' => 'Авторское'
    ];
    
    // Получаем все события
    $events = $eventsCollection->find([])->toArray();
    echo "📊 Найдено событий: " . count($events) . "\n\n";
    
    $updatedCount = 0;
    $skippedCount = 0;
    
    foreach ($events as $event) {
        $eventId = $event['_id'];
        $currentCategory = $event['category'] ?? 'general';
        $newCategory = $categoryMapping[$currentCategory] ?? 'Музыкальное';
        
        if ($currentCategory !== $newCategory) {
            // Обновляем категорию
            $result = $eventsCollection->updateOne(
                ['_id' => $eventId],
                [
                    '$set' => [
                        'category' => $newCategory,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            if ($result->getModifiedCount() > 0) {
                echo "✅ Обновлено событие '{$event['title_ru']}' ({$event['title_en']}): {$currentCategory} → {$newCategory}\n";
                $updatedCount++;
            } else {
                echo "⚠️  Не удалось обновить событие '{$event['title_ru']}'\n";
            }
        } else {
            echo "⏭️  Пропущено событие '{$event['title_ru']}' (категория уже актуальна: {$currentCategory})\n";
            $skippedCount++;
        }
    }
    
    echo "\n📈 Результаты обновления:\n";
    echo "✅ Обновлено событий: {$updatedCount}\n";
    echo "⏭️  Пропущено событий: {$skippedCount}\n";
    echo "📊 Всего обработано: " . ($updatedCount + $skippedCount) . "\n\n";
    
    // Показываем статистику по новым категориям
    echo "📋 Статистика по новым категориям:\n";
    $categoryStats = $eventsCollection->aggregate([
        ['$group' => [
            '_id' => '$category',
            'count' => ['$sum' => 1]
        ]],
        ['$sort' => ['count' => -1]]
    ])->toArray();
    
    foreach ($categoryStats as $stat) {
        echo "  • {$stat['_id']}: {$stat['count']} событий\n";
    }
    
    echo "\n🎉 Обновление категорий завершено успешно!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . "\n";
    echo "📍 Строка: " . $e->getLine() . "\n";
}
?>
