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
    $eventsOnly = isset($_GET['events_only']) && $_GET['events_only'] === 'true';
    
    if ($eventsOnly) {
        // Return only events for specific date
        $events = $eventsService->getEventsForWidget($startDate, 7);
        echo json_encode(['events' => $events], JSON_UNESCAPED_UNICODE);
    } else {
        // Return calendar and events
        $calendar = $eventsService->getCalendarDays($startDate, 7);
        $events = $eventsService->getEventsForWidget($startDate, 7);
        
        echo json_encode([
            'calendar' => $calendar,
            'events' => $events
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
