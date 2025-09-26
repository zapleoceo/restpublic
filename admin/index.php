<?php
/**
 * Admin Dashboard - Modern UI
 * Complete refactoring with new structure
 */

// Page configuration
$page_title = 'Панель управления - North Republic';
$page_header = 'Добро пожаловать!';
$page_description = 'Центральная панель управления North Republic с современным интерфейсом';

// Get dashboard statistics
$stats = getDashboardStats();

// Helper function to get dashboard statistics
function getDashboardStats() {
    try {
        require_once __DIR__ . '/classes/Logger.php';
        $logger = new Logger();

        // Get recent logs count (last 24 hours)
        $recentLogs = $logger->getLogsCount(['hours' => 24]);

        // Get system info
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_time' => date('Y-m-d H:i:s'),
            'memory_usage' => formatBytes(memory_get_peak_usage(true)),
            'uptime' => formatUptime(),
            'admin_user' => $_SESSION['admin_username'] ?? 'TestUser'
        ];

        return [
            'recent_logs' => $recentLogs,
            'system_info' => $systemInfo
        ];
    } catch (Exception $e) {
        return [
            'recent_logs' => 0,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'server_time' => date('Y-m-d H:i:s'),
                'memory_usage' => formatBytes(memory_get_peak_usage(true)),
                'uptime' => formatUptime(),
                'admin_user' => $_SESSION['admin_username'] ?? 'TestUser'
            ]
        ];
    }
}

// Helper function to format bytes
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 3) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Helper function to format uptime
function formatUptime() {
    if (file_exists('/proc/uptime')) {
        $uptime = file_get_contents('/proc/uptime');
        $uptime = explode(' ', $uptime)[0];
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        return "{$days}d {$hours}h";
    }
    return 'Unknown';
}

// Generate page content
ob_start();
?>

<!-- Dashboard Overview -->
<div class="dashboard-overview">
    <div class="stats-grid">
        <!-- Active Users Stat -->
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo htmlspecialchars($stats['system_info']['admin_user']); ?></h3>
                <p class="stat-label">Активный пользователь</p>
                <span class="stat-description">Текущая сессия</span>
            </div>
        </div>

        <!-- System Logs Stat -->
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14,2 14,8 20,8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10,9 9,9 8,9"></polyline>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo number_format($stats['recent_logs']); ?></h3>
                <p class="stat-label">Логов за 24ч</p>
                <span class="stat-description">Системная активность</span>
            </div>
        </div>

        <!-- PHP Version Stat -->
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo htmlspecialchars($stats['system_info']['php_version']); ?></h3>
                <p class="stat-label">PHP Версия</p>
                <span class="stat-description">Серверное окружение</span>
            </div>
        </div>

        <!-- Memory Usage Stat -->
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                    <circle cx="7" cy="12" r="2"></circle>
                    <path d="M15 12h6"></path>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-value"><?php echo htmlspecialchars($stats['system_info']['memory_usage']); ?></h3>
                <p class="stat-label">Память</p>
                <span class="stat-description">Пиковое использование</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <div class="section-header">
        <h2 class="section-title">Быстрые действия</h2>
        <p class="section-description">Основные функции системы в одном месте</p>
    </div>

    <div class="actions-grid">
        <a href="/admin/settings/menu-stats.php" class="action-card">
            <div class="action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"></polyline>
                </svg>
            </div>
            <div class="action-content">
                <h3 class="action-title">Статистика меню</h3>
                <p class="action-description">Просмотр статистики обновлений меню и принудительное обновление кэша</p>
            </div>
            <div class="action-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </div>
        </a>

        <a href="/admin/sepay/" class="action-card">
            <div class="action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
            </div>
            <div class="action-content">
                <h3 class="action-title">SePay транзакции</h3>
                <p class="action-description">Просмотр платежей и транзакций полученных через webhook</p>
            </div>
            <div class="action-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </div>
        </a>

        <a href="/admin/logs/" class="action-card">
            <div class="action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14,2 14,8 20,8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10,9 9,9 8,9"></polyline>
                </svg>
            </div>
            <div class="action-content">
                <h3 class="action-title">Логи системы</h3>
                <p class="action-description">Мониторинг административных действий и системной активности</p>
            </div>
            <div class="action-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </div>
        </a>

        <a href="/admin/health/" class="action-card">
            <div class="action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
            </div>
            <div class="action-content">
                <h3 class="action-title">Здоровье системы</h3>
                <p class="action-description">Проверка всех API endpoints и системных компонентов</p>
            </div>
            <div class="action-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </div>
        </a>

        <a href="/admin/database/" class="action-card">
            <div class="action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M21 18c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                </svg>
            </div>
            <div class="action-content">
                <h3 class="action-title">База данных</h3>
                <p class="action-description">Управление MongoDB и просмотр коллекций</p>
            </div>
            <div class="action-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </div>
        </a>

        <a href="/admin/events/" class="action-card">
            <div class="action-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="action-content">
                <h3 class="action-title">События</h3>
                <p class="action-description">Управление событиями и календарём мероприятий</p>
            </div>
            <div class="action-arrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9,18 15,12 9,6"></polyline>
                </svg>
            </div>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="recent-activity">
    <div class="section-header">
        <h2 class="section-title">Недавняя активность</h2>
        <p class="section-description">Последние действия в системе</p>
    </div>

    <div class="activity-list">
        <div class="activity-item">
            <div class="activity-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10,17 15,12 10,7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </div>
            <div class="activity-content">
                <div class="activity-title">Вход в админку</div>
                <div class="activity-meta">
                    <span class="activity-user"><?php echo htmlspecialchars($stats['system_info']['admin_user']); ?></span>
                                    <span class="activity-time"><?php echo htmlspecialchars($stats['system_info']['server_time']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="system-info">
    <div class="section-header">
        <h2 class="section-title">Системная информация</h2>
        <p class="section-description">Технические детали сервера</p>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">PHP Версия</h3>
                <p class="info-value"><?php echo htmlspecialchars($stats['system_info']['php_version']); ?></p>
            </div>
        </div>

        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect>
                    <line x1="7" y1="2" x2="7" y2="22"></line>
                    <line x1="17" y1="2" x2="17" y2="22"></line>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <line x1="2" y1="7" x2="7" y2="7"></line>
                    <line x1="2" y1="17" x2="7" y2="17"></line>
                    <line x1="17" y1="17" x2="22" y2="17"></line>
                    <line x1="17" y1="7" x2="22" y2="7"></line>
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">Сервер</h3>
                <p class="info-value"><?php echo htmlspecialchars($stats['system_info']['server_software']); ?></p>
            </div>
        </div>

        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">Время сервера</h3>
                <p class="info-value"><?php echo htmlspecialchars($stats['system_info']['server_time']); ?></p>
            </div>
        </div>

        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">Память</h3>
                <p class="info-value"><?php echo htmlspecialchars($stats['system_info']['memory_usage']); ?></p>
            </div>
        </div>

        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">Uptime</h3>
                <p class="info-value"><?php echo htmlspecialchars($stats['system_info']['uptime']); ?></p>
            </div>
        </div>

        <div class="info-card">
            <div class="info-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="info-content">
                <h3 class="info-title">Пользователь</h3>
                <p class="info-value"><?php echo htmlspecialchars($stats['system_info']['admin_user']); ?></p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Load layout
require_once __DIR__ . '/includes/layout.php';
?>
