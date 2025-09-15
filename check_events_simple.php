<?php
// Простой скрипт для проверки событий без composer
// Используем встроенный MongoDB драйвер PHP

try {
    // Подключение к MongoDB (используем стандартные настройки)
    $mongodbUrl = 'mongodb://localhost:27017';
    $dbName = 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    // Получаем все события
    $events = $eventsCollection->find([])->toArray();
    
    echo "=== ПРОВЕРКА СОБЫТИЙ В MONGODB ===\n";
    echo "Всего событий: " . count($events) . "\n\n";
    
    if (count($events) === 0) {
        echo "❌ События не найдены в MongoDB!\n";
        echo "Возможные причины:\n";
        echo "1. Коллекция events пуста\n";
        echo "2. Неправильное подключение к MongoDB\n";
        echo "3. Неправильное имя базы данных\n";
        exit;
    }
    
    $brokenEvents = [];
    $validEvents = [];
    
    foreach ($events as $index => $event) {
        echo "--- Событие " . ($index + 1) . " ---\n";
        echo "ID: " . $event['_id'] . "\n";
        
        // Проверяем обязательные поля
        $requiredFields = ['title', 'date', 'time', 'conditions'];
        $isBroken = false;
        
        foreach ($requiredFields as $field) {
            if (!isset($event[$field]) || empty($event[$field])) {
                echo "❌ ОТСУТСТВУЕТ поле '$field'\n";
                $isBroken = true;
            } else {
                echo "✅ $field: " . $event[$field] . "\n";
            }
        }
        
        // Проверяем дополнительные поля
        echo "🔗 description_link: " . (isset($event['description_link']) ? $event['description_link'] : 'NULL') . "\n";
        echo "🖼️  image: " . (isset($event['image']) ? $event['image'] : 'NULL') . "\n";
        echo "💬 comment: " . (isset($event['comment']) ? $event['comment'] : 'NULL') . "\n";
        echo "🟢 is_active: " . (isset($event['is_active']) ? ($event['is_active'] ? 'true' : 'false') : 'NULL') . "\n";
        echo "📅 created_at: " . (isset($event['created_at']) ? $event['created_at'] : 'NULL') . "\n";
        echo "📅 updated_at: " . (isset($event['updated_at']) ? $event['updated_at'] : 'NULL') . "\n";
        
        if ($isBroken) {
            $brokenEvents[] = $event;
            echo "🚨 СТАТУС: ПОЛОМАННОЕ СОБЫТИЕ\n";
        } else {
            $validEvents[] = $event;
            echo "✅ СТАТУС: ВАЛИДНОЕ СОБЫТИЕ\n";
        }
        
        echo "\n";
    }
    
    echo "=== ИТОГИ ===\n";
    echo "✅ Валидных событий: " . count($validEvents) . "\n";
    echo "❌ Поломанных событий: " . count($brokenEvents) . "\n";
    
    if (count($brokenEvents) > 0) {
        echo "\n🚨 ПОЛОМАННЫЕ СОБЫТИЯ:\n";
        foreach ($brokenEvents as $event) {
            echo "- ID: " . $event['_id'] . " | Title: " . (isset($event['title']) ? $event['title'] : 'NULL') . "\n";
        }
        
        echo "\n💡 РЕКОМЕНДАЦИИ:\n";
        echo "1. Удалить поломанные события из MongoDB\n";
        echo "2. Проверить код создания/редактирования событий\n";
        echo "3. Добавить валидацию данных в API\n";
    }
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "\nВозможные причины:\n";
    echo "1. MongoDB не запущен\n";
    echo "2. Неправильные настройки подключения\n";
    echo "3. Отсутствует MongoDB extension в PHP\n";
}
?>
