<?php
session_start();
require_once '../includes/auth-check.php';

$error = '';
$success = '';

// Файл для логов
$logsFile = __DIR__ . '/../../data/admin_logs.json';

// Загружаем логи из файла
$logs = [];
if (file_exists($logsFile)) {
    $logs = json_decode(file_get_contents($logsFile), true) ?: [];
}

// Сортируем логи по дате (новые сначала)
usort($logs, function($a, $b) {
    return strtotime($b['timestamp'] ?? '') - strtotime($a['timestamp'] ?? '');
});

// Фильтрация
$filterAction = $_GET['action'] ?? '';
$filterUser = $_GET['user'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Применяем фильтры
$filteredLogs = $logs;
if ($filterAction) {
    $filteredLogs = array_filter($filteredLogs, function($log) use ($filterAction) {
        return strpos($log['action'] ?? '', $filterAction) !== false;
    });
}
if ($filterUser) {
    $filteredLogs = array_filter($filteredLogs, function($log) use ($filterUser) {
        return strpos($log['username'] ?? '', $filterUser) !== false;
    });
}

// Пагинация
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalLogs / $limit);
$paginatedLogs = array_slice($filteredLogs, $offset, $limit);

// Обработка просмотра детальной информации о логе
$viewLogIndex = $_GET['view'] ?? '';
$viewLog = null;

if ($viewLogIndex !== '') {
    $logIndex = intval($viewLogIndex);
    if ($logIndex >= 0 && $logIndex < count($filteredLogs)) {
        $viewLog = $filteredLogs[$logIndex];
    }
}

// Получаем уникальные действия и пользователей для фильтров
$actions = array_unique(array_column($logs, 'action'));
$users = array_unique(array_column($logs, 'username'));

// Функция для форматирования уровня логирования
function getLogLevelClass($level) {
    switch (strtolower($level)) {
        case 'error':
            return 'log-error';
        case 'warning':
            return 'log-warning';
        case 'info':
            return 'log-info';
        default:
            return 'log-default';
    }
}

