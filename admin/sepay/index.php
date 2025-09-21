<?php
session_start();

// Проверка авторизации
require_once __DIR__ . '/../includes/auth-check.php';

require_once __DIR__ . '/../../classes/SePayApiService.php';
require_once __DIR__ . '/../../classes/SePayTransactionService.php';

try {
    $apiService = new SePayApiService();
    $transactionService = new SePayTransactionService();
    
    // Получаем параметры фильтрации
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    
    $filters = [
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'amount_min' => $_GET['amount_min'] ?? '',
        'amount_max' => $_GET['amount_max'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Получаем транзакции из API
    $result = $apiService->getTransactions($filters);
    $allTransactions = $result['transactions'];
    $total = $result['total'];
    
    // Пагинация
    $limit = 50;
    $pages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    $transactions = array_slice($allTransactions, $offset, $limit);
    
    // Получаем статистику
    $stats = $apiService->getStats();
    
    // Добавляем информацию о Telegram статусе из MongoDB
    foreach ($transactions as &$transaction) {
        $telegramStatus = $transactionService->getSentStatus($transaction['id']);
        
        // Если транзакция не найдена в MongoDB, создаем запись
        if ($telegramStatus['sent'] === null) {
            $transactionData = [
                'transaction_id' => $transaction['id'],
                'amount' => floatval($transaction['amount_in']),
                'content' => $transaction['transaction_content'],
                'code' => $transaction['reference_number'],
                'gateway' => $transaction['bank_brand_name'],
                'account_number' => $transaction['account_number'],
                'transaction_date' => $transaction['transaction_date'],
                'webhook_received_at' => null, // Не получено через webhook
                'telegram_sent' => false, // По умолчанию не отправлено
                'telegram_sent_at' => null,
                'telegram_message_id' => null
            ];
            
            $transactionService->saveTransaction($transactionData);
            $telegramStatus = $transactionService->getSentStatus($transaction['id']);
        }
        
        $transaction['telegram_sent'] = $telegramStatus['sent'] ?? false;
        $transaction['telegram_sent_at'] = $telegramStatus['sent_at'] ?? null;
        $transaction['telegram_message_id'] = $telegramStatus['message_id'] ?? null;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $transactions = [];
    $stats = [];
    
    // Проверяем, есть ли информация о времени ожидания при Rate Limit
    if (strpos($error, 'Rate limit exceeded') !== false) {
        $retryAfter = 5; // По умолчанию 5 секунд
        $error .= " Подождите {$retryAfter} секунд и обновите страницу.";
    }
}


// Генерируем контент
ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SePay Transactions - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"><div class="admin-container"><main class="admin-main">
            <div class="page-header">
                <h1>SePay Transactions</h1>
                <p>Просмотр транзакций полученных через webhook</p>
            </div>

<div class="container-fluid">

    <?php if (isset($error)): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-danger">
                <strong>Ошибка:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Статистика -->
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-primary-subtle text-primary">
                                <i class="fas fa-credit-card font-size-18"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Всего транзакций</p>
                            <h4 class="mb-0"><?php echo number_format($stats['total_transactions'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-success-subtle text-success">
                                <i class="fas fa-paper-plane font-size-18"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Отправлено в Telegram</p>
                            <h4 class="mb-0"><?php echo number_format($stats['telegram_sent'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-warning-subtle text-warning">
                                <i class="fas fa-clock font-size-18"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Не отправлено</p>
                            <h4 class="mb-0"><?php echo number_format($stats['telegram_not_sent'] ?? 0); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm rounded-circle bg-info-subtle text-info">
                                <i class="fas fa-dollar-sign font-size-18"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Общая сумма</p>
                            <h4 class="mb-0"><?php echo number_format($stats['total_amount'] ?? 0, 0, ',', ' '); ?> VND</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Фильтры</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Дата от</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Дата до</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Сумма от</label>
                            <input type="number" class="form-control" name="amount_min" value="<?php echo htmlspecialchars($filters['amount_min']); ?>" placeholder="VND">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Сумма до</label>
                            <input type="number" class="form-control" name="amount_max" value="<?php echo htmlspecialchars($filters['amount_max']); ?>" placeholder="VND">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Поиск</label>
                            <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="ID, описание, код">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Фильтр</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица транзакций -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Транзакции (<?php echo number_format($total); ?>)</h5>
                    <button class="btn btn-success" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Обновить
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Транзакции не найдены</h5>
                        <p class="text-muted">Попробуйте изменить фильтры или дождитесь новых транзакций</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Сумма</th>
                                    <th>Описание</th>
                                    <th>Банк</th>
                                    <th>Дата</th>
                                    <th>Telegram</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($transaction['id']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($transaction['amount_in'], 0, ',', ' '); ?> VND</strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['transaction_content']); ?>
                                        <?php if (!empty($transaction['reference_number'])): ?>
                                        <br><small class="text-muted">Код: <?php echo htmlspecialchars($transaction['reference_number']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($transaction['bank_brand_name']); ?>
                                        <?php if (!empty($transaction['account_number'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($transaction['account_number']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d.m.Y H:i', strtotime($transaction['transaction_date'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction['telegram_sent']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Отправлено
                                        </span>
                                        <?php if ($transaction['telegram_sent_at']): ?>
                                        <br><small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($transaction['telegram_sent_at'])); ?></small>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock"></i> Не отправлено
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction('<?php echo $transaction['id']; ?>')">
                                            <i class="fas fa-eye"></i> Подробнее
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Пагинация -->
                    <?php if ($pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Предыдущая</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Следующая</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для деталей транзакции -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали транзакции</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="transactionDetails">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewTransaction(transactionId) {
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    const detailsDiv = document.getElementById('transactionDetails');
    
    detailsDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Загрузка...</span></div></div>';
    modal.show();
    
    fetch(`api.php?action=get_transaction&id=${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const transaction = data.transaction;
                detailsDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Основная информация</h6>
                            <table class="table table-sm">
                                <tr><td><strong>ID:</strong></td><td><code>${transaction.id}</code></td></tr>
                                <tr><td><strong>Сумма:</strong></td><td><strong>${new Intl.NumberFormat().format(transaction.amount)} VND</strong></td></tr>
                                <tr><td><strong>Описание:</strong></td><td>${transaction.content}</td></tr>
                                <tr><td><strong>Код:</strong></td><td>${transaction.code || 'Не указан'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Дополнительная информация</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Банк:</strong></td><td>${transaction.gateway}</td></tr>
                                <tr><td><strong>Номер счета:</strong></td><td>${transaction.account_number || 'Не указан'}</td></tr>
                                <tr><td><strong>Дата транзакции:</strong></td><td>${new Date(transaction.transaction_date).toLocaleString()}</td></tr>
                                <tr><td><strong>Webhook получен:</strong></td><td>${new Date(transaction.webhook_received_at).toLocaleString()}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Статус Telegram</h6>
                            ${transaction.telegram_sent ? 
                                `<span class="badge bg-success"><i class="fas fa-check"></i> Отправлено</span>` +
                                (transaction.telegram_sent_at ? `<br><small class="text-muted">${new Date(transaction.telegram_sent_at).toLocaleString()}</small>` : '') +
                                (transaction.telegram_message_id ? `<br><small class="text-muted">Message ID: ${transaction.telegram_message_id}</small>` : '')
                                : 
                                `<span class="badge bg-warning"><i class="fas fa-clock"></i> Не отправлено</span>`
                            }
                        </div>
                    </div>
                `;
            } else {
                detailsDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.error}</div>`;
            }
        })
        .catch(error => {
            detailsDiv.innerHTML = `<div class="alert alert-danger">Ошибка загрузки: ${error.message}</div>`;
        });
}
</script>

        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>