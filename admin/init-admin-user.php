<?php
/**
 * Скрипт для создания первого администратора
 * Запустить один раз для инициализации пользователя
 */

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $usersCollection = $db->admin_users;
    
    // Проверяем, есть ли уже пользователи
    $existingUsers = $usersCollection->countDocuments();
    
    if ($existingUsers > 0) {
        echo "❌ Пользователи уже существуют в системе.\n";
        echo "Используйте существующие учетные данные или обратитесь к администратору.\n";
        exit(1);
    }
    
    // Создаем первого администратора
    $username = 'admin';
    $password = '1q2w#E$R'; // Пароль по требованию
    
    $userData = [
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'email' => 'admin@northrepublic.me',
        'role' => 'super_admin',
        'active' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'last_login' => null,
        'login_attempts' => 0,
        'locked_until' => null
    ];
    
    $result = $usersCollection->insertOne($userData);
    
    if ($result->getInsertedId()) {
        echo "✅ Администратор успешно создан!\n";
        echo "Логин: {$username}\n";
        echo "Пароль: {$password}\n\n";
        echo "⚠️  ВАЖНО: Смените пароль после первого входа!\n";
        echo "⚠️  Удалите этот файл после использования!\n";
    } else {
        echo "❌ Ошибка создания пользователя\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
