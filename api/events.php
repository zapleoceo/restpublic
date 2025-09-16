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
    
    // Get parameters from query
    $startDate = $_GET['start_date'] ?? date('Y-m-d'); // По умолчанию сегодня
    $days = (int)($_GET['days'] ?? 14); // По умолчанию 14 дней
    $language = $_GET['language'] ?? 'ru'; // По умолчанию русский
    
    // Get events from MongoDB for specified period
    $events = $eventsService->getEventsForWidget($startDate, $days, $language);
    
    // Convert to format expected by events widget
    $formattedEvents = [];
    foreach ($events as $event) {
        $formattedEvents[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'description' => $event['description'],
            'conditions' => $event['conditions'],
            'date' => $event['date'],
            'time' => $event['time'],
            'image' => $event['image'],
            'link' => $event['link'],
            'category' => $event['category'] ?? 'general'
        ];
    }
    
    echo json_encode($formattedEvents, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Ошибка API событий: " . $e->getMessage());
    
    // Возвращаем пустой массив вместо фейковых данных
    echo json_encode([], JSON_UNESCAPED_UNICODE);
}
?>
