<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $settingsCollection = $db->admin_settings;
        
        $settings = [
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'default_language' => $_POST['default_language'] ?? 'ru',
            'session_timeout' => intval($_POST['session_timeout'] ?? 6),
            'max_upload_size' => intval($_POST['max_upload_size'] ?? 10),
            'webp_quality' => intval($_POST['webp_quality'] ?? 85),
            'enable_logging' => isset($_POST['enable_logging']),
            'log_retention_days' => intval($_POST['log_retention_days'] ?? 30),
            'backup_enabled' => isset($_POST['backup_enabled']),
            'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
            'telegram_bot_token' => trim($_POST['telegram_bot_token'] ?? ''),
            'telegram_webhook_url' => trim($_POST['telegram_webhook_url'] ?? ''),
            'sepay_api_token' => trim($_POST['sepay_api_token'] ?? ''),
            'sepay_webhook_url' => trim($_POST['sepay_webhook_url'] ?? ''),
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_by' => $_SESSION['admin_username'] ?? 'unknown'
        ];
        
        // Обновляем или создаем настройки
        $result = $settingsCollection->replaceOne(
            ['_id' => 'main_settings'],
            $settings,
            ['upsert' => true]
        );
        
        // Логируем изменение настроек
        logAdminAction('update_settings', 'Обновлены настройки системы', [
            'settings_updated' => array_keys($settings)
        ]);
        
        $success = 'Настройки успешно сохранены!';
        
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении настроек: ' . $e->getMessage();
    }
}

// Получаем текущие настройки
try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $settingsCollection = $db->admin_settings;
    
    $currentSettings = $settingsCollection->findOne(['_id' => 'main_settings']);
    
    if (!$currentSettings) {
        // Создаем настройки по умолчанию
        $defaultSettings = [
            '_id' => 'main_settings',
            'site_name' => 'North Republic',
            'site_description' => 'Ресторан в Нячанге',
            'default_language' => 'ru',
            'session_timeout' => 6,
            'max_upload_size' => 10,
            'webp_quality' => 85,
            'enable_logging' => true,
            'log_retention_days' => 30,
            'backup_enabled' => false,
            'backup_frequency' => 'daily',
            'telegram_bot_token' => '',
            'telegram_webhook_url' => '',
            'sepay_api_token' => '',
            'sepay_webhook_url' => '',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'created_by' => 'system'
        ];
        
        $settingsCollection->insertOne($defaultSettings);
        $currentSettings = $defaultSettings;
    }
    
} catch (Exception $e) {
    $error = "Ошибка подключения к базе данных: " . $e->getMessage();
    $currentSettings = [];
}

