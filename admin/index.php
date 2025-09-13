<?php
session_start();

// Проверка авторизации
require_once __DIR__ . '/includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/MenuCache.php';

try {
    $menuCache = new MenuCache();
    $menuData = $menuCache->getMenu();
    $categoriesCount = count($menuData['categories'] ?? []);
    $productsCount = count($menuData['products'] ?? []);
} catch (Exception $e) {
    $categoriesCount = 0;
    $productsCount = 0;
}

// Статистика для дашборда
$stats = [
    'categories' => $categoriesCount,
    'products' => $productsCount,
    'last_update' => $menuData['updated_at'] ?? null,
    'admin_user' => $_SESSION['admin_username'] ?? 'Unknown'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - North Republic</title>
    <link rel="stylesheet" href="assets/css/admin.css">
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
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['categories']; ?></h3>
                        <p>Категорий меню</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['products']; ?></h3>
                        <p>Блюд в меню</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-content">
                        <h3><?php echo $stats['last_update'] ? date('H:i', $stats['last_update']->toDateTime()->getTimestamp()) : 'N/A'; ?></h3>
                        <p>Последнее обновление</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-content">
                        <h3>1</h3>
                        <p>Активных админов</p>
                    </div>
                </div>
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
                    
                    
                    <a href="/admin/logs/" class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>Логи админов</h3>
                        <p>Просмотр логов административных действий и активности</p>
                    </a>
                    
                    <a href="/admin/images/" class="action-card">
                        <div class="action-icon">🖼️</div>
                        <h3>Управление изображениями</h3>
                        <p>Загрузка, редактирование и управление изображениями сайта</p>
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
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
