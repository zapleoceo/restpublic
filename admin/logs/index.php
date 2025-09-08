<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $logsCollection = $db->admin_logs;
    
    // Параметры фильтрации
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $skip = ($page - 1) * $limit;
    
    $filter = [];
    $sort = ['timestamp' => -1];
    
    // Фильтр по типу действия
    if (!empty($_GET['action_type'])) {
        $filter['action_type'] = $_GET['action_type'];
    }
    
    // Фильтр по пользователю
    if (!empty($_GET['username'])) {
        $filter['username'] = new MongoDB\BSON\Regex($_GET['username'], 'i');
    }
    
    // Фильтр по дате
    if (!empty($_GET['date_from'])) {
        $filter['timestamp']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['date_from'] . ' 00:00:00') * 1000);
    }
    
    if (!empty($_GET['date_to'])) {
        $filter['timestamp']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($_GET['date_to'] . ' 23:59:59') * 1000);
    }
    
    // Поиск по описанию
    if (!empty($_GET['search'])) {
        $filter['description'] = new MongoDB\BSON\Regex($_GET['search'], 'i');
    }
    
    // Получаем логи
    $logs = $logsCollection->find($filter, [
        'sort' => $sort,
        'skip' => $skip,
        'limit' => $limit
    ])->toArray();
    
    // Подсчитываем общее количество
    $totalCount = $logsCollection->countDocuments($filter);
    $totalPages = ceil($totalCount / $limit);
    
    // Получаем уникальные типы действий
    $actionTypes = $logsCollection->distinct('action_type');
    
    // Получаем уникальных пользователей
    $usernames = $logsCollection->distinct('username');
    
    // Статистика
    $stats = [
        'total' => $totalCount,
        'today' => 0,
        'this_week' => 0,
        'this_month' => 0
    ];
    
    // Подсчитываем статистику по периодам
    $today = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
    $weekAgo = new MongoDB\BSON\UTCDateTime(strtotime('-1 week') * 1000);
    $monthAgo = new MongoDB\BSON\UTCDateTime(strtotime('-1 month') * 1000);
    
    $stats['today'] = $logsCollection->countDocuments(['timestamp' => ['$gte' => $today]]);
    $stats['this_week'] = $logsCollection->countDocuments(['timestamp' => ['$gte' => $weekAgo]]);
    $stats['this_month'] = $logsCollection->countDocuments(['timestamp' => ['$gte' => $monthAgo]]);
    
    // Конвертируем BSON объекты для отображения
    $logs = array_map(function($log) {
        $log['_id'] = (string)$log['_id'];
        $log['timestamp'] = $log['timestamp']->toDateTime()->format('Y-m-d H:i:s');
        return $log;
    }, $logs);
    
} catch (Exception $e) {
    $logs = [];
    $totalCount = 0;
    $totalPages = 0;
    $actionTypes = [];
    $usernames = [];
    $stats = ['total' => 0, 'today' => 0, 'this_week' => 0, 'this_month' => 0];
    $error = "Ошибка подключения к базе данных: " . $e->getMessage();
}

// Логируем просмотр логов
logAdminAction('view_logs', 'Просмотр логов админов', [
    'filters' => $_GET,
    'page' => $page
]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи админов - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.total h3 { color: #3498db; }
        .stat-card.today h3 { color: #27ae60; }
        .stat-card.week h3 { color: #f39c12; }
        .stat-card.month h3 { color: #9b59b6; }
        
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
        
        .log-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .log-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e1e5e9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-action {
            font-weight: 600;
            color: #333;
        }
        
        .log-timestamp {
            color: #666;
            font-size: 0.9rem;
        }
        
        .log-content {
            padding: 1.5rem;
        }
        
        .log-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .log-description {
            color: #333;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .log-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            color: #666;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .action-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .action-login { background: #d4edda; color: #155724; }
        .action-logout { background: #f8d7da; color: #721c24; }
        .action-create { background: #d1ecf1; color: #0c5460; }
        .action-update { background: #fff3cd; color: #856404; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-view { background: #e2e3e5; color: #383d41; }
        .action-upload { background: #cce5ff; color: #004085; }
        .action-publish { background: #d4edda; color: #155724; }
        
        .bulk-actions {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
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
                <h1>Логи админов</h1>
                <p>Просмотр всех действий администраторов</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Всего записей</p>
                </div>
                <div class="stat-card today">
                    <h3><?php echo number_format($stats['today']); ?></h3>
                    <p>Сегодня</p>
                </div>
                <div class="stat-card week">
                    <h3><?php echo number_format($stats['this_week']); ?></h3>
                    <p>За неделю</p>
                </div>
                <div class="stat-card month">
                    <h3><?php echo number_format($stats['this_month']); ?></h3>
                    <p>За месяц</p>
                </div>
            </div>
            
            <!-- Массовые действия -->
            <div class="bulk-actions">
                <a href="export.php" class="btn btn-secondary">Экспорт логов</a>
                <a href="cleanup.php" class="btn btn-secondary">Очистить старые</a>
                <a href="stats.php" class="btn btn-secondary">Статистика</a>
            </div>
            
            <!-- Фильтры -->
            <div class="filters-card">
                <h3>Фильтры</h3>
                <form method="GET" class="filters-form">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="action_type">Тип действия</label>
                            <select id="action_type" name="action_type">
                                <option value="">Все действия</option>
                                <?php foreach ($actionTypes as $actionType): ?>
                                    <option value="<?php echo htmlspecialchars($actionType); ?>" 
                                            <?php echo ($_GET['action_type'] ?? '') === $actionType ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($actionType); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Пользователь</label>
                            <select id="username" name="username">
                                <option value="">Все пользователи</option>
                                <?php foreach ($usernames as $username): ?>
                                    <option value="<?php echo htmlspecialchars($username); ?>" 
                                            <?php echo ($_GET['username'] ?? '') === $username ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($username); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_from">От</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to">До</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="search">Поиск</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                   placeholder="Описание действия">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn">Применить фильтры</button>
                        <a href="?" class="btn btn-secondary">Сбросить</a>
                    </div>
                </form>
            </div>
            
            <!-- Список логов -->
            <?php if (empty($logs)): ?>
                <div class="card">
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <h3>Нет логов для отображения</h3>
                        <p>Измените фильтры или подождите новых действий</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <div class="log-item">
                        <div class="log-header">
                            <div>
                                <div class="log-action">
                                    <span class="action-badge action-<?php echo strtolower($log['action_type']); ?>">
                                        <?php echo htmlspecialchars($log['action_type']); ?>
                                    </span>
                                </div>
                                <div class="log-timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></div>
                            </div>
                        </div>
                        
                        <div class="log-content">
                            <div class="log-user">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                </div>
                                <strong><?php echo htmlspecialchars($log['username']); ?></strong>
                            </div>
                            
                            <div class="log-description">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </div>
                            
                            <?php if (!empty($log['details'])): ?>
                                <div class="log-details">
                                    <?php echo htmlspecialchars(json_encode($log['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
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
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
