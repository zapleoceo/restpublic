<?php
require_once '../includes/auth.php';
require_once '../../classes/SePayTransactionService.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isLoggedIn()) {
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
            
            $transaction = $transactionService->getTransactionById($transactionId);
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
