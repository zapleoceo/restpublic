<?php
/**
 * Скрипт для добавления индексов в MongoDB коллекции
 * Запуск: php admin/database/add-indexes.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
    
    echo "🔗 Подключение к MongoDB: {$mongodbUrl}\n";
    echo "📊 База данных: {$dbName}\n\n";
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->selectDatabase($dbName);
    
    // ===== ИНДЕКСЫ ДЛЯ admin_texts =====
    echo "📝 Добавление индексов для admin_texts...\n";
    $adminTextsCollection = $db->selectCollection('admin_texts');
    
    // Индекс по ключу (для быстрого поиска переводов)
    $result = $adminTextsCollection->createIndex(['key' => 1], ['unique' => true]);
    echo "  ✅ Индекс по 'key': {$result}\n";
    
    // Индекс по категории
    $result = $adminTextsCollection->createIndex(['category' => 1]);
    echo "  ✅ Индекс по 'category': {$result}\n";
    
    // Индекс по статусу публикации
    $result = $adminTextsCollection->createIndex(['published' => 1]);
    echo "  ✅ Индекс по 'published': {$result}\n";
    
    // ===== ИНДЕКСЫ ДЛЯ events =====
    echo "\n📅 Добавление индексов для events...\n";
    $eventsCollection = $db->selectCollection('events');
    
    // Индекс по дате (для быстрой выборки событий по периодам)
    $result = $eventsCollection->createIndex(['date' => 1]);
    echo "  ✅ Индекс по 'date': {$result}\n";
    
    // Индекс по статусу активности
    $result = $eventsCollection->createIndex(['is_active' => 1]);
    echo "  ✅ Индекс по 'is_active': {$result}\n";
    
    // Составной индекс (активные события по дате)
    $result = $eventsCollection->createIndex(['is_active' => 1, 'date' => 1]);
    echo "  ✅ Составной индекс по 'is_active' + 'date': {$result}\n";
    
    // ===== ИНДЕКСЫ ДЛЯ users =====
    echo "\n👤 Добавление индексов для users...\n";
    $usersCollection = $db->selectCollection('users');
    
    // Индекс по телефону (для быстрого поиска пользователей)
    $result = $usersCollection->createIndex(['phone' => 1], ['unique' => true]);
    echo "  ✅ Индекс по 'phone': {$result}\n";
    
    // Индекс по telegram_id
    $result = $usersCollection->createIndex(['telegram_id' => 1], ['sparse' => true]);
    echo "  ✅ Индекс по 'telegram_id': {$result}\n";
    
    // Индекс по poster_client_id
    $result = $usersCollection->createIndex(['poster_client_id' => 1], ['sparse' => true]);
    echo "  ✅ Индекс по 'poster_client_id': {$result}\n";
    
    // ===== ИНДЕКСЫ ДЛЯ sepay_transactions =====
    echo "\n💰 Добавление индексов для sepay_transactions...\n";
    $sepayCollection = $db->selectCollection('sepay_transactions');
    
    // Индекс по transaction_id
    $result = $sepayCollection->createIndex(['transaction_id' => 1], ['unique' => true]);
    echo "  ✅ Индекс по 'transaction_id': {$result}\n";
    
    // Индекс по дате получения webhook
    $result = $sepayCollection->createIndex(['webhook_received_at' => -1]);
    echo "  ✅ Индекс по 'webhook_received_at': {$result}\n";
    
    // Индекс по статусу отправки в Telegram
    $result = $sepayCollection->createIndex(['telegram_sent' => 1]);
    echo "  ✅ Индекс по 'telegram_sent': {$result}\n";
    
    // ===== ИНДЕКСЫ ДЛЯ settings =====
    echo "\n⚙️ Добавление индексов для settings...\n";
    $settingsCollection = $db->selectCollection('settings');
    
    // Индекс по ключу
    $result = $settingsCollection->createIndex(['key' => 1], ['unique' => true]);
    echo "  ✅ Индекс по 'key': {$result}\n";
    
    // ===== ИНДЕКСЫ ДЛЯ admin_logs =====
    echo "\n📋 Добавление индексов для admin_logs...\n";
    $adminLogsCollection = $db->selectCollection('admin_logs');
    
    // Индекс по timestamp (для быстрой сортировки логов)
    $result = $adminLogsCollection->createIndex(['timestamp' => -1]);
    echo "  ✅ Индекс по 'timestamp': {$result}\n";
    
    // Индекс по action
    $result = $adminLogsCollection->createIndex(['action' => 1]);
    echo "  ✅ Индекс по 'action': {$result}\n";
    
    // ===== ИНДЕКСЫ ДЛЯ page_content =====
    echo "\n📄 Добавление индексов для page_content...\n";
    $pageContentCollection = $db->selectCollection('page_content');
    
    // Составной индекс по странице и секции (НЕ уникальный, т.к. могут быть дубликаты)
    $result = $pageContentCollection->createIndex(['page' => 1, 'section' => 1]);
    echo "  ✅ Составной индекс по 'page' + 'section': {$result}\n";
    
    // ===== ВЫВОД ВСЕХ ИНДЕКСОВ =====
    echo "\n\n📊 СВОДКА ПО ИНДЕКСАМ:\n";
    echo "═══════════════════════════════════════\n\n";
    
    $collections = [
        'admin_texts',
        'events',
        'users',
        'sepay_transactions',
        'settings',
        'admin_logs',
        'page_content'
    ];
    
    foreach ($collections as $collectionName) {
        $collection = $db->selectCollection($collectionName);
        $indexes = $collection->listIndexes();
        
        echo "📁 {$collectionName}:\n";
        foreach ($indexes as $index) {
            $keyStr = json_encode($index->getKey());
            $name = $index->getName();
            $unique = $index->isUnique() ? ' [UNIQUE]' : '';
            echo "  • {$name}: {$keyStr}{$unique}\n";
        }
        echo "\n";
    }
    
    echo "✅ Все индексы успешно созданы!\n\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}
?>

