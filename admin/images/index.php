<?php
session_start();
require_once '../includes/auth-check.php';

$error = '';
$success = '';

// Директория для изображений
$imagesDir = __DIR__ . '/../../images';
$uploadDir = $imagesDir . '/uploads';

// Создаем директорию для загрузок если не существует
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Получаем список изображений
function getImagesList($dir) {
    $images = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = $dir . '/' . $file;
                if (is_file($filePath) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $images[] = [
                        'name' => $file,
                        'path' => $filePath,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'url' => '/images/' . basename($dir) . '/' . $file
                    ];
                }
            }
        }
    }
    return $images;
}

// Получаем изображения из разных папок
$mainImages = getImagesList($imagesDir);
$uploadImages = getImagesList($uploadDir);
$allImages = array_merge($mainImages, $uploadImages);

// Обработка загрузки файлов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadedFile = $_FILES['image'];
    
    // Проверка ошибок загрузки
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $error = 'Ошибка загрузки файла';
    } else {
        // Проверка типа файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($uploadedFile['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WebP';
        } else {
            // Проверка размера файла (максимум 5MB)
            if ($uploadedFile['size'] > 5 * 1024 * 1024) {
                $error = 'Файл слишком большой. Максимальный размер: 5MB';
            } else {
                // Генерируем уникальное имя файла
                $extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $extension;
                $targetPath = $uploadDir . '/' . $fileName;
                
                if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                    $success = 'Изображение успешно загружено';
                    // Обновляем список изображений
                    $allImages = array_merge($mainImages, getImagesList($uploadDir));
                } else {
                    $error = 'Ошибка сохранения файла';
                }
            }
        }
    }
}

// Обработка удаления файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $fileName = $_POST['file_name'] ?? '';
    $filePath = $uploadDir . '/' . $fileName;
    
    if (file_exists($filePath) && is_file($filePath)) {
        if (unlink($filePath)) {
            $success = 'Изображение удалено';
            // Обновляем список изображений
            $allImages = array_merge($mainImages, getImagesList($uploadDir));
        } else {
            $error = 'Ошибка удаления файла';
        }
    } else {
        $error = 'Файл не найден';
    }
}

// Функция для форматирования размера файла
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
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
    <title>Изображения - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .image-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 0.5rem;
        }
        
        .image-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .image-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .upload-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .upload-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
            padding: 0.75rem 1.5rem;
        }
        
        .btn-primary:hover {
            background: #005a87;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007cba;
        }
        
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Изображения</h1>
                <p>Управление изображениями сайта</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($allImages); ?></div>
                    <div class="stat-label">Всего изображений</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($mainImages); ?></div>
                    <div class="stat-label">Основные изображения</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($uploadImages); ?></div>
                    <div class="stat-label">Загруженные</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatFileSize(array_sum(array_column($allImages, 'size'))); ?></div>
                    <div class="stat-label">Общий размер</div>
                </div>
            </div>
            
            <!-- Загрузка -->
            <div class="upload-section">
                <h3>📤 Загрузить изображение</h3>
                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="image">Выберите файл</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Загрузить</button>
                </form>
                <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                    Поддерживаемые форматы: JPG, PNG, GIF, WebP. Максимальный размер: 5MB
                </p>
            </div>
            
            <!-- Список изображений -->
            <div class="images-grid">
                <?php if (empty($allImages)): ?>
                    <div class="alert alert-info">
                        <strong>Нет изображений</strong><br>
                        Загрузите первое изображение, чтобы начать работу.
                    </div>
                <?php else: ?>
                    <?php foreach ($allImages as $image): ?>
                        <div class="image-card">
                            <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['name']); ?>" 
                                 class="image-preview"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                            <div class="image-info">
                                <strong><?php echo htmlspecialchars($image['name']); ?></strong><br>
                                <?php echo formatFileSize($image['size']); ?><br>
                                <?php echo date('d.m.Y H:i', $image['modified']); ?>
                            </div>
                            <div class="image-actions">
                                <a href="<?php echo htmlspecialchars($image['url']); ?>" 
                                   target="_blank" class="btn btn-info">👁️ Просмотр</a>
                                <?php if (strpos($image['path'], '/uploads/') !== false): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($image['name']); ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Удалить изображение?')">🗑️ Удалить</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>