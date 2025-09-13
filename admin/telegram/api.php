<?php
require_once '../../includes/auth.php';
require_once '../../classes/TelegramService.php';
require_once '../../classes/SepayNotificationService.php';

header('Content-Type: application/json');

// Проверяем авторизацию
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'status':
            $telegramService = new TelegramService();
            $notificationService = new SepayNotificationService();
            
            $botInfo = $telegramService->getBotInfo();
            $status = $notificationService->getStatus();
            
            echo json_encode([
                'success' => true,
                'bot' => $botInfo,
                'notifications' => $status
            ]);
            break;
            
        case 'test_telegram':
            $notificationService = new SepayNotificationService();
            $result = $notificationService->testTelegramConnection();
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result
            ]);
            break;
            
        case 'test_sepay':
            $notificationService = new SepayNotificationService();
            $result = $notificationService->testSepayConnection();
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => $result
            ]);
            break;
            
        case 'send_notification':
            $telegramService = new TelegramService();
            $message = $_POST['message'] ?? '';
            
            if (empty($message)) {
                throw new Exception('Сообщение не может быть пустым');
            }
            
            $result = $telegramService->sendToAllChats($message);
            $successCount = 0;
            
            foreach ($result as $chatId => $success) {
                if ($success) $successCount++;
            }
            
            echo json_encode([
                'success' => $successCount > 0,
                'message' => "Сообщение отправлено в {$successCount} из " . count($telegramService->getChatIds()) . " чатов",
                'details' => $result
            ]);
            break;
            
        case 'check_transactions':
            $notificationService = new SepayNotificationService();
            $result = $notificationService->sendTransactionNotifications();
            
            echo json_encode([
                'success' => true,
                'message' => "Проверка завершена. Найдено: {$result['count']}, отправлено: {$result['sent']}",
                'details' => $result
            ]);
            break;
            
        case 'set_interval':
            $seconds = intval($_POST['seconds'] ?? 30);
            
            if ($seconds < 10) {
                throw new Exception('Минимальный интервал: 10 секунд');
            }
            
            $notificationService = new SepayNotificationService();
            $notificationService->setCheckInterval($seconds);
            
            echo json_encode([
                'success' => true,
                'message' => "Интервал проверки установлен: {$seconds} секунд"
            ]);
            break;
            
        case 'get_chats':
            $telegramService = new TelegramService();
            $chatIds = $telegramService->getChatIds();
            
            echo json_encode([
                'success' => true,
                'chats' => $chatIds
            ]);
            break;
            
        case 'add_chat':
            $chatId = $_POST['chat_id'] ?? '';
            
            if (empty($chatId)) {
                throw new Exception('ID чата не может быть пустым');
            }
            
            $telegramService = new TelegramService();
            $result = $telegramService->addChatId($chatId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => "Чат {$chatId} добавлен в список уведомлений"
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Чат {$chatId} уже существует в списке"
                ]);
            }
            break;
            
        case 'remove_chat':
            $chatId = $_POST['chat_id'] ?? '';
            
            if (empty($chatId)) {
                throw new Exception('ID чата не может быть пустым');
            }
            
            $telegramService = new TelegramService();
            $result = $telegramService->removeChatId($chatId);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => "Чат {$chatId} удален из списка уведомлений"
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Чат {$chatId} не найден в списке"
                ]);
            }
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
