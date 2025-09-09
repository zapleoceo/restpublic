<?php
session_start();
require_once '../includes/auth-check.php';

$error = '';
$success = '';

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
    <link rel="stylesheet" href="../assets/css/admin.css">
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
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>База данных</h1>
                <p>Просмотр информации о файлах данных системы</p>
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
                    <h3>📊 Статистика</h3>
                    <div class="info-item">
                        <span class="info-label">Файлов данных:</span>
                        <span class="info-value"><?php echo count($dataFiles); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Общий размер:</span>
                        <span class="info-value"><?php echo formatFileSize(array_sum(array_column($dataFiles, 'size'))); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Последнее обновление:</span>
                        <span class="info-value"><?php echo count($dataFiles) > 0 ? date('d.m.Y H:i', max(array_column($dataFiles, 'modified'))) : 'Нет данных'; ?></span>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3>🔧 Система</h3>
                    <div class="info-item">
                        <span class="info-label">Тип БД:</span>
                        <span class="info-value">Файловая система (JSON)</span>
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
            
            <!-- Файлы данных -->
            <div class="database-info">
                <h3>📁 Файлы данных</h3>
                <p>Список всех JSON файлов с данными системы:</p>
                
                <?php if (empty($dataFiles)): ?>
                    <div class="alert alert-info">
                        <strong>Нет файлов данных</strong><br>
                        Файлы данных будут созданы автоматически при первом использовании системы.
                    </div>
                <?php else: ?>
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>Имя файла</th>
                                <th>Размер</th>
                                <th>Изменен</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dataFiles as $file): ?>
                                <tr>
                                    <td>
                                        <span class="file-name"><?php echo htmlspecialchars($file['name']); ?></span>
                                    </td>
                                    <td class="file-size">
                                        <?php echo formatFileSize($file['size']); ?>
                                    </td>
                                    <td class="file-date">
                                        <?php echo date('d.m.Y H:i', $file['modified']); ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-active">Активен</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
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