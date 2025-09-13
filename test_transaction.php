<?php
require_once 'classes/SePayTransactionService.php';

$service = new SePayTransactionService();
$transaction = $service->getTransactionById('23211585');

if ($transaction) {
    echo "Transaction found: " . json_encode($transaction) . PHP_EOL;
} else {
    echo "Transaction not found in MongoDB" . PHP_EOL;
}
?>
