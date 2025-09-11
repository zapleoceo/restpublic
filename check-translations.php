<?php
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

try {
    $client = new MongoDB\Client('mongodb://localhost:27017');
    $db = $client->northrepublic;
    $textsCollection = $db->texts;
    
    echo "🔍 Проверяем переводы для категорий...\n";
    $categoryTexts = $textsCollection->find(['category' => 'menu_categories'])->toArray();
    
    if (empty($categoryTexts)) {
        echo "❌ Переводы для категорий не найдены\n";
        echo "📝 Создаем переводы для категорий...\n";
        
        // Создаем переводы для категорий
        $translations = [
            [
                'key' => 'category_food',
                'category' => 'menu_categories',
                'translations' => [
                    'ru' => 'Еда',
                    'en' => 'Food',
                    'vi' => 'Thức ăn'
                ],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ],
            [
                'key' => 'category_beverages',
                'category' => 'menu_categories',
                'translations' => [
                    'ru' => 'Напитки',
                    'en' => 'Beverages',
                    'vi' => 'Đồ uống'
                ],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ],
            [
                'key' => 'category_alcohol',
                'category' => 'menu_categories',
                'translations' => [
                    'ru' => 'Алкоголь',
                    'en' => 'Alcohol',
                    'vi' => 'Rượu'
                ],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ],
            [
                'key' => 'category_hot_drinks',
                'category' => 'menu_categories',
                'translations' => [
                    'ru' => 'Горячие напитки',
                    'en' => 'Hot drinks',
                    'vi' => 'Đồ uống nóng'
                ],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ],
            [
                'key' => 'category_hookah',
                'category' => 'menu_categories',
                'translations' => [
                    'ru' => 'Кальян',
                    'en' => 'Hookah',
                    'vi' => 'Shisha'
                ],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ]
        ];
        
        foreach ($translations as $translation) {
            $result = $textsCollection->replaceOne(
                ['key' => $translation['key']],
                $translation,
                ['upsert' => true]
            );
            echo "✅ Создан перевод для: " . $translation['key'] . "\n";
        }
        
        echo "\n🎉 Все переводы созданы!\n";
    } else {
        echo "📋 Найдено переводов: " . count($categoryTexts) . "\n";
        foreach ($categoryTexts as $text) {
            echo "  - " . $text['key'] . ": " . json_encode($text['translations']) . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>
