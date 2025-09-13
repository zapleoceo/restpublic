<?php
// Тестовый скрипт для проверки различных API endpoints Sepay

$endpoints = [
    'https://sepay.vn/api/v1',
    'https://sepay.vn/api',
    'https://api.sepay.vn',
    'https://docs.sepay.vn/api/v1',
    'https://sepay.vn/v1',
    'https://sepay.vn/transactions'
];

$token = 'MAM0JWTFVWQUZJ5YDISKYO8BFPPAURIOVMR2SDN3XK1TZ2ST9K39JC7KDITBXP6N';

foreach ($endpoints as $endpoint) {
    echo "Testing: $endpoint\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint . '/status');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'User-Agent: NorthRepublic/1.0'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  Error: $error\n";
    } else {
        echo "  HTTP Code: $httpCode\n";
        if ($httpCode === 200) {
            echo "  Response: " . substr($response, 0, 100) . "...\n";
        }
    }
    echo "\n";
}
?>
