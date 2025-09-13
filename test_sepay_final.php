<?php
require_once 'classes/SepayService.php';

try {
    $service = new SepayService();
    $result = $service->getTransactions();
    
    echo "Результат:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
