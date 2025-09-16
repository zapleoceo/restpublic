<?php
// Скрипт для создания события "🎭 Мафия" на все понедельники до конца года
// Запускать на сервере

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
    
    // Ищем существующее событие "Мафия"
    $mafiaEvent = $eventsCollection->findOne([
        'title' => ['$regex' => 'Мафия', '$options' => 'i']
    ]);
    
    if (!$mafiaEvent) {
        echo "❌ Событие 'Мафия' не найдено в базе данных!\n";
        echo "Сначала создайте событие '🎭 Мафия' в админке, а затем запустите этот скрипт.\n";
        exit(1);
    }
    
    echo "✅ Найдено событие: " . $mafiaEvent['title'] . "\n";
    echo "📅 Дата: " . $mafiaEvent['date'] . "\n";
    echo "🕐 Время: " . $mafiaEvent['time'] . "\n";
    echo "📝 Условия: " . $mafiaEvent['conditions'] . "\n\n";
    
    // Получаем текущую дату
    $today = new DateTime();
    $currentYear = $today->format('Y');
    
    // Находим первый понедельник после сегодняшнего дня
    $firstMonday = clone $today;
    $dayOfWeek = (int)$today->format('N'); // 1 = понедельник, 7 = воскресенье
    
    if ($dayOfWeek == 1) {
        // Если сегодня понедельник, начинаем со следующего
        $firstMonday->add(new DateInterval('P7D'));
    } else {
        // Иначе идем к следующему понедельнику
        $daysToMonday = 8 - $dayOfWeek;
        $firstMonday->add(new DateInterval('P' . $daysToMonday . 'D'));
    }
    
    // Создаем события на все понедельники до конца года
    $createdCount = 0;
    $currentMonday = clone $firstMonday;
    
    while ($currentMonday->format('Y') == $currentYear) {
        $dateStr = $currentMonday->format('Y-m-d');
        
        // Проверяем, есть ли уже событие "Мафия" на эту дату
        $existingEvent = $eventsCollection->findOne([
            'title' => ['$regex' => 'Мафия', '$options' => 'i'],
            'date' => $dateStr
        ]);
        
        if (!$existingEvent) {
            // Создаем новое событие на основе найденного
            $newEventData = [
                'title' => $mafiaEvent['title'],
                'date' => $dateStr,
                'time' => $mafiaEvent['time'],
                'conditions' => $mafiaEvent['conditions'],
                'description_link' => $mafiaEvent['description_link'] ?? null,
                'image' => $mafiaEvent['image'] ?? null,
                'comment' => $mafiaEvent['comment'] ?? null,
                'is_active' => $mafiaEvent['is_active'] ?? true,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $eventsCollection->insertOne($newEventData);
            
            if ($result->getInsertedId()) {
                echo "✅ Создано событие на " . $dateStr . " (понедельник)\n";
                $createdCount++;
            } else {
                echo "❌ Ошибка создания события на " . $dateStr . "\n";
            }
        } else {
            echo "⏭️  Событие уже существует на " . $dateStr . "\n";
        }
        
        // Переходим к следующему понедельнику
        $currentMonday->add(new DateInterval('P7D'));
    }
    
    echo "\n🎉 Готово! Создано событий: " . $createdCount . "\n";
    echo "📅 События '🎭 Мафия' запланированы на все понедельники до конца " . $currentYear . " года.\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
?>
