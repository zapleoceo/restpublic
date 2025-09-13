<?php
header('Content-Type: application/json');
session_start();

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Подключение к Sepay API
require_once __DIR__ . '/../../classes/SepayService.php';

try {
    $sepayService = new SepayService();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_transaction':
            $transactionId = $_GET['id'] ?? '';
            if (empty($transactionId)) {
                throw new Exception('Transaction ID is required');
            }
            
            $transaction = $sepayService->getTransactionDetails($transactionId);
            
            if (isset($transaction['error'])) {
                throw new Exception($transaction['error']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $transaction
            ]);
            break;
            
        case 'export':
            $filters = [
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'status' => $_GET['status'] ?? '',
                'amount_min' => $_GET['amount_min'] ?? '',
                'amount_max' => $_GET['amount_max'] ?? '',
                'search' => $_GET['search'] ?? '',
                'limit' => 1000 // Больше записей для экспорта
            ];
            
            $apiResponse = $sepayService->getTransactions($filters);
            
            if (isset($apiResponse['error'])) {
                throw new Exception($apiResponse['error']);
            }
            
            $transactions = $apiResponse['transactions'] ?? [];
            
            // Конвертируем в CSV
            $csv = "Дата,ID Транзакции,Сумма,Статус,Описание,Номер счета,Дополнительные данные\n";
            
            foreach ($transactions as $transaction) {
                $date = isset($transaction['created_at']) ? $transaction['created_at'] : date('Y-m-d H:i:s');
                $csv .= sprintf(
                    '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $date,
                    $transaction['id'] ?? $transaction['transaction_id'] ?? '',
                    $transaction['amount'] ?? 0,
                    $transaction['status'] ?? '',
                    str_replace('"', '""', $transaction['description'] ?? ''),
                    $transaction['account_number'] ?? '',
                    str_replace('"', '""', json_encode($transaction))
                );
            }
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="sepay_transactions_' . date('Y-m-d_H-i-s') . '.csv"');
            echo "\xEF\xBB\xBF"; // BOM для UTF-8
            echo $csv;
            break;
            
        case 'stats':
            $filters = [
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'status' => $_GET['status'] ?? '',
                'amount_min' => $_GET['amount_min'] ?? '',
                'amount_max' => $_GET['amount_max'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            $stats = $sepayService->getStats($filters);
            
            if (isset($stats['error'])) {
                throw new Exception($stats['error']);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        case 'api_status':
            $status = $sepayService->checkApiStatus();
            echo json_encode([
                'success' => true,
                'data' => $status
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
