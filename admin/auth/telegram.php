<?php
session_start();

// Если уже авторизован, перенаправляем на главную
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../index.php');
    exit;
}

$error = '';

// Обработка авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка rate limiting для авторизации
    require_once __DIR__ . '/../../classes/RateLimiter.php';
    $rateLimiter = new RateLimiter();
    
    if (!$rateLimiter->checkAuthLimit()) {
        $error = 'Слишком много попыток входа. Попробуйте через 15 минут.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Валидация входных данных
        if (empty($username) || empty($password)) {
            $error = 'Заполните все поля';
        } elseif (strlen($username) > 50 || strlen($password) > 100) {
            $error = 'Слишком длинные данные';
        } else {
        // Проверяем пользователя в файле
        try {
            $usersFile = __DIR__ . '/../../data/admin_users.json';
            
            if (file_exists($usersFile)) {
                $users = json_decode(file_get_contents($usersFile), true) ?: [];
                
                $user = null;
                foreach ($users as $u) {
                    if ($u['username'] === $username && $u['active'] === true) {
                        $user = $u;
                        break;
                    }
                }
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_user_id'] = $user['username']; // Используем username как ID
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // Обновляем время последнего входа
                    foreach ($users as &$u) {
                        if ($u['username'] === $username) {
                            $u['last_login'] = date('Y-m-d H:i:s');
                            break;
                        }
                    }
                    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                    
                    // Логируем вход
                    logAdminAction('login', 'Вход в админку', [
                        'username' => $username,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                    
                    header('Location: ../index.php');
                    exit;
                } else {
                    $error = 'Неверные данные для входа';
                    
                    // Логируем неудачную попытку входа
                    logAdminAction('login_failed', 'Неудачная попытка входа', [
                        'username' => $username,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                }
            } else {
                $error = 'Пользователи не найдены. Создайте администратора.';
            }
        } catch (Exception $e) {
            error_log("Auth error: " . $e->getMessage());
            $error = 'Ошибка системы. Попробуйте позже.';
        }
        }
    }
}

// Функция логирования действий админа
function logAdminAction($action, $description, $data = []) {
    try {
        $logsFile = __DIR__ . '/../../data/admin_logs.json';
        $logsDir = dirname($logsFile);
        
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        $logs = [];
        if (file_exists($logsFile)) {
            $logs = json_decode(file_get_contents($logsFile), true) ?: [];
        }
        
        $logEntry = [
            'action' => $action,
            'description' => $description,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id()
        ];
        
        $logs[] = $logEntry;
        
        // Ограничиваем количество логов (последние 1000 записей)
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT));
    } catch (Exception $e) {
        error_log("Ошибка логирования: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
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
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
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
        
        .btn-login:hover {
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
        
        .telegram-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🔐 Админка</h1>
                <p>North Republic</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Имя пользователя</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-login">Войти</button>
            </form>
            
            <div class="telegram-info">
                <strong>Безопасная авторизация:</strong><br>
                Используйте учетные данные, предоставленные администратором<br><br>
                <em>Все попытки входа логируются для безопасности</em>
            </div>
        </div>
    </div>
</body>
</html>
