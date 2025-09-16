<?php
// API для работы с событиями
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/ImageService.php';

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
    
    // Обрабатываем данные в зависимости от Content-Type
    if ($method === 'POST' || $method === 'PUT') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        error_log("Content-Type: " . $contentType);
        error_log("_FILES: " . print_r($_FILES, true));
        error_log("_POST: " . print_r($_POST, true));
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            // FormData (для загрузки файлов)
            $input = $_POST;
        } else {
            // JSON
            $input = json_decode(file_get_contents('php://input'), true);
        }
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
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
                
                // Безопасная конвертация дат
                if (isset($event['created_at']) && $event['created_at'] instanceof MongoDB\BSON\UTCDateTime) {
                    $event['created_at'] = $event['created_at']->toDateTime()->format('Y-m-d H:i:s');
                }
                if (isset($event['updated_at']) && $event['updated_at'] instanceof MongoDB\BSON\UTCDateTime) {
                    $event['updated_at'] = $event['updated_at']->toDateTime()->format('Y-m-d H:i:s');
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
            break;
            
        case 'POST':
            // Создать новое событие
            error_log("POST запрос - input: " . print_r($input, true));
            
            $requiredFields = ['title', 'date', 'time', 'conditions'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    error_log("Валидация не пройдена: поле '$field' пустое. Значение: '" . ($input[$field] ?? 'null') . "'");
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Поле '$field' обязательно для заполнения"
                    ]);
                    exit;
                }
            }
            
            // Валидация формата даты
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный формат даты. Используйте YYYY-MM-DD'
                ]);
                exit;
            }
            
            // Валидация формата времени
            if (!preg_match('/^\d{2}:\d{2}$/', $input['time'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный формат времени. Используйте HH:MM'
                ]);
                exit;
            }
            
            // Правильная обработка is_active (checkbox приходит как 'on' или отсутствует)
            $isActive = isset($input['is_active']) && $input['is_active'] !== false && $input['is_active'] !== 'false' && $input['is_active'] !== '0';
            
            // Обработка загрузки изображения в GridFS
            $imageData = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageService = new ImageService();
                $validation = $imageService->validateImage($_FILES['image']);
                
                if ($validation['valid']) {
                    $fileData = file_get_contents($_FILES['image']['tmp_name']);
                    $imageData = $imageService->saveImage($fileData, $_FILES['image']['name'], [
                        'event_type' => 'new_event'
                    ]);
                    error_log("POST - Image saved to GridFS: " . $imageData['file_id']);
                } else {
                    error_log("POST - Image validation failed: " . $validation['error']);
                }
            }
            
            $eventData = [
                'title' => trim($input['title']),
                'date' => $input['date'],
                'time' => $input['time'],
                'conditions' => trim($input['conditions']),
                'description_link' => !empty($input['description_link']) ? trim($input['description_link']) : null,
                'image' => $imageData ? $imageData['file_id'] : null,
                'comment' => !empty($input['comment']) ? trim($input['comment']) : null,
                'is_active' => $isActive,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            // Отладочная информация
            error_log("POST запрос - description_link: " . ($input['description_link'] ?? 'null'));
            
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
            // Получаем данные из FormData или JSON
            $eventId = $input['event_id'] ?? null;
            
            if (empty($eventId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID события обязателен'
                ]);
                exit;
            }
            
            // Валидация обязательных полей
            $requiredFields = ['title', 'date', 'time', 'conditions'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    error_log("Валидация не пройдена: поле '$field' пустое. Значение: '" . ($input[$field] ?? 'null') . "'");
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Поле '$field' обязательно для заполнения"
                    ]);
                    exit;
                }
            }
            
            // Валидация формата даты
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный формат даты. Используйте YYYY-MM-DD'
                ]);
                exit;
            }
            
            // Валидация формата времени
            if (!preg_match('/^\d{2}:\d{2}$/', $input['time'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Неверный формат времени. Используйте HH:MM'
                ]);
                exit;
            }
            
            // Получаем и санитизируем данные
            $title = trim($input['title']);
            $date = $input['date'];
            $time = $input['time'];
            $conditions = trim($input['conditions']);
            $description_link = !empty($input['description_link']) ? trim($input['description_link']) : null;
            $comment = !empty($input['comment']) ? trim($input['comment']) : null;
            
            // Правильная обработка is_active (checkbox приходит как 'on' или отсутствует)
            $is_active = isset($input['is_active']) && $input['is_active'] !== false && $input['is_active'] !== 'false' && $input['is_active'] !== '0';
            
            // Обработка загрузки изображения для редактирования в GridFS
            $imageData = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageService = new ImageService();
                $validation = $imageService->validateImage($_FILES['image']);
                
                if ($validation['valid']) {
                    $fileData = file_get_contents($_FILES['image']['tmp_name']);
                    $imageData = $imageService->saveImage($fileData, $_FILES['image']['name'], [
                        'event_type' => 'updated_event',
                        'original_event_id' => $eventId
                    ]);
                    error_log("PUT - Image saved to GridFS: " . $imageData['file_id']);
                } else {
                    error_log("PUT - Image validation failed: " . $validation['error']);
                }
            } else {
                error_log("PUT - No image file or upload error: " . ($_FILES['image']['error'] ?? 'no file'));
            }
            
            // Отладочная информация
            error_log("PUT запрос - description_link: " . ($description_link ?? 'null'));
            
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
                
                // Обновляем изображение только если загружено новое
                if ($imageData !== null) {
                    $updateData['image'] = $imageData['file_id'];
                    error_log("PUT - Updating with new image: " . $imageData['file_id']);
                } else {
                    // Если изображение не загружено, оставляем существующее
                    $existingEvent = $eventsCollection->findOne(['_id' => $eventId]);
                    if ($existingEvent && isset($existingEvent['image'])) {
                        $updateData['image'] = $existingEvent['image'];
                        error_log("PUT - Keeping existing image: " . $existingEvent['image']);
                    } else {
                        $updateData['image'] = null;
                        error_log("PUT - No image");
                    }
                }
                
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
