<?php
session_start();

// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

// Загружаем MongoDB класс
if (class_exists('MongoDB\Client')) {
    // MongoDB уже доступен
} else {
    error_log('MongoDB\Client class not found');
}

require_once __DIR__ . '/../includes/auth-check.php';

// Настройки страницы для layout
$page_title = 'Статистика меню - Админка';
$page_header = '📊 Статистика обновлений меню';
$page_description = 'Мониторинг обновлений кэша меню и производительности';

// Инициализируем сервис настроек
require_once __DIR__ . '/../../classes/SettingsService.php';
$settingsService = new SettingsService();

// Получаем статистику обновлений
$stats = $settingsService->getUpdateStats();

// Обработка принудительного обновления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_update'])) {
    try {
        // Запускаем обновление
        $cacheUrl = 'http://localhost:3002/api/cache/update-menu';
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 30,
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $result = @file_get_contents($cacheUrl, false, $context);
        
        if ($result !== false) {
            $settingsService->setLastMenuUpdateTime();
            $success = "Меню успешно обновлено!";
        } else {
            $error = "Ошибка при обновлении меню";
        }
        
        // Обновляем статистику
        $stats = $settingsService->getUpdateStats();
        
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}


// Генерируем контент
ob_start();
?>
    <style>
        .stats-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1.5rem;
            border-left: 4px solid #007cba;
        }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #007cba;
            margin: 0;
        }
        
        .stat-detail {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a87;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="stats-container">
            <h2>🔄 Статистика обновлений</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Последнее обновление</h3>
                    <p class="stat-value">
                        <?php echo $stats['last_update_formatted'] ?? 'Никогда'; ?>
                    </p>
                    <?php if (isset($stats['time_since_update_formatted'])): ?>
                        <p class="stat-detail">
                            <?php echo $stats['time_since_update_formatted']; ?> назад
                        </p>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <h3>Последняя проверка</h3>
                    <p class="stat-value">
                        <?php echo isset($stats['last_check_time']) ? date('d.m.Y H:i', $stats['last_check_time']) : 'Никогда'; ?>
                    </p>
                    <?php if (isset($stats['time_since_check_formatted'])): ?>
                        <p class="stat-detail">
                            <?php echo $stats['time_since_check_formatted']; ?> назад
                        </p>
                    <?php endif; ?>
                </div>

                <div class="stat-card" style="grid-column: 1 / -1; width: 100%;">
                    <h3>Доступные столы</h3>
                    <div style="text-align: left; margin-top: 0.5rem;">
                        <?php
                        // Получаем список столов из MongoDB
                        $tables = [];
                        try {
                            require_once __DIR__ . '/../../classes/SettingsService.php';
                            $settingsService = new SettingsService();
                            
                            // Подключаемся к MongoDB для получения столов
                            $client = new MongoDB\Client($_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017');
                            $db = $client->selectDatabase($_ENV['MONGODB_DB_NAME'] ?? 'northrepublic');
                            $menuCollection = $db->selectCollection('menu');
                            
                            $tablesDoc = $menuCollection->findOne(['_id' => 'current_tables']);
                            if ($tablesDoc && isset($tablesDoc['tables'])) {
                                $tables = $tablesDoc['tables'];
                            }
                        } catch (Exception $e) {
                            error_log("Ошибка получения столов: " . $e->getMessage());
                        }
                        
                        if (!empty($tables)): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                <?php foreach ($tables as $table): ?>
                                    <div style="background: #e3f2fd; padding: 0.5rem 0.75rem; border-radius: 4px; font-size: 0.9rem; border: 1px solid #bbdefb;">
                                        <strong><?php echo htmlspecialchars($table['name'] ?? 'Стол ' . ($table['poster_table_id'] ?? 'N/A')); ?></strong>
                                        <small style="color: #666; margin-left: 0.5rem;">ID: <?php echo htmlspecialchars($table['poster_table_id'] ?? 'N/A'); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="status-badge status-warning">Столы не загружены</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <form method="POST" style="display: inline-block;">
                    <button type="submit" name="force_update" class="btn btn-primary">
                        🔄 Принудительно обновить меню
                    </button>
                </form>
                
                <a href="/admin/database/" class="btn btn-secondary" style="margin-left: 1rem;">
                    📊 Просмотр данных MongoDB
                </a>
            </div>
        </div>

        <div class="stats-container">
            <h2>ℹ️ Информация о системе</h2>
            <p><strong>Интервал проверки:</strong> каждые 5 минут</p>
            <p><strong>Интервал обновления:</strong> каждый час</p>
            <p><strong>Часовой пояс:</strong> Asia/Ho_Chi_Minh (UTC+7)</p>
            <p><strong>Источник данных:</strong> Poster API через Node.js backend</p>
    </div>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>