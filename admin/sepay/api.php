<?php
session_start();
require_once '../includes/auth-check.php';
require_once '../../classes/SePayTransactionService.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $transactionService = new SePayTransactionService();
    
    switch ($action) {
        case 'get_transaction':
            $transactionId = $_GET['id'] ?? '';
            if (empty($transactionId)) {
                throw new Exception('Transaction ID is required');
            }
            
            // Сначала пытаемся получить из MongoDB
            $transaction = $transactionService->getTransactionById($transactionId);
            
            // Если не найдено в MongoDB, получаем из SePay API
            if (!$transaction) {
                require_once '../../classes/SePayApiService.php';
                $apiService = new SePayApiService();
                $allTransactions = $apiService->getAllTransactions();
                
                // Ищем транзакцию в API данных
                foreach ($allTransactions['transactions'] as $apiTransaction) {
                    if ($apiTransaction['id'] == $transactionId) {
                        $transaction = [
                            'id' => $apiTransaction['id'],
                            'amount' => floatval($apiTransaction['amount_in']),
                            'content' => $apiTransaction['transaction_content'],
                            'code' => $apiTransaction['reference_number'],
                            'gateway' => $apiTransaction['bank_brand_name'],
                            'account_number' => $apiTransaction['account_number'],
                            'transaction_date' => $apiTransaction['transaction_date'],
                            'telegram_sent' => false,
                            'telegram_sent_at' => null,
                            'telegram_message_id' => null
                        ];
                        break;
                    }
                }
            }
            
            if (!$transaction) {
                throw new Exception('Transaction not found');
            }
            
            echo json_encode([
                'success' => true,
                'transaction' => $transaction
            ]);
            break;
            
        case 'get_stats':
            $stats = $transactionService->getStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'get_transactions':
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = intval($_GET['limit'] ?? 50);
            
            $filters = [
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'amount_min' => $_GET['amount_min'] ?? '',
                'amount_max' => $_GET['amount_max'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            $result = $transactionService->getTransactions($page, $limit, $filters);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'refresh_cache':
            require_once '../../classes/SePayApiService.php';
            $apiService = new SePayApiService();
            $result = $apiService->refreshCache();
            echo json_encode([
                'success' => true,
                'message' => 'Кэш успешно обновлен',
                'total_transactions' => $result['total']
            ]);
            break;
            
        default:
            throw new Exception('Unknown action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
