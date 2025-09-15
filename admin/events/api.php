<?php
// API для работы с событиями
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

try {
    $eventsFile = __DIR__ . '/../../data/events.json';
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            // Получить все события
            if (file_exists($eventsFile)) {
                $events = json_decode(file_get_contents($eventsFile), true);
                
                // Сортируем по дате и времени
                usort($events, function($a, $b) {
                    $dateA = strtotime($a['date'] . ' ' . $a['time']);
                    $dateB = strtotime($b['date'] . ' ' . $b['time']);
                    return $dateA - $dateB;
                });
            } else {
                $events = [];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
            break;
            
        case 'POST':
            // Создать новое событие
            $requiredFields = ['title', 'date', 'time', 'conditions'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Поле '$field' обязательно для заполнения"
                    ]);
                    exit;
                }
            }
            
            // Загружаем существующие события
            $events = [];
            if (file_exists($eventsFile)) {
                $events = json_decode(file_get_contents($eventsFile), true);
            }
            
            // Генерируем новый ID
            $newId = (string)(count($events) + 1);
            
            $eventData = [
                'id' => $newId,
                'title' => $input['title'],
                'date' => $input['date'],
                'time' => $input['time'],
                'conditions' => $input['conditions'],
                'description_link' => $input['description_link'] ?? null,
                'image' => $input['image'] ?? null,
                'comment' => $input['comment'] ?? null,
                'is_active' => $input['is_active'] ?? true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $events[] = $eventData;
            
            if (file_put_contents($eventsFile, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Событие создано успешно',
                    'id' => $newId
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при создании события'
                ]);
            }
            break;
            
        case 'PUT':
            // Обновить событие
            if (empty($input['event_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID события обязателен'
                ]);
                exit;
            }
            
            $eventId = new MongoDB\BSON\ObjectId($input['event_id']);
            
            $updateData = [
                'title' => $input['title'],
                'date' => $input['date'],
                'time' => $input['time'],
                'conditions' => $input['conditions'],
                'description_link' => $input['description_link'] ?? null,
                'image' => $input['image'] ?? null,
                'comment' => $input['comment'] ?? null,
                'is_active' => $input['is_active'] ?? true,
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $eventsCollection->updateOne(
                ['_id' => $eventId],
                ['$set' => $updateData]
            );
            
            if ($result->getModifiedCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Событие обновлено успешно'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Событие не найдено или не было изменений'
                ]);
            }
            break;
            
        case 'DELETE':
            // Удалить событие
            if (empty($input['event_id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID события обязателен'
                ]);
                exit;
            }
            
            $eventId = new MongoDB\BSON\ObjectId($input['event_id']);
            
            $result = $eventsCollection->deleteOne(['_id' => $eventId]);
            
            if ($result->getDeletedCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Событие удалено успешно'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Событие не найдено'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Метод не поддерживается'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Ошибка API событий: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
?>
