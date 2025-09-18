<?php
// Скрипт для отправки списка событий на завтра

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
    
    // Получаем завтрашнюю дату
    $tomorrow = new DateTime();
    $tomorrow->add(new DateInterval('P1D'));
    $tomorrowStr = $tomorrow->format('Y-m-d');
    
    echo "📅 Поиск событий на завтра: $tomorrowStr\n";
    
    // Получаем все события на завтра
    $events = $eventsCollection->find([
        'date' => $tomorrowStr,
        'is_active' => true
    ], [
        'sort' => ['time' => 1]
    ])->toArray();
    
    echo "Найдено событий: " . count($events) . "\n";
    
    // Формируем сообщение
    $weekday = [
        'Monday' => 'Понедельник',
        'Tuesday' => 'Вторник', 
        'Wednesday' => 'Среда',
        'Thursday' => 'Четверг',
        'Friday' => 'Пятница',
        'Saturday' => 'Суббота',
        'Sunday' => 'Воскресенье'
    ][$tomorrow->format('l')];
    
    // Случайные приветствия
    $greetings = [
        "🎉 Завтра будет интересно!",
        "⭐️ Напоминаем о завтрашних мероприятиях!",
        "🔥 Не пропустите завтрашние события!",
        "🚀 Готовьтесь к завтрашнему дню!",
        "💫 Завтра вас ждет что-то особенное!",
        "🎯 Планы на завтра готовы!",
        "⚡️ Завтрашняя программа ждет вас!"
    ];
    
    $randomGreeting = $greetings[array_rand($greetings)];
    
    if (empty($events)) {
        $message = "$randomGreeting\n\n❌ На завтра событий не запланировано";
    } else {
        $message = "$randomGreeting\n\n";
        
        foreach ($events as $event) {
            // Добавляем событие
            $title = $event['title_ru'] ?? $event['title'] ?? 'Без названия';
            $time = $event['time'] ?? 'Время не указано';
            $conditions = $event['conditions_ru'] ?? $event['conditions'] ?? '';
            $description = $event['description_ru'] ?? $event['description'] ?? '';
            
            $message .= "📅 " . $tomorrow->format('d.m.Y') . " в $time\n";
            $message .= "$title\n\n";
            
            // Добавляем описание события, если есть
            if (!empty($description)) {
                $message .= "$description\n\n";
            }
            
            // Добавляем условия участия, если есть
            if (!empty($conditions)) {
                $message .= "💰 Участие: $conditions\n";
            }
            
            $message .= "🎯 Приходите, будет здорово!\n\n";
            
            // Добавляем ссылку, если есть
            if (!empty($event['link'])) {
                $message .= "👆 [Подробности тут](" . $event['link'] . ")\n";
            }
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