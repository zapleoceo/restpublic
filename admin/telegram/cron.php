<?php
/**
 * Cron задача для проверки новых транзакций Sepay и отправки уведомлений в Telegram
 * Запускать каждые 30 минут: 0,30 * * * * /usr/bin/php /var/www/northrepubli_usr/data/www/northrepublic.me/admin/telegram/cron.php
 */

// Устанавливаем лимиты для cron задачи
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 60);

// Подключаем необходимые классы
require_once '../../classes/SepayNotificationService.php';

// Логирование
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    error_log($logMessage, 3, '/var/www/northrepubli_usr/data/www/northrepublic.me/logs/telegram-cron.log');
}

try {
    logMessage('Запуск проверки транзакций Sepay');
    
    // Создаем сервис уведомлений
    $notificationService = new SepayNotificationService();
    
    // Проверяем новые транзакции и отправляем уведомления
    $result = $notificationService->sendTransactionNotifications();
    
    if ($result['count'] > 0) {
        logMessage("Найдено новых транзакций: {$result['count']}, отправлено уведомлений: {$result['sent']}");
    } else {
        logMessage('Новых транзакций не найдено');
    }
    
    // Проверяем статус сервисов каждые 10 минут
    $currentMinute = (int)date('i');
    if ($currentMinute % 10 === 0) {
        $sepayTest = $notificationService->testSepayConnection();
        $telegramTest = $notificationService->testTelegramConnection();
        
        if (!$sepayTest['success']) {
            logMessage("ОШИБКА: Sepay API недоступен - {$sepayTest['error']}");
        }
        
        if (!$telegramTest['success']) {
            logMessage("ОШИБКА: Telegram API недоступен - {$telegramTest['error']}");
        }
    }
    
} catch (Exception $e) {
    logMessage("КРИТИЧЕСКАЯ ОШИБКА: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
}

logMessage('Завершение проверки транзакций Sepay');
?>
