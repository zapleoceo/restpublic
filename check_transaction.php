<?php
require_once 'classes/SePayTransactionService.php';

$service = new SePayTransactionService();
$transaction = $service->getTransactionById('23223788');

if ($transaction) {
    echo "Транзакция найдена:" . PHP_EOL;
    echo "ID: {$transaction['transaction_id']}" . PHP_EOL;
    echo "Amount: {$transaction['amount']} VND" . PHP_EOL;
    echo "Content: {$transaction['content']}" . PHP_EOL;
    echo "Telegram Sent: " . ($transaction['telegram_sent'] ? 'YES' : 'NO') . PHP_EOL;
    echo "Telegram Sent At: " . ($transaction['telegram_sent_at'] ?? 'NULL') . PHP_EOL;
    echo "Telegram Message ID: " . ($transaction['telegram_message_id'] ?? 'NULL') . PHP_EOL;
} else {
    echo "Транзакция НЕ найдена в MongoDB" . PHP_EOL;
}
?>
