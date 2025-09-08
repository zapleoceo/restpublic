<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    // Параметры фильтрации
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $skip = ($page - 1) * $limit;
    
    $filter = [];
    $sort = ['category' => 1, 'key' => 1];
    
    // Фильтр по категории
    if (!empty($_GET['category'])) {
        $filter['category'] = $_GET['category'];
    }
    
    // Поиск по тексту
    if (!empty($_GET['search'])) {
        $filter['$or'] = [
            ['key' => new MongoDB\BSON\Regex($_GET['search'], 'i')],
            ['translations.ru' => new MongoDB\BSON\Regex($_GET['search'], 'i')],
            ['translations.en' => new MongoDB\BSON\Regex($_GET['search'], 'i')],
            ['translations.vi' => new MongoDB\BSON\Regex($_GET['search'], 'i')]
        ];
    }
    
    // Получаем данные
    $texts = $textsCollection->find($filter, [
        'sort' => $sort,
        'skip' => $skip,
        'limit' => $limit
    ])->toArray();
    
    // Подсчитываем общее количество
    $totalCount = $textsCollection->countDocuments($filter);
    $totalPages = ceil($totalCount / $limit);
    
    // Получаем категории
    $categories = $textsCollection->distinct('category');
    
    // Статистика
    $stats = [
        'total' => $totalCount,
        'categories' => count($categories),
        'complete_translations' => $textsCollection->countDocuments([
            'translations.ru' => ['$exists' => true, '$ne' => ''],
            'translations.en' => ['$exists' => true, '$ne' => ''],
            'translations.vi' => ['$exists' => true, '$ne' => '']
        ])
    ];
    
} catch (Exception $e) {
    $texts = [];
    $totalCount = 0;
    $totalPages = 0;
    $categories = [];
    $stats = ['total' => 0, 'categories' => 0, 'complete_translations' => 0];
    $error = "Ошибка подключения к базе данных: " . $e->getMessage();
}

// Логируем просмотр текстов
logAdminAction('view_texts', 'Просмотр управления текстами', [
    'filters' => $_GET,
    'page' => $page
]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление текстами - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .filters-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-item.total h3 { color: #3498db; }
        .stat-item.categories h3 { color: #9b59b6; }
        .stat-item.complete h3 { color: #27ae60; }
        
        .text-item {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .text-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e1e5e9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .text-key {
            font-family: monospace;
            font-weight: 600;
            color: #333;
        }
        
        .text-category {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .text-content {
            padding: 1.5rem;
        }
        
        .language-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .language-tab {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .language-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .language-content {
            display: none;
        }
        
        .language-content.active {
            display: block;
        }
        
        .translation-text {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #e1e5e9;
            min-height: 60px;
            white-space: pre-wrap;
        }
        
        .translation-status {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .status-complete {
            background: #d4edda;
            color: #155724;
        }
        
        .status-missing {
            background: #f8d7da;
            color: #721c24;
        }
        
        .text-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-edit {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-edit:hover {
            background: #218838;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 2rem;
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
        
        .bulk-actions {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Управление текстами</h1>
                <p>Редактирование контента на 3 языках (RU, EN, VI)</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="stats-row">
                <div class="stat-item total">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Всего текстов</p>
                </div>
                <div class="stat-item categories">
                    <h3><?php echo number_format($stats['categories']); ?></h3>
                    <p>Категорий</p>
                </div>
                <div class="stat-item complete">
                    <h3><?php echo number_format($stats['complete_translations']); ?></h3>
                    <p>Полных переводов</p>
                </div>
            </div>
            
            <!-- Массовые действия -->
            <div class="bulk-actions">
                <a href="add.php" class="btn">Добавить текст</a>
                <a href="import.php" class="btn btn-secondary">Импорт</a>
                <a href="export.php" class="btn btn-secondary">Экспорт</a>
                <a href="publish.php" class="btn" style="background: #28a745;">Опубликовать изменения</a>
            </div>
            
            <!-- Фильтры -->
            <div class="filters-card">
                <h3>Фильтры</h3>
                <form method="GET" class="filters-form">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="category">Категория</label>
                            <select id="category" name="category">
                                <option value="">Все категории</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" 
                                            <?php echo ($_GET['category'] ?? '') === $category ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="search">Поиск</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                                   placeholder="Ключ или текст">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn">Применить фильтры</button>
                        <a href="?" class="btn btn-secondary">Сбросить</a>
                    </div>
                </form>
            </div>
            
            <!-- Список текстов -->
            <?php if (empty($texts)): ?>
                <div class="card">
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <h3>Нет текстов для отображения</h3>
                        <p>Добавьте первый текст или измените фильтры</p>
                        <a href="add.php" class="btn">Добавить текст</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($texts as $text): ?>
                    <div class="text-item">
                        <div class="text-header">
                            <div>
                                <div class="text-key"><?php echo htmlspecialchars($text['key']); ?></div>
                                <div class="text-category"><?php echo htmlspecialchars($text['category']); ?></div>
                            </div>
                            <div class="text-actions">
                                <a href="edit.php?id=<?php echo $text['_id']; ?>" class="btn-edit">Редактировать</a>
                            </div>
                        </div>
                        
                        <div class="text-content">
                            <div class="language-tabs">
                                <div class="language-tab active" data-lang="ru">🇷🇺 Русский</div>
                                <div class="language-tab" data-lang="en">🇬🇧 English</div>
                                <div class="language-tab" data-lang="vi">🇻🇳 Tiếng Việt</div>
                            </div>
                            
                            <div class="language-content active" data-lang="ru">
                                <div class="translation-text">
                                    <?php echo htmlspecialchars($text['translations']['ru'] ?? 'Не переведено'); ?>
                                </div>
                                <div class="translation-status">
                                    <span class="status-badge <?php echo !empty($text['translations']['ru']) ? 'status-complete' : 'status-missing'; ?>">
                                        <?php echo !empty($text['translations']['ru']) ? 'Переведено' : 'Не переведено'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="language-content" data-lang="en">
                                <div class="translation-text">
                                    <?php echo htmlspecialchars($text['translations']['en'] ?? 'Not translated'); ?>
                                </div>
                                <div class="translation-status">
                                    <span class="status-badge <?php echo !empty($text['translations']['en']) ? 'status-complete' : 'status-missing'; ?>">
                                        <?php echo !empty($text['translations']['en']) ? 'Translated' : 'Not translated'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="language-content" data-lang="vi">
                                <div class="translation-text">
                                    <?php echo htmlspecialchars($text['translations']['vi'] ?? 'Chưa dịch'); ?>
                                </div>
                                <div class="translation-status">
                                    <span class="status-badge <?php echo !empty($text['translations']['vi']) ? 'status-complete' : 'status-missing'; ?>">
                                        <?php echo !empty($text['translations']['vi']) ? 'Đã dịch' : 'Chưa dịch'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Пагинация -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">← Предыдущая</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Следующая →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        // Переключение языков
        document.querySelectorAll('.language-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const lang = this.dataset.lang;
                const textItem = this.closest('.text-item');
                
                // Убираем активный класс со всех табов и контента
                textItem.querySelectorAll('.language-tab').forEach(t => t.classList.remove('active'));
                textItem.querySelectorAll('.language-content').forEach(c => c.classList.remove('active'));
                
                // Добавляем активный класс к выбранному табу и контенту
                this.classList.add('active');
                textItem.querySelector(`.language-content[data-lang="${lang}"]`).classList.add('active');
            });
        });
    </script>
</body>
</html>
