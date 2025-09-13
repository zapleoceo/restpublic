<?php
require_once 'classes/SePayApiService.php';
require_once 'classes/SePayTransactionService.php';

try {
    $apiService = new SePayApiService();
    $transactionService = new SePayTransactionService();
    
    // Получаем все транзакции из SePay API
    $result = $apiService->getAllTransactions();
    $allTransactions = $result['transactions'];
    
    echo "Всего транзакций в SePay API: " . count($allTransactions) . PHP_EOL;
    
    $created = 0;
    $existing = 0;
    
    foreach ($allTransactions as $transaction) {
        $transactionId = $transaction['id'];
        
        // Проверяем, существует ли уже в MongoDB
        $existingTransaction = $transactionService->getTransactionById($transactionId);
        
        if (!$existingTransaction) {
            // Создаем запись в MongoDB
            $transactionData = [
                'transaction_id' => $transactionId,
                'amount' => floatval($transaction['amount_in']),
                'content' => $transaction['transaction_content'],
                'code' => $transaction['reference_number'],
                'gateway' => $transaction['bank_brand_name'],
                'account_number' => $transaction['account_number'],
                'transaction_date' => $transaction['transaction_date'],
                'webhook_received_at' => null, // Не получено через webhook
                'telegram_sent' => false, // По умолчанию не отправлено
                'telegram_sent_at' => null,
                'telegram_message_id' => null
            ];
            
            $saved = $transactionService->saveTransaction($transactionData);
            if ($saved) {
                echo "✓ Создана транзакция: {$transactionId}" . PHP_EOL;
                $created++;
            } else {
                echo "✗ Ошибка создания транзакции: {$transactionId}" . PHP_EOL;
            }
        } else {
            echo "- Транзакция уже существует: {$transactionId}" . PHP_EOL;
            $existing++;
        }
    }
    
    echo PHP_EOL . "=== РЕЗУЛЬТАТ ===" . PHP_EOL;
    echo "Создано новых: {$created}" . PHP_EOL;
    echo "Уже существовало: {$existing}" . PHP_EOL;
    echo "Всего обработано: " . ($created + $existing) . PHP_EOL;
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . PHP_EOL;
}
?>
