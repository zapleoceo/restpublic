<?php
// Инициализация коллекции событий в MongoDB
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

// Создаем JSON файл с дефолтными событиями
$defaultEvents = [
    [
        'id' => '1',
        'title' => 'Дегустация вин',
        'date' => '2024-12-25',
        'time' => '19:00',
        'conditions' => '1500 руб. с человека',
        'description_link' => 'https://example.com/wine-tasting',
        'image' => null,
        'comment' => 'Внутренний комментарий для админов',
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => '2',
        'title' => 'Новогодний банкет',
        'date' => '2024-12-31',
        'time' => '20:00',
        'conditions' => '3000 руб. с человека, предварительная запись',
        'description_link' => 'https://example.com/new-year-banquet',
        'image' => null,
        'comment' => 'Главное событие года',
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => '3',
        'title' => 'Мастер-класс по приготовлению пасты',
        'date' => '2025-01-15',
        'time' => '18:30',
        'conditions' => 'Бесплатно при заказе от 2000 руб.',
        'description_link' => 'https://example.com/pasta-masterclass',
        'image' => null,
        'comment' => 'Популярное мероприятие',
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => '4',
        'title' => 'Романтический ужин на День Святого Валентина',
        'date' => '2025-02-14',
        'time' => '19:30',
        'conditions' => '2500 руб. за пару, специальное меню',
        'description_link' => 'https://example.com/valentine-dinner',
        'image' => null,
        'comment' => 'Сезонное мероприятие',
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ],
    [
        'id' => '5',
        'title' => 'День рождения ресторана',
        'date' => '2025-03-20',
        'time' => '18:00',
        'conditions' => 'Вход свободный, специальные предложения',
        'description_link' => 'https://example.com/restaurant-birthday',
        'image' => null,
        'comment' => 'Юбилейное мероприятие',
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

// Создаем папку для данных, если её нет
$dataDir = __DIR__ . '/../../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Сохраняем события в JSON файл
$eventsFile = $dataDir . '/events.json';
if (file_put_contents($eventsFile, json_encode($defaultEvents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "✅ Файл событий создан успешно!\n";
    echo "📁 Путь: $eventsFile\n";
    echo "🎯 Добавлено " . count($defaultEvents) . " дефолтных событий\n";
    echo "📊 События сохранены в JSON формате\n";
} else {
    echo "❌ Ошибка при создании файла событий\n";
}
?>