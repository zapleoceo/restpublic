<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\Collection;

class TelegramTransactionTracker {
    private $collection;
    
    public function __construct() {
        try {
            $client = new Client('mongodb://localhost:27017');
            $database = $client->selectDatabase('northrepublic');
            $this->collection = $database->selectCollection('transactions');
        } catch (Exception $e) {
            error_log("MongoDB connection error: " . $e->getMessage());
            throw new Exception("Не удалось подключиться к MongoDB");
        }
    }
    
    /**
     * Отметить транзакцию как отправленную в Telegram
     */
    public function markAsSent($transactionId, $telegramMessageId = null) {
        try {
            $result = $this->collection->updateOne(
                ['transaction_id' => $transactionId],
                [
                    '$set' => [
                        'telegram_sent' => true,
                        'telegram_sent_at' => new MongoDB\BSON\UTCDateTime(),
                        'telegram_message_id' => $telegramMessageId
                    ]
                ],
                ['upsert' => true]
            );
            
            return $result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0;
        } catch (Exception $e) {
            error_log("Error marking transaction as sent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверить, была ли транзакция отправлена в Telegram
     */
    public function isSent($transactionId) {
        try {
            $document = $this->collection->findOne(['transaction_id' => $transactionId]);
            return $document && isset($document['telegram_sent']) && $document['telegram_sent'] === true;
        } catch (Exception $e) {
            error_log("Error checking if transaction is sent: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить статус отправки для списка транзакций
     */
    public function getSentStatus(array $transactionIds) {
        try {
            $cursor = $this->collection->find(
                ['transaction_id' => ['$in' => $transactionIds]],
                ['projection' => ['transaction_id' => 1, 'telegram_sent' => 1, 'telegram_sent_at' => 1]]
            );
            
            $statusMap = [];
            foreach ($cursor as $document) {
                $statusMap[$document['transaction_id']] = [
                    'sent' => isset($document['telegram_sent']) && $document['telegram_sent'] === true,
                    'sent_at' => isset($document['telegram_sent_at']) ? $document['telegram_sent_at']->toDateTime() : null
                ];
            }
            
            return $statusMap;
        } catch (Exception $e) {
            error_log("Error getting sent status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получить неотправленные транзакции
     */
    public function getUnsentTransactions(array $transactionIds) {
        try {
            $cursor = $this->collection->find(
                [
                    'transaction_id' => ['$in' => $transactionIds],
                    '$or' => [
                        ['telegram_sent' => ['$ne' => true]],
                        ['telegram_sent' => ['$exists' => false]]
                    ]
                ],
                ['projection' => ['transaction_id' => 1]]
            );
            
            $unsentIds = [];
            foreach ($cursor as $document) {
                $unsentIds[] = $document['transaction_id'];
            }
            
            return $unsentIds;
        } catch (Exception $e) {
            error_log("Error getting unsent transactions: " . $e->getMessage());
            return $transactionIds; // В случае ошибки считаем все неотправленными
        }
    }
    
    /**
     * Получить статистику отправки
     */
    public function getSendingStats() {
        try {
            $total = $this->collection->countDocuments([]);
            $sent = $this->collection->countDocuments(['telegram_sent' => true]);
            $unsent = $total - $sent;
            
            return [
                'total' => $total,
                'sent' => $sent,
                'unsent' => $unsent,
                'sent_percentage' => $total > 0 ? round(($sent / $total) * 100, 2) : 0
            ];
        } catch (Exception $e) {
            error_log("Error getting sending stats: " . $e->getMessage());
            return [
                'total' => 0,
                'sent' => 0,
                'unsent' => 0,
                'sent_percentage' => 0
            ];
        }
    }
    
    /**
     * Удалить запись о транзакции (для очистки)
     */
    public function removeTransaction($transactionId) {
        try {
            $result = $this->collection->deleteOne(['transaction_id' => $transactionId]);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log("Error removing transaction: " . $e->getMessage());
            return false;
        }
    }
}
