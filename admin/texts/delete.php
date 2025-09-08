<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$textId = $_GET['id'] ?? '';
if (empty($textId)) {
    header('Location: index.php');
    exit;
}

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    // Получаем текст перед удалением
    $text = $textsCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($textId)]);
    
    if (!$text) {
        header('Location: index.php');
        exit;
    }
    
    // Удаляем текст
    $result = $textsCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($textId)]);
    
    if ($result->getDeletedCount() > 0) {
        // Логируем удаление
        logAdminAction('delete_text', 'Удален текст', [
            'key' => $text['key'],
            'category' => $text['category'],
            'text_id' => $textId
        ]);
        
        $_SESSION['success_message'] = 'Текст успешно удален!';
    } else {
        $_SESSION['error_message'] = 'Ошибка при удалении текста';
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Ошибка: ' . $e->getMessage();
}

header('Location: index.php');
exit;
?>
