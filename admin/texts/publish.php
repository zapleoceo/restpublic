<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $textsCollection = $db->admin_texts;
        
        // Проверяем полноту переводов
        $incompleteTexts = $textsCollection->find([
            '$or' => [
                ['translations.ru' => ['$exists' => false, '$eq' => '']],
                ['translations.en' => ['$exists' => false, '$eq' => '']],
                ['translations.vi' => ['$exists' => false, '$eq' => '']]
            ]
        ])->toArray();
        
        if (!empty($incompleteTexts)) {
            $error = 'Нельзя опубликовать изменения: есть тексты без полных переводов. Сначала завершите переводы.';
        } else {
            // Создаем резервную копию
            $backupCollection = $db->admin_texts_backup;
            $allTexts = $textsCollection->find()->toArray();
            
            if (!empty($allTexts)) {
                $backupCollection->insertMany($allTexts);
            }
            
            // Обновляем статус публикации
            $textsCollection->updateMany(
                [],
                ['$set' => [
                    'published' => true,
                    'published_at' => new MongoDB\BSON\UTCDateTime(),
                    'published_by' => $_SESSION['admin_username'] ?? 'unknown'
                ]]
            );
            
            // Логируем публикацию
            logAdminAction('publish_texts', 'Опубликованы изменения текстов', [
                'texts_count' => count($allTexts),
                'backup_created' => true
            ]);
            
            $success = 'Изменения успешно опубликованы! Создана резервная копия.';
        }
        
    } catch (Exception $e) {
        $error = 'Ошибка при публикации: ' . $e->getMessage();
    }
}

// Получаем статистику
try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    $stats = [
        'total' => $textsCollection->countDocuments(),
        'published' => $textsCollection->countDocuments(['published' => true]),
        'unpublished' => $textsCollection->countDocuments(['published' => ['$ne' => true]]),
        'incomplete' => $textsCollection->countDocuments([
            '$or' => [
                ['translations.ru' => ['$exists' => false, '$eq' => '']],
                ['translations.en' => ['$exists' => false, '$eq' => '']],
                ['translations.vi' => ['$exists' => false, '$eq' => '']]
            ]
        ])
    ];
    
    // Получаем последнюю публикацию
    $lastPublish = $textsCollection->findOne(
        ['published' => true],
        ['sort' => ['published_at' => -1]]
    );
    
} catch (Exception $e) {
    $stats = ['total' => 0, 'published' => 0, 'unpublished' => 0, 'incomplete' => 0];
    $lastPublish = null;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Публикация изменений - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .publish-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card.total h3 { color: #3498db; }
        .stat-card.published h3 { color: #27ae60; }
        .stat-card.unpublished h3 { color: #f39c12; }
        .stat-card.incomplete h3 { color: #e74c3c; }
        
        .publish-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .publish-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .publish-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .last-publish {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
        }
        
        .btn-publish {
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-publish:hover {
            background: #218838;
        }
        
        .btn-publish:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Публикация изменений</h1>
                <p>Применение изменений текстов на сайте</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="publish-container">
                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card total">
                        <h3><?php echo number_format($stats['total']); ?></h3>
                        <p>Всего текстов</p>
                    </div>
                    <div class="stat-card published">
                        <h3><?php echo number_format($stats['published']); ?></h3>
                        <p>Опубликовано</p>
                    </div>
                    <div class="stat-card unpublished">
                        <h3><?php echo number_format($stats['unpublished']); ?></h3>
                        <p>Не опубликовано</p>
                    </div>
                    <div class="stat-card incomplete">
                        <h3><?php echo number_format($stats['incomplete']); ?></h3>
                        <p>Неполные переводы</p>
                    </div>
                </div>
                
                <!-- Последняя публикация -->
                <?php if ($lastPublish): ?>
                    <div class="last-publish">
                        <h4>Последняя публикация</h4>
                        <p>
                            Дата: <?php echo $lastPublish['published_at']->toDateTime()->format('d.m.Y H:i:s'); ?> | 
                            Автор: <?php echo htmlspecialchars($lastPublish['published_by'] ?? 'Unknown'); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Предупреждения -->
                <?php if ($stats['incomplete'] > 0): ?>
                    <div class="publish-warning">
                        <h4>⚠️ Внимание!</h4>
                        <p>У вас есть <?php echo $stats['incomplete']; ?> текстов с неполными переводами. Сначала завершите все переводы перед публикацией.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Информация о публикации -->
                <div class="publish-info">
                    <h4>ℹ️ Информация о публикации</h4>
                    <ul>
                        <li>Публикация применит все изменения текстов на сайте</li>
                        <li>Будет создана резервная копия текущих текстов</li>
                        <li>Изменения вступят в силу немедленно</li>
                        <li>Все переводы должны быть завершены</li>
                    </ul>
                </div>
                
                <!-- Форма публикации -->
                <div class="publish-card">
                    <h3>Публиковать изменения</h3>
                    
                    <?php if ($stats['incomplete'] > 0): ?>
                        <p>Нельзя опубликовать изменения из-за неполных переводов.</p>
                        <a href="index.php" class="btn btn-secondary">Вернуться к текстам</a>
                    <?php else: ?>
                        <form method="POST">
                            <p>Готовы опубликовать <?php echo $stats['unpublished']; ?> изменений?</p>
                            <button type="submit" class="btn-publish" 
                                    onclick="return confirm('Вы уверены, что хотите опубликовать изменения? Это действие нельзя отменить.')">
                                🚀 Опубликовать изменения
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Действия -->
                <div class="publish-card">
                    <h3>Дополнительные действия</h3>
                    <div style="display: flex; gap: 1rem;">
                        <a href="index.php" class="btn btn-secondary">Вернуться к текстам</a>
                        <a href="export.php" class="btn btn-secondary">Экспорт текстов</a>
                        <a href="import.php" class="btn btn-secondary">Импорт текстов</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
