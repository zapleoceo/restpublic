<?php
require_once 'classes/SepayService.php';

$sepay = new SepayService();
$transactions = $sepay->getTransactions();

echo "=== SEPAY API TEST ===\n";
echo "Response type: " . gettype($transactions) . "\n";

if (is_array($transactions)) {
    echo "Response keys: " . implode(', ', array_keys($transactions)) . "\n";
    if (isset($transactions['error'])) {
        echo "Error: " . $transactions['error'] . "\n";
    }
    if (isset($transactions['transactions'])) {
        echo "Transactions count: " . count($transactions['transactions']) . "\n";
    }
} else {
    echo "Response content:\n";
    print_r($transactions);
}
?>
