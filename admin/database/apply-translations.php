<?php
// Страница для применения переводов событий
session_start();
// require_once __DIR__ . '/../includes/auth-check.php'; // Временно отключено для тестирования

$pageTitle = 'Применение переводов событий';
$pageDescription = 'Обновление переводов событий на английский и вьетнамский языки';

// Загружаем переменные окружения
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$result = null;
$error = null;

// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_translations'])) {
    try {
        // Подключение к MongoDB
        $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
        $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
        
        $client = new MongoDB\Client($mongodbUrl);
        $db = $client->$dbName;
        $eventsCollection = $db->events;
        
        // Словарь переводов
        $translations = [
            'en' => [
                '🎭 Мафия' => '🎭 Mafia',
                'Дегустация вин' => 'Wine Tasting',
                'Новогодний банкет' => 'New Year Banquet',
                'Мастер-класс по приготовлению пасты' => 'Pasta Cooking Master Class',
                'Романтический ужин на День Святого Валентина' => 'Romantic Valentine\'s Day Dinner',
                'День рождения ресторана' => 'Restaurant Birthday',
                'Дегустация лучших вин с сомелье' => 'Tasting of the best wines with sommelier',
                'Праздничный банкет с живой музыкой' => 'Holiday banquet with live music',
                'Учимся готовить настоящую итальянскую пасту' => 'Learn to cook authentic Italian pasta',
                'Специальное романтическое меню для влюбленных' => 'Special romantic menu for lovers',
                'Празднование годовщины ресторана' => 'Restaurant anniversary celebration',
                '1500 руб. с человека' => '1500 rubles per person',
                '3000 руб. с человека, предварительная запись' => '3000 rubles per person, advance booking required',
                'Бесплатно при заказе от 2000 руб.' => 'Free with order from 2000 rubles',
                '2500 руб. за пару, специальное меню' => '2500 rubles per couple, special menu',
                'Вход свободный, специальные предложения' => 'Free entry, special offers'
            ],
            'vi' => [
                '🎭 Мафия' => '🎭 Mafia',
                'Дегустация вин' => 'Nếm thử rượu vang',
                'Новогодний банкет' => 'Tiệc tất niên',
                'Мастер-класс по приготовлению пасты' => 'Lớp học nấu mì Ý',
                'Романтический ужин на День Святого Валентина' => 'Bữa tối lãng mạn ngày Valentine',
                'День рождения ресторана' => 'Sinh nhật nhà hàng',
                'Дегустация лучших вин с сомелье' => 'Nếm thử những loại rượu vang ngon nhất với chuyên gia rượu',
                'Праздничный банкет с живой музыкой' => 'Tiệc tất niên với nhạc sống',
                'Учимся готовить настоящую итальянскую пасту' => 'Học nấu mì Ý chính thống',
                'Специальное романтическое меню для влюбленных' => 'Thực đơn lãng mạn đặc biệt cho các cặp đôi',
                'Празднование годовщины ресторана' => 'Lễ kỷ niệm ngày thành lập nhà hàng',
                '1500 руб. с человека' => '1500 rúp mỗi người',
                '3000 руб. с человека, предварительная запись' => '3000 rúp mỗi người, cần đặt trước',
                'Бесплатно при заказе от 2000 руб.' => 'Miễn phí khi đặt từ 2000 rúp',
                '2500 руб. за пару, специальное меню' => '2500 rúp cho cặp đôi, thực đơn đặc biệt',
                'Вход свободный, специальные предложения' => 'Vào cửa miễn phí, ưu đãi đặc biệt'
            ]
        ];
        
        // Функция перевода
        function translateText($text, $targetLanguage, $translations) {
            if (!isset($translations[$targetLanguage])) {
                return $text;
            }
            
            $translatedText = $text;
            foreach ($translations[$targetLanguage] as $ru => $translated) {
                $translatedText = str_replace($ru, $translated, $translatedText);
            }
            
            return $translatedText;
        }
        
        // События для обновления
        $eventsToUpdate = [
            [
                'title' => 'Дегустация вин',
                'description' => 'Дегустация лучших вин с сомелье',
                'conditions' => '1500 руб. с человека'
            ],
            [
                'title' => 'Новогодний банкет',
                'description' => 'Праздничный банкет с живой музыкой',
                'conditions' => '3000 руб. с человека, предварительная запись'
            ],
            [
                'title' => 'Мастер-класс по приготовлению пасты',
                'description' => 'Учимся готовить настоящую итальянскую пасту',
                'conditions' => 'Бесплатно при заказе от 2000 руб.'
            ],
            [
                'title' => 'Романтический ужин на День Святого Валентина',
                'description' => 'Специальное романтическое меню для влюбленных',
                'conditions' => '2500 руб. за пару, специальное меню'
            ],
            [
                'title' => 'День рождения ресторана',
                'description' => 'Празднование годовщины ресторана',
                'conditions' => 'Вход свободный, специальные предложения'
            ]
        ];
        
        $updatedCount = 0;
        $details = [];
        
        foreach ($eventsToUpdate as $eventData) {
            // Ищем событие по заголовку
            $event = $eventsCollection->findOne(['title' => $eventData['title']]);
            
            if ($event) {
                $titleEn = translateText($eventData['title'], 'en', $translations);
                $titleVi = translateText($eventData['title'], 'vi', $translations);
                $descriptionEn = translateText($eventData['description'], 'en', $translations);
                $descriptionVi = translateText($eventData['description'], 'vi', $translations);
                $conditionsEn = translateText($eventData['conditions'], 'en', $translations);
                $conditionsVi = translateText($eventData['conditions'], 'vi', $translations);
                
                $updateData = [
                    'title_ru' => $eventData['title'],
                    'title_en' => $titleEn,
                    'title_vi' => $titleVi,
                    'description_ru' => $eventData['description'],
                    'description_en' => $descriptionEn,
                    'description_vi' => $descriptionVi,
                    'conditions_ru' => $eventData['conditions'],
                    'conditions_en' => $conditionsEn,
                    'conditions_vi' => $conditionsVi,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ];
                
                $result = $eventsCollection->updateOne(
                    ['_id' => $event['_id']],
                    ['$set' => $updateData]
                );
                
                if ($result->getModifiedCount() > 0) {
                    $updatedCount++;
                    $details[] = "✅ Обновлено: " . $eventData['title'];
                } else {
                    $details[] = "⚠️ Не изменено: " . $eventData['title'];
                }
            } else {
                $details[] = "❌ Не найдено: " . $eventData['title'];
            }
        }
        
        $result = [
            'success' => true,
            'updated_count' => $updatedCount,
            'total_events' => count($eventsToUpdate),
            'details' => $details
        ];
        
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}

