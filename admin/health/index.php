<?php
// Страница проверки здоровья системы
$page_title = 'Здоровье системы - Админка';
$page_header = 'Здоровье системы';
$page_description = 'Проверка всех API endpoints и системных компонентов';

// Список всех API endpoints для проверки
$api_endpoints = [
    'menu' => [
        'url' => '/api/menu.php',
        'method' => 'GET',
        'description' => 'Получение меню',
        'auth_required' => true
    ],
    'events' => [
        'url' => '/api/events.php',
        'method' => 'GET',
        'description' => 'Получение событий',
        'auth_required' => false
    ],
    'events_calendar' => [
        'url' => '/api/events-calendar.php',
        'method' => 'GET',
        'description' => 'Получение календаря событий',
        'auth_required' => false
    ],
    'tables' => [
        'url' => '/api/tables.php',
        'method' => 'GET',
        'description' => 'Получение столов',
        'auth_required' => false
    ],
    'check_phone' => [
        'url' => '/api/check-phone.php',
        'method' => 'POST',
        'description' => 'Проверка телефона',
        'auth_required' => true
    ],
    'orders' => [
        'url' => '/api/orders.php',
        'method' => 'POST',
        'description' => 'Создание заказа',
        'auth_required' => true
    ],
    'image' => [
        'url' => '/api/image.php',
        'method' => 'GET',
        'description' => 'Получение изображений',
        'auth_required' => false
    ],
    'language_change' => [
        'url' => '/api/language/change.php',
        'method' => 'POST',
        'description' => 'Смена языка',
        'auth_required' => false
    ]
];

// Системные компоненты для проверки
$system_components = [
    'mongodb' => [
        'name' => 'MongoDB',
        'description' => 'База данных MongoDB',
        'check_function' => 'checkMongoDB'
    ],
    'page_content' => [
        'name' => 'Page Content Service',
        'description' => 'Сервис контента страниц',
        'check_function' => 'checkPageContent'
    ],
    'translations' => [
        'name' => 'Translation Service',
        'description' => 'Сервис переводов',
        'check_function' => 'checkTranslations'
    ],
    'menu_cache' => [
        'name' => 'Menu Cache',
        'description' => 'Кэш меню',
        'check_function' => 'checkMenuCache'
    ],
    'events_service' => [
        'name' => 'Events Service',
        'description' => 'Сервис событий',
        'check_function' => 'checkEventsService'
    ],
    'image_service' => [
        'name' => 'Image Service',
        'description' => 'Сервис изображений',
        'check_function' => 'checkImageService'
    ],
    'tables_cache' => [
        'name' => 'Tables Cache',
        'description' => 'Кэш столов',
        'check_function' => 'checkTablesCache'
    ],
    'sepay_service' => [
        'name' => 'SePay Service',
        'description' => 'Сервис SePay',
        'check_function' => 'checkSePayService'
    ],
    'telegram_service' => [
        'name' => 'Telegram Service',
        'description' => 'Сервис Telegram',
        'check_function' => 'checkTelegramService'
    ],
    'rate_limiter' => [
        'name' => 'Rate Limiter',
        'description' => 'Ограничитель запросов',
        'check_function' => 'checkRateLimiter'
    ]
];

// Функции проверки системных компонентов
function checkMongoDB() {
    try {
        require_once __DIR__ . '/../../classes/PageContentService.php';
        $service = new PageContentService();
        $content = $service->getPageContent('home');
        return [
            'status' => 'success',
            'message' => 'MongoDB подключение работает',
            'details' => 'Успешно получен контент главной страницы'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка подключения к MongoDB',
            'details' => $e->getMessage()
        ];
    }
}

function checkPageContent() {
    try {
        require_once __DIR__ . '/../../classes/PageContentService.php';
        $service = new PageContentService();
        $content = $service->getPageContent('home');
        return [
            'status' => 'success',
            'message' => 'Page Content Service работает',
            'details' => 'Контент получен успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Page Content Service',
            'details' => $e->getMessage()
        ];
    }
}

function checkTranslations() {
    try {
        require_once __DIR__ . '/../../classes/TranslationService.php';
        $service = new TranslationService();
        $translations = $service->getTranslations('ru');
        return [
            'status' => 'success',
            'message' => 'Translation Service работает',
            'details' => 'Переводы получены успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Translation Service',
            'details' => $e->getMessage()
        ];
    }
}

function checkMenuCache() {
    try {
        require_once __DIR__ . '/../../classes/MenuCache.php';
        $cache = new MenuCache();
        $menu = $cache->getMenu();
        return [
            'status' => 'success',
            'message' => 'Menu Cache работает',
            'details' => 'Меню получено из кэша'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Menu Cache',
            'details' => $e->getMessage()
        ];
    }
}