// Логируем просмотр настроек
logAdminAction('view_settings', 'Просмотр настроек системы');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .settings-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .settings-header {
            background: #667eea;
            color: white;
            padding: 1.5rem;
        }
        
        .settings-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .settings-content {
            padding: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .settings-actions {
            background: #f8f9fa;
            padding: 1.5rem;
            border-top: 1px solid #e1e5e9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .danger-zone {
            border: 2px solid #e74c3c;
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
            background: #fdf2f2;
        }
        
        .danger-zone h3 {
            color: #e74c3c;
            margin-top: 0;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .system-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .system-info h4 {
            margin: 0 0 1rem 0;
            color: #1976d2;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
        }
        
        .info-value {
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Настройки системы</h1>
                <p>Конфигурация админ-панели и интеграций</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="settings-container">
                <!-- Информация о системе -->
                <div class="system-info">
                    <h4>Информация о системе</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PHP версия:</span>
                            <span class="info-value"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">MongoDB:</span>
                            <span class="info-value"><?php echo class_exists('MongoDB\Client') ? 'Подключен' : 'Не подключен'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">GD Extension:</span>
                            <span class="info-value"><?php echo extension_loaded('gd') ? 'Доступен' : 'Не доступен'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">WebP поддержка:</span>
                            <span class="info-value"><?php echo function_exists('imagewebp') ? 'Доступна' : 'Не доступна'; ?></span>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <!-- Основные настройки -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h3>Основные настройки</h3>
                        </div>
                        <div class="settings-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="site_name">Название сайта</label>
                                    <input type="text" id="site_name" name="site_name" 
                                           value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? ''); ?>" required>
                                    <small>Отображается в заголовках и мета-тегах</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_description">Описание сайта</label>
                                    <textarea id="site_description" name="site_description"><?php echo htmlspecialchars($currentSettings['site_description'] ?? ''); ?></textarea>
                                    <small>Краткое описание для поисковых систем</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="default_language">Язык по умолчанию</label>
                                    <select id="default_language" name="default_language">
                                        <option value="ru" <?php echo ($currentSettings['default_language'] ?? '') === 'ru' ? 'selected' : ''; ?>>Русский</option>
                                        <option value="en" <?php echo ($currentSettings['default_language'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                                        <option value="vi" <?php echo ($currentSettings['default_language'] ?? '') === 'vi' ? 'selected' : ''; ?>>Tiếng Việt</option>
                                    </select>
                                    <small>Язык по умолчанию для новых пользователей</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Настройки безопасности -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h3>Безопасность</h3>
                        </div>
                        <div class="settings-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="session_timeout">Таймаут сессии (часы)</label>
                                    <input type="number" id="session_timeout" name="session_timeout" 
                                           value="<?php echo $currentSettings['session_timeout'] ?? 6; ?>" min="1" max="24">
                                    <small>Время неактивности до автоматического выхода</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="enable_logging">Включить логирование</label>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="enable_logging" name="enable_logging" 
                                               <?php echo ($currentSettings['enable_logging'] ?? true) ? 'checked' : ''; ?>>
                                        <label for="enable_logging">Записывать все действия админов</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="log_retention_days">Хранить логи (дни)</label>
                                    <input type="number" id="log_retention_days" name="log_retention_days" 
                                           value="<?php echo $currentSettings['log_retention_days'] ?? 30; ?>" min="1" max="365">
                                    <small>Автоматическое удаление старых логов</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Настройки изображений -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h3>Изображения</h3>
                        </div>
                        <div class="settings-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="max_upload_size">Максимальный размер файла (MB)</label>
                                    <input type="number" id="max_upload_size" name="max_upload_size" 
                                           value="<?php echo $currentSettings['max_upload_size'] ?? 10; ?>" min="1" max="100">
                                    <small>Ограничение на размер загружаемых изображений</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="webp_quality">Качество WebP (1-100)</label>
                                    <input type="number" id="webp_quality" name="webp_quality" 
                                           value="<?php echo $currentSettings['webp_quality'] ?? 85; ?>" min="1" max="100">
                                    <small>Качество сжатия WebP изображений</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Интеграции -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h3>Интеграции</h3>
                        </div>
                        <div class="settings-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="telegram_bot_token">Telegram Bot Token</label>
                                    <input type="text" id="telegram_bot_token" name="telegram_bot_token" 
                                           value="<?php echo htmlspecialchars($currentSettings['telegram_bot_token'] ?? ''); ?>">
                                    <small>Токен для Telegram бота</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="telegram_webhook_url">Telegram Webhook URL</label>
                                    <input type="url" id="telegram_webhook_url" name="telegram_webhook_url" 
                                           value="<?php echo htmlspecialchars($currentSettings['telegram_webhook_url'] ?? ''); ?>">
                                    <small>URL для получения обновлений от Telegram</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sepay_api_token">Sepay API Token</label>
                                    <input type="text" id="sepay_api_token" name="sepay_api_token" 
                                           value="<?php echo htmlspecialchars($currentSettings['sepay_api_token'] ?? ''); ?>">
                                    <small>Токен для API Sepay</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sepay_webhook_url">Sepay Webhook URL</label>
                                    <input type="url" id="sepay_webhook_url" name="sepay_webhook_url" 
                                           value="<?php echo htmlspecialchars($currentSettings['sepay_webhook_url'] ?? ''); ?>">
                                    <small>URL для получения уведомлений о платежах</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Резервное копирование -->
                    <div class="settings-section">
                        <div class="settings-header">
                            <h3>Резервное копирование</h3>
                        </div>
                        <div class="settings-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="backup_enabled">Включить автоматические бэкапы</label>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="backup_enabled" name="backup_enabled" 
                                               <?php echo ($currentSettings['backup_enabled'] ?? false) ? 'checked' : ''; ?>>
                                        <label for="backup_enabled">Создавать резервные копии</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="backup_frequency">Частота бэкапов</label>
                                    <select id="backup_frequency" name="backup_frequency">
                                        <option value="daily" <?php echo ($currentSettings['backup_frequency'] ?? '') === 'daily' ? 'selected' : ''; ?>>Ежедневно</option>
                                        <option value="weekly" <?php echo ($currentSettings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Еженедельно</option>
                                        <option value="monthly" <?php echo ($currentSettings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Ежемесячно</option>
                                    </select>
                                    <small>Как часто создавать резервные копии</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="settings-actions">
                        <a href="../index.php" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn">Сохранить настройки</button>
                    </div>
                </form>
                
                <!-- Опасная зона -->
                <div class="danger-zone">
                    <h3>⚠️ Опасная зона</h3>
                    <p>Эти действия нельзя отменить. Будьте осторожны!</p>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="reset-settings.php" class="btn-danger" 
                           onclick="return confirm('Сбросить все настройки к значениям по умолчанию?')">
                            Сбросить настройки
                        </a>
                        <a href="clear-logs.php" class="btn-danger" 
                           onclick="return confirm('Удалить все логи? Это действие нельзя отменить!')">
                            Очистить логи
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
