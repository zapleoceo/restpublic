<?php
// Скрипт для исправления поломанных событий в MongoDB
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

    echo "=== ИСПРАВЛЕНИЕ ПОЛОМАННЫХ СОБЫТИЙ ===\n";

    // Получаем все события
    $events = $eventsCollection->find([])->toArray();
    echo "Всего событий найдено: " . count($events) . "\n\n";

    $brokenEvents = [];
    $fixedEvents = [];
    $deletedEvents = [];

    foreach ($events as $event) {
        $eventId = (string)$event['_id'];
        $isBroken = false;
        $fixes = [];

        echo "Проверяю событие ID: $eventId\n";

        // Проверяем обязательные поля
        $requiredFields = ['title', 'date', 'time', 'conditions'];
        foreach ($requiredFields as $field) {
            if (!isset($event[$field]) || empty($event[$field])) {
                echo "  ❌ Отсутствует поле '$field'\n";
                $isBroken = true;
                
                // Пытаемся исправить
                switch ($field) {
                    case 'title':
                        $event[$field] = 'Событие без названия';
                        $fixes[] = "Добавлено название по умолчанию";
                        break;
                    case 'date':
                        $event[$field] = date('Y-m-d');
                        $fixes[] = "Установлена текущая дата";
                        break;
                    case 'time':
                        $event[$field] = '19:00';
                        $fixes[] = "Установлено время по умолчанию";
                        break;
                    case 'conditions':
                        $event[$field] = 'Уточняйте условия';
                        $fixes[] = "Добавлены условия по умолчанию";
                        break;
                }
            }
        }

        // Проверяем формат даты
        if (isset($event['date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $event['date'])) {
            echo "  ❌ Неверный формат даты: " . $event['date'] . "\n";
            $isBroken = true;
            
            // Пытаемся исправить формат даты
            $date = DateTime::createFromFormat('d.m.Y', $event['date']);
            if ($date) {
                $event['date'] = $date->format('Y-m-d');
                $fixes[] = "Исправлен формат даты";
            } else {
                $event['date'] = date('Y-m-d');
                $fixes[] = "Установлена текущая дата";
            }
        }

        // Проверяем формат времени
        if (isset($event['time']) && !preg_match('/^\d{2}:\d{2}$/', $event['time'])) {
            echo "  ❌ Неверный формат времени: " . $event['time'] . "\n";
            $isBroken = true;
            $event['time'] = '19:00';
            $fixes[] = "Исправлен формат времени";
        }

        // Проверяем и исправляем булевые поля
        if (isset($event['is_active']) && !is_bool($event['is_active'])) {
            echo "  ❌ Неверный тип is_active: " . gettype($event['is_active']) . "\n";
            $isBroken = true;
            $event['is_active'] = (bool)$event['is_active'];
            $fixes[] = "Исправлен тип is_active";
        }

        // Проверяем и исправляем строковые поля
        $stringFields = ['title', 'conditions', 'description_link', 'image', 'comment'];
        foreach ($stringFields as $field) {
            if (isset($event[$field]) && !is_string($event[$field]) && !is_null($event[$field])) {
                echo "  ❌ Неверный тип $field: " . gettype($event[$field]) . "\n";
                $isBroken = true;
                
                // Безопасное преобразование в строку
                if (is_array($event[$field]) || is_object($event[$field])) {
                    $event[$field] = null; // Устанавливаем null для сложных типов
                    $fixes[] = "Очищено поле $field (был сложный тип)";
                } else {
                    $event[$field] = (string)$event[$field];
                    $fixes[] = "Исправлен тип $field";
                }
            }
        }

        // Добавляем недостающие поля
        if (!isset($event['created_at'])) {
            $event['created_at'] = new MongoDB\BSON\UTCDateTime();
            $fixes[] = "Добавлено поле created_at";
        }
        
        if (!isset($event['updated_at'])) {
            $event['updated_at'] = new MongoDB\BSON\UTCDateTime();
            $fixes[] = "Добавлено поле updated_at";
        }

        if ($isBroken) {
            $brokenEvents[] = $event;
            
            if (!empty($fixes)) {
                echo "  🔧 Исправления: " . implode(', ', $fixes) . "\n";
                
                // Обновляем событие в базе
                try {
                    $result = $eventsCollection->updateOne(
                        ['_id' => $event['_id']],
                        ['$set' => $event]
                    );
                    
                    if ($result->getModifiedCount() > 0) {
                        $fixedEvents[] = $eventId;
                        echo "  ✅ Событие исправлено\n";
                    } else {
                        echo "  ⚠️ Событие не было изменено\n";
                    }
                } catch (Exception $e) {
                    echo "  ❌ Ошибка при исправлении: " . $e->getMessage() . "\n";
                    
                    // Если исправить не удается, удаляем поломанное событие
                    try {
                        $deleteResult = $eventsCollection->deleteOne(['_id' => $event['_id']]);
                        if ($deleteResult->getDeletedCount() > 0) {
                            $deletedEvents[] = $eventId;
                            echo "  🗑️ Поломанное событие удалено\n";
                        }
                    } catch (Exception $deleteError) {
                        echo "  ❌ Ошибка при удалении: " . $deleteError->getMessage() . "\n";
                    }
                }
            }
        } else {
            echo "  ✅ Событие в порядке\n";
        }
        
        echo "\n";
    }

    echo "=== ИТОГИ ===\n";
    echo "✅ Исправлено событий: " . count($fixedEvents) . "\n";
    echo "🗑️ Удалено поломанных событий: " . count($deletedEvents) . "\n";
    echo "❌ Осталось поломанных событий: " . (count($brokenEvents) - count($fixedEvents) - count($deletedEvents)) . "\n";

    if (!empty($fixedEvents)) {
        echo "\nИсправленные события:\n";
        foreach ($fixedEvents as $id) {
            echo "- $id\n";
        }
    }

    if (!empty($deletedEvents)) {
        echo "\nУдаленные события:\n";
        foreach ($deletedEvents as $id) {
            echo "- $id\n";
        }
    }

    // Проверяем финальное состояние
    $finalEvents = $eventsCollection->find([])->toArray();
    echo "\nФинальное количество событий: " . count($finalEvents) . "\n";

} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
}
?>
