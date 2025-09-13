<?php
require_once 'vendor/autoload.php';

use MongoDB\Client;

try {
    $client = new Client('mongodb://localhost:27017');
    $database = $client->selectDatabase('northrepublic');
    $collection = $database->selectCollection('sepay_transactions');
    
    // Ищем транзакцию 23223788
    $transaction = $collection->findOne(['transaction_id' => '23223788']);
    
    if ($transaction) {
        echo "Транзакция найдена в MongoDB:" . PHP_EOL;
        echo "ID: " . $transaction['transaction_id'] . PHP_EOL;
        echo "Amount: " . $transaction['amount'] . " VND" . PHP_EOL;
        echo "Content: " . $transaction['content'] . PHP_EOL;
        echo "Telegram Sent: " . ($transaction['telegram_sent'] ? 'YES' : 'NO') . PHP_EOL;
        echo "Telegram Sent At: " . (isset($transaction['telegram_sent_at']) ? $transaction['telegram_sent_at']->toDateTime()->format('Y-m-d H:i:s') : 'NULL') . PHP_EOL;
        echo "Telegram Message ID: " . ($transaction['telegram_message_id'] ?? 'NULL') . PHP_EOL;
    } else {
        echo "Транзакция НЕ найдена в MongoDB" . PHP_EOL;
    }
    
    // Также проверим общее количество транзакций
    $count = $collection->countDocuments([]);
    echo PHP_EOL . "Всего транзакций в MongoDB: " . $count . PHP_EOL;
    
    // Покажем последние 5 транзакций
    $cursor = $collection->find([], ['sort' => ['_id' => -1], 'limit' => 5]);
    echo PHP_EOL . "Последние 5 транзакций:" . PHP_EOL;
    foreach ($cursor as $doc) {
        echo "- ID: " . $doc['transaction_id'] . ", Sent: " . ($doc['telegram_sent'] ? 'YES' : 'NO') . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . PHP_EOL;
}
?>
