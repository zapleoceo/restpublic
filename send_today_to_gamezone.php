<?php
// Скрипт для отправки анонса событий на сегодня в группу GameZone

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
    
    // Получаем сегодняшнюю дату
    $today = new DateTime();
    $todayStr = $today->format('Y-m-d');
    
    echo "📅 Поиск событий на сегодня: $todayStr\n";
    
    // Получаем все события на сегодня
    $events = $eventsCollection->find([
        'date' => $todayStr,
        'is_active' => true
    ], [
        'sort' => ['time' => 1]
    ])->toArray();
    
    echo "Найдено событий: " . count($events) . "\n";
    
    // Случайные приветствия
    $greetings = [
        "🎉 Сегодня будет интересно!",
        "⭐️ Напоминаем о сегодняшних мероприятиях!",
        "🔥 Не пропустите сегодняшние события!",
        "🚀 Готовьтесь к сегодняшнему дню!",
        "💫 Сегодня вас ждет что-то особенное!",
        "🎯 Планы на сегодня готовы!",
        "⚡️ Сегодняшняя программа ждет вас!"
    ];
    
    $randomGreeting = $greetings[array_rand($greetings)];
    
    if (empty($events)) {
        $message = "$randomGreeting\n\n❌ На сегодня событий не запланировано";
    } else {
        $message = "$randomGreeting\n\n";
        
        foreach ($events as $event) {
            // Добавляем событие
            $title = $event['title_ru'] ?? $event['title'] ?? 'Без названия';
            $time = $event['time'] ?? 'Время не указано';
            $conditions = $event['conditions_ru'] ?? $event['conditions'] ?? '';
            $description = $event['comment'] ?? $event['description_ru'] ?? $event['description'] ?? '';
            
            $message .= "📅 " . $today->format('d.m.Y') . " в $time\n";
            $message .= "$title\n\n";
            
            // Добавляем описание события, если есть
            if (!empty($description)) {
                $message .= "$description\n\n";
            }
            
            // Добавляем условия участия, если есть
            if (!empty($conditions)) {
                $message .= "💰 Участие: $conditions\n";
            }
            
            // Случайные призывы к участию
            $participationCalls = [
                "🎉 Будет круто, не пропустите!",
                "⚡️ Ждем всех на мероприятии!",
                "🔥 Присоединяйтесь к нам!",
                "👋 Увидимся на событии!",
                "💫 Приходите, будет весело!",
                "🎯 Не упустите возможность!",
                "⭐️ Будем рады видеть вас!",
                "🚀 Приходите, будет классно!",
                "💥 Ждем всех желающих!",
                "🎪 Будет интересно и увлекательно!"
            ];
            
            $randomCall = $participationCalls[array_rand($participationCalls)];
            $message .= "$randomCall\n\n";
            
            // Добавляем ссылку, если есть
            if (!empty($event['link'])) {
                $message .= "👆 [Подробности тут](" . $event['link'] . ")\n";
            }
        }
    }
    
    // Отправляем сообщение в топик GameZone
    $telegramService = new TelegramService();
    $gamezoneChatId = '-1002027215854'; // GameZone группа
    $topicId = 2117; // ID топика
    
    echo "📤 Отправка сообщения в топик GameZone ($gamezoneChatId, топик $topicId)...\n";
    
    $result = $telegramService->sendMessageToTopic($gamezoneChatId, $message, $topicId);
    
    if ($result) {
        echo "✅ Сообщение успешно отправлено в GameZone!\n";
        echo "📊 Отправлено событий на сегодня: " . count($events) . "\n";
    } else {
        echo "❌ Ошибка отправки сообщения в GameZone\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
