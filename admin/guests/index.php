<?php
// Настройки страницы для layout
$page_title = 'Управление - Админка';
$page_header = '👥 Гости';
$page_description = 'Управление гостями ресторана';

// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

$error = '';
$success = '';

// Инициализируем MongoDB
require_once __DIR__ . '/../../classes/Logger.php';
$logger = new Logger();

// Подключаемся к MongoDB
$mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
$client = new MongoDB\Client($mongodbUrl);
$dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
$database = $client->selectDatabase($dbName);
$usersCollection = $database->selectCollection('users');

// Обработка удаления гостя
if ($_POST['action'] ?? '' === 'delete_guest') {
    $userId = $_POST['user_id'] ?? '';
    $posterClientId = $_POST['poster_client_id'] ?? '';
    
    if ($userId) {
        try {
            // Удаляем из MongoDB
            $result = $usersCollection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
            
            if ($result->getDeletedCount() > 0) {
                // Удаляем из Poster API если есть client_id
                if ($posterClientId) {
                    $apiUrl = 'https://northrepublic.me:3002/api/poster/clients.removeClient';
                    $postData = [
                        'client_id' => $posterClientId
                    ];
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'X-API-Token: ' . ($_ENV['API_AUTH_TOKEN'] ?? '')
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        $logger->log('guest_deleted', 'Гость удален из MongoDB и Poster API', [
                            'user_id' => $userId,
                            'poster_client_id' => $posterClientId
                        ]);
                        $success = 'Гость успешно удален из системы и Poster API';
                    } else {
                        $logger->log('guest_deleted_partial', 'Гость удален из MongoDB, но ошибка при удалении из Poster API', [
                            'user_id' => $userId,
                            'poster_client_id' => $posterClientId,
                            'poster_error' => $response
                        ]);
                        $success = 'Гость удален из системы, но произошла ошибка при удалении из Poster API';
                    }
                } else {
                    $logger->log('guest_deleted', 'Гость удален из MongoDB', [
                        'user_id' => $userId
                    ]);
                    $success = 'Гость успешно удален из системы';
                }
            } else {
                $error = 'Гость не найден';
            }
        } catch (Exception $e) {
            $error = 'Ошибка при удалении: ' . $e->getMessage();
            $logger->log('guest_delete_error', 'Ошибка при удалении гостя', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Получаем всех гостей
$guests = [];
try {
    $cursor = $usersCollection->find([], [
        'sort' => ['date_activale' => -1],
        'limit' => 100
    ]);
    
    foreach ($cursor as $user) {
        $guests[] = [
            'id' => (string)$user['_id'],
            'firstname' => $user['name'] ?? $user['firstname'] ?? '',
            'lastname' => $user['lastName'] ?? $user['lastname'] ?? '',
            'phone' => $user['phone'] ?? '',
            'email' => $user['email'] ?? '',
            'poster_client_id' => $user['client_id'] ?? $user['poster_client_id'] ?? null,
            'date_activale' => isset($user['date_activale']) ? 
                $user['date_activale']->toDateTime()->format('Y-m-d H:i:s') : 
                (isset($user['updatedAt']) ? $user['updatedAt']->toDateTime()->format('Y-m-d H:i:s') : ''),
            'total_payed_sum' => $user['total_payed_sum'] ?? 0,
            'bonus' => $user['bonus'] ?? 0,
            'discount_per' => $user['discount_per'] ?? 0
        ];
    }
} catch (Exception $e) {
    $error = 'Ошибка загрузки гостей: ' . $e->getMessage();
}

// Подключаем layout
include __DIR__ . '/../includes/layout.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1><?php echo $page_header; ?></h1>
        <p><?php echo $page_description; ?></p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <strong>Ошибка:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>Успех:</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="guests-section">
        <div class="section-header">
            <h2>Список гостей (<?php echo count($guests); ?>)</h2>
            <div class="section-actions">
                <button class="btn btn-primary" onclick="location.reload()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                    </svg>
                    Обновить
                </button>
            </div>
        </div>

        <?php if (empty($guests)): ?>
            <div class="empty-state">
                <p>Гости не найдены</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Имя</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Poster ID</th>
                            <th>Дата регистрации</th>
                            <th>Потрачено</th>
                            <th>Бонусы</th>
                            <th>Скидка</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guests as $guest): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($guest['firstname'] . ' ' . $guest['lastname']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($guest['phone']); ?></td>
                                <td><?php echo htmlspecialchars($guest['email']); ?></td>
                                <td>
                                    <?php if ($guest['poster_client_id']): ?>
                                        <span class="badge badge-success"><?php echo $guest['poster_client_id']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Не привязан</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $guest['date_activale']; ?></td>
                                <td><?php echo number_format($guest['total_payed_sum'] / 100, 0, ',', ' '); ?> ₫</td>
                                <td><?php echo number_format($guest['bonus'], 0, ',', ' '); ?> ₫</td>
                                <td><?php echo $guest['discount_per']; ?>%</td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этого гостя? Это действие нельзя отменить.');">
                                        <input type="hidden" name="action" value="delete_guest">
                                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($guest['id']); ?>">
                                        <input type="hidden" name="poster_client_id" value="<?php echo htmlspecialchars($guest['poster_client_id'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Удалить гостя">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.guests-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.section-header h2 {
    margin: 0;
    color: #2c2c2c;
}

.section-actions {
    display: flex;
    gap: 10px;
}

.table-container {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}

.admin-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #2c2c2c;
}

.admin-table tr:hover {
    background: #f8f9fa;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.btn-sm {
    padding: 6px 8px;
    font-size: 12px;
}

.btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-danger:hover {
    background: #c82333;
}

.empty-state {
    padding: 40px;
    text-align: center;
    color: #666;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
</style>
