<?php
/**
 * Управление пользователями админки
 * Функции: добавление, редактирование, блокировка, сброс паролей
 */

require_once '../includes/auth-check.php';
require_once '../../vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->northrepublic;
$usersCollection = $db->admin_users;
$logsCollection = $db->admin_logs;

$error = '';
$success = '';

// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_user':
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $email = trim($_POST['email'] ?? '');
                
                if (empty($username) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
                    exit;
                }
                
                // Проверяем, не существует ли пользователь
                $existingUser = $usersCollection->findOne(['username' => $username]);
                if ($existingUser) {
                    echo json_encode(['success' => false, 'message' => 'Пользователь с таким логином уже существует']);
                    exit;
                }
                
                $userData = [
                    'username' => $username,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'email' => $email,
                    'role' => 'admin',
                    'active' => true,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'last_login' => null,
                    'login_attempts' => 0,
                    'locked_until' => null,
                    'created_by' => $_SESSION['admin_username']
                ];
                
                $result = $usersCollection->insertOne($userData);
                
                if ($result->getInsertedId()) {
                    // Логируем создание пользователя
                    $logsCollection->insertOne([
                        'action' => 'user_created',
                        'description' => "Создан пользователь: {$username}",
                        'data' => ['username' => $username, 'created_by' => $_SESSION['admin_username']],
                        'timestamp' => new MongoDB\BSON\UTCDateTime(),
                        'admin_username' => $_SESSION['admin_username']
                    ]);
                    
                    echo json_encode(['success' => true, 'message' => 'Пользователь создан']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка создания пользователя']);
                }
                exit;
                
            case 'edit_user':
                $userId = $_POST['user_id'] ?? '';
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $active = isset($_POST['active']) ? (bool)$_POST['active'] : false;
                
                if (empty($userId) || empty($username)) {
                    echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
                    exit;
                }
                
                $updateData = [
                    'username' => $username,
                    'email' => $email,
                    'active' => $active,
                    'updated_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_by' => $_SESSION['admin_username']
                ];
                
                $result = $usersCollection->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($userId)],
                    ['$set' => $updateData]
                );
                
                if ($result->getModifiedCount() > 0) {
                    // Логируем редактирование
                    $logsCollection->insertOne([
                        'action' => 'user_updated',
                        'description' => "Обновлен пользователь: {$username}",
                        'data' => ['user_id' => $userId, 'updated_by' => $_SESSION['admin_username']],
                        'timestamp' => new MongoDB\BSON\UTCDateTime(),
                        'admin_username' => $_SESSION['admin_username']
                    ]);
                    
                    echo json_encode(['success' => true, 'message' => 'Пользователь обновлен']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка обновления пользователя']);
                }
                exit;
                
            case 'reset_password':
                $userId = $_POST['user_id'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                
                if (empty($userId) || empty($newPassword)) {
                    echo json_encode(['success' => false, 'message' => 'Заполните все поля']);
                    exit;
                }
                
                $result = $usersCollection->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($userId)],
                    [
                        '$set' => [
                            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                            'updated_at' => new MongoDB\BSON\UTCDateTime(),
                            'updated_by' => $_SESSION['admin_username']
                        ]
                    ]
                );
                
                if ($result->getModifiedCount() > 0) {
                    // Логируем сброс пароля
                    $user = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
                    $logsCollection->insertOne([
                        'action' => 'password_reset',
                        'description' => "Сброшен пароль для пользователя: {$user['username']}",
                        'data' => ['user_id' => $userId, 'reset_by' => $_SESSION['admin_username']],
                        'timestamp' => new MongoDB\BSON\UTCDateTime(),
                        'admin_username' => $_SESSION['admin_username']
                    ]);
                    
                    echo json_encode(['success' => true, 'message' => 'Пароль сброшен']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка сброса пароля']);
                }
                exit;
                
            case 'toggle_status':
                $userId = $_POST['user_id'] ?? '';
                $active = isset($_POST['active']) ? (bool)$_POST['active'] : false;
                
                if (empty($userId)) {
                    echo json_encode(['success' => false, 'message' => 'Не указан ID пользователя']);
                    exit;
                }
                
                $user = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
                    exit;
                }
                
                $result = $usersCollection->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($userId)],
                    [
                        '$set' => [
                            'active' => $active,
                            'updated_at' => new MongoDB\BSON\UTCDateTime(),
                            'updated_by' => $_SESSION['admin_username']
                        ]
                    ]
                );
                
                if ($result->getModifiedCount() > 0) {
                    $action = $active ? 'разблокирован' : 'заблокирован';
                    $logsCollection->insertOne([
                        'action' => 'user_status_changed',
                        'description' => "Пользователь {$user['username']} {$action}",
                        'data' => ['user_id' => $userId, 'active' => $active, 'changed_by' => $_SESSION['admin_username']],
                        'timestamp' => new MongoDB\BSON\UTCDateTime(),
                        'admin_username' => $_SESSION['admin_username']
                    ]);
                    
                    echo json_encode(['success' => true, 'message' => "Пользователь {$action}"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка изменения статуса']);
                }
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        exit;
    }
}

