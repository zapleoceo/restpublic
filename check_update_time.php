<?php
require_once 'vendor/autoload.php';
require_once 'classes/MenuCache.php';

try {
    $menuCache = new MenuCache();
    $time = $menuCache->getLastUpdateTimeFormatted();
    echo "Last update time: " . ($time ?: 'No data') . PHP_EOL;
    
    // Также проверим сырые данные
    $rawTime = $menuCache->getLastUpdateTime();
    echo "Raw time: " . ($rawTime ? $rawTime->format('Y-m-d H:i:s') : 'No data') . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
