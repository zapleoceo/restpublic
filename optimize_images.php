<?php
/**
 * Скрипт для оптимизации изображений - конвертация в WebP
 */

function convertToWebP($source, $destination, $quality = 80) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    
    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            echo "Неподдерживаемый формат: $mimeType\n";
            return false;
    }
    
    if (!$image) {
        echo "Ошибка создания изображения из $source\n";
        return false;
    }
    
    // Конвертируем в WebP
    $result = imagewebp($image, $destination, $quality);
    imagedestroy($image);
    
    if ($result) {
        $originalSize = filesize($source);
        $webpSize = filesize($destination);
        $savings = round((($originalSize - $webpSize) / $originalSize) * 100, 2);
        echo "✅ $source -> $destination (экономия: {$savings}%)\n";
        return true;
    } else {
        echo "❌ Ошибка конвертации $source\n";
        return false;
    }
}

function optimizeImages($directory) {
    $extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $totalSavings = 0;
    $totalOriginalSize = 0;
    $convertedCount = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $extension = strtolower($file->getExtension());
            
            if (in_array($extension, $extensions)) {
                $sourcePath = $file->getPathname();
                $webpPath = str_replace(['.jpg', '.jpeg', '.png', '.gif'], '.webp', $sourcePath);
                
                // Пропускаем если WebP уже существует
                if (file_exists($webpPath)) {
                    echo "⏭️  WebP уже существует: $webpPath\n";
                    continue;
                }
                
                echo "🔄 Конвертирую: " . basename($sourcePath) . "\n";
                
                if (convertToWebP($sourcePath, $webpPath)) {
                    $convertedCount++;
                    $totalOriginalSize += filesize($sourcePath);
                    $totalSavings += (filesize($sourcePath) - filesize($webpPath));
                }
            }
        }
    }
    
    echo "\n📊 Итоговая статистика:\n";
    echo "Конвертировано изображений: $convertedCount\n";
    echo "Общий размер оригиналов: " . formatBytes($totalOriginalSize) . "\n";
    echo "Общая экономия: " . formatBytes($totalSavings) . "\n";
    echo "Процент экономии: " . round(($totalSavings / $totalOriginalSize) * 100, 2) . "%\n";
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

// Проверяем поддержку WebP
if (!function_exists('imagewebp')) {
    echo "❌ WebP не поддерживается в этой версии PHP\n";
    echo "Установите php-gd с поддержкой WebP\n";
    exit(1);
}

echo "🚀 Начинаю оптимизацию изображений...\n\n";

// Оптимизируем изображения в папке images
$imagesDir = __DIR__ . '/images';
if (is_dir($imagesDir)) {
    echo "📁 Обрабатываю папку: $imagesDir\n";
    optimizeImages($imagesDir);
} else {
    echo "❌ Папка images не найдена\n";
}

echo "\n✅ Оптимизация завершена!\n";
?>
