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

echo "=== ПРОВЕРКА ОЧИЩЕННОЙ MONGODB ===\n\n";

$users = $usersCollection->find([])->toArray();
echo "Всего записей: " . count($users) . "\n\n";

foreach ($users as $index => $user) {
    echo "Запись " . ($index + 1) . ":\n";
    echo "  _id: " . $user['_id'] . "\n";
    echo "  client_id: " . ($user['client_id'] ?? 'НЕТ') . "\n";
    echo "  Поля: " . implode(', ', array_keys($user->toArray())) . "\n";
    echo "\n";
}

echo "✅ MongoDB успешно очищена!\n";
?>
