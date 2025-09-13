<?php
require_once 'classes/SepayService.php';

try {
    $service = new SepayService();
    
    // Сначала проверим статус API
    echo "Проверяем статус API...\n";
    $status = $service->checkApiStatus();
    echo "Статус: " . json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Теперь попробуем получить транзакции
    echo "Получаем транзакции...\n";
    $result = $service->getTransactions();
    
    echo "Результат:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Трассировка:\n" . $e->getTraceAsString() . "\n";
}
