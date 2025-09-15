<?php
// Инициализация коллекции событий в MongoDB
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
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27018';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    // Создаем индексы для оптимизации запросов
    $eventsCollection->createIndex(['date' => 1, 'time' => 1]);
    $eventsCollection->createIndex(['created_at' => -1]);
    $eventsCollection->createIndex(['is_active' => 1]);
    
    // Создаем 5 дефолтных событий
    $defaultEvents = [
        [
            'title' => 'Дегустация вин',
            'date' => '2024-12-25',
            'time' => '19:00',
            'conditions' => '1500 руб. с человека',
            'description_link' => 'https://example.com/wine-tasting',
            'image' => null,
            'comment' => 'Внутренний комментарий для админов',
            'is_active' => true,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'title' => 'Новогодний банкет',
            'date' => '2024-12-31',
            'time' => '20:00',
            'conditions' => '3000 руб. с человека, предварительная запись',
            'description_link' => 'https://example.com/new-year-banquet',
            'image' => null,
            'comment' => 'Главное событие года',
            'is_active' => true,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'title' => 'Мастер-класс по приготовлению пасты',
            'date' => '2025-01-15',
            'time' => '18:30',
            'conditions' => 'Бесплатно при заказе от 2000 руб.',
            'description_link' => 'https://example.com/pasta-masterclass',
            'image' => null,
            'comment' => 'Популярное мероприятие',
            'is_active' => true,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'title' => 'Романтический ужин на День Святого Валентина',
            'date' => '2025-02-14',
            'time' => '19:30',
            'conditions' => '2500 руб. за пару, специальное меню',
            'description_link' => 'https://example.com/valentine-dinner',
            'image' => null,
            'comment' => 'Сезонное мероприятие',
            'is_active' => true,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'title' => 'День рождения ресторана',
            'date' => '2025-03-20',
            'time' => '18:00',
            'conditions' => 'Вход свободный, специальные предложения',
            'description_link' => 'https://example.com/restaurant-birthday',
            'image' => null,
            'comment' => 'Юбилейное мероприятие',
            'is_active' => true,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];
    
    // Очищаем коллекцию перед добавлением дефолтных событий
    $eventsCollection->deleteMany([]);
    
    // Вставляем дефолтные события
    $result = $eventsCollection->insertMany($defaultEvents);
    
    if ($result->getInsertedCount() > 0) {
        echo "✅ Коллекция событий создана успешно!\n";
        echo "📊 Созданы индексы для оптимизации\n";
        echo "🎯 Добавлено " . $result->getInsertedCount() . " дефолтных событий\n";
        echo "🆔 ID событий: " . implode(', ', $result->getInsertedIds()) . "\n";
    } else {
        echo "❌ Ошибка при создании коллекции\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>