<?php
/**
 * Скрипт для проверки остатков на складах
 * 
 * Использование:
 * php check_storage_leftovers.php [storage_id]
 * 
 * Если storage_id не указан, проверяет все склады
 */

// Загружаем переменные окружения
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Настройки API
$api_token = $_ENV['POSTER_API_TOKEN'] ?? '922371:489411264005b482039f38b8ee21f6fb';
$api_base = 'https://joinposter.com/api';

// Получаем ID склада из аргументов командной строки
$storage_id = $argv[1] ?? null;

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
    logMessage("🔍 Проверяем остатки на складах");
    
    // Получаем список складов
    $storages_url = $api_base . '/storage.getStorages?token=' . $api_token;
    $storages_data = makeApiRequest($storages_url);
    
    if (!isset($storages_data['response'])) {
        throw new Exception("Не удалось получить список складов");
    }
    
    $storages = $storages_data['response'];
    logMessage("✅ Найдено складов: " . count($storages));
    
    // Если указан конкретный склад, проверяем только его
    if ($storage_id) {
        $storages = array_filter($storages, function($storage) use ($storage_id) {
            return $storage['storage_id'] == $storage_id;
        });
        
        if (empty($storages)) {
            throw new Exception("Склад с ID $storage_id не найден");
        }
    }
    
    $total_positive_items = 0;
    $total_items = 0;
    
    foreach ($storages as $storage) {
        $storage_id = $storage['storage_id'];
        $storage_name = $storage['storage_name'];
        
        logMessage("\n📦 Склад: $storage_name (ID: $storage_id)");
        
        // Получаем остатки на складе
        $leftovers_url = $api_base . '/storage.getStorageLeftovers?token=' . $api_token . '&storage_id=' . $storage_id;
        $leftovers_data = makeApiRequest($leftovers_url);
        
        if (!isset($leftovers_data['response'])) {
            logMessage("❌ Не удалось получить остатки на складе $storage_name", 'ERROR');
            continue;
        }
        
        $leftovers = $leftovers_data['response'];
        $positive_items = 0;
        $zero_items = 0;
        
        logMessage("📋 Всего позиций на складе: " . count($leftovers));
        
        // Группируем по типам
        $by_type = [];
        foreach ($leftovers as $item) {
            $quantity = $item['storage_ingredient_left'] ?? 0;
            $type = $item['ingredients_type'] ?? 'unknown';
            
            if (!isset($by_type[$type])) {
                $by_type[$type] = ['positive' => 0, 'zero' => 0, 'items' => []];
            }
            
            if ($quantity > 0) {
                $positive_items++;
                $by_type[$type]['positive']++;
                $by_type[$type]['items'][] = [
                    'name' => $item['ingredient_name'],
                    'quantity' => $quantity,
                    'unit' => $item['ingredient_unit']
                ];
            } else {
                $zero_items++;
                $by_type[$type]['zero']++;
            }
        }
        
        $total_positive_items += $positive_items;
        $total_items += count($leftovers);
        
        logMessage("✅ Позиций с остатками: $positive_items");
        logMessage("⚪ Позиций без остатков: $zero_items");
        
        // Показываем детали по типам
        foreach ($by_type as $type => $data) {
            if ($data['positive'] > 0) {
                $type_name = match($type) {
                    1 => 'Товары',
                    4 => 'Ингредиенты',
                    5 => 'Модификаторы',
                    default => "Тип $type"
                };
                
                logMessage("  📁 $type_name: {$data['positive']} позиций с остатками");
                
                // Показываем топ-5 позиций с наибольшими остатками
                usort($data['items'], function($a, $b) {
                    return $b['quantity'] <=> $a['quantity'];
                });
                
                $top_items = array_slice($data['items'], 0, 5);
                foreach ($top_items as $item) {
                    logMessage("    - {$item['name']}: {$item['quantity']} {$item['unit']}");
                }
            }
        }
    }
    
    // Общая статистика
    logMessage("\n📊 Общая статистика:");
    logMessage("  - Всего складов: " . count($storages));
    logMessage("  - Всего позиций: $total_items");
    logMessage("  - Позиций с остатками: $total_positive_items");
    logMessage("  - Позиций без остатков: " . ($total_items - $total_positive_items));
    
    if ($total_positive_items > 0) {
        logMessage("\n💡 Для перемещения остатков со склада 1 на склад 3 запустите:");
        logMessage("   php move_storage_leftovers.php");
    } else {
        logMessage("\nℹ️ Нет позиций с положительными остатками для перемещения");
    }
    
} catch (Exception $e) {
    logMessage("❌ Ошибка: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
