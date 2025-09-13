<?php
require_once 'classes/SePayTransactionService.php';

$service = new SePayTransactionService();
$unsentTransactions = $service->getUnsentTransactions();

echo "Найдено неотправленных транзакций: " . count($unsentTransactions) . PHP_EOL;

foreach ($unsentTransactions as $transaction) {
    echo "ID: {$transaction['transaction_id']}, Amount: {$transaction['amount']} VND, Date: {$transaction['transaction_date']}" . PHP_EOL;
}
?>
