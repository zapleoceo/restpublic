<?php
// Скрипт для отправки еженедельных анонсов в группу Силы

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/TelegramService.php';

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
    
    // Получаем следующий понедельник
    $today = new DateTime();
    $dayOfWeek = (int)$today->format('N'); // 1 = понедельник, 7 = воскресенье
    
    // Если сегодня пятница (5), то следующий понедельник через 3 дня
    // Если сегодня суббота (6), то следующий понедельник через 2 дня  
    // Если сегодня воскресенье (7), то следующий понедельник через 1 день
    $daysToMonday = (8 - $dayOfWeek) % 7;
    if ($daysToMonday == 0) $daysToMonday = 7;
    
    $monday = clone $today;
    $monday->add(new DateInterval('P' . $daysToMonday . 'D'));
    
    // Находим воскресенье следующей недели
    $sunday = clone $monday;
    $sunday->add(new DateInterval('P6D'));
    
    $mondayStr = $monday->format('Y-m-d');
    $sundayStr = $sunday->format('Y-m-d');
    
    echo "📅 Поиск событий с $mondayStr по $sundayStr\n";
    
    // Получаем все события на следующую неделю
    $events = $eventsCollection->find([
        'date' => [
            '$gte' => $mondayStr,
            '$lte' => $sundayStr
        ],
        'is_active' => true
    ], [
        'sort' => ['date' => 1, 'time' => 1]
    ])->toArray();
    
    echo "Найдено событий: " . count($events) . "\n";
    
    // Фильтруем события, которые уже прошли
    $currentDateTime = new DateTime();
    $filteredEvents = [];
    
    foreach ($events as $event) {
        $eventDate = new DateTime($event['date']);
        $eventTime = $event['time'] ?? '00:00';
        
        // Создаем полную дату и время события
        $eventDateTime = clone $eventDate;
        $timeParts = explode(':', $eventTime);
        $eventDateTime->setTime((int)$timeParts[0], (int)$timeParts[1]);
        
        // Если событие в будущем - включаем
        if ($eventDateTime > $currentDateTime) {
            $filteredEvents[] = $event;
        } else {
            echo "⏰ Событие '$event[title_ru]' $event[date] $eventTime уже прошло, пропускаем\n";
        }
    }
    
    $events = $filteredEvents;
    echo "Событий после фильтрации: " . count($events) . "\n";
    
    // Формируем сообщение
    $message = "=======\n";
    $message .= "Оля, это тебе рыба сообщения\n";
    $message .= "+++++++\n\n";
    $message .= "📅 События на следующую неделю\n";
    $message .= "С " . $monday->format('d.m.Y') . " по " . $sunday->format('d.m.Y') . "\n\n";
    
    if (empty($events)) {
        echo "❌ Нет активных событий на следующую неделю, отправка отменена\n";
        exit;
    } else {
        $currentDate = null;
        
        foreach ($events as $event) {
            $eventDate = new DateTime($event['date']);
            $dateStr = $eventDate->format('d.m.Y');
            
            // Добавляем заголовок дня, если дата изменилась
            if ($currentDate !== $dateStr) {
                if ($currentDate !== null) {
                    $message .= "\n";
                }
                
                $weekday = [
                    'Monday' => 'Понедельник',
                    'Tuesday' => 'Вторник', 
                    'Wednesday' => 'Среда',
                    'Thursday' => 'Четверг',
                    'Friday' => 'Пятница',
                    'Saturday' => 'Суббота',
                    'Sunday' => 'Воскресенье'
                ][$eventDate->format('l')];
                
                $currentDate = $dateStr;
            }
            
            // Добавляем событие
            $title = $event['title_ru'] ?? $event['title'] ?? 'Без названия';
            $time = $event['time'] ?? 'Время не указано';
            $conditions = $event['conditions_ru'] ?? $event['conditions'] ?? '';
            $description = $event['description_ru'] ?? $event['description'] ?? $event['comment'] ?? '';
            
            $message .= "🗓️ **$weekday, $dateStr**  $time\n";
            $message .= "$title\n";
            
            // Добавляем описание события или предупреждение
            if (!empty($description)) {
                $message .= "$description\n";
            } else {
                $message .= "!!!!!!!ОПИСАНИЕ ОТСУТСТВУЕТ!!!!\n";
            }
            
            // Добавляем условия участия, если есть
            if (!empty($conditions)) {
                $message .= "📝 $conditions\n";
            }
            
            // Добавляем ссылку, если есть
            if (!empty($event['link'])) {
                $message .= "🔗 [Подробнее](" . $event['link'] . ")\n";
            }
            
            $message .= "\n";
        }
    }
    
    // Отправляем сообщение в группу Силы
    $telegramService = new TelegramService();
    $silaGroupId = '-1002745794705'; // Группа Силы
    
    echo "📤 Отправка сообщения в группу Силы ($silaGroupId)...\n";
    
    $result = $telegramService->sendMessage($silaGroupId, $message);
    
    if ($result) {
        echo "✅ Сообщение успешно отправлено в группу Силы!\n";
        echo "📊 Отправлено событий: " . count($events) . "\n";
    } else {
        echo "❌ Ошибка отправки сообщения в группу Силы\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
