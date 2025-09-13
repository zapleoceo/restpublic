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
    $createdCount = 0;
    
    foreach ($allTransactions as $transaction) {
        $transactionId = $transaction['id'];
        
        if (in_array($transactionId, $excludeIds)) {
            echo "Пропускаем транзакцию $transactionId (исключена)\n";
            $skippedCount++;
            continue;
        }
        
        // Проверяем, существует ли запись в MongoDB
        $status = $transactionService->getSentStatus($transactionId);
        
        if ($status['sent']) {
            echo "Транзакция $transactionId уже помечена как отправленная\n";
            continue;
        }
        
        // Если записи нет в MongoDB, создаем ее
        if (!$status['sent'] && $status['sent_at'] === null) {
            $transactionData = [
                'transaction_id' => $transactionId,
                'amount' => floatval($transaction['amount_in']),
                'content' => $transaction['transaction_content'],
                'code' => $transaction['reference_number'],
                'gateway' => $transaction['bank_brand_name'],
                'account_number' => $transaction['account_number'],
                'transaction_date' => $transaction['transaction_date'],
                'webhook_received_at' => new MongoDB\BSON\UTCDateTime(),
                'telegram_sent' => true,
                'telegram_sent_at' => new MongoDB\BSON\UTCDateTime(),
                'telegram_message_id' => 'marked_by_script'
            ];
            
            $created = $transactionService->saveTransaction($transactionData);
            if ($created) {
                echo "✓ Создана и помечена транзакция $transactionId\n";
                $createdCount++;
            } else {
                echo "✗ Ошибка при создании транзакции $transactionId\n";
            }
        } else {
            // Запись есть, просто помечаем как отправленную
            $success = $transactionService->markTelegramSent($transactionId, 'marked_by_script');
            
            if ($success) {
                echo "✓ Транзакция $transactionId помечена как отправленная\n";
                $markedCount++;
            } else {
                echo "✗ Ошибка при пометке транзакции $transactionId\n";
            }
        }
    }
    
    echo "\n=== РЕЗУЛЬТАТ ===\n";
    echo "Создано и помечено: $createdCount\n";
    echo "Помечено как отправленные: $markedCount\n";
    echo "Пропущено (исключены): $skippedCount\n";
    echo "Всего обработано: " . ($createdCount + $markedCount + $skippedCount) . "\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
