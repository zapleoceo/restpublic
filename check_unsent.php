<?php
require_once 'classes/SePayTransactionService.php';

$service = new SePayTransactionService();
$unsentTransactions = $service->getUnsentTransactions();

echo "Неотправленных транзакций в MongoDB: " . count($unsentTransactions) . PHP_EOL;

foreach ($unsentTransactions as $transaction) {
    echo "ID: {$transaction['transaction_id']}, Sent: " . ($transaction['telegram_sent'] ? 'YES' : 'NO') . ", Date: {$transaction['transaction_date']}" . PHP_EOL;
}

// Также проверим конкретные транзакции, которые показываются в админке
$testIds = ['23211585', '23209753'];
echo PHP_EOL . "Проверка конкретных транзакций:" . PHP_EOL;

foreach ($testIds as $id) {
    $transaction = $service->getTransactionById($id);
    if ($transaction) {
        echo "ID: {$id}, Sent: " . ($transaction['telegram_sent'] ? 'YES' : 'NO') . ", Date: {$transaction['transaction_date']}" . PHP_EOL;
    } else {
        echo "ID: {$id} - НЕ НАЙДЕНА в MongoDB" . PHP_EOL;
    }
}
?>
