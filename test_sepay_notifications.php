<?php
require_once 'classes/SepayNotificationService.php';

try {
    $service = new SepayNotificationService();
    
    echo "=== Тест Sepay API ===\n";
    $sepayTest = $service->testSepayConnection();
    print_r($sepayTest);
    
    echo "\n=== Тест Telegram API ===\n";
    $telegramTest = $service->testTelegramConnection();
    print_r($telegramTest);
    
    echo "\n=== Проверка новых транзакций ===\n";
    $result = $service->sendTransactionNotifications();
    print_r($result);
    
    echo "\n=== Статус сервиса ===\n";
    $status = $service->getStatus();
    print_r($status);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
