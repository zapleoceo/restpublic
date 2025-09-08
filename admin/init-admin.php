<?php
// Скрипт инициализации админ-панели
// Создает необходимые коллекции и настройки

require_once __DIR__ . '/../vendor/autoload.php';

echo "🚀 Инициализация админ-панели North Republic...\n\n";

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    
    echo "✅ Подключение к MongoDB установлено\n";
    
    // Создаем коллекции
    $collections = [
        'admin_users' => 'Пользователи админки',
        'admin_texts' => 'Тексты сайта',
        'admin_images' => 'Изображения',
        'admin_logs' => 'Логи действий',
        'admin_settings' => 'Настройки системы',
        'sepay_transactions' => 'Транзакции Sepay'
    ];
    
    foreach ($collections as $collectionName => $description) {
        $collection = $db->selectCollection($collectionName);
        
        // Создаем индексы для оптимизации
        switch ($collectionName) {
            case 'admin_users':
                $collection->createIndex(['username' => 1], ['unique' => true]);
                $collection->createIndex(['telegram_id' => 1], ['unique' => true]);
                break;
                
            case 'admin_texts':
                $collection->createIndex(['key' => 1], ['unique' => true]);
                $collection->createIndex(['category' => 1]);
                $collection->createIndex(['published' => 1]);
                break;
                
            case 'admin_images':
                $collection->createIndex(['filename' => 1], ['unique' => true]);
                $collection->createIndex(['category' => 1]);
                $collection->createIndex(['uploaded_at' => -1]);
                break;
                
            case 'admin_logs':
                $collection->createIndex(['timestamp' => -1]);
                $collection->createIndex(['action_type' => 1]);
                $collection->createIndex(['username' => 1]);
                break;
                
            case 'sepay_transactions':
                $collection->createIndex(['transaction_id' => 1], ['unique' => true]);
                $collection->createIndex(['timestamp' => -1]);
                $collection->createIndex(['status' => 1]);
                break;
        }
        
        echo "✅ Коллекция '{$collectionName}' создана ({$description})\n";
    }
    
    // Создаем первого админа
    $usersCollection = $db->admin_users;
    $existingAdmin = $usersCollection->findOne(['username' => 'zapleosoft']);
    
    if (!$existingAdmin) {
        $adminUser = [
            'username' => 'zapleosoft',
            'telegram_id' => null, // Будет заполнено при первом входе через Telegram
            'role' => 'admin',
            'is_active' => true,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'last_login' => null
        ];
        
        $usersCollection->insertOne($adminUser);
        echo "✅ Создан первый администратор: zapleosoft\n";
    } else {
        echo "ℹ️  Администратор zapleosoft уже существует\n";
    }
    
    // Создаем настройки по умолчанию
    $settingsCollection = $db->admin_settings;
    $existingSettings = $settingsCollection->findOne(['_id' => 'main_settings']);
    
    if (!$existingSettings) {
        $defaultSettings = [
            '_id' => 'main_settings',
            'site_name' => 'North Republic',
            'site_description' => 'Ресторан в Нячанге',
            'default_language' => 'ru',
            'session_timeout' => 6,
            'max_upload_size' => 10,
            'webp_quality' => 85,
            'enable_logging' => true,
            'log_retention_days' => 30,
            'backup_enabled' => false,
            'backup_frequency' => 'daily',
            'telegram_bot_token' => '',
            'telegram_webhook_url' => '',
            'sepay_api_token' => '',
            'sepay_webhook_url' => '',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'created_by' => 'system'
        ];
        
        $settingsCollection->insertOne($defaultSettings);
        echo "✅ Созданы настройки по умолчанию\n";
    } else {
        echo "ℹ️  Настройки уже существуют\n";
    }
    
    // Создаем директории для изображений
    $imageDirs = [
        '../../images/original',
        '../../images/webp'
    ];
    
    foreach ($imageDirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "✅ Создана директория: {$dir}\n";
            } else {
                echo "❌ Ошибка создания директории: {$dir}\n";
            }
        } else {
            echo "ℹ️  Директория уже существует: {$dir}\n";
        }
    }
    
    // Проверяем расширения PHP
    $requiredExtensions = ['mongodb', 'gd'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $extension) {
        if (!extension_loaded($extension)) {
            $missingExtensions[] = $extension;
        }
    }
    
    if (!empty($missingExtensions)) {
        echo "⚠️  Отсутствуют расширения PHP: " . implode(', ', $missingExtensions) . "\n";
        echo "   Установите их для полной функциональности\n";
    } else {
        echo "✅ Все необходимые расширения PHP установлены\n";
    }
    
    echo "\n🎉 Инициализация завершена успешно!\n";
    echo "\n📋 Следующие шаги:\n";
    echo "1. Перейдите в админ-панель: /admin/\n";
    echo "2. Войдите с логином: zapleosoft\n";
    echo "3. Инициализируйте базовые тексты: /admin/texts/init-texts.php\n";
    echo "4. Настройте интеграции в разделе 'Настройки'\n";
    echo "\n🔗 Полезные ссылки:\n";
    echo "- Главная админки: /admin/\n";
    echo "- Управление текстами: /admin/texts/\n";
    echo "- Управление изображениями: /admin/images/\n";
    echo "- База данных: /admin/database/\n";
    echo "- Логи: /admin/logs/\n";
    echo "- Настройки: /admin/settings/\n";
    echo "- Логи Sepay: /admin/sepay/\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка инициализации: " . $e->getMessage() . "\n";
    echo "Проверьте подключение к MongoDB и права доступа\n";
}
?>
