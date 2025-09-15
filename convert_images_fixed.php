<?php
function convertToWebP($source, $destination, $quality = 80) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) return false;
    
    $mimeType = $imageInfo['mime'];
    switch ($mimeType) {
        case 'image/jpeg': $image = imagecreatefromjpeg($source); break;
        case 'image/png': $image = imagecreatefrompng($source); break;
        case 'image/gif': $image = imagecreatefromgif($source); break;
        default: return false;
    }
    
    if (!$image) return false;
    $result = imagewebp($image, $destination, $quality);
    imagedestroy($image);
    return $result;
}

echo "Начинаю конвертацию изображений...\n";
$converted = 0;
$savings = 0;

// Правильные пути к изображениям
$images = [
    '/var/www/northrepubli_usr/data/www/northrepublic.me/images/intro-pic-primary.jpg',
    '/var/www/northrepubli_usr/data/www/northrepublic.me/images/intro-pic-secondary.jpg', 
    '/var/www/northrepubli_usr/data/www/northrepublic.me/images/about-pic-primary.jpg',
    '/var/www/northrepubli_usr/data/www/northrepublic.me/images/logo.png'
];

foreach ($images as $image) {
    if (file_exists($image)) {
        $webp = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $image);
        if (!file_exists($webp)) {
            if (convertToWebP($image, $webp)) {
                $originalSize = filesize($image);
                $webpSize = filesize($webp);
                $saving = $originalSize - $webpSize;
                $savings += $saving;
                $percent = round(($saving / $originalSize) * 100, 1);
                echo "✅ " . basename($image) . " -> " . basename($webp) . " (экономия: {$percent}%)\n";
                $converted++;
            }
        } else {
            echo "⏭️  WebP уже существует: " . basename($webp) . "\n";
        }
    } else {
        echo "❌ Файл не найден: " . basename($image) . "\n";
    }
}

echo "\nКонвертировано: $converted изображений\n";
echo "Общая экономия: " . round($savings / 1024, 1) . " KB\n";
?>
