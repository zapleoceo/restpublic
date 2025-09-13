<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

// Обработка preflight запросов
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Проверяем метод запроса
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Загружаем переменные окружения
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    // Подключаемся к MongoDB
    require_once __DIR__ . '/../classes/MenuCache.php';
    $menuCache = new MenuCache();
    
    // Получаем столы из MongoDB
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->selectDatabase($dbName);
    $menuCollection = $db->selectCollection('menu');
    
    // Получаем столы из документа current_tables
    $tablesDoc = $menuCollection->findOne(['_id' => 'current_tables']);
    
    $formattedTables = [];
    
    if ($tablesDoc && isset($tablesDoc['tables'])) {
        // Форматируем столы для frontend
        foreach ($tablesDoc['tables'] as $table) {
            $formattedTables[] = [
                'id' => $table['table_id'] ?? $table['_id']->__toString() ?? uniqid(),
                'table_id' => $table['table_id'] ?? $table['_id']->__toString() ?? uniqid(),
                'name' => $table['table_title'] ?? $table['name'] ?? 'Стол ' . ($table['table_num'] ?? $table['table_id'] ?? ''),
                'capacity' => $table['table_seats'] ?? $table['capacity'] ?? 2,
                'status' => ($table['is_deleted'] ?? 0) === 0 ? 'available' : 'unavailable'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'tables' => $formattedTables,
        'count' => count($formattedTables)
    ]);
    
} catch (Exception $e) {
    error_log("Tables API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch tables from MongoDB',
        'message' => $e->getMessage()
    ]);
}
?>
