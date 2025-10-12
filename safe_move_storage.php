<?php
/**
 * Безопасный скрипт для перемещения остатков со склада 1 на склад 3
 * С предварительной проверкой и подтверждением
 * 
 * Использование:
 * php safe_move_storage.php [--dry-run] [--force]
 * 
 * Опции:
 * --dry-run  - только показать что будет перемещено, не выполнять
 * --force    - выполнить без подтверждения
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

// Парсим аргументы командной строки
$options = getopt('', ['dry-run', 'force']);
$is_dry_run = isset($options['dry-run']);
$is_force = isset($options['force']);

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
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
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

// Функция для получения подтверждения пользователя
function askConfirmation($message) {
    echo "\n$message (y/N): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return trim(strtolower($line)) === 'y';
}

try {
    if ($is_dry_run) {
        logMessage("🔍 РЕЖИМ ПРЕДВАРИТЕЛЬНОГО ПРОСМОТРА (dry-run)");
    } else {
        logMessage("🚀 Безопасное перемещение остатков со склада $from_storage_id на склад $to_storage_id");
    }
    
    // Шаг 1: Получаем список складов
    logMessage("📋 Получаем список складов...");
    $storages_url = $api_base . '/storage.getStorages?token=' . $api_token;
    $storages_data = makeApiRequest($storages_url);
    
    if (!isset($storages_data['response'])) {
        throw new Exception("Не удалось получить список складов");
    }
    
    $storages = $storages_data['response'];
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
    
    logMessage("✅ Склад источник: " . $from_storage['storage_name'] . " (ID: $from_storage_id)");
    logMessage("✅ Склад назначения: " . $to_storage['storage_name'] . " (ID: $to_storage_id)");
    
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
    $total_quantity = 0;
    $total_value = 0;
    
    foreach ($leftovers as $item) {
        if (isset($item['storage_ingredient_left']) && $item['storage_ingredient_left'] > 0) {
            $quantity = $item['storage_ingredient_left'];
            $prime_cost = $item['prime_cost'] ?? 0;
            $value = $quantity * $prime_cost;
            
            $positive_leftovers[] = [
                'ingredient_id' => $item['ingredient_id'],
                'ingredient_name' => $item['ingredient_name'],
                'quantity' => $quantity,
                'unit' => $item['ingredient_unit'],
                'type' => $item['ingredients_type'] ?? 4,
                'prime_cost' => $prime_cost,
                'value' => $value
            ];
            
            $total_quantity += $quantity;
            $total_value += $value;
        }
    }
    
    logMessage("✅ Найдено позиций с положительными остатками: " . count($positive_leftovers));
    
    if (empty($positive_leftovers)) {
        logMessage("ℹ️ Нет позиций для перемещения");
        exit(0);
    }
    
    // Шаг 4: Показываем детальную информацию
    logMessage("\n📋 ДЕТАЛЬНАЯ ИНФОРМАЦИЯ О ПЕРЕМЕЩЕНИИ:");
    logMessage("═══════════════════════════════════════════════════════════");
    
    // Группируем по типам
    $by_type = [];
    foreach ($positive_leftovers as $item) {
        $type = $item['type'];
        if (!isset($by_type[$type])) {
            $by_type[$type] = ['items' => [], 'count' => 0, 'quantity' => 0, 'value' => 0];
        }
        
        $by_type[$type]['items'][] = $item;
        $by_type[$type]['count']++;
        $by_type[$type]['quantity'] += $item['quantity'];
        $by_type[$type]['value'] += $item['value'];
    }
    
    foreach ($by_type as $type => $data) {
        $type_name = match($type) {
            1 => 'Товары',
            4 => 'Ингредиенты', 
            5 => 'Модификаторы',
            default => "Тип $type"
        };
        
        logMessage("\n📁 $type_name ({$data['count']} позиций):");
        
        // Сортируем по количеству (по убыванию)
        usort($data['items'], function($a, $b) {
            return $b['quantity'] <=> $a['quantity'];
        });
        
        foreach ($data['items'] as $item) {
            $value_str = $item['value'] > 0 ? " (стоимость: " . number_format($item['value'], 0, ',', ' ') . " VND)" : "";
            logMessage("  • {$item['ingredient_name']}: {$item['quantity']} {$item['unit']}$value_str");
        }
        
        logMessage("  📊 Итого по типу: {$data['quantity']} единиц, стоимость: " . number_format($data['value'], 0, ',', ' ') . " VND");
    }
    
    logMessage("\n📊 ОБЩАЯ СТАТИСТИКА:");
    logMessage("  • Всего позиций: " . count($positive_leftovers));
    logMessage("  • Общее количество: $total_quantity единиц");
    logMessage("  • Общая стоимость: " . number_format($total_value, 0, ',', ' ') . " VND");
    logMessage("  • Склад источник: {$from_storage['storage_name']}");
    logMessage("  • Склад назначения: {$to_storage['storage_name']}");
    
    // Шаг 5: Подтверждение (если не dry-run и не force)
    if (!$is_dry_run && !$is_force) {
        logMessage("\n⚠️  ВНИМАНИЕ: Это действие переместит ВСЕ положительные остатки!");
        logMessage("   После выполнения остатки на складе $from_storage_id будут обнулены.");
        
        if (!askConfirmation("Продолжить перемещение?")) {
            logMessage("❌ Операция отменена пользователем");
            exit(0);
        }
    }
    
    if ($is_dry_run) {
        logMessage("\n🔍 РЕЖИМ ПРЕДВАРИТЕЛЬНОГО ПРОСМОТРА ЗАВЕРШЕН");
        logMessage("   Для выполнения перемещения запустите скрипт без --dry-run");
        exit(0);
    }
    
    // Шаг 6: Создаем перемещение
    logMessage("\n🔄 Создаем перемещение...");
    
    // Подготавливаем данные в правильном формате для Poster API
    $moving_data = [
        'date' => date('Y-m-d H:i:s'),
        'from_storage' => $from_storage_id,
        'to_storage' => $to_storage_id
    ];
    
    // Добавляем ингредиенты в правильном формате для Poster API
    foreach ($positive_leftovers as $index => $item) {
        $moving_data["ingredients[{$index}][ingredient_id]"] = $item['ingredient_id'];
        $moving_data["ingredients[{$index}][amount]"] = $item['quantity'];
        $moving_data["ingredients[{$index}][type]"] = $item['type'];
    }
    
    // Отправляем запрос на создание перемещения
    $create_moving_url = $api_base . '/storage.createMoving?token=' . $api_token;
    $moving_result = makeApiRequest($create_moving_url, 'POST', $moving_data);
    
    if (isset($moving_result['success']) && $moving_result['success'] == 1) {
        $moving_id = $moving_result['response'];
        logMessage("✅ Перемещение успешно создано! ID: $moving_id");
        
        logMessage("\n🎉 ПЕРЕМЕЩЕНИЕ ЗАВЕРШЕНО УСПЕШНО!");
        logMessage("═══════════════════════════════════════════════════════════");
        logMessage("📋 ID перемещения: $moving_id");
        logMessage("📅 Дата: " . date('Y-m-d H:i:s'));
        logMessage("📦 Позиций перемещено: " . count($positive_leftovers));
        logMessage("💰 Общая стоимость: " . number_format($total_value, 0, ',', ' ') . " VND");
        logMessage("🏪 Склад источник: {$from_storage['storage_name']} (ID: $from_storage_id)");
        logMessage("🏪 Склад назначения: {$to_storage['storage_name']} (ID: $to_storage_id)");
        
    } else {
        throw new Exception("Ошибка при создании перемещения: " . json_encode($moving_result));
    }
    
} catch (Exception $e) {
    logMessage("❌ Ошибка: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
