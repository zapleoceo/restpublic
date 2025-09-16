<?php
// Тестовый скрипт для проверки загрузки изображений
require_once 'vendor/autoload.php';

echo "=== Тест загрузки изображений ===\n";

// Проверяем настройки PHP для загрузки файлов
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'enabled' : 'disabled') . "\n";

// Проверяем папку для загрузки
$uploadDir = __DIR__ . '/images/events/';
echo "Upload directory: $uploadDir\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'YES' : 'NO') . "\n";

if (is_dir($uploadDir)) {
    echo "Directory permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
    echo "Directory contents:\n";
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
}

// Проверяем временную папку
$tmpDir = sys_get_temp_dir();
echo "Temp directory: $tmpDir\n";
echo "Temp directory writable: " . (is_writable($tmpDir) ? 'YES' : 'NO') . "\n";

echo "\n=== Тест завершен ===\n";
?>
