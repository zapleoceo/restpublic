<?php
require_once 'classes/SepayService.php';

$sepay = new SepayService();
$transactions = $sepay->getTransactions();

echo "=== DEBUG SEPAY API ===\n";
echo "Transactions type: " . gettype($transactions) . "\n";
echo "Transactions count: " . (is_array($transactions) ? count($transactions) : 'N/A') . "\n";

if (is_array($transactions) && !empty($transactions)) {
    echo "First transaction:\n";
    print_r($transactions[0]);
} else {
    echo "Transactions content:\n";
    print_r($transactions);
}
?>
