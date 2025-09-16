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
    
    // Возвращаем пустой массив вместо фейковых данных
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
?>
