<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/ImageService.php';

// Загружаем переменные окружения
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

header('Content-Type: application/json');

try {
    $fileId = $_GET['id'] ?? null;
    
    if (!$fileId) {
        http_response_code(400);
        echo json_encode(['error' => 'File ID required']);
        exit;
    }
    
    $imageService = new ImageService();
    $metadata = $imageService->getImageMetadata($fileId);
    
    if (!$metadata) {
        http_response_code(404);
        echo json_encode(['error' => 'Image not found']);
        exit;
    }
    
    $imageData = $imageService->getImage($fileId);
    
    if (!$imageData) {
        http_response_code(404);
        echo json_encode(['error' => 'Image data not found']);
        exit;
    }
    
    // Устанавливаем правильные заголовки
    header('Content-Type: ' . ($metadata['metadata']['content_type'] ?? 'image/jpeg'));
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: public, max-age=3600'); // Кешируем на час
    
    // Выводим изображение
    echo $imageData;
    
} catch (Exception $e) {
    error_log("Image API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
