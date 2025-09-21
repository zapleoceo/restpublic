<?php
// Главная страница админки
$page_title = 'Панель управления - Админка';
$page_header = 'Панель управления';
$page_description = 'Добро пожаловать в панель управления North Republic';

// Статистика для дашборда
$stats = [
    'admin_user' => $_SESSION['admin_username'] ?? 'Unknown'
];

// Генерируем контент
ob_start();
?>

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
        
        <a href="/admin/health/" class="action-card">
            <div class="action-icon">🏥</div>
            <h3>Здоровье системы</h3>
            <p>Проверка всех API endpoints и системных компонентов</p>
        </a>
        
        <a href="/admin/database/" class="action-card">
            <div class="action-icon">🗄️</div>
            <h3>База данных</h3>
            <p>Управление базой данных MongoDB</p>
        </a>
        
        <a href="/admin/events/" class="action-card">
            <div class="action-icon">📅</div>
            <h3>События</h3>
            <p>Управление событиями и календарем</p>
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

<!-- Системная информация -->
<div class="system-info">
    <h2>Системная информация</h2>
    <div class="info-grid">
        <div class="info-card">
            <h3>Версия PHP</h3>
            <p><?php echo PHP_VERSION; ?></p>
        </div>
        <div class="info-card">
            <h3>Сервер</h3>
            <p><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно'; ?></p>
        </div>
        <div class="info-card">
            <h3>Время сервера</h3>
            <p><?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        <div class="info-card">
            <h3>Пользователь</h3>
            <p><?php echo htmlspecialchars($stats['admin_user']); ?></p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/includes/layout.php';
?>
