<?php
session_start();

// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

require_once __DIR__ . '/../includes/auth-check.php';


// Генерируем контент
ob_start();
?>
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .settings-card h3 {
            margin-top: 0;
            color: #333;
        }
        
        .settings-card p {
            color: #666;
            margin-bottom: 1.5rem;
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
        
        .settings-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style><div class="admin-container"><main class="admin-main">
            <div class="page-header">
                <h1>⚙️ Настройки системы</h1>
                <nav class="breadcrumb">
                    <a href="/admin/">Главная</a> > Настройки
                </nav>
            </div>

        <div class="settings-grid">
            <div class="settings-card">
                <div class="settings-icon">📊</div>
                <h3>Статистика меню</h3>
                <p>Просмотр статистики обновлений меню, времени последнего обновления и принудительное обновление кэша.</p>
                <a href="/admin/settings/menu-stats.php" class="btn btn-primary">
                    Открыть статистику
                </a>
            </div>

            <div class="settings-card">
                <div class="settings-icon">🗄️</div>
                <h3>База данных</h3>
                <p>Управление MongoDB, просмотр коллекций и данных, мониторинг состояния базы данных.</p>
                <a href="/admin/database/" class="btn btn-primary">
                    Управление БД
                </a>
            </div>

            <div class="settings-card">
                <div class="settings-icon">👥</div>
                <h3>Пользователи</h3>
                <p>Управление административными пользователями, создание и редактирование аккаунтов.</p>
                <a href="/admin/users/" class="btn btn-primary">
                    Управление пользователями
                </a>
            </div>

            <div class="settings-card">
                <div class="settings-icon">📊</div>
                <h3>Логи системы</h3>
                <p>Просмотр логов административных действий, ошибок системы и активности пользователей.</p>
                <a href="/admin/logs/" class="btn btn-primary">
                    Просмотр логов
                </a>
            </div>
        </div>
        </main>
    </div>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>