// Получаем список пользователей
$users = $usersCollection->find([], ['sort' => ['created_at' => -1]])->toArray();
$usersCount = count($users);
$activeUsersCount = $usersCollection->countDocuments(['active' => true]);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .users-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .users-stats {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .users-list {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007cba;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 500;
            color: #333;
        }
        
        .user-email {
            color: #666;
            font-size: 0.9rem;
        }
        
        .user-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 1rem;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .user-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #007cba;
            color: white;
        }
        
        .btn-edit:hover {
            background: #005a87;
        }
        
        .btn-reset {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-reset:hover {
            background: #e0a800;
        }
        
        .btn-toggle {
            background: #28a745;
            color: white;
        }
        
        .btn-toggle.inactive {
            background: #dc3545;
        }
        
        .btn-toggle:hover {
            opacity: 0.8;
        }
        
        .add-user-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .btn-add {
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-add:hover {
            background: #1e7e34;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        @media (max-width: 768px) {
            .users-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .user-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .user-actions {
                width: 100%;
                justify-content: flex-end;
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
                <p>Добавление, редактирование и управление пользователями админки</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="users-container">
                <!-- Статистика -->
                <div class="users-stats">
                    <h3>📊 Статистика</h3>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $usersCount; ?></div>
                        <div class="stat-label">Всего пользователей</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $activeUsersCount; ?></div>
                        <div class="stat-label">Активных</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $usersCount - $activeUsersCount; ?></div>
                        <div class="stat-label">Заблокированных</div>
                    </div>
                </div>
                
                <!-- Список пользователей -->
                <div class="users-list">
                    <h3>👤 Пользователи</h3>
                    
                    <?php foreach ($users as $user): ?>
                        <div class="user-item">
                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <span class="user-status <?php echo $user['active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['active'] ? 'Активен' : 'Заблокирован'; ?>
                                    </span>
                                </div>
                                <div class="user-email">
                                    <?php echo htmlspecialchars($user['email'] ?? 'Не указан'); ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #999; margin-top: 0.5rem;">
                                    Создан: <?php echo date('d.m.Y H:i', $user['created_at']->toDateTime()->getTimestamp()); ?>
                                    <?php if ($user['last_login']): ?>
                                        | Последний вход: <?php echo date('d.m.Y H:i', $user['last_login']->toDateTime()->getTimestamp()); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="user-actions">
                                <button class="btn btn-edit" onclick="editUser('<?php echo (string)$user['_id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email'] ?? ''); ?>', <?php echo $user['active'] ? 'true' : 'false'; ?>)">
                                    ✏️ Редактировать
                                </button>
                                <button class="btn btn-reset" onclick="resetPassword('<?php echo (string)$user['_id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')">
                                    🔑 Сбросить пароль
                                </button>
                                <button class="btn btn-toggle <?php echo !$user['active'] ? 'inactive' : ''; ?>" onclick="toggleStatus('<?php echo (string)$user['_id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['active'] ? 'true' : 'false'; ?>)">
                                    <?php echo $user['active'] ? '🔒 Заблокировать' : '🔓 Разблокировать'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Модальное окно добавления пользователя -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>➕ Добавить пользователя</h3>
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            
            <form id="addUserForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_username">Логин *</label>
                        <input type="text" id="new_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Пароль *</label>
                        <input type="password" id="new_password" name="password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_email">Email</label>
                    <input type="email" id="new_email" name="email">
                </div>
                
                <button type="submit" class="btn-add">Создать пользователя</button>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно редактирования пользователя -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Редактировать пользователя</h3>
                <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            </div>
            
            <form id="editUserForm">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_username">Логин *</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="edit_active" name="active" value="1">
                        Активный пользователь
                    </label>
                </div>
                
                <button type="submit" class="btn-add">Сохранить изменения</button>
            </form>
        </div>
    </div>
    
    <!-- Модальное окно сброса пароля -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔑 Сбросить пароль</h3>
                <span class="close" onclick="closeModal('resetPasswordModal')">&times;</span>
            </div>
            
            <form id="resetPasswordForm">
                <input type="hidden" id="reset_user_id" name="user_id">
                
                <div class="form-group">
                    <label>Пользователь: <span id="reset_username"></span></label>
                </div>
                
                <div class="form-group">
                    <label for="new_password_reset">Новый пароль *</label>
                    <input type="password" id="new_password_reset" name="new_password" required>
                </div>
                
                <button type="submit" class="btn-add">Сбросить пароль</button>
            </form>
        </div>
    </div>
    
    <script>
        // Добавление пользователя
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_user');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('addUserModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка добавления пользователя', 'error');
            });
        });
        
        // Редактирование пользователя
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'edit_user');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('editUserModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка редактирования пользователя', 'error');
            });
        });
        
        // Сброс пароля
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'reset_password');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('resetPasswordModal');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка сброса пароля', 'error');
            });
        });
        
        // Функции модальных окон
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editUser(userId, username, email, active) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_active').checked = active;
            openModal('editUserModal');
        }
        
        function resetPassword(userId, username) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_username').textContent = username;
            document.getElementById('new_password_reset').value = '';
            openModal('resetPasswordModal');
        }
        
        function toggleStatus(userId, username, currentStatus) {
            const newStatus = !currentStatus;
            const action = newStatus ? 'разблокировать' : 'заблокировать';
            
            if (confirm(`Вы уверены, что хотите ${action} пользователя "${username}"?`)) {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('user_id', userId);
                formData.append('active', newStatus);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('Ошибка изменения статуса', 'error');
                });
            }
        }
        
        // Уведомления
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'error'}`;
            notification.textContent = message;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.padding = '1rem';
            notification.style.borderRadius = '5px';
            notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Закрытие модальных окон по клику вне их
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
