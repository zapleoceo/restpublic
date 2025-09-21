<?php
session_start();
require_once __DIR__ . '/../includes/auth-check.php';

$error = '';
$success = '';

// Проверяем статус MongoDB
$mongoStatus = 'Недоступна';
$mongoConnection = false;
$database = null;
$databaseName = 'northrepublic';

try {
    // Загружаем переменные окружения
    require_once __DIR__ . '/../../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();
    }
    
    if (class_exists('MongoDB\Client')) {
        $mongoUri = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
        $databaseName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
        $client = new MongoDB\Client($mongoUri);
        $database = $client->selectDatabase($databaseName);
        $client->listDatabases();
        $mongoStatus = 'Доступна';
        $mongoConnection = true;
    } else {
        $mongoStatus = 'Класс MongoDB\Client не найден';
    }
} catch (Exception $e) {
    $mongoStatus = 'Ошибка: ' . $e->getMessage();
}

// Функция для получения информации о файлах данных
function getDataFilesInfo() {
    $dataDir = __DIR__ . '/../../data';
    $files = [];
    
    if (is_dir($dataDir)) {
        $fileList = scandir($dataDir);
        foreach ($fileList as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $filePath = $dataDir . '/' . $file;
                $files[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                    'path' => $filePath
                ];
            }
        }
    }
    
    return $files;
}

// Получаем информацию о файлах
$dataFiles = getDataFilesInfo();

// Обработка просмотра файла
$viewFile = $_GET['view'] ?? '';
$fileContent = '';
$fileName = '';

if ($viewFile) {
    $filePath = $dataDir . '/' . $viewFile;
    if (file_exists($filePath) && pathinfo($viewFile, PATHINFO_EXTENSION) === 'json') {
        $fileName = $viewFile;
        $content = file_get_contents($filePath);
        $jsonData = json_decode($content, true);
        
        if ($jsonData !== null) {
            $fileContent = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $fileContent = $content; // Показываем как есть, если не валидный JSON
        }
    }
}

// Функция для форматирования размера файла
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База данных - Админка</title>
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <style>
        .database-info {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .files-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .files-table th,
        .files-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .files-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .file-name {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }
        
        .file-size {
            color: #666;
        }
        
        .file-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .json-viewer {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1rem;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .json-content {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .json-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>База данных</h1>
                <p>Просмотр содержимого MongoDB и статистики системы</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Общая информация -->
            <div class="info-grid">
                <div class="info-card">
                    <h3>📊 Статистика MongoDB</h3>
                    <?php if ($mongoConnection): ?>
                        <?php
                        try {
                            $collections = $database->listCollections();
                            $totalCollections = 0;
                            $totalDocuments = 0;
                            
                            foreach ($collections as $collection) {
                                $totalCollections++;
                                $totalDocuments += $database->selectCollection($collection->getName())->countDocuments();
                            }
                        } catch (Exception $e) {
                            $totalCollections = 0;
                            $totalDocuments = 0;
                        }
                        ?>
                        <div class="info-item">
                            <span class="info-label">Коллекций:</span>
                            <span class="info-value"><?php echo $totalCollections; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Документов:</span>
                            <span class="info-value"><?php echo number_format($totalDocuments); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">База данных:</span>
                            <span class="info-value"><?php echo htmlspecialchars($databaseName); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <span class="info-label">Статус:</span>
                            <span class="info-value">MongoDB недоступна</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-card">
                    <h3>🔧 Система</h3>
                    <div class="info-item">
                        <span class="info-label">Тип БД:</span>
                        <span class="info-value"><?php echo $mongoConnection ? 'MongoDB' : 'Файловая система (JSON)'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">MongoDB:</span>
                        <span class="info-value">
                            <span class="status-badge <?php echo class_exists('MongoDB\Client') ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo class_exists('MongoDB\Client') ? 'Доступна' : 'Недоступна'; ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">PHP версия:</span>
                        <span class="info-value"><?php echo PHP_VERSION; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Статус MongoDB -->
            <div class="database-info">
                <h3>🗄️ Статус MongoDB</h3>
                <div class="mongo-status" style="padding: 1rem; border-radius: 5px; background: <?php echo $mongoConnection ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo $mongoConnection ? '#c3e6cb' : '#f5c6cb'; ?>;">
                    <strong>MongoDB:</strong> 
                    <span style="color: <?php echo $mongoConnection ? '#155724' : '#721c24'; ?>;">
                        <?php echo htmlspecialchars($mongoStatus); ?>
                    </span>
                    <?php if ($mongoConnection): ?>
                        <span style="color: #155724;">✅</span>
                    <?php else: ?>
                        <span style="color: #721c24;">❌</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- MongoDB Viewer -->
            <?php include 'mongodb-viewer.php'; ?>
            
            <!-- Просмотр JSON файла -->
            <?php if ($fileContent): ?>
                <div class="json-viewer">
                    <div class="json-header">
                        <h3>📄 Содержимое файла: <?php echo htmlspecialchars($fileName); ?></h3>
                        <a href="?" class="btn btn-secondary">← Назад к списку</a>
                    </div>
                    <div class="json-content"><?php echo htmlspecialchars($fileContent); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Предупреждение -->
            <div class="alert alert-warning">
                <strong>⚠️ Только для просмотра</strong><br>
                Этот раздел предназначен только для просмотра информации о базе данных. 
                Редактирование данных производится через соответствующие разделы админки.
            </div>
        </main>
    </div>
</body>
</html>