<?php
require_once 'classes/SePayApiService.php';
require_once 'classes/SePayTransactionService.php';

try {
    $apiService = new SePayApiService();
    $transactionService = new SePayTransactionService();
    
    // Получаем все транзакции
    $result = $apiService->getAllTransactions();
    $allTransactions = $result['transactions'];
    
    echo "Всего транзакций: " . count($allTransactions) . "\n";
    
    // ID транзакций, которые НЕ нужно помечать как отправленные
    $excludeIds = ['23211585', '23209753'];
    
    $markedCount = 0;
    $skippedCount = 0;
    
    foreach ($allTransactions as $transaction) {
        $transactionId = $transaction['id'];
        
        if (in_array($transactionId, $excludeIds)) {
            echo "Пропускаем транзакцию $transactionId (исключена)\n";
            $skippedCount++;
            continue;
        }
        
        // Проверяем, не помечена ли уже как отправленная
        $status = $transactionService->getSentStatus($transactionId);
        if ($status['sent']) {
            echo "Транзакция $transactionId уже помечена как отправленная\n";
            continue;
        }
        
        // Помечаем как отправленную
        $success = $transactionService->markTelegramSent($transactionId, 'marked_by_script');
        
        if ($success) {
            echo "✓ Транзакция $transactionId помечена как отправленная\n";
            $markedCount++;
        } else {
            echo "✗ Ошибка при пометке транзакции $transactionId\n";
        }
    }
    
    echo "\n=== РЕЗУЛЬТАТ ===\n";
    echo "Помечено как отправленные: $markedCount\n";
    echo "Пропущено (исключены): $skippedCount\n";
    echo "Всего обработано: " . ($markedCount + $skippedCount) . "\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
