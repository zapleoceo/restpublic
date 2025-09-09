<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/SecurityValidator.php';

// Валидация CSRF токена
if (!isset($_GET['csrf_token']) || !SecurityValidator::validateCSRFToken($_GET['csrf_token'])) {
    $_SESSION['error_message'] = 'Неверный CSRF токен';
    header('Location: index.php');
    exit;
}

if ($_SESSION['csrf_token'] !== $_GET['csrf_token']) {
    $_SESSION['error_message'] = 'Неверный CSRF токен';
    header('Location: index.php');
    exit;
}

$imageId = $_GET['id'] ?? '';
if (empty($imageId) || !SecurityValidator::validateObjectId($imageId)) {
    $_SESSION['error_message'] = 'Неверный ID изображения';
    header('Location: index.php');
    exit;
}

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $imagesCollection = $db->admin_images;
    
    // Получаем изображение перед удалением
    $image = $imagesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($imageId)]);
    
    if (!$image) {
        header('Location: index.php');
        exit;
    }
    
    // Удаляем файлы
    $originalPath = '../../' . $image['original_path'];
    $webpPath = '../../' . $image['webp_path'];
    
    if (file_exists($originalPath)) {
        unlink($originalPath);
    }
    
    if (file_exists($webpPath)) {
        unlink($webpPath);
    }
    
    // Удаляем из базы данных
    $result = $imagesCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($imageId)]);
    
    if ($result->getDeletedCount() > 0) {
        // Логируем удаление
        logAdminAction('delete_image', 'Удалено изображение', [
            'filename' => $image['filename'],
            'category' => $image['category'],
            'image_id' => $imageId
        ]);
        
        $_SESSION['success_message'] = 'Изображение успешно удалено!';
    } else {
        $_SESSION['error_message'] = 'Ошибка при удалении изображения';
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Ошибка: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>
