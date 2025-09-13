<?php
require_once 'classes/SepayService.php';

$sepay = new SepayService();
$response = $sepay->getTransactions();

echo "=== CHECK TRANSACTIONS ===\n";
echo "Total: " . ($response['total'] ?? 'null') . "\n";
echo "Transactions count: " . count($response['transactions']) . "\n";
echo "Error: " . ($response['error'] ?? 'none') . "\n";

if (!empty($response['transactions'])) {
    echo "First transaction ID: " . $response['transactions'][0]['id'] . "\n";
    echo "First transaction amount: " . ($response['transactions'][0]['amount_in'] ?? 'null') . "\n";
    echo "First transaction date: " . ($response['transactions'][0]['transaction_date'] ?? 'null') . "\n";
} else {
    echo "No transactions found\n";
}

// Проверим, есть ли транзакции в API напрямую
echo "\n=== DIRECT API CHECK ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://my.sepay.vn/userapi/transactions/list?limit=50&page=1');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer MAM0JWTFVWQUZJ5YDISKYO8BFPPAURIOVMR2SDN3XK1TZ2ST9K39JC7KDITBXP6N',
    'Content-Type: application/json',
    'User-Agent: NorthRepublic/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Direct API transactions count: " . count($data['transactions'] ?? []) . "\n";
    if (!empty($data['transactions'])) {
        echo "First transaction ID: " . $data['transactions'][0]['id'] . "\n";
    }
} else {
    echo "API Error: " . $response . "\n";
}
?>
