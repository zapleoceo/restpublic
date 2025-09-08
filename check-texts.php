<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    
    echo "=== Проверка текстов в БД ===\n\n";
    
    // Проверяем конкретный текст
    $text = $db->admin_texts->findOne(['key' => 'intro_welcome_text']);
    
    if ($text) {
        echo "✅ Текст найден:\n";
        echo "Key: " . $text['key'] . "\n";
        echo "Published: " . (isset($text['published']) ? ($text['published'] ? 'true' : 'false') : 'not set') . "\n";
        echo "Translations: " . json_encode($text['translations'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Текст не найден\n";
    }
    
    echo "\n=== Статистика всех текстов ===\n";
    
    $total = $db->admin_texts->countDocuments();
    $published = $db->admin_texts->countDocuments(['published' => true]);
    $unpublished = $db->admin_texts->countDocuments(['published' => ['$ne' => true]]);
    
    echo "Всего текстов: $total\n";
    echo "Опубликовано: $published\n";
    echo "Не опубликовано: $unpublished\n";
    
    if ($unpublished > 0) {
        echo "\n=== Неопубликованные тексты ===\n";
        $unpublishedTexts = $db->admin_texts->find(['published' => ['$ne' => true]])->toArray();
        foreach ($unpublishedTexts as $text) {
            echo "- " . $text['key'] . " (published: " . (isset($text['published']) ? ($text['published'] ? 'true' : 'false') : 'not set') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