function checkEventsService() {
    try {
        require_once __DIR__ . '/../../classes/EventsService.php';
        $service = new EventsService();
        $events = $service->getEvents();
        return [
            'status' => 'success',
            'message' => 'Events Service работает',
            'details' => 'События получены успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Events Service',
            'details' => $e->getMessage()
        ];
    }
}

function checkImageService() {
    try {
        require_once __DIR__ . '/../../classes/ImageService.php';
        $service = new ImageService();
        // Проверяем существование класса
        return [
            'status' => 'success',
            'message' => 'Image Service работает',
            'details' => 'Класс ImageService загружен успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Image Service',
            'details' => $e->getMessage()
        ];
    }
}

function checkTablesCache() {
    try {
        require_once __DIR__ . '/../../classes/TablesCache.php';
        $cache = new TablesCache();
        $tables = $cache->getTables();
        return [
            'status' => 'success',
            'message' => 'Tables Cache работает',
            'details' => 'Столы получены из кэша'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Tables Cache',
            'details' => $e->getMessage()
        ];
    }
}

function checkSePayService() {
    try {
        require_once __DIR__ . '/../../classes/SePayApiService.php';
        $service = new SePayApiService();
        return [
            'status' => 'success',
            'message' => 'SePay Service работает',
            'details' => 'Класс SePayApiService загружен успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка SePay Service',
            'details' => $e->getMessage()
        ];
    }
}

function checkTelegramService() {
    try {
        require_once __DIR__ . '/../../classes/TelegramService.php';
        $service = new TelegramService();
        return [
            'status' => 'success',
            'message' => 'Telegram Service работает',
            'details' => 'Класс TelegramService загружен успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Telegram Service',
            'details' => $e->getMessage()
        ];
    }
}

function checkRateLimiter() {
    try {
        require_once __DIR__ . '/../../classes/RateLimiter.php';
        $limiter = new RateLimiter();
        return [
            'status' => 'success',
            'message' => 'Rate Limiter работает',
            'details' => 'Класс RateLimiter загружен успешно'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Ошибка Rate Limiter',
            'details' => $e->getMessage()
        ];
    }
}

// Проверяем системные компоненты
$system_results = [];
foreach ($system_components as $key => $component) {
    $system_results[$key] = call_user_func($component['check_function']);
}

// Генерируем контент
ob_start();
?>

