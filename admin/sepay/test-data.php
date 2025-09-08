<?php
// Скрипт для добавления тестовых данных Sepay
// Запускать только для тестирования!

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $sepayCollection = $db->sepay_logs;
    
    // Тестовые данные
    $testTransactions = [
        [
            'transaction_id' => 'TXN_' . uniqid(),
            'amount' => 150000,
            'status' => 'success',
            'description' => 'Оплата заказа #12345',
            'account_number' => '970422****1234',
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'additional_data' => [
                'order_id' => '12345',
                'customer_name' => 'Иван Петров',
                'payment_method' => 'BIDV'
            ]
        ],
        [
            'transaction_id' => 'TXN_' . uniqid(),
            'amount' => 75000,
            'status' => 'success',
            'description' => 'Оплата заказа #12346',
            'account_number' => '970422****5678',
            'timestamp' => new MongoDB\BSON\UTCDateTime(time() - 3600), // 1 час назад
            'additional_data' => [
                'order_id' => '12346',
                'customer_name' => 'Мария Сидорова',
                'payment_method' => 'BIDV'
            ]
        ],
        [
            'transaction_id' => 'TXN_' . uniqid(),
            'amount' => 200000,
            'status' => 'failed',
            'description' => 'Оплата заказа #12347 - Недостаточно средств',
            'account_number' => '970422****9012',
            'timestamp' => new MongoDB\BSON\UTCDateTime(time() - 7200), // 2 часа назад
            'additional_data' => [
                'order_id' => '12347',
                'customer_name' => 'Алексей Козлов',
                'payment_method' => 'BIDV',
                'error_code' => 'INSUFFICIENT_FUNDS'
            ]
        ],
        [
            'transaction_id' => 'TXN_' . uniqid(),
            'amount' => 300000,
            'status' => 'pending',
            'description' => 'Оплата заказа #12348',
            'account_number' => '970422****3456',
            'timestamp' => new MongoDB\BSON\UTCDateTime(time() - 1800), // 30 минут назад
            'additional_data' => [
                'order_id' => '12348',
                'customer_name' => 'Елена Волкова',
                'payment_method' => 'BIDV'
            ]
        ],
        [
            'transaction_id' => 'TXN_' . uniqid(),
            'amount' => 125000,
            'status' => 'success',
            'description' => 'Оплата заказа #12349',
            'account_number' => '970422****7890',
            'timestamp' => new MongoDB\BSON\UTCDateTime(time() - 10800), // 3 часа назад
            'additional_data' => [
                'order_id' => '12349',
                'customer_name' => 'Дмитрий Новиков',
                'payment_method' => 'BIDV'
            ]
        ]
    ];
    
    // Добавляем тестовые данные
    $result = $sepayCollection->insertMany($testTransactions);
    
    echo "Добавлено " . $result->getInsertedCount() . " тестовых транзакций\n";
    
    // Показываем статистику
    $totalCount = $sepayCollection->countDocuments();
    $successCount = $sepayCollection->countDocuments(['status' => 'success']);
    $failedCount = $sepayCollection->countDocuments(['status' => 'failed']);
    $pendingCount = $sepayCollection->countDocuments(['status' => 'pending']);
    
    echo "\nСтатистика:\n";
    echo "Всего транзакций: $totalCount\n";
    echo "Успешных: $successCount\n";
    echo "Неудачных: $failedCount\n";
    echo "В обработке: $pendingCount\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
