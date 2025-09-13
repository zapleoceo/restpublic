<?php

require_once 'SepayService.php';
require_once 'TelegramService.php';

class SepayNotificationService {
    private $sepayService;
    private $telegramService;
    private $lastTransactionId;
    private $isRunning;
    private $checkInterval;
    
    public function __construct() {
        $this->sepayService = new SepayService();
        $this->telegramService = new TelegramService();
        $this->lastTransactionId = null;
        $this->isRunning = false;
        $this->checkInterval = 30; // Проверка каждые 30 секунд
    }
    
    /**
     * Получение новых транзакций
     */
    public function getNewTransactions() {
        try {
            $transactions = $this->sepayService->getTransactions();
            
            if (empty($transactions)) {
                return [];
            }
            
            // Если это первый запуск, сохраняем ID последней транзакции
            if ($this->lastTransactionId === null) {
                $this->lastTransactionId = $transactions[0]['id'];
                error_log("SepayNotificationService: Первый запуск, сохранен ID: " . $this->lastTransactionId);
                return [];
            }
            
            // Находим новые транзакции (входящие платежи)
            $newTransactions = [];
            foreach ($transactions as $transaction) {
                if ($transaction['id'] === $this->lastTransactionId) {
                    break; // Достигли последней известной транзакции
                }
                
                // Проверяем, что это входящий платеж
                if (floatval($transaction['amount_in']) > 0) {
                    $newTransactions[] = $transaction;
                }
            }
            
            // Обновляем ID последней транзакции
            if (!empty($transactions)) {
                $this->lastTransactionId = $transactions[0]['id'];
            }
            
            return $newTransactions;
            
        } catch (Exception $e) {
            error_log("SepayNotificationService: Ошибка получения транзакций: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Отправка уведомлений о новых транзакциях
     */
    public function sendTransactionNotifications() {
        try {
            $newTransactions = $this->getNewTransactions();
            
            if (empty($newTransactions)) {
                return ['count' => 0, 'sent' => 0];
            }
            
            error_log("SepayNotificationService: Найдено " . count($newTransactions) . " новых транзакций");
            
            $sentCount = 0;
            foreach ($newTransactions as $transaction) {
                $result = $this->telegramService->sendSepayTransactionNotification($transaction);
                
                if ($result) {
                    $sentCount++;
                    error_log("SepayNotificationService: Уведомление отправлено для транзакции " . $transaction['id']);
                } else {
                    error_log("SepayNotificationService: Ошибка отправки уведомления для транзакции " . $transaction['id']);
                }
                
                // Небольшая задержка между отправками
                sleep(1);
            }
            
            return ['count' => count($newTransactions), 'sent' => $sentCount];
            
        } catch (Exception $e) {
            error_log("SepayNotificationService: Ошибка отправки уведомлений: " . $e->getMessage());
            return ['count' => 0, 'sent' => 0, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Запуск мониторинга (для cron или демона)
     */
    public function startMonitoring() {
        if ($this->isRunning) {
            error_log("SepayNotificationService: Мониторинг уже запущен");
            return false;
        }
        
        $this->isRunning = true;
        error_log("SepayNotificationService: Запуск мониторинга транзакций Sepay");
        
        // Первая проверка сразу при запуске
        $this->sendTransactionNotifications();
        
        return true;
    }
    
    /**
     * Остановка мониторинга
     */
    public function stopMonitoring() {
        if (!$this->isRunning) {
            error_log("SepayNotificationService: Мониторинг не запущен");
            return false;
        }
        
        $this->isRunning = false;
        error_log("SepayNotificationService: Остановка мониторинга транзакций Sepay");
        
        return true;
    }
    
    /**
     * Проверка статуса мониторинга
     */
    public function getStatus() {
        return [
            'isRunning' => $this->isRunning,
            'lastTransactionId' => $this->lastTransactionId,
            'checkInterval' => $this->checkInterval,
            'chatIds' => $this->telegramService->getChatIds()
        ];
    }
    
    /**
     * Тестирование подключения к Sepay API
     */
    public function testSepayConnection() {
        try {
            $transactions = $this->sepayService->getTransactions();
            return [
                'success' => true,
                'count' => count($transactions),
                'message' => 'Подключение к Sepay API успешно'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Ошибка подключения к Sepay API'
            ];
        }
    }
    
    /**
     * Тестирование отправки в Telegram
     */
    public function testTelegramConnection() {
        try {
            $testMessage = "🧪 Тестовое сообщение от SepayNotificationService\n\nВремя: " . date('d.m.Y H:i:s');
            $result = $this->telegramService->sendToAllChats($testMessage);
            
            $successCount = 0;
            foreach ($result as $chatId => $success) {
                if ($success) $successCount++;
            }
            
            return [
                'success' => $successCount > 0,
                'sentTo' => $successCount,
                'totalChats' => count($this->telegramService->getChatIds()),
                'message' => "Тестовое сообщение отправлено в {$successCount} из " . count($this->telegramService->getChatIds()) . " чатов"
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Ошибка отправки тестового сообщения в Telegram'
            ];
        }
    }
    
    /**
     * Установка интервала проверки
     */
    public function setCheckInterval($seconds) {
        if ($seconds < 10) {
            throw new Exception('Минимальный интервал проверки: 10 секунд');
        }
        
        $this->checkInterval = $seconds;
        error_log("SepayNotificationService: Установлен интервал проверки: {$seconds} секунд");
        
        return true;
    }
    
    /**
     * Получение интервала проверки
     */
    public function getCheckInterval() {
        return $this->checkInterval;
    }
}
