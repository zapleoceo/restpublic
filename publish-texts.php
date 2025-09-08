<?php
// Скрипт для публикации текстов через админку
// Следует правилам проекта - изменения только через git

require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    echo "🚀 Публикация текстов через админку...\n\n";
    
    // Проверяем полноту переводов
    $incompleteTexts = $textsCollection->find([
        '$or' => [
            ['translations.ru' => ['$exists' => false, '$eq' => '']],
            ['translations.en' => ['$exists' => false, '$eq' => '']],
            ['translations.vi' => ['$exists' => false, '$eq' => '']]
        ]
    ])->toArray();
    
    if (!empty($incompleteTexts)) {
        echo "⚠️  Найдены тексты с неполными переводами:\n";
        foreach ($incompleteTexts as $text) {
            echo "- " . $text['key'] . "\n";
        }
        echo "\nПубликация невозможна. Сначала завершите переводы.\n";
        exit(1);
    }
    
    // Создаем резервную копию
    $backupCollection = $db->admin_texts_backup;
    $allTexts = $textsCollection->find()->toArray();
    
    if (!empty($allTexts)) {
        $backupCollection->insertMany($allTexts);
        echo "✅ Создана резервная копия\n";
    }
    
    // Публикуем все тексты
    $result = $textsCollection->updateMany(
        [],
        ['$set' => [
            'published' => true,
            'published_at' => new MongoDB\BSON\UTCDateTime(),
            'published_by' => 'system'
        ]]
    );
    
    echo "✅ Опубликовано " . $result->getModifiedCount() . " текстов\n";
    
    // Проверяем результат
    $publishedCount = $textsCollection->countDocuments(['published' => true]);
    echo "📊 Всего опубликованных текстов: $publishedCount\n";
    
    echo "\n🎉 Публикация завершена успешно!\n";
    echo "Теперь тексты доступны на сайте через TextManager.\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка публикации: " . $e->getMessage() . "\n";
    exit(1);
}
?>
