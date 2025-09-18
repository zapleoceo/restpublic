<?php
// Скрипт для отправки списка событий на текущую неделю

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
    
    // Получаем текущую дату и находим понедельник текущей недели
    $today = new DateTime();
    $dayOfWeek = (int)$today->format('N'); // 1 = понедельник, 7 = воскресенье
    $monday = clone $today;
    $monday->sub(new DateInterval('P' . ($dayOfWeek - 1) . 'D'));
    
    // Находим воскресенье текущей недели
    $sunday = clone $monday;
    $sunday->add(new DateInterval('P6D'));
    
    $mondayStr = $monday->format('Y-m-d');
    $sundayStr = $sunday->format('Y-m-d');
    
    echo "📅 Поиск событий с $mondayStr по $sundayStr\n";
    
    // Получаем все события на текущую неделю
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
    
    // Формируем сообщение
    $message = "📅 **События на неделю**\n";
    $message .= "С " . $monday->format('d.m.Y') . " по " . $sunday->format('d.m.Y') . "\n\n";
    
    if (empty($events)) {
        $message .= "❌ На эту неделю событий не запланировано";
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
            $description = $event['description_ru'] ?? $event['description'] ?? '';
            
            $message .= "🗓️ **$weekday, $dateStr**  $time\n";
            $message .= "$title\n";
            
            // Добавляем описание события, если есть
            if (!empty($description)) {
                $message .= "$description\n";
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
    
    // Отправляем сообщение
    $telegramService = new TelegramService();
    $chatId = '169510539'; // Дима
    
    echo "📤 Отправка сообщения в чат $chatId...\n";
    
    $result = $telegramService->sendMessage($chatId, $message);
    
    if ($result) {
        echo "✅ Сообщение успешно отправлено!\n";
        echo "📊 Отправлено событий: " . count($events) . "\n";
    } else {
        echo "❌ Ошибка отправки сообщения\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
