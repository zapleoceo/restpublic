<?php
require_once 'vendor/autoload.php';
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
$client = new MongoDB\Client($mongodbUrl);
$dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
$database = $client->selectDatabase($dbName);
$usersCollection = $database->selectCollection('users');

echo "=== ОЧИСТКА MONGODB ОТ ЛИЧНЫХ ДАННЫХ ===\n\n";

// Получаем все записи до очистки
$allUsers = $usersCollection->find([])->toArray();
echo "Всего записей в MongoDB: " . count($allUsers) . "\n\n";

// Показываем текущие данные
echo "=== ТЕКУЩИЕ ДАННЫЕ ===\n";
foreach ($allUsers as $index => $user) {
    echo "Запись " . ($index + 1) . ":\n";
    echo "  _id: " . $user['_id'] . "\n";
    echo "  client_id: " . ($user['client_id'] ?? 'НЕТ') . "\n";
    echo "  phone: " . ($user['phone'] ?? 'НЕТ') . "\n";
    echo "  name: " . ($user['name'] ?? 'НЕТ') . "\n";
    echo "  lastName: " . ($user['lastName'] ?? 'НЕТ') . "\n";
    echo "  email: " . ($user['email'] ?? 'НЕТ') . "\n";
    echo "  updatedAt: " . (isset($user['updatedAt']) ? $user['updatedAt']->toDateTime()->format('Y-m-d H:i:s') : 'НЕТ') . "\n";
    echo "\n";
}

// Подтверждение
echo "ВНИМАНИЕ! Это действие удалит все личные данные из MongoDB.\n";
echo "Останутся только _id и client_id.\n";
echo "Продолжить? (y/N): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Операция отменена.\n";
    exit(0);
}

echo "\n=== НАЧИНАЕМ ОЧИСТКУ ===\n";

$cleanedCount = 0;
$errorCount = 0;

foreach ($allUsers as $user) {
    try {
        $userId = $user['_id'];
        $clientId = $user['client_id'] ?? null;
        
        if (!$clientId) {
            echo "⚠️ Пропускаем запись без client_id: " . $userId . "\n";
            continue;
        }
        
        // Создаем новую запись только с необходимыми полями
        $cleanUserData = [
            '_id' => $userId,
            'client_id' => $clientId
        ];
        
        // Заменяем запись
        $result = $usersCollection->replaceOne(
            ['_id' => $userId],
            $cleanUserData
        );
        
        if ($result->getModifiedCount() > 0) {
            echo "✅ Очищена запись: " . $userId . " (client_id: " . $clientId . ")\n";
            $cleanedCount++;
        } else {
            echo "⚠️ Запись не изменилась: " . $userId . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Ошибка при очистке записи " . $userId . ": " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo "\n=== РЕЗУЛЬТАТ ===\n";
echo "Очищено записей: $cleanedCount\n";
echo "Ошибок: $errorCount\n";

// Показываем результат
echo "\n=== ПРОВЕРКА РЕЗУЛЬТАТА ===\n";
$cleanedUsers = $usersCollection->find([])->toArray();
foreach ($cleanedUsers as $index => $user) {
    echo "Запись " . ($index + 1) . ":\n";
    echo "  _id: " . $user['_id'] . "\n";
    echo "  client_id: " . ($user['client_id'] ?? 'НЕТ') . "\n";
    echo "  Поля: " . implode(', ', array_keys($user->toArray())) . "\n";
    echo "\n";
}

echo "✅ Очистка MongoDB завершена!\n";
?>
