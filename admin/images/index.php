<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

// Обработка загрузки файлов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $imagesCollection = $db->admin_images;
        
        $uploadDir = '../../images/';
        $originalDir = $uploadDir . 'original/';
        $webpDir = $uploadDir . 'webp/';
        
        // Создаем директории если не существуют
        if (!is_dir($originalDir)) mkdir($originalDir, 0755, true);
        if (!is_dir($webpDir)) mkdir($webpDir, 0755, true);
        
        $file = $_FILES['image'];
        $category = $_POST['category'] ?? 'general';
        $description = trim($_POST['description'] ?? '');
        
        // Валидация файла
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки файла');
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB
            throw new Exception('Файл слишком большой (максимум 10MB)');
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Неподдерживаемый тип файла');
        }
        
        // Генерируем уникальное имя файла
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        
        $originalPath = $originalDir . $filename;
        $webpPath = $webpDir . $webpFilename;
        
        // Сохраняем оригинал
        if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
            throw new Exception('Ошибка сохранения файла');
        }
        
        // Конвертируем в WebP
        $image = null;
        switch ($file['type']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($originalPath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($originalPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($originalPath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($originalPath);
                break;
        }
        
        if (!$image) {
            throw new Exception('Ошибка обработки изображения');
        }
        
        // Сохраняем WebP версию с оптимальным качеством
        $webpQuality = 85; // Оптимальное качество по рекомендациям Google
        if (!imagewebp($image, $webpPath, $webpQuality)) {
            throw new Exception('Ошибка конвертации в WebP');
        }
        
        imagedestroy($image);
        
        // Получаем размеры
        $originalSize = getimagesize($originalPath);
        $webpSize = filesize($webpPath);
        $originalFileSize = filesize($originalPath);
        
        // Сохраняем в базу данных
        $imageData = [
            'filename' => $filename,
            'webp_filename' => $webpFilename,
            'original_path' => 'images/original/' . $filename,
            'webp_path' => 'images/webp/' . $webpFilename,
            'category' => $category,
            'description' => $description,
            'original_size' => $originalFileSize,
            'webp_size' => $webpSize,
            'width' => $originalSize[0],
            'height' => $originalSize[1],
            'mime_type' => $file['type'],
            'uploaded_at' => new MongoDB\BSON\UTCDateTime(),
            'uploaded_by' => $_SESSION['admin_username'] ?? 'unknown'
        ];
        
        $result = $imagesCollection->insertOne($imageData);
        
        if ($result->getInsertedId()) {
            // Логируем загрузку
            logAdminAction('upload_image', 'Загружено изображение', [
                'filename' => $filename,
                'category' => $category,
                'image_id' => (string)$result->getInsertedId()
            ]);
            
            $success = 'Изображение успешно загружено и конвертировано в WebP!';
        } else {
            throw new Exception('Ошибка сохранения в базу данных');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Получаем список изображений
try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $imagesCollection = $db->admin_images;
    
    // Параметры фильтрации
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $skip = ($page - 1) * $limit;
    
    $filter = [];
    $sort = ['uploaded_at' => -1];
    
    // Фильтр по категории
    if (!empty($_GET['category'])) {
        $filter['category'] = $_GET['category'];
    }
    
    // Поиск по описанию
    if (!empty($_GET['search'])) {
        $filter['description'] = new MongoDB\BSON\Regex($_GET['search'], 'i');
    }
    
    // Получаем данные
    $images = $imagesCollection->find($filter, [
        'sort' => $sort,
        'skip' => $skip,
        'limit' => $limit
    ])->toArray();
    
    // Подсчитываем общее количество
    $totalCount = $imagesCollection->countDocuments($filter);
    $totalPages = ceil($totalCount / $limit);
    
    // Получаем категории
    $categories = $imagesCollection->distinct('category');
    
    // Статистика
    $stats = [
        'total' => $totalCount,
        'categories' => count($categories),
        'total_size' => 0,
        'webp_savings' => 0
    ];
    
    // Подсчитываем размеры
    $sizeStats = $imagesCollection->aggregate([
        ['$group' => [
            '_id' => null,
            'total_original_size' => ['$sum' => '$original_size'],
            'total_webp_size' => ['$sum' => '$webp_size']
        ]]
    ])->toArray();
    
    if (!empty($sizeStats)) {
        $stats['total_size'] = $sizeStats[0]['total_original_size'] ?? 0;
        $stats['webp_savings'] = ($stats['total_size'] - ($sizeStats[0]['total_webp_size'] ?? 0));
    }
    
} catch (Exception $e) {
    $images = [];
    $totalCount = 0;
    $totalPages = 0;
    $categories = [];
    $stats = ['total' => 0, 'categories' => 0, 'total_size' => 0, 'webp_savings' => 0];
    $error = "Ошибка подключения к базе данных: " . $e->getMessage();
}

// Логируем просмотр изображений
logAdminAction('view_images', 'Просмотр управления изображениями', [
    'filters' => $_GET,
    'page' => $page
]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление изображениями - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .upload-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .upload-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: end;
        }
        
        .file-upload-area {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            background: #f0f2ff;
            border-color: #5a6fd8;
        }
        
        .file-upload-area.dragover {
            background: #e8f0ff;
            border-color: #4c63d2;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.total h3 { color: #3498db; }
        .stat-card.categories h3 { color: #9b59b6; }
        .stat-card.size h3 { color: #e67e22; }
        .stat-card.savings h3 { color: #27ae60; }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .image-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        
        .image-info {
            padding: 1rem;
        }
        
        .image-filename {
            font-family: monospace;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .image-category {
            background: #667eea;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .image-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .image-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 1rem;
        }
        
        .image-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-view:hover {
            background: #2980b9;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
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
            <div class="page-header">
                <h1>Управление изображениями</h1>
                <p>Загрузка и управление изображениями с автоматической конвертацией в WebP</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <h3><?php echo number_format($stats['total']); ?></h3>
                    <p>Всего изображений</p>
                </div>
                <div class="stat-card categories">
                    <h3><?php echo number_format($stats['categories']); ?></h3>
                    <p>Категорий</p>
                </div>
                <div class="stat-card size">
                    <h3><?php echo number_format($stats['total_size'] / 1024 / 1024, 1); ?> MB</h3>
                    <p>Общий размер</p>
                </div>
                <div class="stat-card savings">
                    <h3><?php echo number_format($stats['webp_savings'] / 1024 / 1024, 1); ?> MB</h3>
                    <p>Экономия WebP</p>
                </div>
            </div>
            
            <!-- Загрузка изображений -->
            <div class="upload-section">
                <h3>Загрузить новое изображение</h3>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div>
                        <div class="file-upload-area" onclick="document.getElementById('image').click()">
                            <div class="upload-icon">📁</div>
                            <p>Нажмите для выбора файла или перетащите сюда</p>
                            <p><small>Поддерживаются: JPG, PNG, GIF, WebP (максимум 10MB)</small></p>
                            <input type="file" id="image" name="image" accept="image/*" style="display: none;" required>
                        </div>
                    </div>
                    
                    <div>
                        <div class="form-group">
                            <label for="category">Категория</label>
                            <select id="category" name="category" required>
                                <option value="general">Общие</option>
                                <option value="intro">Главная страница</option>
                                <option value="about">О нас</option>
                                <option value="menu">Меню</option>
                                <option value="gallery">Галерея</option>
                                <option value="products">Продукты</option>
                                <option value="backgrounds">Фоны</option>
                                <option value="icons">Иконки</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Описание</label>
                            <input type="text" id="description" name="description" 
                                   placeholder="Краткое описание изображения">
                        </div>
                        
                        <button type="submit" class="btn">Загрузить и конвертировать</button>
                    </div>
                </form>
            </div>
            
            <!-- Массовые действия -->
            <div class="bulk-actions">
                <a href="optimize.php" class="btn btn-secondary">Оптимизировать все</a>
                <a href="cleanup.php" class="btn btn-secondary">Очистить неиспользуемые</a>
                <a href="export.php" class="btn btn-secondary">Экспорт списка</a>
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
                                   placeholder="Описание или имя файла">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn">Применить фильтры</button>
                        <a href="?" class="btn btn-secondary">Сбросить</a>
                    </div>
                </form>
            </div>
            
            <!-- Список изображений -->
            <?php if (empty($images)): ?>
                <div class="card">
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <h3>Нет изображений для отображения</h3>
                        <p>Загрузите первое изображение или измените фильтры</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="image-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="image-card">
                            <div class="image-preview">
                                <img src="<?php echo htmlspecialchars($image['webp_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['description']); ?>"
                                     loading="lazy">
                            </div>
                            
                            <div class="image-info">
                                <div class="image-filename"><?php echo htmlspecialchars($image['filename']); ?></div>
                                <div class="image-category"><?php echo htmlspecialchars($image['category']); ?></div>
                                
                                <?php if (!empty($image['description'])): ?>
                                    <div class="image-description"><?php echo htmlspecialchars($image['description']); ?></div>
                                <?php endif; ?>
                                
                                <div class="image-stats">
                                    <span><?php echo $image['width']; ?>×<?php echo $image['height']; ?></span>
                                    <span><?php echo number_format($image['webp_size'] / 1024, 1); ?> KB</span>
                                </div>
                                
                                <div class="image-actions">
                                    <a href="<?php echo htmlspecialchars($image['webp_path']); ?>" 
                                       target="_blank" class="btn-view">Просмотр</a>
                                    <a href="delete.php?id=<?php echo $image['_id']; ?>" 
                                       class="btn-delete" 
                                       onclick="return confirm('Удалить изображение?')">Удалить</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
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
        // Drag and drop для загрузки файлов
        const uploadArea = document.querySelector('.file-upload-area');
        const fileInput = document.getElementById('image');
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileDisplay(files[0]);
            }
        });
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                updateFileDisplay(e.target.files[0]);
            }
        });
        
        function updateFileDisplay(file) {
            const uploadArea = document.querySelector('.file-upload-area');
            uploadArea.innerHTML = `
                <div class="upload-icon">✅</div>
                <p><strong>${file.name}</strong></p>
                <p><small>${(file.size / 1024 / 1024).toFixed(2)} MB</small></p>
                <p><small>Нажмите для выбора другого файла</small></p>
            `;
        }
    </script>
</body>
</html>
