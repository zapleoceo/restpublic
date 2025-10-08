<?php
// Используем данные из основного файла, если они доступны
if (isset($database) && isset($databaseName)) {
    // Используем уже установленное подключение
    $mongoConnection = true;
    $error = null;
} else {
    // Загружаем переменные окружения
    require_once __DIR__ . '/../../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    }

    // Подключение к MongoDB
    $mongoUri = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $databaseName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
    $mongoConnection = false;
    $error = null;

    try {
        $client = new MongoDB\Client($mongoUri);
        $database = $client->selectDatabase($databaseName);
        $mongoConnection = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

if ($mongoConnection && !$error) {
    try {
    
    // Описания коллекций
    $collectionDescriptions = [
        'admin_users' => 'Пользователи админки - логины, пароли, роли администраторов',
        'admin_logs' => 'Логи действий администраторов - входы, изменения, операции',
        'admin_sessions' => 'Активные сессии администраторов - токены, время входа',
        'page_content' => 'Контент страниц сайта - тексты, мета-данные, переводы',
        'admin_texts' => 'Тексты интерфейса - переводы меню, кнопок, сообщений',
        'menu_cache' => 'Кэш меню ресторана - блюда, категории, цены',
        'events' => 'События ресторана - банкеты, вечеринки, специальные мероприятия',
        'tables_cache' => 'Кэш столов - доступность, бронирование, статус',
        'sepay_transactions' => 'Транзакции SePay - платежи, статусы, webhook данные',
        'rate_limits' => 'Ограничения запросов - IP адреса, лимиты, блокировки',
        'telegram_logs' => 'Логи Telegram бота - сообщения, команды, уведомления',
        'image_cache' => 'Кэш изображений - миниатюры, оптимизированные версии',
        'settings' => 'Настройки системы - конфигурация, параметры приложения',
        'user_sessions' => 'Сессии пользователей сайта - корзина, предпочтения',
        'orders' => 'Заказы клиентов - блюда, суммы, статусы доставки',
        'feedback' => 'Отзывы клиентов - оценки, комментарии, рейтинги',
        'newsletter' => 'Подписки на рассылку - email адреса, предпочтения',
        'analytics' => 'Аналитика сайта - просмотры, клики, конверсии'
    ];

    // Получаем список коллекций
    $collections = $database->listCollections();
    $collectionsList = [];
    
    foreach ($collections as $collection) {
        $collectionName = $collection->getName();
        $collectionObj = $database->selectCollection($collectionName);
        $count = $collectionObj->countDocuments();
        
        $collectionsList[] = [
            'name' => $collectionName,
            'count' => $count,
            'description' => $collectionDescriptions[$collectionName] ?? 'Коллекция данных - назначение не указано'
        ];
    }
    
    // Получаем данные для конкретной коллекции
    $selectedCollection = $_GET['collection'] ?? '';
    $collectionData = [];
    $collectionStats = [];
    
    if ($selectedCollection && in_array($selectedCollection, array_column($collectionsList, 'name'))) {
        $collectionObj = $database->selectCollection($selectedCollection);
        
        // Получаем статистику коллекции
        $stats = $database->command(['collStats' => $selectedCollection])->toArray()[0];
        $collectionStats = [
            'count' => $stats['count'] ?? 0,
            'size' => $stats['size'] ?? 0,
            'avgObjSize' => $stats['avgObjSize'] ?? 0,
            'storageSize' => $stats['storageSize'] ?? 0,
            'indexes' => $stats['nindexes'] ?? 0
        ];
        
        // Получаем документы (увеличиваем лимит до 100)
        $limit = min(100, $collectionStats['count']);
        $documents = $collectionObj->find([], ['limit' => $limit, 'sort' => ['_id' => -1]]);
        $collectionData = iterator_to_array($documents);
        
        // Получаем структуру коллекции (все уникальные поля)
        $structureFields = [];
        $sampleDocuments = $collectionObj->find([], ['limit' => 10]);
        foreach ($sampleDocuments as $doc) {
            extractFields($doc, $structureFields);
        }
    }
    
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    // Если подключение не удалось, создаем пустые массивы
    $collectionsList = [];
    $collectionData = [];
    $collectionStats = [];
}

// Функция для форматирования размера
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Функция для форматирования JSON
function formatJson($data) {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Функция для извлечения полей из документа
function extractFields($document, &$fields, $prefix = '') {
    foreach ($document as $key => $value) {
        $fieldName = $prefix ? $prefix . '.' . $key : $key;
        
        if (is_array($value) || is_object($value)) {
            if (is_array($value) && !empty($value) && !isset($value[0])) {
                // Ассоциативный массив - рекурсивно обрабатываем
                extractFields($value, $fields, $fieldName);
            } else {
                // Обычный массив или объект
                $fields[$fieldName] = gettype($value) . (is_array($value) ? '[' . count($value) . ']' : '');
            }
        } else {
            $fields[$fieldName] = gettype($value);
        }
    }
}
?>

<div class="mongodb-viewer">
    <h3>🗄️ MongoDB Коллекции</h3>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <strong>Ошибка подключения к MongoDB:</strong><br>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        
        <!-- Список коллекций -->
        <div class="collections-list">
            <h4>Доступные коллекции:</h4>
            <div class="collections-grid">
                <?php foreach ($collectionsList as $collection): ?>
                    <div class="collection-card">
                        <a href="?collection=<?php echo urlencode($collection['name']); ?>" 
                           class="collection-link <?php echo $selectedCollection === $collection['name'] ? 'active' : ''; ?>">
                            <div class="collection-name">
                                <?php echo htmlspecialchars($collection['name']); ?>
                                <small style="display: block; color: #666; font-size: 0.8em; margin-top: 0.25rem; font-weight: normal;">
                                    <?php echo htmlspecialchars($collection['description']); ?>
                                </small>
                            </div>
                            <div class="collection-count"><?php echo number_format($collection['count']); ?> документов</div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if ($selectedCollection && !empty($collectionStats)): ?>
            <!-- Статистика коллекции -->
            <div class="collection-stats">
                <h4>📊 Статистика коллекции: <?php echo htmlspecialchars($selectedCollection); ?></h4>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-label">Документов:</span>
                        <span class="stat-value"><?php echo number_format($collectionStats['count']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Размер данных:</span>
                        <span class="stat-value"><?php echo formatBytes($collectionStats['size']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Средний размер документа:</span>
                        <span class="stat-value"><?php echo formatBytes($collectionStats['avgObjSize']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Размер на диске:</span>
                        <span class="stat-value"><?php echo formatBytes($collectionStats['storageSize']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Индексов:</span>
                        <span class="stat-value"><?php echo $collectionStats['indexes']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Структура коллекции -->
            <?php if (!empty($structureFields)): ?>
                <div class="collection-structure">
                    <h4>🏗️ Структура коллекции:</h4>
                    <div class="structure-grid">
                        <?php foreach ($structureFields as $fieldName => $fieldType): ?>
                            <div class="structure-item">
                                <span class="field-name"><?php echo htmlspecialchars($fieldName); ?></span>
                                <span class="field-type"><?php echo htmlspecialchars($fieldType); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Документы коллекции -->
            <div class="collection-documents">
                <h4>📄 Документы (показано до 100 последних):</h4>
                
                <?php if (empty($collectionData)): ?>
                    <div class="alert alert-info">
                        Коллекция пуста
                    </div>
                <?php else: ?>
                    <div class="documents-list">
                        <?php foreach ($collectionData as $index => $document): ?>
                            <div class="document-item">
                                <div class="document-header">
                                    <span class="document-index">#<?php echo $index + 1; ?></span>
                                    <span class="document-id">ID: <?php echo htmlspecialchars($document['_id']); ?></span>
                                </div>
                                <div class="document-content">
                                    <pre class="json-content"><?php echo htmlspecialchars(formatJson($document)); ?></pre>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<style>
.mongodb-viewer {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.collections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.collection-card {
    border: 1px solid #e1e5e9;
    border-radius: 5px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.collection-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.collection-link {
    display: block;
    padding: 1rem;
    text-decoration: none;
    color: inherit;
}

.collection-link.active {
    background: #e3f2fd;
    border-color: #2196f3;
}

.collection-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.collection-count {
    font-size: 0.9rem;
    color: #666;
}

.collection-stats {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 5px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem;
    background: white;
    border-radius: 3px;
    border: 1px solid #e1e5e9;
}

.stat-label {
    font-weight: 500;
    color: #666;
}

.stat-value {
    font-weight: 600;
    color: #333;
}

.collection-documents {
    margin-top: 2rem;
}

.documents-list {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #e1e5e9;
    border-radius: 5px;
}

.document-item {
    border-bottom: 1px solid #e1e5e9;
}

.document-item:last-child {
    border-bottom: none;
}

.document-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e1e5e9;
    font-size: 0.9rem;
}

.document-index {
    font-weight: 600;
    color: #666;
}

.document-id {
    font-family: monospace;
    color: #333;
}

.document-content {
    padding: 1rem;
}

.json-content {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 3px;
    padding: 1rem;
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 300px;
    overflow-y: auto;
}

.alert {
    padding: 1rem;
    border-radius: 5px;
    margin: 1rem 0;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.collection-structure {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 5px;
}

.structure-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 0.5rem;
    margin-top: 1rem;
}

.structure-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: white;
    border-radius: 3px;
    border: 1px solid #e1e5e9;
    font-size: 0.9rem;
}

.field-name {
    font-family: monospace;
    font-weight: 600;
    color: #333;
}

.field-type {
    font-size: 0.8rem;
    color: #666;
    background: #e9ecef;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
}
</style>
