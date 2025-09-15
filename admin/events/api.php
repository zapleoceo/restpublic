<?php
// API для работы с событиями
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    // Подключение к MongoDB
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            // Получить все события
            $events = $eventsCollection->find(
                [],
                ['sort' => ['date' => 1, 'time' => 1]]
            )->toArray();
            
            // Конвертируем ObjectId в строки для JSON
            foreach ($events as &$event) {
                $event['_id'] = (string)$event['_id'];
                $event['id'] = (string)$event['_id']; // Добавляем поле id для совместимости
                $event['created_at'] = $event['created_at']->toDateTime()->format('Y-m-d H:i:s');
                $event['updated_at'] = $event['updated_at']->toDateTime()->format('Y-m-d H:i:s');
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
            
            $eventData = [
                'title' => $input['title'],
                'date' => $input['date'],
                'time' => $input['time'],
                'conditions' => $input['conditions'],
                'description_link' => $input['description_link'] ?? null,
                'image' => $input['image'] ?? null,
                'comment' => $input['comment'] ?? null,
                'is_active' => $input['is_active'] ?? true,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $eventsCollection->insertOne($eventData);
            
            if ($result->getInsertedId()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Событие создано успешно',
                    'id' => (string)$result->getInsertedId()
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
