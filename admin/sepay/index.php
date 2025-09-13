<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к Sepay API
require_once __DIR__ . '/../../classes/SepayService.php';

try {
    $sepayService = new SepayService();
    
    // Параметры фильтрации
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    
    $filters = [
        'page' => $page,
        'limit' => $limit,
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'status' => $_GET['status'] ?? '',
        'amount_min' => $_GET['amount_min'] ?? '',
        'amount_max' => $_GET['amount_max'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    // Получаем транзакции от API
    $apiResponse = $sepayService->getTransactions($filters);
    
    // Проверяем на rate limit
    $rateLimit = false;
    $retryAfter = null;
    if (isset($apiResponse['rate_limit']) && $apiResponse['rate_limit']) {
        $rateLimit = true;
        $retryAfter = $apiResponse['retry_after'] ?? null;
    }
    
    if (isset($apiResponse['error']) && !$rateLimit) {
        throw new Exception("Ошибка API Sepay: " . $apiResponse['error']);
    }
    
    $logs = $apiResponse['transactions'] ?? [];
    $totalCount = $apiResponse['total'] ?? 0;
    $totalPages = $apiResponse['total_pages'] ?? 0;
    
    // Получаем статистику
    $statsResponse = $sepayService->getStats($filters);
    $stats = [
        'total' => $statsResponse['total'] ?? 0,
        'success' => $statsResponse['success'] ?? 0,
        'failed' => $statsResponse['failed'] ?? 0,
        'pending' => $statsResponse['pending'] ?? 0
    ];
    
} catch (Exception $e) {
    $logs = [];
    $totalCount = 0;
    $totalPages = 0;
    $stats = ['total' => 0, 'success' => 0, 'failed' => 0, 'pending' => 0];
    $error = "Ошибка подключения к Sepay API: " . $e->getMessage();
    $rateLimit = false;
    $retryAfter = null;
}

// Логируем просмотр логов
logAdminAction('view_sepay_logs', 'Просмотр логов платежей Sepay', [
    'filters' => $_GET,
    'page' => $page
]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи платежей Sepay - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .filters-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-item.success h3 { color: #27ae60; }
        .stat-item.failed h3 { color: #e74c3c; }
        .stat-item.pending h3 { color: #f39c12; }
        .stat-item.total h3 { color: #3498db; }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #e1e5e9;
            border-radius: 5px;
            text-decoration: none;
            color: #666;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Логи платежей Sepay</h1>
                <p>Мониторинг транзакций BIDV</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if ($rateLimit && $retryAfter): ?>
                        <br><br>
                        <strong>Rate Limit активен!</strong><br>
                        Можно обновить через: <span id="countdown"><?php echo $retryAfter; ?></span> секунд
                        <br><br>
                        <button id="refreshBtn" onclick="location.reload()" disabled>
                            Обновить (через <?php echo $retryAfter; ?> сек)
                        </button>
                        
                        <script>
                        let countdown = <?php echo $retryAfter; ?>;
                        const countdownElement = document.getElementById('countdown');
                        const refreshBtn = document.getElementById('refreshBtn');
                        
                        const timer = setInterval(() => {
                            countdown--;
                            countdownElement.textContent = countdown;
                            refreshBtn.textContent = `Обновить (через ${countdown} сек)`;
                            
                            if (countdown <= 0) {
                                clearInterval(timer);
                                refreshBtn.disabled = false;
                                refreshBtn.textContent = 'Обновить';
                                countdownElement.textContent = '0';
                            }
                        }, 1000);
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="stats-row">
                <div class="stat-item total">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Всего транзакций</p>
                </div>
                <div class="stat-item success">
                    <h3><?php echo number_format($stats['success']); ?></h3>
                    <p>Успешных</p>
                </div>
                <div class="stat-item failed">
                    <h3><?php echo number_format($stats['failed']); ?></h3>
                    <p>Неудачных</p>
                </div>
                <div class="stat-item pending">
                    <h3><?php echo number_format($stats['pending']); ?></h3>
                    <p>В обработке</p>
                </div>
            </div>
            
            <!-- Фильтры -->
            <div class="filters-card">
                <h3>Фильтры</h3>
                <form method="GET" class="filters-form">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="date_from">Дата от</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">Дата до</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Статус</label>
                            <select id="status" name="status">
                                <option value="">Все статусы</option>
                                <option value="success" <?php echo ($_GET['status'] ?? '') === 'success' ? 'selected' : ''; ?>>Успешно</option>
                                <option value="failed" <?php echo ($_GET['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Неудачно</option>
                                <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>В обработке</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount_min">Сумма от</label>
                            <input type="number" id="amount_min" name="amount_min" value="<?php echo htmlspecialchars($_GET['amount_min'] ?? ''); ?>" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="amount_max">Сумма до</label>
                            <input type="number" id="amount_max" name="amount_max" value="<?php echo htmlspecialchars($_GET['amount_max'] ?? ''); ?>" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="search">Поиск</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="ID транзакции, описание, номер счета">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn">Применить фильтры</button>
                        <a href="?" class="btn btn-secondary">Сбросить</a>
                        <a href="?export=1<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'export'; }, ARRAY_FILTER_USE_KEY)); ?>" class="btn btn-secondary">Экспорт</a>
                    </div>
                </form>
            </div>
            
            <!-- Таблица логов -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Транзакции (<?php echo number_format($totalCount); ?> записей)</h3>
                    <div>
                        Страница <?php echo $page; ?> из <?php echo $totalPages; ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Дата/Время</th>
                                <th>ID Транзакции</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Описание</th>
                                <th>Номер счета</th>
                                <th>Детали</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                        Нет данных для отображения
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $date = isset($log['transaction_date']) ? new DateTime($log['transaction_date']) : new DateTime();
                                            echo $date->format('d.m.Y H:i:s');
                                            ?>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($log['id'] ?? 'N/A'); ?></code>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format(floatval($log['amount_in'] ?? 0), 0, ',', ' '); ?> ₫</strong>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo floatval($log['amount_in'] ?? 0) > 0 ? 'success' : 'failed'; ?>">
                                                <?php echo floatval($log['amount_in'] ?? 0) > 0 ? 'Успешно' : 'Неудачно'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['transaction_content'] ?? 'N/A'); ?></td>
                                        <td>
                                            <code><?php echo htmlspecialchars($log['account_number'] ?? 'N/A'); ?></code>
                                        </td>
                                        <td>
                                            <button class="btn btn-secondary" onclick="showTransactionDetails('<?php echo htmlspecialchars($log['id'] ?? ''); ?>')">
                                                Подробнее
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Пагинация -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">← Предыдущая</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Следующая →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Модальное окно для деталей транзакции -->
    <div id="transactionModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Детали транзакции</h3>
                <span class="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Детали будут загружены через AJAX -->
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        function showTransactionDetails(transactionId) {
            // Здесь можно добавить AJAX запрос для получения деталей транзакции
            document.getElementById('transactionDetails').innerHTML = '<p>Загрузка деталей транзакции...</p>';
            AdminPanel.openModal({ target: { closest: () => document.getElementById('transactionModal') } });
        }
    </script>
</body>
</html>
