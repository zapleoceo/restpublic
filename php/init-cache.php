<?php
/**
 * Скрипт для инициализации кэша меню
 * Запускается один раз для заполнения MongoDB кэша
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/classes/MenuCache.php';

echo "🔄 Инициализация кэша меню...\n";

try {
    $menuCache = new MenuCache();
    
    // Проверяем, есть ли уже кэш
    $existingMenu = $menuCache->getMenu();
    if ($existingMenu && !empty($existingMenu['categories'])) {
        echo "✅ Кэш уже существует с " . count($existingMenu['categories']) . " категориями\n";
        echo "📅 Последнее обновление: " . ($existingMenu['updated_at'] ?? 'неизвестно') . "\n";
        
        // Обновляем кэш принудительно
        echo "🔄 Принудительное обновление кэша...\n";
        
        // Запускаем обновление через API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://northrepublic.me:3002/api/cache/update-menu');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Кэш успешно обновлен\n";
            
            // Проверяем обновленный кэш
            $updatedMenu = $menuCache->getMenu();
            if ($updatedMenu) {
                echo "📊 Обновленный кэш содержит:\n";
                echo "   - Категории: " . count($updatedMenu['categories']) . "\n";
                echo "   - Продукты: " . count($updatedMenu['products']) . "\n";
            }
        } else {
            echo "❌ Ошибка обновления кэша. HTTP код: " . $httpCode . "\n";
        }
    } else {
        echo "📝 Кэш пуст, создаем новый...\n";
        
        // Запускаем обновление через API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://northrepublic.me:3002/api/cache/update-menu');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ Кэш успешно создан\n";
            
            // Проверяем созданный кэш
            $newMenu = $menuCache->getMenu();
            if ($newMenu) {
                echo "📊 Созданный кэш содержит:\n";
                echo "   - Категории: " . count($newMenu['categories']) . "\n";
                echo "   - Продукты: " . count($newMenu['products']) . "\n";
            }
        } else {
            echo "❌ Ошибка создания кэша. HTTP код: " . $httpCode . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "🔍 Проверьте:\n";
    echo "   1. MongoDB запущен на порту 27017\n";
    echo "   2. Backend API доступен на порту 3002\n";
    echo "   3. Poster API токен настроен\n";
}

echo "\n🎉 Инициализация завершена!\n";
?>
