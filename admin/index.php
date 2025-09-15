<?php
session_start();

// Проверка авторизации
require_once __DIR__ . '/includes/auth-check.php';

// Статистика для дашборда
$stats = [
    'admin_user' => $_SESSION['admin_username'] ?? 'Unknown'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - North Republic</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="icon" type="image/png" href="../template/favicon-32x32.png">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Панель управления</h1>
                <p>Добро пожаловать, <?php echo htmlspecialchars($stats['admin_user']); ?>!</p>
            </div>
            
            
            <!-- Компоненты -->
            <div class="quick-actions">
                <h2>Компоненты системы</h2>
                <div class="actions-grid">
                    <a href="/admin/settings/menu-stats.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>Статистика меню</h3>
                        <p>Просмотр статистики обновлений меню и принудительное обновление кэша</p>
                    </a>
                    
                    
                    <a href="/admin/sepay/" class="action-card">
                        <div class="action-icon">💳</div>
                        <h3>SePay Transactions</h3>
                        <p>Просмотр транзакций полученных через webhook</p>
                    </a>
                    
                    <a href="/admin/logs/" class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>Логи админов</h3>
                        <p>Просмотр логов административных действий и активности</p>
                    </a>
                    
                </div>
            </div>
            
            <!-- Последние действия -->
            <div class="recent-actions">
                <h2>Последние действия</h2>
                <div class="actions-list">
                    <div class="action-item">
                        <div class="action-time"><?php echo date('H:i'); ?></div>
                        <div class="action-text">Вход в админку</div>
                        <div class="action-user"><?php echo htmlspecialchars($stats['admin_user']); ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
