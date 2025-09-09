<?php
header('Content-Type: application/json');

// Простая проверка - только для создания первого пользователя
try {
    // Проверяем, есть ли уже пользователи
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $usersCollection = $db->admin_users;
    
    $existingUsers = $usersCollection->countDocuments();
    
    if ($existingUsers > 0) {
        echo json_encode(['success' => false, 'error' => 'Пользователи уже существуют в системе']);
        exit;
    }
    
    // Получаем данные из POST запроса
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['username']) || !isset($input['password'])) {
        echo json_encode(['success' => false, 'error' => 'Неполные данные']);
        exit;
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    $email = $input['email'] ?? $username . '@northrepublic.me';
    $role = $input['role'] ?? 'admin';
    
    // Валидация
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Заполните все поля']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => 'Пароль должен быть не менее 6 символов']);
        exit;
    }
    
    // Создаем пользователя
    $userData = [
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email,
        'role' => $role,
        'active' => true,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'last_login' => null,
        'login_attempts' => 0,
        'locked_until' => null
    ];
    
    $result = $usersCollection->insertOne($userData);
    
    if ($result->getInsertedId()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Пользователь успешно создан',
            'username' => $username
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка создания пользователя']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка системы: ' . $e->getMessage()]);
}
?>
