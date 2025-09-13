<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Collection;

class SePayTransactionService {
    private $collection;
    
    public function __construct() {
        try {
            $client = new Client('mongodb://localhost:27017');
            $database = $client->selectDatabase('northrepublic');
            $this->collection = $database->selectCollection('sepay_transactions');
        } catch (Exception $e) {
            error_log("MongoDB connection error: " . $e->getMessage());
            throw new Exception("Не удалось подключиться к MongoDB");
        }
    }
    
    /**
     * Сохранить транзакцию в MongoDB
     */
    public function saveTransaction($transactionData) {
        try {
            // Проверяем, не существует ли уже транзакция с таким ID
            $existing = $this->collection->findOne(['transaction_id' => $transactionData['transaction_id']]);
            if ($existing) {
                error_log("Transaction {$transactionData['transaction_id']} already exists");
                return true; // Возвращаем true, так как транзакция уже есть
            }
            
            $result = $this->collection->insertOne($transactionData);
            return $result->getInsertedCount() > 0;
        } catch (Exception $e) {
            error_log("Error saving transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Отметить транзакцию как отправленную в Telegram
     */
    public function markTelegramSent($transactionId, $telegramMessageId = null) {
        try {
            $result = $this->collection->updateOne(
                ['transaction_id' => $transactionId],
                [
                    '$set' => [
                        'telegram_sent' => true,
                        'telegram_sent_at' => new MongoDB\BSON\UTCDateTime(),
                        'telegram_message_id' => $telegramMessageId
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log("Error marking transaction as sent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить все транзакции с пагинацией
     */
    public function getTransactions($page = 1, $limit = 50, $filters = []) {
        try {
            $skip = ($page - 1) * $limit;
            
            $query = [];
            
            // Фильтры
            if (!empty($filters['date_from'])) {
                $query['transaction_date'] = ['$gte' => $filters['date_from']];
            }
            if (!empty($filters['date_to'])) {
                if (isset($query['transaction_date'])) {
                    $query['transaction_date']['$lte'] = $filters['date_to'];
                } else {
                    $query['transaction_date'] = ['$lte' => $filters['date_to']];
                }
            }
            if (!empty($filters['amount_min'])) {
                $query['amount'] = ['$gte' => floatval($filters['amount_min'])];
            }
            if (!empty($filters['amount_max'])) {
                if (isset($query['amount'])) {
                    $query['amount']['$lte'] = floatval($filters['amount_max']);
                } else {
                    $query['amount'] = ['$lte' => floatval($filters['amount_max'])];
                }
            }
            if (!empty($filters['search'])) {
                $query['$or'] = [
                    ['transaction_id' => ['$regex' => $filters['search'], '$options' => 'i']],
                    ['content' => ['$regex' => $filters['search'], '$options' => 'i']],
                    ['code' => ['$regex' => $filters['search'], '$options' => 'i']]
                ];
            }
            
            $options = [
                'sort' => ['webhook_received_at' => -1],
                'skip' => $skip,
                'limit' => $limit
            ];
            
            $cursor = $this->collection->find($query, $options);
            $transactions = [];
            
            foreach ($cursor as $document) {
                $transactions[] = [
                    'id' => $document['transaction_id'],
                    'amount' => $document['amount'],
                    'content' => $document['content'],
                    'code' => $document['code'],
                    'gateway' => $document['gateway'],
                    'account_number' => $document['account_number'],
                    'transaction_date' => $document['transaction_date'],
                    'webhook_received_at' => $document['webhook_received_at']->toDateTime()->format('Y-m-d H:i:s'),
                    'telegram_sent' => $document['telegram_sent'] ?? false,
                    'telegram_sent_at' => isset($document['telegram_sent_at']) ? 
                        $document['telegram_sent_at']->toDateTime()->format('Y-m-d H:i:s') : null,
                    'telegram_message_id' => $document['telegram_message_id'] ?? null
                ];
            }
            
            // Получаем общее количество
            $total = $this->collection->countDocuments($query);
            
            return [
                'transactions' => $transactions,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error getting transactions: " . $e->getMessage());
            return [
                'transactions' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'pages' => 0
            ];
        }
    }
    
    /**
     * Получить статистику транзакций
     */
    public function getStats() {
        try {
            $total = $this->collection->countDocuments();
            $sent = $this->collection->countDocuments(['telegram_sent' => true]);
            $notSent = $total - $sent;
            
            // Статистика по суммам
            $pipeline = [
                [
                    '$group' => [
                        '_id' => null,
                        'total_amount' => ['$sum' => '$amount'],
                        'avg_amount' => ['$avg' => '$amount'],
                        'max_amount' => ['$max' => '$amount'],
                        'min_amount' => ['$min' => '$amount']
                    ]
                ]
            ];
            
            $result = $this->collection->aggregate($pipeline)->toArray();
            $amountStats = !empty($result) ? $result[0] : [
                'total_amount' => 0,
                'avg_amount' => 0,
                'max_amount' => 0,
                'min_amount' => 0
            ];
            
            return [
                'total_transactions' => $total,
                'telegram_sent' => $sent,
                'telegram_not_sent' => $notSent,
                'total_amount' => $amountStats['total_amount'],
                'avg_amount' => round($amountStats['avg_amount'], 2),
                'max_amount' => $amountStats['max_amount'],
                'min_amount' => $amountStats['min_amount']
            ];
            
        } catch (Exception $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return [
                'total_transactions' => 0,
                'telegram_sent' => 0,
                'telegram_not_sent' => 0,
                'total_amount' => 0,
                'avg_amount' => 0,
                'max_amount' => 0,
                'min_amount' => 0
            ];
        }
    }
    
    /**
     * Получить статус отправки в Telegram
     */
    public function getSentStatus($transactionId) {
        try {
            $document = $this->collection->findOne(['transaction_id' => $transactionId]);
            
            if (!$document) {
                return ['sent' => false, 'sent_at' => null, 'message_id' => null];
            }
            
            return [
                'sent' => $document['telegram_sent'] ?? false,
                'sent_at' => isset($document['telegram_sent_at']) ? 
                    $document['telegram_sent_at']->toDateTime()->format('Y-m-d H:i:s') : null,
                'message_id' => $document['telegram_message_id'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Error getting sent status: " . $e->getMessage());
            return ['sent' => false, 'sent_at' => null, 'message_id' => null];
        }
    }
    
    /**
     * Получить детали транзакции по ID
     */
    public function getTransactionById($transactionId) {
        try {
            $document = $this->collection->findOne(['transaction_id' => $transactionId]);
            
            if (!$document) {
                return null;
            }
            
            return [
                'id' => $document['transaction_id'],
                'amount' => $document['amount'],
                'content' => $document['content'],
                'code' => $document['code'],
                'gateway' => $document['gateway'],
                'account_number' => $document['account_number'],
                'transaction_date' => $document['transaction_date'],
                'webhook_received_at' => $document['webhook_received_at']->toDateTime()->format('Y-m-d H:i:s'),
                'telegram_sent' => $document['telegram_sent'] ?? false,
                'telegram_sent_at' => isset($document['telegram_sent_at']) ? 
                    $document['telegram_sent_at']->toDateTime()->format('Y-m-d H:i:s') : null,
                'telegram_message_id' => $document['telegram_message_id'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Error getting transaction by ID: " . $e->getMessage());
            return null;
        }
    }
}
?>
