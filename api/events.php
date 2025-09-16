<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Подключение к базе данных (замените на свои данные)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=north_republic;charset=utf8', 'username', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Возвращаем тестовые данные при ошибке подключения
    echo json_encode([
        [
            'id' => 1,
            'title' => 'Music Festival',
            'event_date' => date('Y-m-d'),
            'price' => 2500,
            'image' => '/images/events/music-festival.jpg',
            'description' => 'Грандиозный музыкальный фестиваль с участием лучших исполнителей'
        ],
        [
            'id' => 2,
            'title' => 'Business Conference',
            'event_date' => date('Y-m-d', strtotime('+1 day')),
            'price' => 1500,
            'image' => '/images/events/business-conference.jpg',
            'description' => 'Конференция для бизнес-лидеров и предпринимателей'
        ],
        [
            'id' => 3,
            'title' => 'Art Exhibition',
            'event_date' => date('Y-m-d', strtotime('+2 days')),
            'price' => 800,
            'image' => '/images/events/art-exhibition.jpg',
            'description' => 'Выставка современного искусства от местных художников'
        ],
        [
            'id' => 4,
            'title' => 'Food Festival',
            'event_date' => date('Y-m-d', strtotime('+3 days')),
            'price' => 1200,
            'image' => '/images/events/food-festival.jpg',
            'description' => 'Фестиваль кулинарного искусства и гастрономии'
        ],
        [
            'id' => 5,
            'title' => 'Tech Meetup',
            'event_date' => date('Y-m-d', strtotime('+5 days')),
            'price' => 500,
            'image' => '/images/events/tech-meetup.jpg',
            'description' => 'Встреча IT-специалистов и обсуждение новых технологий'
        ]
    ]);
    exit;
}

try {
    // Получаем события из базы данных
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            title, 
            event_date, 
            price, 
            image, 
            description
        FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC 
        LIMIT 20
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Преобразуем price в число
    foreach ($events as &$event) {
        $event['price'] = (int)$event['price'];
    }
    
    echo json_encode($events);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
