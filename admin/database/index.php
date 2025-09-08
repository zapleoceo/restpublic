<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    
    // Получаем список коллекций
    $collections = $db->listCollections();
    $collectionNames = [];
    
    foreach ($collections as $collection) {
        $collectionNames[] = $collection->getName();
    }
    
    // Получаем статистику по коллекциям
    $stats = [];
    foreach ($collectionNames as $collectionName) {
        $collection = $db->selectCollection($collectionName);
        $count = $collection->countDocuments();
        $stats[$collectionName] = $count;
    }
    
    // Если выбрана конкретная коллекция
    $selectedCollection = $_GET['collection'] ?? '';
    $collectionData = [];
    $totalPages = 0;
    $currentPage = 1;
    
    if ($selectedCollection && in_array($selectedCollection, $collectionNames)) {
        $collection = $db->selectCollection($selectedCollection);
        $currentPage = max(1, intval($_GET['page'] ?? 1));
        $limit = 10;
        $skip = ($currentPage - 1) * $limit;
        
        // Получаем документы
        $documents = $collection->find([], [
            'limit' => $limit,
            'skip' => $skip,
            'sort' => ['_id' => -1]
        ])->toArray();
        
        $totalCount = $collection->countDocuments();
        $totalPages = ceil($totalCount / $limit);
        
        // Конвертируем BSON объекты для отображения
        $collectionData = array_map(function($doc) {
            $doc['_id'] = (string)$doc['_id'];
            
            // Конвертируем даты
            foreach ($doc as $key => $value) {
                if ($value instanceof MongoDB\BSON\UTCDateTime) {
                    $doc[$key] = $value->toDateTime()->format('Y-m-d H:i:s');
                }
            }
            
            return $doc;
        }, $documents);
    }
    
} catch (Exception $e) {
    $error = "Ошибка подключения к базе данных: " . $e->getMessage();
    $collections = [];
    $stats = [];
    $collectionData = [];
}

// Логируем просмотр базы данных
logAdminAction('view_database', 'Просмотр базы данных', [
    'collection' => $selectedCollection,
    'page' => $currentPage
]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База данных - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .database-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            height: calc(100vh - 200px);
        }
        
        .collections-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .collections-sidebar h3 {
            margin-top: 0;
            color: #667eea;
            border-bottom: 2px solid #f0f2ff;
            padding-bottom: 1rem;
        }
        
        .collection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .collection-item:hover {
            background: #f8f9ff;
            border-color: #e0e6ff;
        }
        
        .collection-item.active {
            background: #667eea;
            color: white;
            border-color: #5a6fd8;
        }
        
        .collection-name {
            font-weight: 600;
            font-family: monospace;
        }
        
        .collection-count {
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .collection-item.active .collection-count {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .database-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .collection-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f2ff;
        }
        
        .collection-title {
            color: #667eea;
            margin: 0;
        }
        
        .collection-actions {
            display: flex;
            gap: 1rem;
        }
        
        .document-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .document-id {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .document-content {
            color: #495057;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .json-viewer {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
            overflow-x: auto;
        }
        
        .json-key {
            color: #68d391;
        }
        
        .json-string {
            color: #fbb6ce;
        }
        
        .json-number {
            color: #90cdf4;
        }
        
        .json-boolean {
            color: #f6ad55;
        }
        
        .json-null {
            color: #a0aec0;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #e1e5e9;
            border-radius: 5px;
            text-decoration: none;
            color: #666;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-item h4 {
            margin: 0 0 0.5rem 0;
            color: #667eea;
            font-size: 1.5rem;
        }
        
        .stat-item p {
            margin: 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state h3 {
            color: #495057;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>База данных</h1>
                <p>Просмотр и управление MongoDB коллекциями</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="database-container">
                <!-- Список коллекций -->
                <div class="collections-sidebar">
                    <h3>Коллекции</h3>
                    
                    <?php if (empty($collectionNames)): ?>
                        <p>Коллекции не найдены</p>
                    <?php else: ?>
                        <?php foreach ($collectionNames as $collectionName): ?>
                            <div class="collection-item <?php echo $selectedCollection === $collectionName ? 'active' : ''; ?>" 
                                 onclick="window.location.href='?collection=<?php echo urlencode($collectionName); ?>'">
                                <div class="collection-name"><?php echo htmlspecialchars($collectionName); ?></div>
                                <div class="collection-count"><?php echo number_format($stats[$collectionName] ?? 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Содержимое коллекции -->
                <div class="database-content">
                    <?php if (empty($selectedCollection)): ?>
                        <div class="empty-state">
                            <h3>Выберите коллекцию</h3>
                            <p>Выберите коллекцию из списка слева для просмотра документов</p>
                        </div>
                    <?php else: ?>
                        <div class="collection-header">
                            <h2 class="collection-title"><?php echo htmlspecialchars($selectedCollection); ?></h2>
                            <div class="collection-actions">
                                <a href="?collection=<?php echo urlencode($selectedCollection); ?>&export=json" class="btn btn-secondary">Экспорт JSON</a>
                                <a href="?collection=<?php echo urlencode($selectedCollection); ?>&export=csv" class="btn btn-secondary">Экспорт CSV</a>
                            </div>
                        </div>
                        
                        <!-- Статистика коллекции -->
                        <div class="stats-grid">
                            <div class="stat-item">
                                <h4><?php echo number_format($stats[$selectedCollection] ?? 0); ?></h4>
                                <p>Документов</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo $currentPage; ?></h4>
                                <p>Страница</p>
                            </div>
                            <div class="stat-item">
                                <h4><?php echo $totalPages; ?></h4>
                                <p>Всего страниц</p>
                            </div>
                        </div>
                        
                        <!-- Документы -->
                        <?php if (empty($collectionData)): ?>
                            <div class="empty-state">
                                <h3>Коллекция пуста</h3>
                                <p>В коллекции <?php echo htmlspecialchars($selectedCollection); ?> нет документов</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($collectionData as $document): ?>
                                <div class="document-item">
                                    <div class="document-id">ID: <?php echo htmlspecialchars($document['_id']); ?></div>
                                    <div class="json-viewer"><?php echo formatJsonForDisplay(json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <!-- Пагинация -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?collection=<?php echo urlencode($selectedCollection); ?>&page=<?php echo $currentPage - 1; ?>">← Предыдущая</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                        <?php if ($i == $currentPage): ?>
                                            <span class="current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?collection=<?php echo urlencode($selectedCollection); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?collection=<?php echo urlencode($selectedCollection); ?>&page=<?php echo $currentPage + 1; ?>">Следующая →</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>

<?php
// Функция для форматирования JSON с подсветкой синтаксиса
function formatJsonForDisplay($json) {
    $json = htmlspecialchars($json);
    
    // Подсветка ключей
    $json = preg_replace('/"([^"]+)"\s*:/', '<span class="json-key">"$1"</span>:', $json);
    
    // Подсветка строк
    $json = preg_replace('/:\s*"([^"]*)"/', ': <span class="json-string">"$1"</span>', $json);
    
    // Подсветка чисел
    $json = preg_replace('/:\s*(\d+\.?\d*)/', ': <span class="json-number">$1</span>', $json);
    
    // Подсветка булевых значений
    $json = preg_replace('/:\s*(true|false)/', ': <span class="json-boolean">$1</span>', $json);
    
    // Подсветка null
    $json = preg_replace('/:\s*null/', ': <span class="json-null">null</span>', $json);
    
    return $json;
}
?>
