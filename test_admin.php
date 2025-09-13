<?php
require_once 'classes/SepayService.php';

$sepay = new SepayService();
$response = $sepay->getTransactions();

echo "=== ADMIN TEST ===\n";
echo "Transactions count: " . count($response['transactions']) . "\n";
echo "Error: " . ($response['error'] ?? 'none') . "\n";
echo "Rate limit: " . ($response['rate_limit'] ?? 'false') . "\n";
echo "Retry after: " . ($response['retry_after'] ?? 'null') . "\n";
echo "Total: " . ($response['total'] ?? 'null') . "\n";

if (!empty($response['transactions'])) {
    echo "First transaction ID: " . $response['transactions'][0]['id'] . "\n";
    echo "First transaction amount: " . ($response['transactions'][0]['amount_in'] ?? 'null') . "\n";
} else {
    echo "No transactions found\n";
}

// Проверим полный ответ
echo "\nFull response structure:\n";
print_r($response);
?>
