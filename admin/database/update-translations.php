<?php
/**
 * Скрипт для обновления переводов в MongoDB
 * Добавляет новые переводы для хардкод текстов
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/PageContentService.php';

try {
    $pageContentService = new PageContentService();
    
    echo "🚀 Обновляем переводы в MongoDB...\n\n";
    
    // Новые переводы для всех языков
    $newTranslations = [
        'ru' => [
            'menu_top_5' => 'Top 5 позиций',
            'menu_updated' => 'Обновлено',
            'location_nha_trang' => 'Нячанг',
            'events_title' => 'События',
            'events_widget_title' => 'Афиша событий',
            'events_empty_title' => 'Мы еще не придумали что у нас тут будет.',
            'events_empty_text' => 'Есть идеи?',
            'events_empty_link' => 'Свяжитесь с нами!',
            'intro_image_primary_alt' => 'Главное изображение ресторана North Republic',
            'intro_image_secondary_alt' => 'Дополнительное изображение интерьера ресторана',
            'about_image_primary_alt' => 'Фотография интерьера ресторана North Republic'
        ],
        'en' => [
            'menu_top_5' => 'Top 5 Items',
            'menu_updated' => 'Updated',
            'location_nha_trang' => 'Nha Trang',
            'events_title' => 'Events',
            'events_widget_title' => 'Events Schedule',
            'events_empty_title' => 'We haven\'t figured out what we\'ll have here yet.',
            'events_empty_text' => 'Have ideas?',
            'events_empty_link' => 'Contact us!',
            'intro_image_primary_alt' => 'Main image of North Republic restaurant',
            'intro_image_secondary_alt' => 'Additional interior image of the restaurant',
            'about_image_primary_alt' => 'Interior photo of North Republic restaurant'
        ],
        'vi' => [
            'menu_top_5' => 'Top 5 món',
            'menu_updated' => 'Cập nhật',
            'location_nha_trang' => 'Nha Trang',
            'events_title' => 'Sự kiện',
            'events_widget_title' => 'Lịch sự kiện',
            'events_empty_title' => 'Chúng tôi chưa nghĩ ra sẽ có gì ở đây.',
            'events_empty_text' => 'Có ý tưởng?',
            'events_empty_link' => 'Liên hệ với chúng tôi!',
            'intro_image_primary_alt' => 'Hình ảnh chính của nhà hàng North Republic',
            'intro_image_secondary_alt' => 'Hình ảnh nội thất bổ sung của nhà hàng',
            'about_image_primary_alt' => 'Ảnh nội thất nhà hàng North Republic'
        ]
    ];
    
    $updatedCount = 0;
    
    foreach ($newTranslations as $lang => $translations) {
        echo "📝 Обновляем переводы для языка: {$lang}\n";
        
        // Получаем существующий контент
        $existingContent = $pageContentService->getPageContent('index', $lang);
        
        if ($existingContent) {
            // Обновляем мета-данные с новыми переводами
            $existingMeta = $existingContent['meta'] ?? [];
            $updatedMeta = array_merge($existingMeta, $translations);
            
            // Сохраняем обновленный контент
            $result = $pageContentService->savePageContent(
                'index',
                $lang,
                $existingContent['content'] ?? '',
                $updatedMeta,
                'published',
                'translation_update'
            );
            
            if ($result) {
                echo "  ✅ {$lang}: переводы обновлены (" . count($translations) . " ключей)\n";
                $updatedCount += count($translations);
            } else {
                echo "  ❌ {$lang}: ошибка обновления\n";
            }
        } else {
            echo "  ⚠️ {$lang}: контент не найден, создаем новый\n";
            
            // Создаем новый контент с переводами
            $result = $pageContentService->savePageContent(
                'index',
                $lang,
                'Добро пожаловать в North Republic',
                $translations,
                'published',
                'translation_update'
            );
            
            if ($result) {
                echo "  ✅ {$lang}: новый контент создан\n";
                $updatedCount += count($translations);
            } else {
                echo "  ❌ {$lang}: ошибка создания\n";
            }
        }
    }
    
    echo "\n🎉 Обновление переводов завершено!\n";
    echo "📊 Обновлено переводов: {$updatedCount}\n";
    echo "📝 Проверьте результат: https://northrepublic.me/index3.php\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . "\n";
    echo "📍 Строка: " . $e->getLine() . "\n";
}
?>
