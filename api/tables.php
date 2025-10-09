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
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->selectDatabase($dbName);
    $menuCollection = $db->selectCollection('menu');
    
    // Получаем столы из документа current_tables
    $tablesDoc = $menuCollection->findOne(['_id' => 'current_tables']);
    error_log("DEBUG: Document _id: " . ($tablesDoc['_id'] ?? 'NOT SET'));
    error_log("DEBUG: Document updated_at: " . json_encode($tablesDoc['updated_at'] ?? 'NOT SET'));
    error_log("DEBUG: Document halls count: " . (isset($tablesDoc['halls']) ? count($tablesDoc['halls']) : 0));
    if (isset($tablesDoc['halls']) && is_array($tablesDoc['halls'])) {
        foreach ($tablesDoc['halls'] as $h) {
            error_log("DEBUG: Hall from MongoDB: hall_id=" . ($h['hall_id'] ?? 'NO ID') . ", hall_name=" . ($h['hall_name'] ?? 'NO NAME'));
        }
    }
    
    $formattedTables = [];
    $hallsMap = [];
    
    if ($tablesDoc && isset($tablesDoc['tables'])) {
        // Форматируем столы для frontend
        foreach ($tablesDoc['tables'] as $table) {
            // Пытаемся определить зал из различных возможных полей
            $hallId = $table['hall_id']
                ?? $table['zone_id']
                ?? $table['spot_id']
                ?? null;
            $hallName = $table['hall_name']
                ?? $table['zone_name']
                ?? $table['spot_name']
                ?? ($table['hall'] ?? null);

            $formatted = [
                'id' => $table['poster_table_id'] ?? uniqid(),
                'table_id' => $table['poster_table_id'] ?? uniqid(),
                'name' => $table['name'] ?? 'Стол ' . ($table['poster_table_id'] ?? ''),
                'capacity' => (int)($table['capacity'] ?? 2),
                'status' => $table['status'] ?? 'available'
            ];

            if ($hallId !== null) {
                $formatted['hall_id'] = (string)$hallId;
            }
            if ($hallName) {
                $formatted['hall_name'] = (string)$hallName;
            }

            // Копим список залов
            if ($hallId !== null) {
                $hallsMap[(string)$hallId] = [
                    'hall_id' => (string)$hallId,
                    'hall_name' => $hallName ? (string)$hallName : ('Зал ' . (string)$hallId)
                ];
            }

            $formattedTables[] = $formatted;
        }
        
        // Сортируем столы: сначала числовые, потом буквенные
        usort($formattedTables, function($a, $b) {
            $nameA = $a['name'];
            $nameB = $b['name'];
            
            // Проверяем, является ли название числовым
            $isNumericA = is_numeric($nameA);
            $isNumericB = is_numeric($nameB);
            
            // Если оба числовые - сортируем по числовому значению
            if ($isNumericA && $isNumericB) {
                return intval($nameA) - intval($nameB);
            }
            
            // Если только A числовое - A идет первым
            if ($isNumericA && !$isNumericB) {
                return -1;
            }
            
            // Если только B числовое - B идет первым
            if (!$isNumericA && $isNumericB) {
                return 1;
            }
            
            // Если оба буквенные - сортируем по алфавиту
            return strcmp($nameA, $nameB);
        });
    }
    
    $response = [
        'success' => true,
        'tables' => $formattedTables,
        'count' => count($formattedTables),
        'updated_at' => isset($tablesDoc['updated_at']) ? $tablesDoc['updated_at']->toDateTime()->format('Y-m-d H:i:s') : null
    ];
    
    // Получаем залы из MongoDB (приходят из Poster API через getSpotTablesHalls)
    error_log("DEBUG: tablesDoc halls: " . json_encode($tablesDoc['halls'] ?? 'NOT SET'));
    error_log("DEBUG: tablesDoc updated_at: " . ($tablesDoc['updated_at'] ?? 'NOT SET'));
    
    if (isset($tablesDoc['halls']) && is_array($tablesDoc['halls']) && !empty($tablesDoc['halls'])) {
        error_log("DEBUG: Using halls from MongoDB");
        error_log("DEBUG: Halls data: " . json_encode($tablesDoc['halls']));
        $response['halls'] = $tablesDoc['halls'];
    } elseif (!empty($hallsMap)) {
        error_log("DEBUG: Using halls from hallsMap");
        // Если залов нет в MongoDB, используем извлеченные из столов
        $halls = array_values($hallsMap);
        usort($halls, function($a, $b) { return strcmp($a['hall_name'], $b['hall_name']); });
        $response['halls'] = $halls;
    } else {
        error_log("DEBUG: No halls found, returning empty array");
        // Если залов вообще нет, возвращаем пустой массив
        $response['halls'] = [];
    }

    echo json_encode($response);
    
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