<div class="health-container">
    <div class="health-section">
        <h2>Системные компоненты</h2>
        <div class="health-grid">
            <?php foreach ($system_components as $key => $component): ?>
                <div class="health-card">
                    <div class="health-header">
                        <h3><?php echo htmlspecialchars($component['name']); ?></h3>
                        <span class="health-status <?php echo $system_results[$key]['status']; ?>">
                            <?php echo $system_results[$key]['status'] === 'success' ? '✅' : '❌'; ?>
                        </span>
                    </div>
                    <p class="health-description"><?php echo htmlspecialchars($component['description']); ?></p>
                    <div class="health-result">
                        <strong><?php echo htmlspecialchars($system_results[$key]['message']); ?></strong>
                        <p><?php echo htmlspecialchars($system_results[$key]['details']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="health-section">
        <h2>API Endpoints</h2>
        <div class="api-endpoints">
            <?php foreach ($api_endpoints as $key => $endpoint): ?>
                <div class="api-endpoint">
                    <div class="endpoint-header">
                        <h3><?php echo htmlspecialchars($endpoint['description']); ?></h3>
                        <span class="endpoint-method <?php echo strtolower($endpoint['method']); ?>">
                            <?php echo htmlspecialchars($endpoint['method']); ?>
                        </span>
                    </div>
                    <div class="endpoint-url"><?php echo htmlspecialchars($endpoint['url']); ?></div>
                    <div class="endpoint-auth">
                        <?php if ($endpoint['auth_required']): ?>
                            <span class="auth-required">🔒 Требует авторизации</span>
                        <?php else: ?>
                            <span class="auth-public">🔓 Публичный</span>
                        <?php endif; ?>
                    </div>
                    <div class="endpoint-test">
                        <button class="test-btn" onclick="testEndpoint('<?php echo $key; ?>', '<?php echo htmlspecialchars($endpoint['url']); ?>', '<?php echo $endpoint['method']; ?>', <?php echo $endpoint['auth_required'] ? 'true' : 'false'; ?>)">
                            Тестировать
                        </button>
                        <div id="result-<?php echo $key; ?>" class="test-result"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="health-section">
        <h2>Общая статистика</h2>
        <div class="health-stats">
            <?php
            $total_components = count($system_components);
            $successful_components = count(array_filter($system_results, function($result) {
                return $result['status'] === 'success';
            }));
            $success_rate = $total_components > 0 ? round(($successful_components / $total_components) * 100, 1) : 0;
            ?>
            <div class="stat-card">
                <h3>Компоненты системы</h3>
                <div class="stat-value"><?php echo $successful_components; ?> / <?php echo $total_components; ?></div>
                <div class="stat-percentage"><?php echo $success_rate; ?>% работают</div>
            </div>
            
            <div class="stat-card">
                <h3>API Endpoints</h3>
                <div class="stat-value"><?php echo count($api_endpoints); ?></div>
                <div class="stat-description">доступно для тестирования</div>
            </div>
        </div>
    </div>
</div>

<script>
// Функция для тестирования API endpoints
function testEndpoint(key, url, method, authRequired) {
    const resultDiv = document.getElementById('result-' + key);
    resultDiv.innerHTML = '<div class="loading">Тестирование...</div>';
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (authRequired) {
        options.headers['X-API-Token'] = '<?php echo $_ENV['API_AUTH_TOKEN'] ?? 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6'; ?>';
    }
    
    if (method === 'POST') {
        options.body = JSON.stringify({ test: true });
    }
    
    fetch(url, options)
        .then(response => {
            const statusClass = response.ok ? 'success' : 'error';
            const statusIcon = response.ok ? '✅' : '❌';
            const statusText = response.ok ? 'Успешно' : 'Ошибка';
            
            return response.text().then(text => {
                resultDiv.innerHTML = `
                    <div class="test-result-content ${statusClass}">
                        <div class="result-header">
                            <span class="result-icon">${statusIcon}</span>
                            <span class="result-status">${statusText}</span>
                            <span class="result-code">HTTP ${response.status}</span>
                        </div>
                        <div class="result-details">
                            <strong>Ответ:</strong>
                            <pre>${text.substring(0, 500)}${text.length > 500 ? '...' : ''}</pre>
                        </div>
                    </div>
                `;
            });
        })
        .catch(error => {
            resultDiv.innerHTML = `
                <div class="test-result-content error">
                    <div class="result-header">
                        <span class="result-icon">❌</span>
                        <span class="result-status">Ошибка</span>
                    </div>
                    <div class="result-details">
                        <strong>Ошибка:</strong>
                        <pre>${error.message}</pre>
                    </div>
                </div>
            `;
        });
}

// Автоматическое тестирование при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('Страница здоровья системы загружена');
});
</script>

<style>
.health-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.health-section {
    margin-bottom: 40px;
}

.health-section h2 {
    color: #333;
    border-bottom: 2px solid #007cba;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.health-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.health-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.health-header h3 {
    margin: 0;
    color: #333;
}

.health-status {
    font-size: 20px;
}

.health-status.success {
    color: #28a745;
}

.health-status.error {
    color: #dc3545;
}

.health-description {
    color: #666;
    margin-bottom: 15px;
}

.health-result {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    border-left: 4px solid #007cba;
}

.health-result strong {
    color: #333;
}

.api-endpoints {
    display: grid;
    gap: 20px;
}

.api-endpoint {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.endpoint-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.endpoint-header h3 {
    margin: 0;
    color: #333;
}

.endpoint-method {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.endpoint-method.get {
    background: #28a745;
    color: white;
}

.endpoint-method.post {
    background: #007cba;
    color: white;
}

.endpoint-url {
    font-family: monospace;
    background: #f8f9fa;
    padding: 8px;
    border-radius: 4px;
    margin-bottom: 10px;
    color: #333;
}

.endpoint-auth {
    margin-bottom: 15px;
}

.auth-required {
    color: #dc3545;
    font-weight: bold;
}

.auth-public {
    color: #28a745;
    font-weight: bold;
}

.test-btn {
    background: #007cba;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.test-btn:hover {
    background: #005a8b;
}

.test-result {
    margin-top: 15px;
}

.loading {
    color: #007cba;
    font-style: italic;
}

.test-result-content {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.test-result-content.success {
    border-left: 4px solid #28a745;
}

.test-result-content.error {
    border-left: 4px solid #dc3545;
}

.result-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.result-icon {
    font-size: 18px;
}

.result-status {
    font-weight: bold;
}

.result-code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    font-family: monospace;
}

.result-details pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
    margin: 0;
}

.health-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #007cba;
    margin-bottom: 5px;
}

.stat-percentage {
    color: #28a745;
    font-weight: bold;
}

.stat-description {
    color: #666;
    font-size: 14px;
}
</style>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>
