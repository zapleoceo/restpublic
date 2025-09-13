<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://my.sepay.vn/userapi/transactions/list?limit=50&page=1');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ATUV13DSBM72D6JQXOZIGGE0OH8ULFBOBFNZ9XXEIWFQEY4NWYHCGCSKLVMYPWEJ',
    'Content-Type: application/json',
    'User-Agent: NorthRepublic/1.0'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Header Size: $headerSize\n";
echo "Body length: " . strlen($body) . "\n";
echo "Headers:\n$headers\n";
echo "Body:\n$body\n";
