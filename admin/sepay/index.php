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
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card__icon stats-card__icon--primary">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stats-card__content">
                    <div class="stats-card__label">Всего транзакций</div>
                    <div class="stats-card__value"><?php echo number_format($stats['total_transactions'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card__icon stats-card__icon--success">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stats-card__content">
                    <div class="stats-card__label">Общая сумма</div>
                    <div class="stats-card__value"><?php echo number_format($stats['total_amount'] ?? 0, 0, ',', ' '); ?> ₫</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card__icon stats-card__icon--info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stats-card__content">
                    <div class="stats-card__label">Средняя сумма</div>
                    <div class="stats-card__value"><?php echo number_format(($stats['total_amount'] ?? 0) / max(1, $stats['total_transactions'] ?? 1), 0, ',', ' '); ?> ₫</div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card">
                <div class="stats-card__icon stats-card__icon--warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-card__content">
                    <div class="stats-card__label">Сегодня</div>
                    <div class="stats-card__value"><?php echo number_format($stats['today_transactions'] ?? 0); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="filters-section mb-4">
        <div class="filters-header">
            <h5 class="filters-title">
                <i class="fas fa-filter me-2"></i>
                Фильтры и поиск
            </h5>
            <div class="filters-actions">
                <div class="quick-filters">
                    <button type="button" class="btn btn-outline-light btn-sm quick-filter" data-filter="today">
                        <i class="fas fa-calendar-day me-1"></i>Сегодня
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm quick-filter" data-filter="week">
                        <i class="fas fa-calendar-week me-1"></i>Неделя
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm quick-filter" data-filter="month">
                        <i class="fas fa-calendar-alt me-1"></i>Месяц
                    </button>
                </div>
                <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="false">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
        
        <div class="collapse show" id="filtersCollapse">
            <div class="filters-content">
                <form method="GET" class="filters-form">
                    <div class="row g-3">
                        <!-- Дата от -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Дата от
                                </label>
                                <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>
                        </div>
                        
                        <!-- Дата до -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Дата до
                                </label>
                                <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>
                        </div>
                        
                        <!-- Сумма от -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    Сумма от
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" name="amount_min" value="<?php echo htmlspecialchars($filters['amount_min']); ?>" placeholder="0">
                                    <span class="input-group-text">₫</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Сумма до -->
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    Сумма до
                                </label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" name="amount_max" value="<?php echo htmlspecialchars($filters['amount_max']); ?>" placeholder="999999999">
                                    <span class="input-group-text">₫</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Поиск -->
                        <div class="col-lg-3 col-md-8 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-search me-1"></i>
                                    Поиск
                                </label>
                                <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="ID, описание, код, банк...">
                            </div>
                        </div>
                        
                        <!-- Кнопки -->
                        <div class="col-lg-1 col-md-4 col-sm-12">
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-filter me-1"></i>
                                        Применить
                                    </button>
                                    <a href="?" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i>
                                        Сбросить
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
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
                                <tr><td><strong>Сумма:</strong></td><td><strong>${new Intl.NumberFormat().format(transaction.amount)} ₫</strong></td></tr>
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
                `;
            } else {
                detailsDiv.innerHTML = `<div class="alert alert-danger">Ошибка: ${data.error}</div>`;
            }
        })
        .catch(error => {
            detailsDiv.innerHTML = `<div class="alert alert-danger">Ошибка загрузки: ${error.message}</div>`;
        });
}

// Дополнительные функции для работы с фильтрами
document.addEventListener('DOMContentLoaded', function() {
    // Автоматическое сворачивание фильтров на мобильных устройствах
    if (window.innerWidth < 768) {
        const collapse = document.getElementById('filtersCollapse');
        if (collapse) {
            collapse.classList.remove('show');
        }
    }
    
    // Быстрые фильтры
    const quickFilters = document.querySelectorAll('.quick-filter');
    quickFilters.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            applyQuickFilter(filter);
        });
    });
    
    // Автосохранение фильтров в localStorage
    const form = document.querySelector('.filters-form');
    if (form) {
        // Загружаем сохраненные фильтры
        loadSavedFilters();
        
        // Сохраняем фильтры при изменении
        form.addEventListener('change', function() {
            saveFilters();
        });
    }
});

function saveFilters() {
    const form = document.querySelector('.filters-form');
    const formData = new FormData(form);
    const filters = {};
    
    for (let [key, value] of formData.entries()) {
        if (value) filters[key] = value;
    }
    
    localStorage.setItem('sepay_filters', JSON.stringify(filters));
}

function loadSavedFilters() {
    const saved = localStorage.getItem('sepay_filters');
    if (saved) {
        const filters = JSON.parse(saved);
        const form = document.querySelector('.filters-form');
        
        Object.keys(filters).forEach(key => {
            const input = form.querySelector(`input[name="${key}"]`);
            if (input) {
                input.value = filters[key];
            }
        });
    }
}

function applyQuickFilter(filter) {
    const form = document.querySelector('.filters-form');
    const inputs = form.querySelectorAll('input');
    
    // Очищаем все поля
    inputs.forEach(input => {
        input.value = '';
    });
    
    // Применяем быстрый фильтр
    switch(filter) {
        case 'today':
            const today = new Date().toISOString().split('T')[0];
            form.querySelector('input[name="date_from"]').value = today;
            form.querySelector('input[name="date_to"]').value = today;
            break;
        case 'week':
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            form.querySelector('input[name="date_from"]').value = weekAgo.toISOString().split('T')[0];
            break;
        case 'month':
            const monthAgo = new Date();
            monthAgo.setMonth(monthAgo.getMonth() - 1);
            form.querySelector('input[name="date_from"]').value = monthAgo.toISOString().split('T')[0];
            break;
    }
    
    form.submit();
}
</script>

        </main>
    </div>

<?php
$content = ob_get_clean();

// Подключаем дополнительные стили
$additional_css = ['/admin/sepay/sepay.css'];

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>