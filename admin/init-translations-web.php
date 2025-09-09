<?php
/**
 * Веб-версия скрипта для инициализации переводов
 * Доступ: https://northrepublic.me/admin/init-translations-web.php
 */

session_start();
require_once '../includes/auth-check.php';

// Подключаем MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['init_translations'])) {
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
                }
            } else {
                // Создаем новый перевод
                $result = $textsCollection->insertOne($translation);
                if ($result->getInsertedId()) {
                    $inserted++;
                }
            }
        }
        
        $success = "Переводы успешно инициализированы! Добавлено: $inserted, Обновлено: $updated";
        
        // Логируем действие
        logAdminAction('translations_init', 'Инициализация переводов', [
            'inserted' => $inserted,
            'updated' => $updated,
            'total' => count($translations)
        ]);
    }
    
} catch (Exception $e) {
    $error = "Ошибка: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>Инициализация переводов</h1>
        <p>Добавление переводов для навигации и футера в базу данных</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="admin-section">
        <h2>Переводы для добавления</h2>
        
        <h3>Навигация:</h3>
        <ul>
            <li><strong>Главная</strong> - Home - Trang chủ</li>
            <li><strong>О нас</strong> - About - Giới thiệu</li>
            <li><strong>Меню</strong> - Menu - Thực đơn</li>
            <li><strong>Галерея</strong> - Gallery - Thư viện ảnh</li>
        </ul>
        
        <h3>Футер:</h3>
        <ul>
            <li><strong>Адрес</strong> - Address - Địa chỉ</li>
            <li><strong>Контакты</strong> - Contacts - Liên hệ</li>
            <li><strong>Часы работы</strong> - Opening Hours - Giờ mở cửa</li>
            <li><strong>Будни</strong> - Weekdays - Ngày thường</li>
            <li><strong>Выходные</strong> - Weekends - Cuối tuần</li>
            <li><strong>Наверх</strong> - Back to top - Lên đầu trang</li>
            <li><strong>Копирайт</strong> - Copyright - Bản quyền</li>
        </ul>
        
        <form method="POST" style="margin-top: 2rem;">
            <button type="submit" name="init_translations" class="btn btn-primary">
                Инициализировать переводы
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
