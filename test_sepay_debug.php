<?php
require_once 'classes/SepayService.php';

$sepay = new SepayService();
$transactions = $sepay->getTransactions();

echo "=== DEBUG SEPAY API ===\n";
echo "Transactions type: " . gettype($transactions) . "\n";
echo "Transactions count: " . (is_array($transactions) ? count($transactions) : 'N/A') . "\n";

echo "Transactions content:\n";
print_r($transactions);

if (is_array($transactions) && !empty($transactions)) {
    echo "\nFirst transaction (using array_values):\n";
    $values = array_values($transactions);
    if (!empty($values)) {
        print_r($values[0]);
    }
}
?>
