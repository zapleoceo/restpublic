<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к сервисам
require_once __DIR__ . '/../../classes/SepayNotificationService.php';

header('Content-Type: application/json');

try {
    $notificationService = new SepayNotificationService();
    $result = $notificationService->sendUnsentTransactions();
    
    if (isset($result['error'])) {
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'count' => $result['count'],
            'sent' => $result['sent'],
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in send-unsent.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Внутренняя ошибка сервера'
    ]);
}
