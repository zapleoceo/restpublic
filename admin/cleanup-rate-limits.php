<?php
/**
 * Скрипт для очистки старых записей rate limiting
 * Рекомендуется запускать по cron каждые 6 часов
 */

require_once __DIR__ . '/../classes/RateLimiter.php';

try {
    $rateLimiter = new RateLimiter();
    $deletedCount = $rateLimiter->cleanup(24); // Удаляем записи старше 24 часов
    
    echo "✅ Очищено {$deletedCount} старых записей rate limiting\n";
    
    // Логируем очистку
    error_log("Rate limiting cleanup: deleted {$deletedCount} old records");
    
} catch (Exception $e) {
    echo "❌ Ошибка очистки: " . $e->getMessage() . "\n";
    error_log("Rate limiting cleanup error: " . $e->getMessage());
}
?>
