<?php
// API для работы с событиями
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../../vendor/autoload.php';

// Проверка авторизации без редиректа
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Не авторизован'
    ]);
    exit;
}

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
            // Получаем данные из JSON
            $eventId = $input['event_id'] ?? null;
            
            if (empty($eventId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID события обязателен'
                ]);
                exit;
            }
            
            // Получаем данные из JSON
            $title = $input['title'] ?? '';
            $date = $input['date'] ?? '';
            $time = $input['time'] ?? '';
            $conditions = $input['conditions'] ?? '';
            $description_link = $input['description_link'] ?? null;
            $comment = $input['comment'] ?? null;
            $is_active = $input['is_active'] ?? true;
            
            try {
                $eventId = new MongoDB\BSON\ObjectId($eventId);
                
                $updateData = [
                    'title' => $title,
                    'date' => $date,
                    'time' => $time,
                    'conditions' => $conditions,
                    'description_link' => $description_link,
                    'comment' => $comment,
                    'is_active' => $is_active,
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
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный формат ID события'
                ]);
            }
            break;
            
        case 'DELETE':
            // Удалить событие
            // Получаем данные из JSON
            $eventId = $input['event_id'] ?? null;
            
            // Отладочная информация
            error_log("DELETE запрос - event_id: " . ($eventId ?? 'null'));
            error_log("INPUT данные: " . json_encode($input));
            
            if (empty($eventId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID события обязателен'
                ]);
                exit;
            }
            
            try {
                $eventId = new MongoDB\BSON\ObjectId($eventId);
                
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
            } catch (Exception $e) {
                error_log("Ошибка удаления события: " . $e->getMessage());
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный формат ID события: ' . $e->getMessage()
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
