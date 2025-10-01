<?php
// Скрипт для вечерней отправки анонса событий на завтра в группу GameZone
// Запускается в 21:12 по вьетнамскому времени (14:12 UTC)

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
    
    // Фильтруем события, которые уже прошли (для завтрашних событий это не актуально, но оставляем для консистентности)
    $currentTime = $tomorrow->format('H:i');
    $filteredEvents = [];
    
    foreach ($events as $event) {
        $eventTime = $event['time'] ?? '00:00';
        // Для завтрашних событий всегда включаем, так как они еще не прошли
        $filteredEvents[] = $event;
    }
    
    $events = $filteredEvents;
    echo "Событий после фильтрации: " . count($events) . "\n";
    
    // Случайные вечерние приветствия
    $greetings = [
        "🌙 Добрый вечер! Завтра будет интересно!",
        "⭐️ Вечернее напоминание о завтрашних мероприятиях!",
        "🔥 Не забудьте про завтрашние события!",
        "🚀 Готовьтесь к завтрашнему дню!",
        "💫 Завтра вас ждет что-то особенное!",
        "🎯 Планы на завтра готовы!",
        "⚡️ Завтрашняя программа ждет вас!",
        "🌆 Вечерний анонс на завтра!",
        "🎭 Завтра будет весело!",
        "🌟 Не пропустите завтрашние события!"
    ];
    
    $randomGreeting = $greetings[array_rand($greetings)];
    
    if (empty($events)) {
        echo "❌ Нет активных событий на завтра, отправка отменена\n";
        exit;
    } else {
        $message = "$randomGreeting\n\n";
        
        foreach ($events as $event) {
            // Добавляем событие
            $title = $event['title_ru'] ?? $event['title'] ?? 'Без названия';
            $time = $event['time'] ?? 'Время не указано';
            $conditions = $event['conditions_ru'] ?? $event['conditions'] ?? '';
            $description = $event['description_ru'] ?? $event['description'] ?? $event['comment'] ?? '';
            
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
        echo "📊 Отправлено событий на завтра: " . count($events) . "\n";
    } else {
        echo "❌ Ошибка отправки сообщения в GameZone\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
