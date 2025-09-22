<?php
/**
 * API endpoint для проверки статуса авторизации
 * GET /api/auth/status
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Загружаем переменные окружения
    require_once __DIR__ . '/../../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    }

    // Запускаем сессию если она еще не запущена
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Проверяем авторизацию
    $isAuthenticated = isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
    
    if ($isAuthenticated) {
        $clientId = $_SESSION['user_client_id'] ?? null;
        
        // Дополнительная проверка сессии в MongoDB
        if ($clientId) {
            require_once __DIR__ . '/../../classes/UserAuth.php';
            $userAuth = new UserAuth();
            
            if (!$userAuth->validateSession($clientId)) {
                // Сессия недействительна, очищаем
                session_destroy();
                $isAuthenticated = false;
            }
        }
        
        if ($isAuthenticated) {
            // Получаем данные пользователя из сессии
            $userData = [
                'client_id' => $_SESSION['user_client_id'] ?? null,
                'phone' => $_SESSION['user_phone'] ?? null,
                'name' => $_SESSION['user_name'] ?? null,
                'authenticated_at' => $_SESSION['user_authenticated_at'] ?? null
            ];

            echo json_encode([
                'success' => true,
                'authenticated' => true,
                'user' => $userData
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'authenticated' => false,
                'user' => null
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false,
            'user' => null
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log("Auth status API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
