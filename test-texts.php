<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/TextManager.php';

$textManager = new TextManager();

// Обработка смены языка
if (isset($_GET['lang'])) {
    $textManager->setLanguage($_GET['lang']);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

$currentLang = $textManager->getCurrentLanguage();
$availableLangs = $textManager->getAvailableLanguages();
$stats = $textManager->getTranslationStats();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест загрузки текстов - North Republic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .lang-switcher {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .lang-btn {
            padding: 8px 16px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .lang-btn:hover, .lang-btn.active {
            background: #667eea;
            color: white;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 2rem;
        }
        .stat-card p {
            margin: 0;
            color: #666;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .test-section h3 {
            color: #333;
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .text-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .text-item:last-child {
            border-bottom: none;
        }
        .text-key {
            font-family: monospace;
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .text-value {
            flex-grow: 1;
            margin: 0 20px;
            color: #333;
        }
        .text-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-complete {
            background: #d4edda;
            color: #155724;
        }
        .status-missing {
            background: #f8d7da;
            color: #721c24;
        }
        .status-fallback {
            background: #fff3cd;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Тест загрузки текстов из БД</h1>
            <p>Проверка работы системы мультиязычности</p>
        </div>
        
        <!-- Переключатель языков -->
        <div class="lang-switcher">
            <?php foreach ($availableLangs as $code => $name): ?>
                <a href="?lang=<?php echo $code; ?>" 
                   class="lang-btn <?php echo $currentLang === $code ? 'active' : ''; ?>">
                    <?php echo $name; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Статистика -->
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Всего текстов</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['complete']; ?></h3>
                <p>Полных переводов</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['incomplete']; ?></h3>
                <p>Неполных переводов</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['by_language'][$currentLang]; ?></h3>
                <p>На <?php echo $availableLangs[$currentLang]; ?></p>
            </div>
        </div>
        
        <!-- Тест основных текстов -->
        <div class="test-section">
            <h3>🏠 Главная страница</h3>
            
            <div class="text-item">
                <span class="text-key">intro_welcome_text</span>
                <span class="text-value"><?php echo htmlspecialchars(t('intro_welcome_text')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('intro_welcome_text') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('intro_welcome_text') ? '✓' : '✗'; ?>
                </span>
            </div>
            
            <div class="text-item">
                <span class="text-key">intro_restaurant_name</span>
                <span class="text-value"><?php echo htmlspecialchars(t('intro_restaurant_name')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('intro_restaurant_name') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('intro_restaurant_name') ? '✓' : '✗'; ?>
                </span>
            </div>
            
            <div class="text-item">
                <span class="text-key">intro_description</span>
                <span class="text-value"><?php echo htmlspecialchars(t('intro_description')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('intro_description') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('intro_description') ? '✓' : '✗'; ?>
                </span>
            </div>
        </div>
        
        <!-- Тест навигации -->
        <div class="test-section">
            <h3>🧭 Навигация</h3>
            
            <div class="text-item">
                <span class="text-key">header_home</span>
                <span class="text-value"><?php echo htmlspecialchars(t('header_home')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('header_home') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('header_home') ? '✓' : '✗'; ?>
                </span>
            </div>
            
            <div class="text-item">
                <span class="text-key">header_about</span>
                <span class="text-value"><?php echo htmlspecialchars(t('header_about')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('header_about') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('header_about') ? '✓' : '✗'; ?>
                </span>
            </div>
            
            <div class="text-item">
                <span class="text-key">header_menu</span>
                <span class="text-value"><?php echo htmlspecialchars(t('header_menu')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('header_menu') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('header_menu') ? '✓' : '✗'; ?>
                </span>
            </div>
        </div>
        
        <!-- Тест кнопок -->
        <div class="test-section">
            <h3>🔘 Кнопки</h3>
            
            <div class="text-item">
                <span class="text-key">menu_full_menu_button</span>
                <span class="text-value"><?php echo htmlspecialchars(t('menu_full_menu_button')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('menu_full_menu_button') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('menu_full_menu_button') ? '✓' : '✗'; ?>
                </span>
            </div>
            
            <div class="text-item">
                <span class="text-key">button_back_to_home</span>
                <span class="text-value"><?php echo htmlspecialchars(t('button_back_to_home')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('button_back_to_home') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('button_back_to_home') ? '✓' : '✗'; ?>
                </span>
            </div>
        </div>
        
        <!-- Тест ошибок -->
        <div class="test-section">
            <h3>⚠️ Сообщения об ошибках</h3>
            
            <div class="text-item">
                <span class="text-key">error_menu_not_available</span>
                <span class="text-value"><?php echo htmlspecialchars(t('error_menu_not_available')); ?></span>
                <span class="text-status <?php echo $textManager->hasTranslation('error_menu_not_available') ? 'status-complete' : 'status-missing'; ?>">
                    <?php echo $textManager->hasTranslation('error_menu_not_available') ? '✓' : '✗'; ?>
                </span>
            </div>
        </div>
        
        <!-- Тест несуществующего ключа -->
        <div class="test-section">
            <h3>🔍 Тест fallback</h3>
            
            <div class="text-item">
                <span class="text-key">nonexistent_key</span>
                <span class="text-value"><?php echo htmlspecialchars(t('nonexistent_key', 'Дефолтное значение')); ?></span>
                <span class="text-status status-fallback">Fallback</span>
            </div>
        </div>
        
        <!-- Информация о системе -->
        <div class="test-section">
            <h3>ℹ️ Информация о системе</h3>
            <p><strong>Текущий язык:</strong> <?php echo $availableLangs[$currentLang]; ?> (<?php echo $currentLang; ?>)</p>
            <p><strong>Язык браузера:</strong> <?php echo $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Не определен'; ?></p>
            <p><strong>MongoDB:</strong> <?php echo class_exists('MongoDB\Client') ? '✅ Подключен' : '❌ Не подключен'; ?></p>
            <p><strong>Время загрузки:</strong> <?php echo round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3); ?> сек</p>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><a href="/admin/">🔧 Перейти в админку</a> | <a href="/">🏠 На главную</a></p>
        </div>
    </div>
</body>
</html>
