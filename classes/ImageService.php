<?php

require_once __DIR__ . '/../vendor/autoload.php';

class ImageService {
    private $database;
    private $bucket;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $client = new MongoDB\Client($_ENV['MONGODB_URI']);
            $this->database = $client->selectDatabase($_ENV['MONGODB_DATABASE']);
            $this->bucket = $this->database->selectGridFSBucket(['bucketName' => 'event_images']);
        } catch (Exception $e) {
            error_log("ImageService connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Сохранить изображение в GridFS
     */
    public function saveImage($fileData, $filename, $metadata = []) {
        try {
            // Генерируем уникальное имя файла
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uniqueFilename = 'event_' . time() . '_' . uniqid() . '.' . $extension;
            
            // Добавляем метаданные
            $metadata = array_merge($metadata, [
                'original_name' => $filename,
                'uploaded_at' => new MongoDB\BSON\UTCDateTime(),
                'content_type' => $this->getContentType($extension)
            ]);
            
            // Сохраняем в GridFS
            $fileId = $this->bucket->uploadFromStream($uniqueFilename, $fileData, [
                'metadata' => $metadata
            ]);
            
            return [
                'file_id' => (string)$fileId,
                'filename' => $uniqueFilename,
                'content_type' => $metadata['content_type']
            ];
            
        } catch (Exception $e) {
            error_log("ImageService save error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Получить изображение из GridFS
     */
    public function getImage($fileId) {
        try {
            $stream = $this->bucket->openDownloadStream(new MongoDB\BSON\ObjectId($fileId));
            $content = stream_get_contents($stream);
            fclose($stream);
            
            return $content;
        } catch (Exception $e) {
            error_log("ImageService get error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получить метаданные изображения
     */
    public function getImageMetadata($fileId) {
        try {
            $file = $this->bucket->findOne(['_id' => new MongoDB\BSON\ObjectId($fileId)]);
            return $file ? $file : null;
        } catch (Exception $e) {
            error_log("ImageService metadata error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Удалить изображение из GridFS
     */
    public function deleteImage($fileId) {
        try {
            $this->bucket->delete(new MongoDB\BSON\ObjectId($fileId));
            return true;
        } catch (Exception $e) {
            error_log("ImageService delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить URL для отображения изображения
     */
    public function getImageUrl($fileId) {
        return "/api/image.php?id=" . $fileId;
    }
    
    /**
     * Определить content-type по расширению
     */
    private function getContentType($extension) {
        $types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        return $types[strtolower($extension)] ?? 'application/octet-stream';
    }
    
    /**
     * Валидировать файл изображения
     */
    public function validateImage($fileInfo) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($fileInfo['type'], $allowedTypes)) {
            return ['valid' => false, 'error' => 'Неподдерживаемый тип файла'];
        }
        
        if ($fileInfo['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'Файл слишком большой (максимум 5MB)'];
        }
        
        return ['valid' => true];
    }
}
