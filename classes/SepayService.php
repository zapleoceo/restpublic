<?php
/**
 * Сервис для работы с Sepay API
 * Получает данные транзакций напрямую от API без хранения в локальной БД
 */

class SepayService {
    private $apiToken;
    private $apiBaseUrl;
    private $cache;
    
    public function __construct() {
        // Загружаем переменные окружения
        $this->loadEnvironmentVariables();
        
        $this->apiToken = $_ENV['SEPAY_API_TOKEN'] ?? 'MAM0JWTFVWQUZJ5YDISKYO8BFPPAURIOVMR2SDN3XK1TZ2ST9K39JC7KDITBXP6N';
        $this->apiBaseUrl = 'https://my.sepay.vn/userapi';
        
        // Инициализируем кэш (простой файловый кэш)
        $this->cache = new SepayCache();
    }
    
    private function loadEnvironmentVariables() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
    
    /**
     * Получить транзакции с фильтрацией
     */
    public function getTransactions($filters = []) {
        $cacheKey = 'sepay_transactions_' . md5(serialize($filters));
        
        // Проверяем кэш (кэшируем на 2 минуты)
        $cachedData = $this->cache->get($cacheKey, 120);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        try {
            $params = $this->buildApiParams($filters);
            $url = $this->apiBaseUrl . '/transactions/list?' . http_build_query($params);
            
            error_log("SepayService: Making request to: " . $url);
            $response = $this->makeApiRequest($url);
            
            if (!$response || !isset($response['transactions'])) {
                throw new Exception('Invalid API response');
            }
            
            $result = [
                'transactions' => $response['transactions'],
                'total' => count($response['transactions']),
                'page' => 1,
                'per_page' => 50,
                'total_pages' => 1
            ];
            
            // Кэшируем результат
            $this->cache->set($cacheKey, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("SepayService Error: " . $e->getMessage());
            
            // Если это rate limit, попробуем вернуть кэшированные данные
            if (strpos($e->getMessage(), 'Rate limit exceeded') !== false) {
                $cachedData = $this->cache->get($cacheKey, 3600); // Ищем в кэше до 1 часа
                if ($cachedData !== null) {
                    error_log("SepayService: Returning cached data due to rate limit");
                    return $cachedData;
                }
            }
            
            return [
                'transactions' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 50,
                'total_pages' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить статистику транзакций
     */
    public function getStats($filters = []) {
        $cacheKey = 'sepay_stats_' . md5(serialize($filters));
        
        // Проверяем кэш (кэшируем на 5 минут)
        $cachedData = $this->cache->get($cacheKey, 300);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        try {
            $params = $this->buildApiParams($filters);
            $url = $this->apiBaseUrl . '/transactions/count?' . http_build_query($params);
            
            $response = $this->makeApiRequest($url);
            
            if (!$response || !isset($response['transactions'])) {
                throw new Exception('Invalid API response');
            }
            
            $transactions = $response['transactions'];
            $total = count($transactions);
            $success = 0;
            $failed = 0;
            $pending = 0;
            $totalAmount = 0;
            
            foreach ($transactions as $transaction) {
                $amount = floatval($transaction['amount_in'] ?? 0);
                $totalAmount += $amount;
                
                // Определяем статус на основе суммы
                if ($amount > 0) {
                    $success++;
                } else {
                    $failed++;
                }
            }
            
            $stats = [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'pending' => $pending,
                'total_amount' => $totalAmount,
                'avg_amount' => $total > 0 ? $totalAmount / $total : 0
            ];
            
            // Кэшируем результат
            $this->cache->set($cacheKey, $stats);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("SepayService Stats Error: " . $e->getMessage());
            return [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'pending' => 0,
                'total_amount' => 0,
                'avg_amount' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить детали конкретной транзакции
     */
    public function getTransactionDetails($transactionId) {
        $cacheKey = 'sepay_transaction_' . $transactionId;
        
        // Проверяем кэш (кэшируем на 10 минут)
        $cachedData = $this->cache->get($cacheKey, 600);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        try {
            $url = $this->apiBaseUrl . '/transactions/details/' . $transactionId;
            $response = $this->makeApiRequest($url);
            
            if (!$response || !isset($response['transactions']) || empty($response['transactions'])) {
                throw new Exception('Transaction not found');
            }
            
            $transaction = $response['transactions'][0];
            
            // Кэшируем результат
            $this->cache->set($cacheKey, $transaction);
            
            return $transaction;
            
        } catch (Exception $e) {
            error_log("SepayService Transaction Details Error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Построить параметры для API запроса
     */
    private function buildApiParams($filters) {
        $params = [
            'limit' => $filters['limit'] ?? 50,
            'page' => $filters['page'] ?? 1
        ];
        
        if (!empty($filters['date_from'])) {
            $params['from_date'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $params['to_date'] = $filters['date_to'];
        }
        
        if (!empty($filters['status'])) {
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['amount_min'])) {
            $params['amount_min'] = $filters['amount_min'];
        }
        
        if (!empty($filters['amount_max'])) {
            $params['amount_max'] = $filters['amount_max'];
        }
        
        if (!empty($filters['search'])) {
            $params['search'] = $filters['search'];
        }
        
        return $params;
    }
    
    /**
     * Выполнить запрос к API
     */
    private function makeApiRequest($url) {
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'User-Agent: NorthRepublic/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'NorthRepublic/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        if ($httpCode === 429) {
            // Rate limit exceeded - читаем заголовок x-sepay-userapi-retry-after
            $retryAfter = null;
            if (preg_match('/x-sepay-userapi-retry-after:\s*(\d+)/i', $headers, $matches)) {
                $retryAfter = intval($matches[1]);
            }
            
            $message = "Rate limit exceeded. Sepay allows 2 requests per second.";
            if ($retryAfter) {
                $message .= " Retry after {$retryAfter} seconds.";
            }
            throw new Exception($message);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP {$httpCode} - {$body}");
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON Decode Error: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Проверить статус API
     */
    public function checkApiStatus() {
        try {
            $url = $this->apiBaseUrl . '/status';
            $response = $this->makeApiRequest($url);
            
            return [
                'status' => 'ok',
                'api_version' => $response['version'] ?? 'unknown',
                'timestamp' => time()
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => time()
            ];
        }
    }
}

/**
 * Простой файловый кэш для Sepay API
 */
class SepayCache {
    private $cacheDir;
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/sepay/';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function get($key, $ttl = 300) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['content'];
    }
    
    public function set($key, $content) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        $data = [
            'content' => $content,
            'expires' => time() + 300 // 5 минут по умолчанию
        ];
        
        file_put_contents($file, serialize($data));
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
?>
