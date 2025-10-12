<?php
/**
 * Скрипт для перемещения всех положительных остатков со склада 1 на склад 3
 * 
 * Использование:
 * php move_storage_leftovers.php
 * 
 * Требования:
 * - Доступ к Poster API
 * - PHP с поддержкой cURL
 * - Настроенные переменные окружения
 */

// Загружаем переменные окружения
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Настройки API
$api_token = $_ENV['POSTER_API_TOKEN'] ?? '922371:489411264005b482039f38b8ee21f6fb';
$api_base = 'https://joinposter.com/api';

// Настройки складов
$from_storage_id = 1; // Склад источник
$to_storage_id = 3;   // Склад назначения

// Функция для логирования
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

// Функция для отправки HTTP запросов
function makeApiRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception("CURL Error: " . $curl_error);
    }
    
    if ($http_code !== 200) {
        throw new Exception("HTTP Error: " . $http_code . " Response: " . $response);
    }
    
    return json_decode($response, true);
}

try {
    logMessage("🚀 Начинаем процесс перемещения остатков со склада $from_storage_id на склад $to_storage_id");
    
    // Шаг 1: Получаем список складов
    logMessage("📋 Получаем список складов...");
    $storages_url = $api_base . '/storage.getStorages?token=' . $api_token;
    $storages_data = makeApiRequest($storages_url);
    
    if (!isset($storages_data['response'])) {
        throw new Exception("Не удалось получить список складов");
    }
    
    $storages = $storages_data['response'];
    logMessage("✅ Найдено складов: " . count($storages));
    
    // Проверяем существование складов
    $from_storage = null;
    $to_storage = null;
    
    foreach ($storages as $storage) {
        if ($storage['storage_id'] == $from_storage_id) {
            $from_storage = $storage;
        }
        if ($storage['storage_id'] == $to_storage_id) {
            $to_storage = $storage;
        }
    }
    
    if (!$from_storage) {
        throw new Exception("Склад $from_storage_id не найден");
    }
    if (!$to_storage) {
        throw new Exception("Склад $to_storage_id не найден");
    }
    
    logMessage("✅ Склад источник: " . $from_storage['storage_name']);
    logMessage("✅ Склад назначения: " . $to_storage['storage_name']);
    
    // Шаг 2: Получаем остатки на складе источнике
    logMessage("📦 Получаем остатки на складе $from_storage_id...");
    $leftovers_url = $api_base . '/storage.getStorageLeftovers?token=' . $api_token . '&storage_id=' . $from_storage_id;
    $leftovers_data = makeApiRequest($leftovers_url);
    
    if (!isset($leftovers_data['response'])) {
        throw new Exception("Не удалось получить остатки на складе");
    }
    
    $leftovers = $leftovers_data['response'];
    logMessage("✅ Найдено позиций на складе: " . count($leftovers));
    
    // Шаг 3: Фильтруем позиции с положительными остатками
    $positive_leftovers = [];
    foreach ($leftovers as $item) {
        if (isset($item['storage_ingredient_left']) && $item['storage_ingredient_left'] > 0) {
            $positive_leftovers[] = [
                'ingredient_id' => $item['ingredient_id'],
                'ingredient_name' => $item['ingredient_name'],
                'quantity' => $item['storage_ingredient_left'],
                'unit' => $item['ingredient_unit'],
                'type' => $item['ingredients_type'] ?? 4, // По умолчанию ингредиент
                'prime_cost' => $item['prime_cost'] ?? 0
            ];
        }
    }
    
    logMessage("✅ Найдено позиций с положительными остатками: " . count($positive_leftovers));
    
    if (empty($positive_leftovers)) {
        logMessage("ℹ️ Нет позиций для перемещения");
        exit(0);
    }
    
    // Выводим список позиций для перемещения
    logMessage("📋 Позиции для перемещения:");
    foreach ($positive_leftovers as $item) {
        logMessage("  - {$item['ingredient_name']}: {$item['quantity']} {$item['unit']} (ID: {$item['ingredient_id']})");
    }
    
    // Шаг 4: Создаем перемещение
    logMessage("🔄 Создаем перемещение...");
    
    // Подготавливаем данные для создания перемещения
    $moving_data = [
        'date' => date('Y-m-d H:i:s'),
        'from_storage' => $from_storage_id,
        'to_storage' => $to_storage_id
    ];
    
    // Добавляем ингредиенты
    $ingredients = [];
    foreach ($positive_leftovers as $index => $item) {
        $ingredients[] = [
            'id' => $item['ingredient_id'],
            'type' => $item['type'],
            'num' => $item['quantity']
        ];
    }
    
    $moving_data['ingredient'] = $ingredients;
    
    // Отправляем запрос на создание перемещения
    $create_moving_url = $api_base . '/storage.createMoving?token=' . $api_token;
    $moving_result = makeApiRequest($create_moving_url, 'POST', $moving_data);
    
    if (isset($moving_result['success']) && $moving_result['success'] == 1) {
        $moving_id = $moving_result['response'];
        logMessage("✅ Перемещение успешно создано! ID: $moving_id");
        
        // Дополнительная информация
        logMessage("📊 Статистика перемещения:");
        logMessage("  - Количество позиций: " . count($positive_leftovers));
        logMessage("  - Склад источник: {$from_storage['storage_name']} (ID: $from_storage_id)");
        logMessage("  - Склад назначения: {$to_storage['storage_name']} (ID: $to_storage_id)");
        logMessage("  - Дата перемещения: " . date('Y-m-d H:i:s'));
        
    } else {
        throw new Exception("Ошибка при создании перемещения: " . json_encode($moving_result));
    }
    
    logMessage("🎉 Процесс перемещения завершен успешно!");
    
} catch (Exception $e) {
    logMessage("❌ Ошибка: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
