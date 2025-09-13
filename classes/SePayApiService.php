<?php

class SePayApiService {
    private $apiToken;
    private $apiUrl = 'https://my.sepay.vn/userapi';
    
    public function __construct() {
        // Загружаем переменные окружения из .env файла
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        $this->apiToken = $_ENV['SEPAY_API_TOKEN'] ?? null;
        
        if (empty($this->apiToken)) {
            throw new Exception('SEPAY_API_TOKEN не установлен в переменных окружения');
        }
    }
    
    /**
     * Получить все транзакции из SePay API
     */
    public function getAllTransactions() {
        $allTransactions = [];
        $page = 1;
        $limit = 100; // Максимальный лимит на страницу
        
        do {
            $response = $this->makeApiRequest('/transactions/list', [
                'page' => $page,
                'limit' => $limit
            ]);
            
            if (isset($response['error'])) {
                throw new Exception('Ошибка API SePay: ' . $response['error']);
            }
            
            if (empty($response['transactions'])) {
                break; // Больше нет транзакций
            }
            
            $allTransactions = array_merge($allTransactions, $response['transactions']);
            $page++;
            
            // Защита от бесконечного цикла
            if ($page > 1000) {
                break;
            }
            
        } while (count($response['transactions']) === $limit);
        
        return [
            'transactions' => $allTransactions,
            'total' => count($allTransactions)
        ];
    }
    
    /**
     * Получить транзакции с фильтрацией
     */
    public function getTransactions($filters = []) {
        $allTransactions = $this->getAllTransactions();
        $transactions = $allTransactions['transactions'];
        
        // Применяем фильтры
        if (!empty($filters['date_from'])) {
            $transactions = array_filter($transactions, function($transaction) use ($filters) {
                return strtotime($transaction['transaction_date']) >= strtotime($filters['date_from']);
            });
        }
        
        if (!empty($filters['date_to'])) {
            $transactions = array_filter($transactions, function($transaction) use ($filters) {
                return strtotime($transaction['transaction_date']) <= strtotime($filters['date_to'] . ' 23:59:59');
            });
        }
        
        if (!empty($filters['amount_min'])) {
            $transactions = array_filter($transactions, function($transaction) use ($filters) {
                return floatval($transaction['amount_in']) >= floatval($filters['amount_min']);
            });
        }
        
        if (!empty($filters['amount_max'])) {
            $transactions = array_filter($transactions, function($transaction) use ($filters) {
                return floatval($transaction['amount_in']) <= floatval($filters['amount_max']);
            });
        }
        
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $transactions = array_filter($transactions, function($transaction) use ($search) {
                return strpos(strtolower($transaction['id']), $search) !== false ||
                       strpos(strtolower($transaction['transaction_content']), $search) !== false ||
                       strpos(strtolower($transaction['reference_number']), $search) !== false;
            });
        }
        
        // Сортируем по дате (новые сначала)
        usort($transactions, function($a, $b) {
            return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
        });
        
        return [
            'transactions' => array_values($transactions),
            'total' => count($transactions)
        ];
    }
    
    /**
     * Получить статистику транзакций
     */
    public function getStats() {
        $allTransactions = $this->getAllTransactions();
        $transactions = $allTransactions['transactions'];
        
        $total = count($transactions);
        $totalAmount = 0;
        $amounts = [];
        
        foreach ($transactions as $transaction) {
            $amount = floatval($transaction['amount_in']);
            if ($amount > 0) {
                $totalAmount += $amount;
                $amounts[] = $amount;
            }
        }
        
        return [
            'total_transactions' => $total,
            'total_amount' => $totalAmount,
            'avg_amount' => $total > 0 ? round($totalAmount / $total, 2) : 0,
            'max_amount' => !empty($amounts) ? max($amounts) : 0,
            'min_amount' => !empty($amounts) ? min($amounts) : 0
        ];
    }
    
    /**
     * Выполнить запрос к API
     */
    private function makeApiRequest($endpoint, $params = []) {
        $url = $this->apiUrl . $endpoint;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'User-Agent: NorthRepublic/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        if ($httpCode === 429) {
            // Rate limit
            $retryAfter = 1;
            if (preg_match('/x-sepay-userapi-retry-after:\s*(\d+)/i', $responseHeaders, $matches)) {
                $retryAfter = intval($matches[1]);
            }
            
            return [
                'error' => 'Rate limit exceeded',
                'retry_after' => $retryAfter
            ];
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode . ' Response: ' . $body);
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON Decode Error: ' . json_last_error_msg());
        }
        
        return $data;
    }
}
?>
