<?php
// API для работы с событиями
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Загружаем переменные окружения
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/ImageService.php';
require_once __DIR__ . '/../../classes/Logger.php';

// Проверка авторизации без редиректа - ВРЕМЕННО ОТКЛЮЧЕНО ДЛЯ ТЕСТИРОВАНИЯ
session_start();
/*
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Не авторизован'
    ]);
    exit;
}
*/

try {
    // Подключение к MongoDB
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DATABASE'] ?? 'veranda';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    // Инициализация логгера
    $logger = new Logger();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Обрабатываем данные в зависимости от Content-Type
    if ($method === 'POST' || $method === 'PUT') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Логируем информацию о запросе
        error_log("API Events - Method: $method, Content-Type: $contentType");
        error_log("API Events - POST data: " . print_r($_POST, true));
        error_log("API Events - FILES data: " . print_r($_FILES, true));
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            // FormData (для загрузки файлов)
            $input = $_POST;
            error_log("API Events - Using FormData input: " . print_r($input, true));
            error_log("API Events - FormData keys: " . implode(', ', array_keys($input)));
        } else {
            // JSON
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            error_log("API Events - Using JSON input: " . print_r($input, true));
        }
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    switch ($method) {
        case 'GET':
            // Проверяем, запрашиваются ли только картинки
            if (isset($_GET['action']) && $_GET['action'] === 'get_images') {
                // Получить все уникальные картинки из событий
                $pipeline = [
                    [
                        '$match' => [
                            'image' => ['$exists' => true, '$ne' => null]
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => '$image',
                            'count' => ['$sum' => 1],
                            'first_event' => ['$first' => [
                                'title' => '$title',
                                'date' => '$date',
                                'time' => '$time'
                            ]]
                        ]
                    ],
                    [
                        '$sort' => ['count' => -1, 'first_event.date' => 1]
                    ]
                ];
                
                $images = $eventsCollection->aggregate($pipeline)->toArray();
                
                // Формируем результат
                $result = [];
                foreach ($images as $image) {
                    $result[] = [
                        'image_id' => $image['_id'],
                        'usage_count' => $image['count'],
                        'first_used_in' => $image['first_event']['title'],
                        'first_used_date' => $image['first_event']['date'] . ' ' . $image['first_event']['time']
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
                exit;
            }
            
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
            
            // Логируем просмотр событий
            $logger->log('view_events', 'Просмотр списка событий', [
                'events_count' => count($events)
            ], [
                'username' => $_SESSION['admin_username'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => $events
            ]);
            break;
            
        case 'POST':
            // Создать новое событие или обновить существующее (если есть event_id)
            
            // Проверяем, это обновление или создание
            $isUpdate = !empty($input['event_id']);
            
            if ($isUpdate) {
                // Это обновление существующего события
                $eventId = $input['event_id'];
                error_log("API Events - POST UPDATE request for event_id: " . $eventId);
                
                // Валидация обязательных полей для обновления
                $requiredFields = ['title_ru', 'date', 'time', 'conditions_ru'];
                foreach ($requiredFields as $field) {
                    if (empty($input[$field])) {
                        error_log("API Events - Validation error: missing field '$field'");
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
                $title_ru = trim($input['title_ru']);
                $title_en = !empty($input['title_en']) ? trim($input['title_en']) : $title_ru;
                $title_vi = !empty($input['title_vi']) ? trim($input['title_vi']) : $title_ru;
                $description_ru = !empty($input['description_ru']) ? trim($input['description_ru']) : '';
                $description_en = !empty($input['description_en']) ? trim($input['description_en']) : $description_ru;
                $description_vi = !empty($input['description_vi']) ? trim($input['description_vi']) : $description_ru;
                $conditions_ru = trim($input['conditions_ru']);
                $conditions_en = !empty($input['conditions_en']) ? trim($input['conditions_en']) : $conditions_ru;
                $conditions_vi = !empty($input['conditions_vi']) ? trim($input['conditions_vi']) : $conditions_ru;
                $date = $input['date'];
                $time = $input['time'];
                $link = !empty($input['link']) ? trim($input['link']) : null;
                $category = !empty($input['category']) ? trim($input['category']) : 'general';
                
                // Правильная обработка is_active
                $is_active = isset($input['is_active']) && $input['is_active'] !== false && $input['is_active'] !== 'false' && $input['is_active'] !== '0';
                
                // Обработка загрузки изображения для редактирования в GridFS
                $imageData = null;
                
                // Проверяем, выбрано ли существующее изображение
                if (isset($input['existing_image_id']) && !empty($input['existing_image_id'])) {
                    $imageData = ['file_id' => $input['existing_image_id']];
                    error_log("API Events - Using existing image ID: " . $input['existing_image_id']);
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $imageService = new ImageService();
                    $validation = $imageService->validateImage($_FILES['image']);
                    
                    if ($validation['valid']) {
                        try {
                            $fileData = file_get_contents($_FILES['image']['tmp_name']);
                            $imageData = $imageService->saveImage($fileData, $_FILES['image']['name'], [
                                'event_type' => 'updated_event',
                                'original_event_id' => $eventId
                            ]);
                        } catch (Exception $e) {
                            http_response_code(400);
                            echo json_encode([
                                'success' => false,
                                'message' => 'Ошибка загрузки изображения: ' . $e->getMessage()
                            ]);
                            exit;
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Ошибка валидации изображения: ' . $validation['error']
                        ]);
                        exit;
                    }
                }
                
                try {
                    $eventId = new MongoDB\BSON\ObjectId($eventId);
                    
                    $updateData = [
                        'title_ru' => $title_ru,
                        'title_en' => $title_en,
                        'title_vi' => $title_vi,
                        'description_ru' => $description_ru,
                        'description_en' => $description_en,
                        'description_vi' => $description_vi,
                        'conditions_ru' => $conditions_ru,
                        'conditions_en' => $conditions_en,
                        'conditions_vi' => $conditions_vi,
                        'date' => $date,
                        'time' => $time,
                        'link' => $link,
                        'category' => $category,
                        'is_active' => $is_active,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ];
                    
                    // Обновляем изображение только если загружено новое
                    if ($imageData !== null) {
                        $updateData['image'] = $imageData['file_id'];
                    } else {
                        // Если изображение не загружено, оставляем существующее
                        $existingEvent = $eventsCollection->findOne(['_id' => $eventId]);
                        if ($existingEvent && isset($existingEvent['image'])) {
                            $updateData['image'] = $existingEvent['image'];
                        } else {
                            $updateData['image'] = null;
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
            }
            
            // Это создание нового события
            $requiredFields = ['title_ru', 'date', 'time', 'conditions_ru'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    // Логируем ошибку валидации
                    $logger->log('validation_error', 'Ошибка валидации при создании события', [
                        'missing_field' => $field,
                        'provided_data' => array_keys($input)
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
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
            
            // Проверяем, выбрано ли существующее изображение
            if (isset($input['existing_image_id']) && !empty($input['existing_image_id'])) {
                $imageData = ['file_id' => $input['existing_image_id']];
                error_log("API Events - Using existing image ID for new event: " . $input['existing_image_id']);
            } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageService = new ImageService();
                $validation = $imageService->validateImage($_FILES['image']);
                
                if ($validation['valid']) {
                    try {
                        $fileData = file_get_contents($_FILES['image']['tmp_name']);
                        $imageData = $imageService->saveImage($fileData, $_FILES['image']['name'], [
                            'event_type' => 'new_event'
                        ]);
                    } catch (Exception $e) {
                        // Логируем ошибку загрузки изображения
                        $logger->log('image_upload_error', 'Ошибка загрузки изображения при создании события', [
                            'error_message' => $e->getMessage(),
                            'filename' => $_FILES['image']['name']
                        ], [
                            'username' => $_SESSION['admin_username'] ?? 'unknown',
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Ошибка загрузки изображения: ' . $e->getMessage()
                        ]);
                        exit;
                    }
                } else {
                    // Логируем ошибку валидации изображения
                    $logger->log('image_validation_error', 'Ошибка валидации изображения при создании события', [
                        'validation_error' => $validation['error'],
                        'filename' => $_FILES['image']['name']
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ошибка валидации изображения: ' . $validation['error']
                    ]);
                    exit;
                }
            }
            
            $eventData = [
                'title_ru' => trim($input['title_ru']),
                'title_en' => !empty($input['title_en']) ? trim($input['title_en']) : trim($input['title_ru']),
                'title_vi' => !empty($input['title_vi']) ? trim($input['title_vi']) : trim($input['title_ru']),
                'description_ru' => !empty($input['description_ru']) ? trim($input['description_ru']) : '',
                'description_en' => !empty($input['description_en']) ? trim($input['description_en']) : (!empty($input['description_ru']) ? trim($input['description_ru']) : ''),
                'description_vi' => !empty($input['description_vi']) ? trim($input['description_vi']) : (!empty($input['description_ru']) ? trim($input['description_ru']) : ''),
                'conditions_ru' => trim($input['conditions_ru']),
                'conditions_en' => !empty($input['conditions_en']) ? trim($input['conditions_en']) : trim($input['conditions_ru']),
                'conditions_vi' => !empty($input['conditions_vi']) ? trim($input['conditions_vi']) : trim($input['conditions_ru']),
                'date' => $input['date'],
                'time' => $input['time'],
                'image' => $imageData ? $imageData['file_id'] : null,
                'link' => !empty($input['link']) ? trim($input['link']) : null,
                'category' => !empty($input['category']) ? trim($input['category']) : 'Музыкальное',
                'is_active' => $isActive,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            $result = $eventsCollection->insertOne($eventData);
            
            if ($result->getInsertedId()) {
                // Логируем создание события
                $logger->log('create_event', 'Создано новое событие', [
                    'event_id' => (string)$result->getInsertedId(),
                    'event_title' => $eventData['title'],
                    'event_date' => $eventData['date'],
                    'event_time' => $eventData['time'],
                    'has_image' => !empty($eventData['image']),
                    'is_active' => $eventData['is_active']
                ], [
                    'username' => $_SESSION['admin_username'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Событие создано успешно',
                    'id' => (string)$result->getInsertedId()
                ]);
            } else {
                // Логируем ошибку создания
                $logger->log('create_event_failed', 'Ошибка при создании события', [
                    'event_title' => $eventData['title'],
                    'event_date' => $eventData['date']
                ], [
                    'username' => $_SESSION['admin_username'] ?? 'unknown',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ошибка при создании события'
                ]);
            }
            break;
            
        case 'PUT':
            // Обновить событие
            $debugLog = "=== PUT REQUEST DEBUG ===\n";
            $debugLog .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
            $debugLog .= "Input data: " . print_r($input, true) . "\n";
            $debugLog .= "POST data: " . print_r($_POST, true) . "\n";
            $debugLog .= "FILES data: " . print_r($_FILES, true) . "\n";
            $debugLog .= "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
            $debugLog .= "========================\n";
            
            // Записываем в файл для отладки
            file_put_contents('/var/www/northrepubli_usr/data/logs/api_debug.log', $debugLog, FILE_APPEND);
            
            error_log("API Events - PUT request started");
            error_log("API Events - Input data: " . print_r($input, true));
            error_log("API Events - POST data: " . print_r($_POST, true));
            error_log("API Events - FILES data: " . print_r($_FILES, true));
            
            // Получаем данные из FormData или JSON
            $eventId = $input['event_id'] ?? null;
            error_log("API Events - Event ID from input: " . $eventId);
            
            // Дополнительная проверка в $_POST для FormData
            if (empty($eventId) && isset($_POST['event_id'])) {
                $eventId = $_POST['event_id'];
                error_log("API Events - Event ID from POST: " . $eventId);
            }
            
            if (empty($eventId)) {
                error_log("API Events - Missing event_id");
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID события обязателен',
                    'debug' => [
                        'input_keys' => array_keys($input),
                        'input_data' => $input
                    ]
                ]);
                exit;
            }
            
            // Валидация обязательных полей
            $requiredFields = ['title_ru', 'date', 'time', 'conditions_ru'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    error_log("API Events - Validation error: missing field '$field'");
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
            $title_ru = trim($input['title_ru']);
            $title_en = !empty($input['title_en']) ? trim($input['title_en']) : $title_ru;
            $title_vi = !empty($input['title_vi']) ? trim($input['title_vi']) : $title_ru;
            $description_ru = !empty($input['description_ru']) ? trim($input['description_ru']) : '';
            $description_en = !empty($input['description_en']) ? trim($input['description_en']) : $description_ru;
            $description_vi = !empty($input['description_vi']) ? trim($input['description_vi']) : $description_ru;
            $conditions_ru = trim($input['conditions_ru']);
            $conditions_en = !empty($input['conditions_en']) ? trim($input['conditions_en']) : $conditions_ru;
            $conditions_vi = !empty($input['conditions_vi']) ? trim($input['conditions_vi']) : $conditions_ru;
            $date = $input['date'];
            $time = $input['time'];
            $link = !empty($input['link']) ? trim($input['link']) : null;
            $category = !empty($input['category']) ? trim($input['category']) : 'general';
            
            // Правильная обработка is_active (checkbox приходит как 'on' или отсутствует)
            $is_active = isset($input['is_active']) && $input['is_active'] !== false && $input['is_active'] !== 'false' && $input['is_active'] !== '0';
            
            // Обработка загрузки изображения для редактирования в GridFS
            $imageData = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageService = new ImageService();
                $validation = $imageService->validateImage($_FILES['image']);
                
                if ($validation['valid']) {
                    try {
                        $fileData = file_get_contents($_FILES['image']['tmp_name']);
                        $imageData = $imageService->saveImage($fileData, $_FILES['image']['name'], [
                            'event_type' => 'updated_event',
                            'original_event_id' => $eventId
                        ]);
                    } catch (Exception $e) {
                        // Логируем ошибку загрузки изображения
                        $logger->log('image_upload_error', 'Ошибка загрузки изображения при обновлении события', [
                            'error_message' => $e->getMessage(),
                            'filename' => $_FILES['image']['name'],
                            'event_id' => (string)$eventId
                        ], [
                            'username' => $_SESSION['admin_username'] ?? 'unknown',
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'message' => 'Ошибка загрузки изображения: ' . $e->getMessage()
                        ]);
                        exit;
                    }
                } else {
                    // Логируем ошибку валидации изображения
                    $logger->log('image_validation_error', 'Ошибка валидации изображения при обновлении события', [
                        'validation_error' => $validation['error'],
                        'filename' => $_FILES['image']['name'],
                        'event_id' => (string)$eventId
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ошибка валидации изображения: ' . $validation['error']
                    ]);
                    exit;
                }
            }
            
            try {
                $eventId = new MongoDB\BSON\ObjectId($eventId);
                
                $updateData = [
                    'title_ru' => $title_ru,
                    'title_en' => $title_en,
                    'title_vi' => $title_vi,
                    'description_ru' => $description_ru,
                    'description_en' => $description_en,
                    'description_vi' => $description_vi,
                    'conditions_ru' => $conditions_ru,
                    'conditions_en' => $conditions_en,
                    'conditions_vi' => $conditions_vi,
                    'date' => $date,
                    'time' => $time,
                    'link' => $link,
                    'category' => $category,
                    'is_active' => $is_active,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                // Обновляем изображение только если загружено новое
                if ($imageData !== null) {
                    $updateData['image'] = $imageData['file_id'];
                } else {
                    // Если изображение не загружено, оставляем существующее
                    $existingEvent = $eventsCollection->findOne(['_id' => $eventId]);
                    if ($existingEvent && isset($existingEvent['image'])) {
                        $updateData['image'] = $existingEvent['image'];
                    } else {
                        $updateData['image'] = null;
                    }
                }
                
                $result = $eventsCollection->updateOne(
                    ['_id' => $eventId],
                    ['$set' => $updateData]
                );
                
                if ($result->getModifiedCount() > 0) {
                    // Логируем обновление события
                    $logger->log('update_event', 'Событие обновлено', [
                        'event_id' => (string)$eventId,
                        'event_title' => $title,
                        'event_date' => $date,
                        'event_time' => $time,
                        'has_image' => !empty($updateData['image']),
                        'is_active' => $is_active,
                        'image_updated' => $imageData !== null
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Событие обновлено успешно'
                    ]);
                } else {
                    // Логируем попытку обновления несуществующего события
                    $logger->log('update_event_failed', 'Попытка обновления несуществующего события', [
                        'event_id' => (string)$eventId,
                        'event_title' => $title
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
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
                
                // Получаем информацию о событии перед удалением для логирования
                $eventToDelete = $eventsCollection->findOne(['_id' => $eventId]);
                
                $result = $eventsCollection->deleteOne(['_id' => $eventId]);
                
                if ($result->getDeletedCount() > 0) {
                    // Логируем удаление события
                    $logger->log('delete_event', 'Событие удалено', [
                        'event_id' => (string)$eventId,
                        'event_title' => $eventToDelete['title'] ?? 'Unknown',
                        'event_date' => $eventToDelete['date'] ?? 'Unknown',
                        'event_time' => $eventToDelete['time'] ?? 'Unknown'
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Событие удалено успешно'
                    ]);
                } else {
                    // Логируем попытку удаления несуществующего события
                    $logger->log('delete_event_failed', 'Попытка удаления несуществующего события', [
                        'event_id' => (string)$eventId
                    ], [
                        'username' => $_SESSION['admin_username'] ?? 'unknown',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Событие не найдено'
                    ]);
                }
            } catch (Exception $e) {
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
    // Логируем общую ошибку сервера
    if (isset($logger)) {
        $logger->log('server_error', 'Внутренняя ошибка сервера в API событий', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ], [
            'username' => $_SESSION['admin_username'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Внутренняя ошибка сервера'
    ]);
}
?>
