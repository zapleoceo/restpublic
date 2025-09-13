<?php
require_once 'classes/TelegramService.php';

try {
    $service = new TelegramService();
    $result = $service->getBotInfo();
    
    echo "Telegram Bot Info:\n";
    print_r($result);
    
    // Тест отправки сообщения
    $testMessage = "🧪 Тестовое сообщение от NR сервера\n\nВремя: " . date('d.m.Y H:i:s');
    $sendResult = $service->sendToAllChats($testMessage);
    
    echo "\nSend Result:\n";
    print_r($sendResult);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
