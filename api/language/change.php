<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['language'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Language parameter required']);
    exit;
}

$language = $input['language'];

// Validate language
if (!in_array($language, ['ru', 'en', 'vi'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid language code']);
    exit;
}

try {
    // Include translation service
    require_once __DIR__ . '/../../classes/TranslationService.php';
    
    $translationService = new TranslationService();
    $result = $translationService->setLanguage($language);
    
    if ($result) {
        // Проверяем, что язык действительно установлен
        $currentLanguage = $translationService->getLanguage();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Language changed successfully',
            'language' => $language,
            'current_language' => $currentLanguage
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to change language']);
    }
} catch (Exception $e) {
    error_log("Language change error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
