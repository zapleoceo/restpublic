<?php
require_once '../includes/auth.php';
require_once '../../classes/TelegramService.php';
require_once '../../classes/SepayNotificationService.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Telegram Bot Management';
include '../includes/header.php';

try {
    $telegramService = new TelegramService();
    $notificationService = new SepayNotificationService();
    
    $botInfo = $telegramService->getBotInfo();
    $status = $notificationService->getStatus();
    $chatIds = $telegramService->getChatIds();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Telegram Bot Management</h4>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger">
                <strong>Ошибка:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Статус бота -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Статус Telegram Bot</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($botInfo) && !isset($botInfo['error'])): ?>
                    <div class="alert alert-success">
                        <strong>✅ Бот активен</strong><br>
                        <strong>Имя:</strong> <?php echo htmlspecialchars($botInfo['first_name']); ?><br>
                        <strong>Username:</strong> @<?php echo htmlspecialchars($botInfo['username']); ?><br>
                        <strong>ID:</strong> <?php echo htmlspecialchars($botInfo['id']); ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>❌ Бот неактивен</strong><br>
                        <?php echo isset($botInfo['error']) ? htmlspecialchars($botInfo['error']) : 'Не удалось получить информацию о боте'; ?>
                    </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary" onclick="testTelegram()">
                        <i class="fas fa-paper-plane"></i> Тест Telegram
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Статус Sepay API</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>🔗 Подключение к Sepay API</strong><br>
                        <strong>Последняя транзакция:</strong> <?php echo $status['lastTransactionId'] ?? 'Не определена'; ?><br>
                        <strong>Интервал проверки:</strong> <?php echo $status['checkInterval']; ?> сек
                    </div>
                    
                    <button class="btn btn-info" onclick="testSepay()">
                        <i class="fas fa-link"></i> Тест Sepay API
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Управление уведомлениями -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Управление уведомлениями</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Настроенные чаты:</h6>
                            <ul class="list-group">
                                <?php foreach ($chatIds as $chatId): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($chatId); ?>
                                    <button class="btn btn-sm btn-danger" onclick="removeChat('<?php echo $chatId; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="mt-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="newChatId" placeholder="ID чата">
                                    <button class="btn btn-success" onclick="addChat()">
                                        <i class="fas fa-plus"></i> Добавить
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Действия:</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-warning" onclick="checkTransactions()">
                                    <i class="fas fa-search"></i> Проверить транзакции
                                </button>
                                
                                <button class="btn btn-info" onclick="sendTestMessage()">
                                    <i class="fas fa-paper-plane"></i> Отправить тестовое сообщение
                                </button>
                                
                                <div class="input-group mt-2">
                                    <input type="number" class="form-control" id="intervalSeconds" 
                                           value="<?php echo $status['checkInterval']; ?>" min="10" max="3600">
                                    <span class="input-group-text">сек</span>
                                    <button class="btn btn-secondary" onclick="setInterval()">
                                        <i class="fas fa-clock"></i> Установить интервал
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Логи и статистика -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Информация о системе</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Сервер:</strong> NR (northrepublic.me)<br>
                            <strong>Статус мониторинга:</strong> 
                            <span class="badge bg-<?php echo $status['isRunning'] ? 'success' : 'secondary'; ?>">
                                <?php echo $status['isRunning'] ? 'Активен' : 'Неактивен'; ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <strong>Количество чатов:</strong> <?php echo count($chatIds); ?><br>
                            <strong>Интервал проверки:</strong> <?php echo $status['checkInterval']; ?> сек
                        </div>
                        <div class="col-md-4">
                            <strong>Последняя транзакция:</strong><br>
                            <code><?php echo $status['lastTransactionId'] ?? 'Не определена'; ?></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testTelegram() {
    showLoading('Тестирование Telegram...');
    
    fetch('api.php?action=test_telegram')
        .then(response => response.json())
        .then(data => {
            hideLoading();
            showAlert(data.success ? 'success' : 'danger', data.message);
        })
        .catch(error => {
            hideLoading();
            showAlert('danger', 'Ошибка: ' + error.message);
        });
}

function testSepay() {
    showLoading('Тестирование Sepay API...');
    
    fetch('api.php?action=test_sepay')
        .then(response => response.json())
        .then(data => {
            hideLoading();
            showAlert(data.success ? 'success' : 'danger', data.message);
        })
        .catch(error => {
            hideLoading();
            showAlert('danger', 'Ошибка: ' + error.message);
        });
}

function checkTransactions() {
    showLoading('Проверка транзакций...');
    
    fetch('api.php?action=check_transactions')
        .then(response => response.json())
        .then(data => {
            hideLoading();
            showAlert(data.success ? 'success' : 'danger', data.message);
        })
        .catch(error => {
            hideLoading();
            showAlert('danger', 'Ошибка: ' + error.message);
        });
}

function sendTestMessage() {
    const message = prompt('Введите тестовое сообщение:');
    if (!message) return;
    
    showLoading('Отправка сообщения...');
    
    const formData = new FormData();
    formData.append('message', message);
    
    fetch('api.php?action=send_notification', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert(data.success ? 'success' : 'danger', data.message);
    })
    .catch(error => {
        hideLoading();
        showAlert('danger', 'Ошибка: ' + error.message);
    });
}

function addChat() {
    const chatId = document.getElementById('newChatId').value.trim();
    if (!chatId) {
        showAlert('warning', 'Введите ID чата');
        return;
    }
    
    showLoading('Добавление чата...');
    
    const formData = new FormData();
    formData.append('chat_id', chatId);
    
    fetch('api.php?action=add_chat', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert(data.success ? 'success' : 'warning', data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('danger', 'Ошибка: ' + error.message);
    });
}

function removeChat(chatId) {
    if (!confirm('Удалить чат ' + chatId + ' из списка уведомлений?')) {
        return;
    }
    
    showLoading('Удаление чата...');
    
    const formData = new FormData();
    formData.append('chat_id', chatId);
    
    fetch('api.php?action=remove_chat', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert(data.success ? 'success' : 'warning', data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        hideLoading();
        showAlert('danger', 'Ошибка: ' + error.message);
    });
}

function setInterval() {
    const seconds = document.getElementById('intervalSeconds').value;
    if (!seconds || seconds < 10) {
        showAlert('warning', 'Интервал должен быть не менее 10 секунд');
        return;
    }
    
    showLoading('Установка интервала...');
    
    const formData = new FormData();
    formData.append('seconds', seconds);
    
    fetch('api.php?action=set_interval', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        showAlert(data.success ? 'success' : 'danger', data.message);
    })
    .catch(error => {
        hideLoading();
        showAlert('danger', 'Ошибка: ' + error.message);
    });
}

function showLoading(message) {
    // Создаем модальное окно загрузки
    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">${message}</p>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function hideLoading() {
    const modal = document.querySelector('.modal.show');
    if (modal) {
        modal.remove();
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Автоматически скрываем через 5 секунд
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>

<?php include '../includes/footer.php'; ?>
