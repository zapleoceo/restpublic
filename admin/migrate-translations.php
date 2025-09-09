<?php
/**
 * Простой скрипт миграции переводов
 * Запуск: php admin/migrate-translations.php
 */

// Подключаем autoload
require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists('MongoDB\Client')) {
    echo "❌ MongoDB PHP драйвер не установлен.\n";
    echo "Установите: composer require mongodb/mongodb\n";
    exit(1);
}

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    echo "🔄 Инициализация переводов...\n";
    
    // Переводы для навигации
    $translations = [
        // Навигация
        [
            'key' => 'nav.home',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'Главная',
                'en' => 'Home',
                'vi' => 'Trang chủ'
            ],
            'description' => 'Главная страница',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'nav.about',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'О нас',
                'en' => 'About',
                'vi' => 'Giới thiệu'
            ],
            'description' => 'О нас',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'nav.menu',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'Меню',
                'en' => 'Menu',
                'vi' => 'Thực đơn'
            ],
            'description' => 'Меню ресторана',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'nav.gallery',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'Галерея',
                'en' => 'Gallery',
                'vi' => 'Thư viện ảnh'
            ],
            'description' => 'Галерея фотографий',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        
        // Футер
        [
            'key' => 'footer.copyright',
            'category' => 'footer',
            'translations' => [
                'ru' => '© 2025 North Republic. Все права защищены.',
                'en' => '© 2025 North Republic. All rights reserved.',
                'vi' => '© 2025 North Republic. Tất cả quyền được bảo lưu.'
            ],
            'description' => 'Копирайт в футере',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'footer.address_title',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Адрес',
                'en' => 'Address',
                'vi' => 'Địa chỉ'
            ],
            'description' => 'Заголовок адреса в футере',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'footer.contacts_title',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Контакты',
                'en' => 'Contacts',
                'vi' => 'Liên hệ'
            ],
            'description' => 'Заголовок контактов в футере',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'footer.hours_title',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Часы работы',
                'en' => 'Opening Hours',
                'vi' => 'Giờ mở cửa'
            ],
            'description' => 'Заголовок часов работы в футере',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'footer.weekdays',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Будни',
                'en' => 'Weekdays',
                'vi' => 'Ngày thường'
            ],
            'description' => 'Будние дни',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'footer.weekends',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Выходные',
                'en' => 'Weekends',
                'vi' => 'Cuối tuần'
            ],
            'description' => 'Выходные дни',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'key' => 'footer.back_to_top',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Наверх',
                'en' => 'Back to top',
                'vi' => 'Lên đầu trang'
            ],
            'description' => 'Кнопка возврата наверх',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];
    
    $inserted = 0;
    $updated = 0;
    
    foreach ($translations as $translation) {
        $existing = $textsCollection->findOne(['key' => $translation['key']]);
        
        if ($existing) {
            // Обновляем существующий перевод
            $result = $textsCollection->updateOne(
                ['key' => $translation['key']],
                [
                    '$set' => [
                        'translations' => $translation['translations'],
                        'description' => $translation['description'],
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            if ($result->getModifiedCount() > 0) {
                $updated++;
                echo "✅ Обновлен: {$translation['key']}\n";
            }
        } else {
            // Создаем новый перевод
            $result = $textsCollection->insertOne($translation);
            if ($result->getInsertedId()) {
                $inserted++;
                echo "✅ Добавлен: {$translation['key']}\n";
            }
        }
    }
    
    echo "\n📊 Результат:\n";
    echo "   Добавлено: $inserted\n";
    echo "   Обновлено: $updated\n";
    echo "   Всего переводов: " . count($translations) . "\n";
    
    echo "\n🎉 Переводы успешно инициализированы!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
?>