// Получаем текущие события для отображения
$currentEvents = [];
try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    $events = $eventsCollection->find([])->toArray();
    foreach ($events as $event) {
        $currentEvents[] = [
            'id' => (string)$event['_id'],
            'title' => $event['title'] ?? 'Нет заголовка',
            'title_ru' => $event['title_ru'] ?? 'Нет',
            'title_en' => $event['title_en'] ?? 'Нет',
            'title_vi' => $event['title_vi'] ?? 'Нет',
            'description_ru' => $event['description_ru'] ?? 'Нет',
            'description_en' => $event['description_en'] ?? 'Нет',
            'description_vi' => $event['description_vi'] ?? 'Нет',
            'conditions_ru' => $event['conditions_ru'] ?? 'Нет',
            'conditions_en' => $event['conditions_en'] ?? 'Нет',
            'conditions_vi' => $event['conditions_vi'] ?? 'Нет'
        ];
    }
} catch (Exception $e) {
    $error = "Ошибка загрузки событий: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>🌐 Применение переводов событий</h1>
        <p>Обновление переводов событий на английский и вьетнамский языки</p>
    </div>

    <?php if ($result): ?>
        <div class="alert alert-<?php echo $result['success'] ? 'success' : 'error'; ?>">
            <h3><?php echo $result['success'] ? '✅ Переводы применены успешно!' : '❌ Ошибка при применении переводов'; ?></h3>
            <p><strong>Обновлено событий:</strong> <?php echo $result['updated_count']; ?> из <?php echo $result['total_events']; ?></p>
            
            <?php if (!empty($result['details'])): ?>
                <h4>Детали:</h4>
                <ul>
                    <?php foreach ($result['details'] as $detail): ?>
                        <li><?php echo htmlspecialchars($detail); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <h3>❌ Ошибка</h3>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <div class="admin-section">
        <h2>📋 Текущие события</h2>
        
        <?php if (empty($currentEvents)): ?>
            <div class="alert alert-info">
                <p>События не найдены в базе данных.</p>
            </div>
        <?php else: ?>
            <div class="events-list">
                <?php foreach ($currentEvents as $event): ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                        
                        <div class="translations-grid">
                            <div class="translation-item">
                                <h4>🇷🇺 Русский</h4>
                                <p><strong>Заголовок:</strong> <?php echo htmlspecialchars($event['title_ru']); ?></p>
                                <p><strong>Описание:</strong> <?php echo htmlspecialchars($event['description_ru']); ?></p>
                                <p><strong>Условия:</strong> <?php echo htmlspecialchars($event['conditions_ru']); ?></p>
                            </div>
                            
                            <div class="translation-item">
                                <h4>🇬🇧 English</h4>
                                <p><strong>Заголовок:</strong> <?php echo htmlspecialchars($event['title_en']); ?></p>
                                <p><strong>Описание:</strong> <?php echo htmlspecialchars($event['description_en']); ?></p>
                                <p><strong>Условия:</strong> <?php echo htmlspecialchars($event['conditions_en']); ?></p>
                            </div>
                            
                            <div class="translation-item">
                                <h4>🇻🇳 Tiếng Việt</h4>
                                <p><strong>Заголовок:</strong> <?php echo htmlspecialchars($event['title_vi']); ?></p>
                                <p><strong>Описание:</strong> <?php echo htmlspecialchars($event['description_vi']); ?></p>
                                <p><strong>Условия:</strong> <?php echo htmlspecialchars($event['conditions_vi']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-section">
        <h2>🚀 Применить переводы</h2>
        <p>Нажмите кнопку ниже, чтобы обновить все переводы событий в базе данных.</p>
        
        <form method="POST" onsubmit="return confirm('Вы уверены, что хотите обновить переводы событий? Это действие нельзя отменить.');">
            <button type="submit" name="apply_translations" class="btn btn-primary">
                🌐 Применить переводы
            </button>
        </form>
    </div>
</div>

<style>
.translations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.translation-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 5px;
    border: 1px solid #e1e5e9;
}

.translation-item h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.translation-item p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.event-card {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.event-card h3 {
    margin: 0 0 1rem 0;
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 0.5rem;
}

.events-list {
    max-height: 600px;
    overflow-y: auto;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.alert {
    padding: 1rem;
    border-radius: 5px;
    margin: 1rem 0;
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

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
