<?php
header('Content-Type: application/json');
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $sepayCollection = $db->sepay_transactions;
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_transaction':
            $transactionId = $_GET['id'] ?? '';
            if (empty($transactionId)) {
                throw new Exception('Transaction ID is required');
            }
            
            $transaction = $sepayCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($transactionId)]);
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            // Конвертируем BSON в массив
            $transactionArray = $transaction->toArray();
            
            // Конвертируем дату
            if (isset($transactionArray['timestamp'])) {
                $transactionArray['timestamp'] = $transactionArray['timestamp']->toDateTime()->format('Y-m-d H:i:s');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $transactionArray
            ]);
            break;
            
        case 'export':
            $filter = [];
            
            // Применяем те же фильтры, что и в основном интерфейсе
            if (!empty($_GET['date_from'])) {
                $filter['timestamp']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['date_from']) * 1000);
            }
            if (!empty($_GET['date_to'])) {
                $filter['timestamp']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['date_to'] . ' 23:59:59') * 1000);
            }
            if (!empty($_GET['status'])) {
                $filter['status'] = $_GET['status'];
            }
            if (!empty($_GET['amount_min'])) {
                $filter['amount']['$gte'] = floatval($_GET['amount_min']);
            }
            if (!empty($_GET['amount_max'])) {
                $filter['amount']['$lte'] = floatval($_GET['amount_max']);
            }
            if (!empty($_GET['search'])) {
                $filter['$or'] = [
                    ['transaction_id' => new MongoDB\BSON\Regex($_GET['search'], 'i')],
                    ['description' => new MongoDB\BSON\Regex($_GET['search'], 'i')],
                    ['account_number' => new MongoDB\BSON\Regex($_GET['search'], 'i')]
                ];
            }
            
            $transactions = $sepayCollection->find($filter, [
                'sort' => ['timestamp' => -1]
            ])->toArray();
            
            // Конвертируем в CSV
            $csv = "Дата,ID Транзакции,Сумма,Статус,Описание,Номер счета,Дополнительные данные\n";
            
            foreach ($transactions as $transaction) {
                $date = $transaction['timestamp']->toDateTime()->format('Y-m-d H:i:s');
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $date,
                    $transaction['transaction_id'] ?? '',
                    $transaction['amount'] ?? 0,
                    $transaction['status'] ?? '',
                    str_replace('"', '""', $transaction['description'] ?? ''),
                    $transaction['account_number'] ?? '',
                    str_replace('"', '""', json_encode($transaction['additional_data'] ?? []))
                );
            }
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="sepay_logs_' . date('Y-m-d_H-i-s') . '.csv"');
            echo "\xEF\xBB\xBF"; // BOM для UTF-8
            echo $csv;
            break;
            
        case 'stats':
            $filter = [];
            
            // Применяем фильтры по дате
            if (!empty($_GET['date_from'])) {
                $filter['timestamp']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['date_from']) * 1000);
            }
            if (!empty($_GET['date_to'])) {
                $filter['timestamp']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['date_to'] . ' 23:59:59') * 1000);
            }
            
            $stats = [
                'total' => $sepayCollection->countDocuments($filter),
                'success' => $sepayCollection->countDocuments(array_merge($filter, ['status' => 'success'])),
                'failed' => $sepayCollection->countDocuments(array_merge($filter, ['status' => 'failed'])),
                'pending' => $sepayCollection->countDocuments(array_merge($filter, ['status' => 'pending']))
            ];
            
            // Статистика по суммам
            $pipeline = [
                ['$match' => $filter],
                ['$group' => [
                    '_id' => null,
                    'total_amount' => ['$sum' => '$amount'],
                    'avg_amount' => ['$avg' => '$amount'],
                    'min_amount' => ['$min' => '$amount'],
                    'max_amount' => ['$max' => '$amount']
                ]]
            ];
            
            $amountStats = $sepayCollection->aggregate($pipeline)->toArray();
            if (!empty($amountStats)) {
                $stats = array_merge($stats, $amountStats[0]);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
