<?php
// Создание дефолтной картинки для событий с блюром
$sourceImage = 'images/logo.png';
$outputImage = 'images/event-default.jpg';

// Проверяем, существует ли исходное изображение
if (!file_exists($sourceImage)) {
    echo "❌ Исходное изображение не найдено: $sourceImage\n";
    exit;
}

// Создаем изображение из исходного файла
$imageInfo = getimagesize($sourceImage);
if (!$imageInfo) {
    echo "❌ Не удалось прочитать изображение\n";
    exit;
}

$mimeType = $imageInfo['mime'];
$sourceImageResource = null;

switch ($mimeType) {
    case 'image/jpeg':
        $sourceImageResource = imagecreatefromjpeg($sourceImage);
        break;
    case 'image/png':
        $sourceImageResource = imagecreatefrompng($sourceImage);
        break;
    default:
        echo "❌ Неподдерживаемый формат изображения\n";
        exit;
}

if (!$sourceImageResource) {
    echo "❌ Не удалось создать ресурс изображения\n";
    exit;
}

// Получаем размеры исходного изображения
$sourceWidth = imagesx($sourceImageResource);
$sourceHeight = imagesy($sourceImageResource);

// Создаем новое изображение с блюром
$blurredImage = imagecreatetruecolor($sourceWidth, $sourceHeight);

// Применяем блюр эффект
for ($i = 0; $i < 3; $i++) {
    imagefilter($sourceImageResource, IMG_FILTER_GAUSSIAN_BLUR);
}

// Копируем размытое изображение
imagecopy($blurredImage, $sourceImageResource, 0, 0, 0, 0, $sourceWidth, $sourceHeight);

// Добавляем полупрозрачный оверлей
$overlay = imagecreatetruecolor($sourceWidth, $sourceHeight);
$overlayColor = imagecolorallocatealpha($overlay, 0, 0, 0, 50); // Полупрозрачный черный
imagefill($overlay, 0, 0, $overlayColor);
imagecopy($blurredImage, $overlay, 0, 0, 0, 0, $sourceWidth, $sourceHeight);

// Сохраняем результат
if (imagejpeg($blurredImage, $outputImage, 80)) {
    echo "✅ Дефолтная картинка для событий создана: $outputImage\n";
    echo "📊 Размер: {$sourceWidth}x{$sourceHeight}\n";
    echo "🎨 Применен блюр эффект\n";
} else {
    echo "❌ Ошибка при сохранении изображения\n";
}

// Освобождаем память
imagedestroy($sourceImageResource);
imagedestroy($blurredImage);
imagedestroy($overlay);
?>
