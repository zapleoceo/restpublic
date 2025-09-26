<?php
// Настройки страницы для layout
$page_title = 'Управление гостями - North Republic';
$page_header = 'Управление гостями';
$page_description = 'Управление гостями ресторана и их данными';

// Breadcrumbs для навигации
$breadcrumb = [
    ['title' => 'Управление гостями']
];

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
    $clientId = $_POST['user_id'] ?? ''; // Теперь это client_id
    $posterClientId = $_POST['poster_client_id'] ?? '';
    
    if ($clientId) {
        try {
            // Удаляем из MongoDB по client_id
            $result = $usersCollection->deleteOne(['client_id' => $clientId]);
            
            // Удаляем из Poster API
            if ($posterClientId) {
                $apiUrl = 'http://localhost:3002/api/poster/clients.removeClient';
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
                        'client_id' => $clientId,
                        'poster_client_id' => $posterClientId
                    ]);
                    $success = 'Гость успешно удален из системы и Poster API';
                } else {
                    $logger->log('guest_deleted_partial', 'Гость удален из MongoDB, но ошибка при удалении из Poster API', [
                        'client_id' => $clientId,
                        'poster_client_id' => $posterClientId,
                        'poster_error' => $response
                    ]);
                    $success = 'Гость удален из системы, но произошла ошибка при удалении из Poster API';
                }
            } else {
                $logger->log('guest_deleted', 'Гость удален из MongoDB', [
                    'client_id' => $clientId
                ]);
                $success = 'Гость успешно удален из системы';
            }
        } catch (Exception $e) {
            $error = 'Ошибка при удалении: ' . $e->getMessage();
            $logger->log('guest_delete_error', 'Ошибка при удалении гостя', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Получаем всех гостей из Poster API
$guests = [];
try {
    // Получаем всех клиентов из Poster API
    $apiUrl = 'http://localhost:3002/api/poster/clients.getClients';
    $authToken = $_ENV['API_AUTH_TOKEN'] ?? '';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-API-Token: ' . $authToken
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $posterClients = json_decode($response, true);
        
        if (is_array($posterClients)) {
            // Получаем все client_id из MongoDB для связи
            $mongoClientIds = [];
            $cursor = $usersCollection->find([], ['projection' => ['client_id' => 1]]);
            foreach ($cursor as $user) {
                if (isset($user['client_id'])) {
                    $mongoClientIds[] = (string)$user['client_id'];
                }
            }
            
            // Обрабатываем клиентов из Poster API
            foreach ($posterClients as $client) {
                $clientId = (string)$client['client_id'];
                $isInMongoDB = in_array($clientId, $mongoClientIds);
                
                $guests[] = [
                    'id' => $clientId, // Используем client_id как ID
                    'firstname' => $client['firstname'] ?? '',
                    'lastname' => $client['lastname'] ?? '',
                    'phone' => $client['phone'] ?? '',
                    'email' => $client['email'] ?? '',
                    'poster_client_id' => $clientId,
                    'poster_name' => trim(($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? '')),
                    'date_activale' => $client['date_activale'] ?? '',
                    'total_payed_sum' => isset($client['total_payed_sum']) ? (float)$client['total_payed_sum'] : 0,
                    'bonus' => isset($client['bonus']) ? (float)$client['bonus'] : 0,
                    'discount_per' => isset($client['discount_per']) ? (float)$client['discount_per'] : 0,
                    'client_groups_name' => $client['client_groups_name'] ?? '',
                    'is_in_mongodb' => $isInMongoDB
                ];
            }
            
            // Сортируем по дате активности (новые сначала)
            usort($guests, function($a, $b) {
                $dateA = strtotime($a['date_activale']);
                $dateB = strtotime($b['date_activale']);
                return $dateB - $dateA;
            });
            
        } else {
            $error = 'Неверный формат ответа от Poster API';
        }
    } else {
        $error = 'Ошибка получения данных из Poster API: HTTP ' . $httpCode;
        error_log("Poster API error: HTTP $httpCode, Response: $response");
    }
} catch (Exception $e) {
    $error = 'Ошибка загрузки гостей: ' . $e->getMessage();
}

// Generate page content
ob_start();
?>

<?php if ($error || $success): ?>
<div class="page-notifications">
<?php if ($error): ?>
<div class="notification notification-error">
    <div class="notification-content">
        <span class="notification-message"><?php echo htmlspecialchars($error); ?></span>
        <button class="notification-close" onclick="adminPanel.hideNotification(this.parentNode.parentNode)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="notification notification-success">
    <div class="notification-content">
        <span class="notification-message"><?php echo htmlspecialchars($success); ?></span>
        <button class="notification-close" onclick="adminPanel.hideNotification(this.parentNode.parentNode)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
</div>
<?php endif; ?>
</div>
<?php endif; ?>
<div class="guests-section">
    <div class="section-header">
        <div class="section-header-info">
            <h2 class="section-title">Список гостей</h2>
            <p class="section-subtitle">Всего: <?php echo count($guests); ?> гостей</p>
        </div>
        <div class="section-actions">
            <button class="btn btn-primary" onclick="location.reload()" title="Обновить список">
                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Обновить
            </button>
        </div>
    </div>

    <?php if (empty($guests)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <h3>Гости не найдены</h3>
        <p>В системе пока нет зарегистрированных гостей</p>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table class="admin-table" id="guests-table">
            <thead>
                <tr>
                    <th data-sort="name">Имя</th>
                    <th data-sort="phone">Телефон</th>
                    <th data-sort="email">Email</th>
                    <th data-sort="poster_id">Poster ID</th>
                    <th data-sort="group">Группа</th>
                    <th data-sort="date">Дата активности</th>
                    <th data-sort="spent">Потрачено</th>
                    <th data-sort="bonus">Бонусы</th>
                    <th data-sort="discount">Скидка</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guests as $guest): ?>
                <tr>
                    <td data-sort="name">
                        <div class="guest-name">
                            <strong><?php echo htmlspecialchars(trim($guest['firstname'] . ' ' . $guest['lastname'])); ?></strong>
                        </div>
                    </td>
                    <td data-sort="phone">
                        <div class="guest-contact">
                            <span class="phone-number"><?php echo htmlspecialchars($guest['phone']); ?></span>
                        </div>
                    </td>
                    <td data-sort="email">
                        <div class="guest-contact">
                            <span class="email-address"><?php echo htmlspecialchars($guest['email']); ?></span>
                        </div>
                    </td>
                    <td data-sort="poster_id">
                        <span class="badge badge-success" title="ID в Poster API">
                            <?php echo htmlspecialchars($guest['poster_client_id']); ?>
                        </span>
                    </td>
                    <td data-sort="group">
                        <div class="guest-group">
                            <span class="group-name"><?php echo htmlspecialchars($guest['client_groups_name'] ?: 'Не указана'); ?></span>
                        </div>
                    </td>
                    <td data-sort="date">
                        <div class="guest-date">
                            <time datetime="<?php echo date('c', strtotime($guest['date_activale'])); ?>">
                                <?php echo date('d.m.Y H:i', strtotime($guest['date_activale'])); ?>
                            </time>
                        </div>
                    </td>
                    <td data-sort="spent">
                        <div class="guest-amount">
                            <span class="currency-amount">
                                <?php echo number_format($guest['total_payed_sum'] / 100, 0, ',', ' '); ?> ₫
                            </span>
                        </div>
                    </td>
                    <td data-sort="bonus">
                        <div class="guest-amount">
                            <span class="currency-amount bonus-amount">
                                <?php echo number_format($guest['bonus'], 0, ',', ' '); ?> ₫
                            </span>
                        </div>
                    </td>
                    <td data-sort="discount">
                        <div class="guest-discount">
                            <span class="discount-percentage">
                                <?php echo $guest['discount_per']; ?>%
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="guest-status">
                            <?php if ($guest['is_in_mongodb']): ?>
                                <span class="badge badge-success" title="Есть в MongoDB">Связан</span>
                            <?php else: ?>
                                <span class="badge badge-warning" title="Только в Poster API">Только Poster</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="guest-actions">
                            <form method="POST" class="delete-form" onsubmit="return confirm('Вы уверены, что хотите удалить этого гостя? Это действие нельзя отменить.');">
                                <input type="hidden" name="action" value="delete_guest">
                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($guest['id']); ?>">
                                <input type="hidden" name="poster_client_id" value="<?php echo htmlspecialchars($guest['poster_client_id'] ?? ''); ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Удалить гостя">
                                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3,6 5,6 21,6"></polyline>
                                        <path d="M19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Search functionality -->
<div class="table-search-container">
    <div class="search-box">
        <input type="search" class="table-search" placeholder="Поиск гостей по имени, телефону или email..." aria-label="Поиск гостей">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
    </div>
</div>

<?php
$content = ob_get_clean();

// Load layout
require_once __DIR__ . '/../includes/layout.php';
?>
