<?php
// Скрипт для обновления кэша меню
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/php/classes/MenuCache.php';

echo "Обновление кэша меню...\n";

try {
    $menuCache = new MenuCache();
    
    // Получаем данные из API
    $api_url = 'http://127.0.0.1:3002/api/menu';
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = file_get_contents($api_url, false, $context);
    if ($response === false) {
        throw new Exception("Не удалось получить данные из API");
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception("Некорректные данные от API");
    }
    
    // Сохраняем в кэш
    $result = $menuCache->updateCache($data);
    
    if ($result) {
        echo "✅ Кэш успешно обновлен!\n";
        echo "Категории: " . count($data['categories']) . "\n";
        echo "Продукты: " . count($data['products']) . "\n";
    } else {
        echo "❌ Ошибка при сохранении кэша\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
