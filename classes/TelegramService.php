<?php

class TelegramService {
    private $botToken;
    private $apiUrl;
    private $chatIds;
    
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
        
        $this->botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
        
        // Целевые чаты для уведомлений
        $this->chatIds = [
            '7795513546', // Rest_publica_bar
            '169510539'   // zapleosoft
        ];
        
        if (empty($this->botToken)) {
            throw new Exception('TELEGRAM_BOT_TOKEN не установлен');
        }
    }
    
    /**
     * Отправка сообщения в Telegram
     */
    public function sendMessage($chatId, $text, $parseMode = 'Markdown') {
        $url = $this->apiUrl . '/sendMessage';
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("TelegramService: cURL Error: " . $error);
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("TelegramService: HTTP Error: " . $httpCode . " Response: " . $response);
            return false;
        }
        
        $result = json_decode($response, true);
        
        if (!$result['ok']) {
            error_log("TelegramService: API Error: " . $response);
            return false;
        }
        
        return true;
    }
    
    /**
     * Отправка сообщения во все настроенные чаты
     */
    public function sendToAllChats($text, $parseMode = 'Markdown') {
        $results = [];
        
        foreach ($this->chatIds as $chatId) {
            $result = $this->sendMessage($chatId, $text, $parseMode);
            $results[$chatId] = $result;
            
            // Небольшая задержка между отправками
            usleep(500000); // 0.5 секунды
        }
        
        return $results;
    }
    
    /**
     * Форматирование сообщения о транзакции Sepay
     */
    public function formatSepayTransactionMessage($transaction) {
        $amount = number_format($transaction['amount_in'], 0, ',', ' ');
        $date = date('d.m.Y H:i', strtotime($transaction['transaction_date']));
        
        $message = "💵 **Новый платеж: {$amount} VND**\n\n";
        $message .= "📅 Время: {$date}\n";
        $message .= "📝 Описание: {$transaction['transaction_content']}\n";
        $message .= "🆔 ID: `{$transaction['id']}`";
        
        return $message;
    }
    
    /**
     * Отправка уведомления о новой транзакции Sepay
     */
    public function sendSepayTransactionNotification($transaction) {
        $message = $this->formatSepayTransactionMessage($transaction);
        return $this->sendToAllChats($message);
    }
    
    /**
     * Проверка статуса бота
     */
    public function getBotInfo() {
        $url = $this->apiUrl . '/getMe';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['error' => 'cURL Error: ' . $error];
        }
        
        if ($httpCode !== 200) {
            return ['error' => 'HTTP Error: ' . $httpCode];
        }
        
        $result = json_decode($response, true);
        
        if (!$result['ok']) {
            return ['error' => 'API Error: ' . $response];
        }
        
        return $result['result'];
    }
    
    /**
     * Получение списка настроенных чатов
     */
    public function getChatIds() {
        return $this->chatIds;
    }
    
    /**
     * Добавление нового чата для уведомлений
     */
    public function addChatId($chatId) {
        if (!in_array($chatId, $this->chatIds)) {
            $this->chatIds[] = $chatId;
            return true;
        }
        return false;
    }
    
    /**
     * Удаление чата из списка уведомлений
     */
    public function removeChatId($chatId) {
        $key = array_search($chatId, $this->chatIds);
        if ($key !== false) {
            unset($this->chatIds[$key]);
            $this->chatIds = array_values($this->chatIds); // Переиндексация
            return true;
        }
        return false;
    }
}
