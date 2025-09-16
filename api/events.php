<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Load environment variables
require_once __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Initialize events service
require_once __DIR__ . '/../classes/EventsService.php';

try {
    $eventsService = new EventsService();
    
    // Get start date from query parameter
    $startDate = $_GET['start_date'] ?? null;
    $limit = (int)($_GET['limit'] ?? 20);
    
    // Get events from MongoDB
    $events = $eventsService->getEventsForWidget($startDate, $limit);
    
    // Convert to format expected by events widget
    $formattedEvents = [];
    foreach ($events as $event) {
        $formattedEvents[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'event_date' => $event['date'],
            'price' => $event['price'],
            'image' => $event['image'],
            'description' => $event['description']
        ];
    }
    
    echo json_encode($formattedEvents, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Ошибка API событий: " . $e->getMessage());
    
    // Fallback to test data if MongoDB is unavailable
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
    ], JSON_UNESCAPED_UNICODE);
}
?>
