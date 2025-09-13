<?php
require_once 'classes/SepayService.php';

try {
    $service = new SepayService();
    
    // Получаем токен из env
    $reflection = new ReflectionClass($service);
    $property = $reflection->getProperty('apiToken');
    $property->setAccessible(true);
    $token = $property->getValue($service);
    
    echo "Токен: " . substr($token, 0, 20) . "...\n\n";
    
    // Делаем прямой запрос
    $url = 'https://my.sepay.vn/userapi/transactions/list?limit=50&page=1';
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'User-Agent: NorthRepublic/1.0'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'NorthRepublic/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "cURL Error: " . ($error ?: 'none') . "\n";
    echo "Body length: " . strlen($body) . "\n";
    echo "Body (first 500 chars):\n" . substr($body, 0, 500) . "\n\n";
    
    if ($httpCode === 200) {
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "JSON decode: SUCCESS\n";
            echo "Keys in response: " . implode(', ', array_keys($data)) . "\n";
            if (isset($data['transactions'])) {
                echo "Transactions count: " . count($data['transactions']) . "\n";
            }
        } else {
            echo "JSON decode error: " . json_last_error_msg() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
