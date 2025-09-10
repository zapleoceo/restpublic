<?php
/**
 * Управление пользователями админки
 * Использует AuthManager для работы с MongoDB
 */

// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

// Проверка авторизации уже включена в header.php

$error = '';
$success = '';

// Инициализируем AuthManager
require_once __DIR__ . '/../classes/AuthManager.php';
$authManager = new AuthManager();

// Получаем всех пользователей
$users = $authManager->getAllUsers();

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create_user':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'admin';
            
            if (empty($username) || empty($password)) {
                $error = 'Имя пользователя и пароль обязательны';
            } else {
                $createdBy = $_SESSION['admin_username'] ?? 'admin';
                if ($authManager->createUser($username, $password, $email, $role, $createdBy)) {
                    $success = 'Пользователь успешно создан!';
                    $users = $authManager->getAllUsers(); // Обновляем список
                } else {
                    $error = 'Ошибка при создании пользователя';
                }
            }
            break;
            
        case 'update_user':
            $username = $_POST['username'] ?? '';
            $newRole = $_POST['role'] ?? '';
            $newStatus = $_POST['status'] ?? '';
            
            $updatedBy = $_SESSION['admin_username'] ?? 'admin';
            if ($authManager->updateUser($username, $newRole, $newStatus, $updatedBy)) {
                $success = 'Пользователь успешно обновлен!';
                $users = $authManager->getAllUsers(); // Обновляем список
            } else {
                $error = 'Ошибка при обновлении пользователя';
            }
            break;
            
        case 'delete_user':
            $username = $_POST['username'] ?? '';
            
            if ($username === $_SESSION['admin_username']) {
                $error = 'Нельзя удалить самого себя';
            } else {
                if ($authManager->deleteUser($username)) {
                    $success = 'Пользователь успешно удален!';
                    $users = $authManager->getAllUsers(); // Обновляем список
                } else {
                    $error = 'Ошибка при удалении пользователя';
                }
            }
            break;
    }
}

// Статистика
$totalUsers = count($users);
$activeUsers = count(array_filter($users, function($user) { return $user['status'] === 'active'; }));
$adminUsers = count(array_filter($users, function($user) { return $user['role'] === 'admin'; }));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    
    <style>
        .users-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .users-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .users-section h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a87;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .users-table th,
        .users-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin {
            background: #cce5ff;
            color: #004085;
        }
        
        .role-editor {
            background: #fff3cd;
            color: #856404;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
            color: #007cba;
        }
        
        .stat-card p {
            margin: 0.5rem 0 0 0;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .users-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>👥 Управление пользователями</h1>
                <p>Создание и управление пользователями админки</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Статистика -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Всего пользователей</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $activeUsers; ?></h3>
                    <p>Активных</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $adminUsers; ?></h3>
                    <p>Администраторов</p>
                </div>
            </div>
            
            <div class="users-container">
                <!-- Создание пользователя -->
                <div class="users-section">
                    <h3>➕ Создать пользователя</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="form-group">
                            <label for="username">Имя пользователя:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Пароль:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Роль:</label>
                            <select id="role" name="role">
                                <option value="admin">Администратор</option>
                                <option value="editor">Редактор</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Создать пользователя</button>
                    </form>
                </div>
                
                <!-- Список пользователей -->
                <div class="users-section">
                    <h3>📋 Список пользователей</h3>
                    
                    <?php if (empty($users)): ?>
                        <p>Пользователи не найдены</p>
                    <?php else: ?>
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Пользователь</th>
                                    <th>Роль</th>
                                    <th>Статус</th>
                                    <th>Последний вход</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($user['email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo $user['role'] === 'admin' ? 'Админ' : 'Редактор'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $user['status']; ?>">
                                                <?php echo $user['status'] === 'active' ? 'Активен' : 'Неактивен'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo isset($user['last_login']) && $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Никогда'; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['username'] !== $_SESSION['admin_username']): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="update_user">
                                                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                    
                                                    <select name="status" onchange="this.form.submit()" style="font-size: 12px; padding: 0.25rem;">
                                                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Активен</option>
                                                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Неактивен</option>
                                                    </select>
                                                </form>
                                                
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Удалить пользователя?')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 12px;">Удалить</button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color: #666; font-size: 12px;">Текущий пользователь</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Действия -->
            <div style="margin-top: 2rem; text-align: center;">
                <a href="/admin/" class="btn btn-secondary">← Назад в админку</a>
            </div>
        </main>
    </div>
</body>
</html>
