<?php
/**
 * Скрипт для получения транзакций от Sepay API и сохранения в MongoDB
 * Этот скрипт должен запускаться по cron job каждые несколько минут
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Загружаем переменные окружения
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$sepayApiToken = $_ENV['SEPAY_API_TOKEN'] ?? 'MAM0JWTFVWQUZJ5YDISKYO8BFPPAURIOVMR2SDN3XK1TZ2ST9K39JC7KDITBXP6N';

try {
    echo "🔄 Получение транзакций от Sepay API...\n";
    
    // Подключение к MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $sepayCollection = $db->sepay_transactions;
    
    // Получаем последнюю транзакцию для определения времени последнего обновления
    $lastTransaction = $sepayCollection->findOne([], ['sort' => ['timestamp' => -1]]);
    $lastUpdateTime = $lastTransaction ? $lastTransaction['timestamp']->toDateTime() : new DateTime('-1 day');
    
    echo "📅 Последнее обновление: " . $lastUpdateTime->format('Y-m-d H:i:s') . "\n";
    
    // Запрос к Sepay API
    $apiUrl = 'https://api.sepay.vn/v1/transactions';
    $headers = [
        'Authorization: Bearer ' . $sepayApiToken,
        'Content-Type: application/json'
    ];
    
    $params = [
        'from_date' => $lastUpdateTime->format('Y-m-d'),
        'to_date' => date('Y-m-d'),
        'limit' => 100
    ];
    
    $url = $apiUrl . '?' . http_build_query($params);
    
    echo "🌐 Запрос к API: {$url}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("cURL Error: {$error}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("API Error: HTTP {$httpCode} - {$response}");
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['data'])) {
        throw new Exception("Invalid API response format");
    }
    
    $transactions = $data['data'];
    echo "📊 Получено транзакций: " . count($transactions) . "\n";
    
    $newTransactions = 0;
    $updatedTransactions = 0;
    
    foreach ($transactions as $transactionData) {
        // Проверяем, существует ли уже такая транзакция
        $existingTransaction = $sepayCollection->findOne([
            'transaction_id' => $transactionData['id']
        ]);
        
        $document = [
            'transaction_id' => $transactionData['id'],
            'amount' => floatval($transactionData['amount']),
            'status' => $transactionData['status'],
            'description' => $transactionData['description'] ?? '',
            'account_number' => $transactionData['account_number'] ?? '',
            'timestamp' => new MongoDB\BSON\UTCDateTime(strtotime($transactionData['created_at']) * 1000),
            'additional_data' => $transactionData,
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ];
        
        if ($existingTransaction) {
            // Обновляем существующую транзакцию
            $sepayCollection->replaceOne(
                ['transaction_id' => $transactionData['id']],
                $document
            );
            $updatedTransactions++;
        } else {
            // Добавляем новую транзакцию
            $sepayCollection->insertOne($document);
            $newTransactions++;
        }
    }
    
    echo "✅ Обработка завершена:\n";
    echo "  - Новых транзакций: {$newTransactions}\n";
    echo "  - Обновленных транзакций: {$updatedTransactions}\n";
    
    // Логируем действие
    $logsCollection = $db->admin_logs;
    $logsCollection->insertOne([
        'action_type' => 'sepay_sync',
        'description' => 'Синхронизация транзакций Sepay',
        'details' => [
            'new_transactions' => $newTransactions,
            'updated_transactions' => $updatedTransactions,
            'total_received' => count($transactions)
        ],
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    
    // Логируем ошибку
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $logsCollection = $db->admin_logs;
        $logsCollection->insertOne([
            'action_type' => 'sepay_sync_error',
            'description' => 'Ошибка синхронизации транзакций Sepay',
            'details' => ['error' => $e->getMessage()],
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]);
    } catch (Exception $logError) {
        echo "❌ Ошибка логирования: " . $logError->getMessage() . "\n";
    }
    
    exit(1);
}
?>
