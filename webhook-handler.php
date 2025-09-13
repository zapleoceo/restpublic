<?php
header('Content-Type: application/json');

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

require_once 'classes/TelegramService.php';
require_once 'classes/SePayTransactionService.php';

// Проверяем API ключ
$apiKey = $_ENV['SEPAY_INCOMING_API_TOKEN'] ?? null;
if (empty($apiKey)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'API key not configured']);
    exit;
}

// Проверяем авторизацию через API ключ
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// SePay отправляет: Authorization: Apikey API_KEY
$receivedApiKey = '';
if (strpos($authHeader, 'Apikey ') === 0) {
    $receivedApiKey = str_replace('Apikey ', '', $authHeader);
} elseif (strpos($authHeader, 'Bearer ') === 0) {
    $receivedApiKey = str_replace('Bearer ', '', $authHeader);
}

if ($receivedApiKey !== $apiKey) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Получаем данные от SePay
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Логируем входящий запрос (для отладки)
    file_put_contents('logs/sepay_webhook.log', date('Y-m-d H:i:s') . " - RAW: " . $input . "\n", FILE_APPEND | LOCK_EX);
    file_put_contents('logs/sepay_webhook.log', date('Y-m-d H:i:s') . " - DECODED: " . json_encode($data) . "\n", FILE_APPEND | LOCK_EX);

    // Обработка транзакции
    if ($data && isset($data['id'])) {
        $transactionService = new SePayTransactionService();
        $telegramService = new TelegramService();
        
        // Извлекаем данные
        $transactionId = $data['id'];
        $amount = $data['transferAmount'] ?? $data['amount'] ?? 0;
        $content = $data['content'] ?? $data['transaction_content'] ?? '';
        $code = $data['code'] ?? $data['reference_number'] ?? '';
        $gateway = $data['gateway'] ?? $data['bank_brand_name'] ?? '';
        $accountNumber = $data['accountNumber'] ?? $data['account_number'] ?? '';
        $transactionDate = $data['transaction_date'] ?? date('Y-m-d H:i:s');
        
        // Сохраняем транзакцию в MongoDB
        $transactionData = [
            'transaction_id' => $transactionId,
            'amount' => floatval($amount),
            'content' => $content,
            'code' => $code,
            'gateway' => $gateway,
            'account_number' => $accountNumber,
            'transaction_date' => $transactionDate,
            'webhook_received_at' => new MongoDB\BSON\UTCDateTime(),
            'telegram_sent' => false,
            'telegram_sent_at' => null,
            'telegram_message_id' => null
        ];
        
        $saved = $transactionService->saveTransaction($transactionData);
        
        if ($saved) {
            // Отправляем уведомление в Telegram
            $message = "💵 **Новый платеж: " . number_format($amount, 0, ',', ' ') . " VND**\n\n";
            $message .= "📅 Время: " . date('d.m.Y H:i', strtotime($transactionDate)) . "\n";
            $message .= "📝 Описание: {$content}\n";
            $message .= "🏦 Банк: {$gateway}\n";
            $message .= "🆔 ID: `{$transactionId}`";
            
            $telegramResult = $telegramService->sendToAllChats($message);
            
            // Обновляем статус отправки в Telegram
            $telegramSent = false;
            $telegramMessageId = null;
            
            foreach ($telegramResult as $chatId => $success) {
                if ($success) {
                    $telegramSent = true;
                    // Получаем message_id если возможно
                    $telegramMessageId = $success['message_id'] ?? null;
                    break;
                }
            }
            
            if ($telegramSent) {
                $transactionService->markTelegramSent($transactionId, $telegramMessageId);
            }
            
            // ОБЯЗАТЕЛЬНО: Возвращаем успешный ответ
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'transaction_id' => $transactionId,
                'telegram_sent' => $telegramSent
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save transaction']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid data format']);
    }
    
} catch (Exception $e) {
    // Логируем ошибку
    file_put_contents('logs/sepay_webhook.log', date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
