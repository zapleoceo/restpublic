<?php
/**
 * API endpoint для выхода из системы
 * POST /api/auth/logout
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

// Обработка OPTIONS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Логируем выход если пользователь был авторизован
    if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
        $userId = $_SESSION['user_client_id'] ?? 'unknown';
        $userPhone = $_SESSION['user_phone'] ?? 'unknown';
        
        error_log("User logout: client_id={$userId}, phone={$userPhone}, ip=" . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Удаляем сессию из MongoDB
        require_once __DIR__ . '/../../classes/UserAuth.php';
        $userAuth = new UserAuth();
        $userAuth->deleteSession($userId);
    }

    // Очищаем сессию
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Successfully logged out'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Auth logout API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
