<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $tablesCollection = $db->tables;
    
    echo "Подключение к MongoDB: $mongodbUrl\n";
    echo "База данных: $dbName\n";
    echo "Коллекция: tables\n\n";
    
    // Проверяем существующие столы
    $existingTables = $tablesCollection->find()->toArray();
    echo "Существующих столов: " . count($existingTables) . "\n";
    
    if (count($existingTables) > 0) {
        echo "Столы уже существуют:\n";
        foreach ($existingTables as $table) {
            echo "- " . ($table['name'] ?? $table['table_name'] ?? 'Без названия') . " (ID: " . ($table['table_id'] ?? $table['_id']) . ")\n";
        }
        echo "\n";
    }
    
    // Добавляем столы если их нет
    if (count($existingTables) == 0) {
        echo "Добавляем столы...\n";
        
        $tables = [
            ['table_id' => '1', 'name' => 'Стол у окна', 'capacity' => 2, 'status' => 'available'],
            ['table_id' => '2', 'name' => 'Стол в центре', 'capacity' => 4, 'status' => 'available'],
            ['table_id' => '3', 'name' => 'Стол у входа', 'capacity' => 2, 'status' => 'available'],
            ['table_id' => '4', 'name' => 'Стол VIP', 'capacity' => 6, 'status' => 'available'],
            ['table_id' => '5', 'name' => 'Стол на террасе', 'capacity' => 4, 'status' => 'available'],
            ['table_id' => '6', 'name' => 'Стол для двоих', 'capacity' => 2, 'status' => 'available'],
            ['table_id' => '7', 'name' => 'Стол семейный', 'capacity' => 8, 'status' => 'available'],
            ['table_id' => '8', 'name' => 'Стол у бара', 'capacity' => 2, 'status' => 'available'],
            ['table_id' => '9', 'name' => 'Стол романтический', 'capacity' => 2, 'status' => 'available'],
            ['table_id' => '10', 'name' => 'Стол бизнес', 'capacity' => 4, 'status' => 'available']
        ];
        
        $result = $tablesCollection->insertMany($tables);
        echo "Добавлено столов: " . $result->getInsertedCount() . "\n";
        
        // Показываем добавленные столы
        echo "\nДобавленные столы:\n";
        foreach ($tables as $table) {
            echo "- " . $table['name'] . " (ID: " . $table['table_id'] . ", мест: " . $table['capacity'] . ")\n";
        }
    }
    
    // Проверяем итоговое количество
    $totalTables = $tablesCollection->countDocuments();
    echo "\nВсего столов в базе: $totalTables\n";
    
    echo "\n✅ Готово!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