// Функция для форматирования действия
function formatAction($action) {
    $icons = [
        'login' => '🔐',
        'logout' => '🚪',
        'login_failed' => '❌',
        'save' => '💾',
        'delete' => '🗑️',
        'create' => '➕',
        'update' => '✏️',
        'upload' => '📤',
        'download' => '📥'
    ];
    
    $icon = $icons[$action] ?? '📝';
    return $icon . ' ' . ucfirst($action);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи админов - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .logs-container {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .logs-table th,
        .logs-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .logs-table th {
            background: #f8f9fa;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .log-level {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .log-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .log-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .log-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .log-default {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .log-action {
            font-weight: 500;
        }
        
        .log-user {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }
        
        .log-timestamp {
            color: #666;
            font-size: 0.9rem;
        }
        
        .log-description {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 3px;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .pagination .current {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007cba;
        }
        
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        
        .no-logs {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .log-detail {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        .log-detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .log-detail-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .log-detail-item {
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .log-detail-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .log-detail-value {
            color: #333;
            word-break: break-word;
        }
        
        .log-detail-json {
            grid-column: 1 / -1;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
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
                <p>Просмотр действий администраторов системы</p>
            </div>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($logs); ?></div>
                    <div class="stat-label">Всего записей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($filteredLogs); ?></div>
                    <div class="stat-label">Отфильтровано</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Пользователей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($actions); ?></div>
                    <div class="stat-label">Типов действий</div>
                </div>
            </div>
            
            <!-- Фильтры -->
            <div class="filters">
                <form method="GET" style="display: flex; gap: 1rem; align-items: end;">
                    <div class="filter-group">
                        <label for="action">Действие</label>
                        <select name="action" id="action">
                            <option value="">Все действия</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo htmlspecialchars($action); ?>" 
                                        <?php echo $filterAction === $action ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="user">Пользователь</label>
                        <select name="user" id="user">
                            <option value="">Все пользователи</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user); ?>" 
                                        <?php echo $filterUser === $user ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Фильтровать</button>
                    <a href="?" class="btn btn-secondary">Сбросить</a>
                </form>
            </div>
            
            <!-- Таблица логов -->
            <div class="logs-container">
                <?php if (empty($paginatedLogs)): ?>
                    <div class="no-logs">
                        <h3>📝 Нет логов</h3>
                        <p>Логи действий будут отображаться здесь после выполнения операций в админке.</p>
                    </div>
                <?php else: ?>
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Уровень</th>
                                <th>Действие</th>
                                <th>Пользователь</th>
                                <th>Описание</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paginatedLogs as $log): ?>
                                <tr>
                                    <td class="log-timestamp">
                                        <?php echo date('d.m.Y H:i:s', strtotime($log['timestamp'] ?? '')); ?>
                                    </td>
                                    <td>
                                        <span class="log-level <?php echo getLogLevelClass($log['level'] ?? ''); ?>">
                                            <?php echo strtoupper($log['level'] ?? 'INFO'); ?>
                                        </span>
                                    </td>
                                    <td class="log-action">
                                        <?php echo formatAction($log['action'] ?? ''); ?>
                                    </td>
                                    <td>
                                        <span class="log-user"><?php echo htmlspecialchars($log['username'] ?? 'unknown'); ?></span>
                                    </td>
                                    <td class="log-description">
                                        <?php 
                                        $description = $log['message'] ?? $log['description'] ?? '';
                                        if (strlen($description) > 50) {
                                            echo htmlspecialchars(substr($description, 0, 50)) . '...';
                                        } else {
                                            echo htmlspecialchars($description);
                                        }
                                        ?>
                                        <?php if (strlen($description) > 50): ?>
                                            <a href="?view=<?php echo $offset + $key; ?>" class="btn btn-info" style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.8rem;">👁️ Подробнее</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-family: monospace; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($log['ip'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Пагинация -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&action=<?php echo urlencode($filterAction); ?>&user=<?php echo urlencode($filterUser); ?>">← Предыдущая</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&action=<?php echo urlencode($filterAction); ?>&user=<?php echo urlencode($filterUser); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&action=<?php echo urlencode($filterAction); ?>&user=<?php echo urlencode($filterUser); ?>">Следующая →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Детальный просмотр лога -->
            <?php if ($viewLog): ?>
                <div class="log-detail">
                    <div class="log-detail-header">
                        <h3>📋 Детальная информация о логе</h3>
                        <a href="?" class="btn btn-secondary">← Назад к списку</a>
                    </div>
                    
                    <div class="log-detail-content">
                        <div class="log-detail-item">
                            <div class="log-detail-label">Время</div>
                            <div class="log-detail-value"><?php echo date('d.m.Y H:i:s', strtotime($viewLog['timestamp'] ?? '')); ?></div>
                        </div>
                        
                        <div class="log-detail-item">
                            <div class="log-detail-label">Уровень</div>
                            <div class="log-detail-value">
                                <span class="log-level <?php echo getLogLevelClass($viewLog['level'] ?? ''); ?>">
                                    <?php echo strtoupper($viewLog['level'] ?? 'INFO'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="log-detail-item">
                            <div class="log-detail-label">Действие</div>
                            <div class="log-detail-value"><?php echo formatAction($viewLog['action'] ?? ''); ?></div>
                        </div>
                        
                        <div class="log-detail-item">
                            <div class="log-detail-label">Пользователь</div>
                            <div class="log-detail-value">
                                <span class="log-user"><?php echo htmlspecialchars($viewLog['username'] ?? 'unknown'); ?></span>
                            </div>
                        </div>
                        
                        <div class="log-detail-item">
                            <div class="log-detail-label">IP адрес</div>
                            <div class="log-detail-value" style="font-family: monospace;"><?php echo htmlspecialchars($viewLog['ip'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="log-detail-item">
                            <div class="log-detail-label">User Agent</div>
                            <div class="log-detail-value"><?php echo htmlspecialchars($viewLog['user_agent'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="log-detail-item">
                            <div class="log-detail-label">Описание</div>
                            <div class="log-detail-value"><?php echo htmlspecialchars($viewLog['message'] ?? $viewLog['description'] ?? ''); ?></div>
                        </div>
                        
                        <?php if (isset($viewLog['data']) && !empty($viewLog['data'])): ?>
                            <div class="log-detail-json">
                                <strong>Дополнительные данные:</strong><br>
                                <?php echo htmlspecialchars(json_encode($viewLog['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
