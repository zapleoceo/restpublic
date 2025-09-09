<?php
session_start();

require_once __DIR__ . '/../classes/AuthManager.php';

$error = '';
$success = '';
$authManager = new AuthManager();

// Проверяем, есть ли уже пользователи
if ($authManager->getUsersCount() > 0) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    if ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        $result = $authManager->createUser($username, $password, $email);
        
        if ($result['success']) {
            $success = 'Администратор успешно создан! Теперь вы можете войти в систему.';
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание администратора - North Republic</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .create-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .create-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .create-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .create-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-create {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-create:hover {
            background: #5a6fd8;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #cfc;
        }
        
        .info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .password-requirements {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #333;
        }
        
        .password-requirements h4 {
            margin: 0 0 0.5rem 0;
            color: #667eea;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="create-container">
        <div class="create-card">
            <div class="create-header">
                <h1>👤 Создание администратора</h1>
                <p>North Republic</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <div class="info">
                    <a href="login.php" style="color: #667eea; text-decoration: none;">→ Войти в админку</a>
                </div>
            <?php else: ?>
                <div class="password-requirements">
                    <h4>Требования к паролю:</h4>
                    <ul>
                        <li>Минимум 8 символов</li>
                        <li>Содержит заглавные буквы</li>
                        <li>Содержит цифры</li>
                        <li>Содержит специальные символы</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Имя пользователя</label>
                        <input type="text" id="username" name="username" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email (необязательно)</label>
                        <input type="email" id="email" name="email" autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль</label>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    
                    <button type="submit" class="btn-create">Создать администратора</button>
                </form>
                
                <div class="back-link">
                    <a href="login.php">← Вернуться к авторизации</a>
                </div>
                
                <div class="info">
                    <strong>Создание первого администратора:</strong><br>
                    Этот интерфейс доступен только для создания первого пользователя системы
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
