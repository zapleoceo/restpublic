<?php
// Скрипт для инициализации базовых текстов сайта
// Запускать только для первоначальной настройки!

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    // Базовые тексты сайта
    $baseTexts = [
        // Главная страница
        [
            'key' => 'intro_welcome_text',
            'category' => 'intro',
            'translations' => [
                'ru' => 'Добро пожаловать в',
                'en' => 'Welcome to',
                'vi' => 'Chào mừng đến với'
            ]
        ],
        [
            'key' => 'intro_restaurant_name',
            'category' => 'intro',
            'translations' => [
                'ru' => 'North Republic',
                'en' => 'North Republic',
                'vi' => 'North Republic'
            ]
        ],
        [
            'key' => 'intro_description',
            'category' => 'intro',
            'translations' => [
                'ru' => 'Добро пожаловать в North Republic — место, где встречаются изысканная кухня, уютная атмосфера и незабываемые моменты.',
                'en' => 'Welcome to North Republic — a place where exquisite cuisine, cozy atmosphere and unforgettable moments meet.',
                'vi' => 'Chào mừng đến với North Republic — nơi gặp gỡ giữa ẩm thực tinh tế, bầu không khí ấm cúng và những khoảnh khắc khó quên.'
            ]
        ],
        
        // О нас
        [
            'key' => 'about_title',
            'category' => 'about',
            'translations' => [
                'ru' => 'О нас',
                'en' => 'About Us',
                'vi' => 'Về chúng tôi'
            ]
        ],
        [
            'key' => 'about_description_1',
            'category' => 'about',
            'translations' => [
                'ru' => 'Добро пожаловать в «Республику Север» — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга.',
                'en' => 'Welcome to "North Republic" — an oasis of adventure and gastronomic discoveries among the majestic landscapes of northern Nha Trang.',
                'vi' => 'Chào mừng đến với "Cộng hòa Bắc" — ốc đảo của những cuộc phiêu lưu và khám phá ẩm thực giữa cảnh quan hùng vĩ của Nha Trang phía bắc.'
            ]
        ],
        [
            'key' => 'about_description_2',
            'category' => 'about',
            'translations' => [
                'ru' => 'Взгляните вверх — перед вами раскинулись склоны Горы Феи, той самой Ко Тьен, чья мифическая красота веками вдохновляла поэтов и путешественников.',
                'en' => 'Look up — before you stretch the slopes of Fairy Mountain, that very Co Tien, whose mythical beauty has inspired poets and travelers for centuries.',
                'vi' => 'Hãy nhìn lên — trước mặt bạn trải dài những sườn núi của Núi Tiên, chính là Cô Tiên, vẻ đẹp huyền thoại đã truyền cảm hứng cho các nhà thơ và du khách qua nhiều thế kỷ.'
            ]
        ],
        
        // Меню
        [
            'key' => 'menu_title',
            'category' => 'menu',
            'translations' => [
                'ru' => 'Наше меню',
                'en' => 'Our Menu',
                'vi' => 'Thực đơn của chúng tôi'
            ]
        ],
        [
            'key' => 'menu_empty_category',
            'category' => 'menu',
            'translations' => [
                'ru' => 'В этой категории пока нет блюд',
                'en' => 'No dishes in this category yet',
                'vi' => 'Chưa có món ăn nào trong danh mục này'
            ]
        ],
        [
            'key' => 'menu_working_on_it',
            'category' => 'menu',
            'translations' => [
                'ru' => 'Мы работаем над пополнением меню',
                'en' => 'We are working on expanding the menu',
                'vi' => 'Chúng tôi đang làm việc để mở rộng thực đơn'
            ]
        ],
        [
            'key' => 'menu_full_menu_button',
            'category' => 'buttons',
            'translations' => [
                'ru' => 'Открыть полное меню',
                'en' => 'Open Full Menu',
                'vi' => 'Mở thực đơn đầy đủ'
            ]
        ],
        
        // Галерея
        [
            'key' => 'gallery_title',
            'category' => 'gallery',
            'translations' => [
                'ru' => 'Галерея',
                'en' => 'Gallery',
                'vi' => 'Thư viện ảnh'
            ]
        ],
        
        // Подвал
        [
            'key' => 'footer_address_title',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Адрес',
                'en' => 'Address',
                'vi' => 'Địa chỉ'
            ]
        ],
        [
            'key' => 'footer_address',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Trần Khát Chân, Đường Đệ, Nha Trang, Khánh Hòa, Vietnam',
                'en' => 'Trần Khát Chân, Đường Đệ, Nha Trang, Khánh Hòa, Vietnam',
                'vi' => 'Trần Khát Chân, Đường Đệ, Nha Trang, Khánh Hòa, Vietnam'
            ]
        ],
        [
            'key' => 'footer_contacts_title',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Контакты',
                'en' => 'Contacts',
                'vi' => 'Liên hệ'
            ]
        ],
        [
            'key' => 'footer_working_hours_title',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Часы работы',
                'en' => 'Working Hours',
                'vi' => 'Giờ làm việc'
            ]
        ],
        [
            'key' => 'footer_working_hours_weekdays',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Будни: 8:00 - 22:00',
                'en' => 'Weekdays: 8:00 - 22:00',
                'vi' => 'Ngày thường: 8:00 - 22:00'
            ]
        ],
        [
            'key' => 'footer_working_hours_weekends',
            'category' => 'footer',
            'translations' => [
                'ru' => 'Выходные: 9:00 - 23:00',
                'en' => 'Weekends: 9:00 - 23:00',
                'vi' => 'Cuối tuần: 9:00 - 23:00'
            ]
        ],
        [
            'key' => 'footer_copyright',
            'category' => 'footer',
            'translations' => [
                'ru' => '© 2025 North Republic. Все права защищены.',
                'en' => '© 2025 North Republic. All rights reserved.',
                'vi' => '© 2025 North Republic. Tất cả quyền được bảo lưu.'
            ]
        ],
        
        // Шапка
        [
            'key' => 'header_home',
            'category' => 'header',
            'translations' => [
                'ru' => 'Главная',
                'en' => 'Home',
                'vi' => 'Trang chủ'
            ]
        ],
        [
            'key' => 'header_about',
            'category' => 'header',
            'translations' => [
                'ru' => 'О нас',
                'en' => 'About',
                'vi' => 'Về chúng tôi'
            ]
        ],
        [
            'key' => 'header_menu',
            'category' => 'header',
            'translations' => [
                'ru' => 'Меню',
                'en' => 'Menu',
                'vi' => 'Thực đơn'
            ]
        ],
        [
            'key' => 'header_gallery',
            'category' => 'header',
            'translations' => [
                'ru' => 'Галерея',
                'en' => 'Gallery',
                'vi' => 'Thư viện ảnh'
            ]
        ],
        
        // Кнопки
        [
            'key' => 'button_back_to_home',
            'category' => 'buttons',
            'translations' => [
                'ru' => 'Вернуться на главную',
                'en' => 'Back to Home',
                'vi' => 'Về trang chủ'
            ]
        ],
        [
            'key' => 'button_refresh_page',
            'category' => 'buttons',
            'translations' => [
                'ru' => 'Обновить страницу',
                'en' => 'Refresh Page',
                'vi' => 'Làm mới trang'
            ]
        ],
        
        // Ошибки
        [
            'key' => 'error_menu_not_available',
            'category' => 'errors',
            'translations' => [
                'ru' => 'Упс, что-то с меню не так',
                'en' => 'Oops, something is wrong with the menu',
                'vi' => 'Ôi, có gì đó không ổn với thực đơn'
            ]
        ],
        [
            'key' => 'error_menu_try_later',
            'category' => 'errors',
            'translations' => [
                'ru' => 'К сожалению, меню временно недоступно. Попробуйте обновить страницу или зайти позже.',
                'en' => 'Unfortunately, the menu is temporarily unavailable. Try refreshing the page or come back later.',
                'vi' => 'Thật không may, thực đơn tạm thời không khả dụng. Hãy thử làm mới trang hoặc quay lại sau.'
            ]
        ]
    ];
    
    // Добавляем метаданные к каждому тексту
    foreach ($baseTexts as &$text) {
        $text['created_at'] = new MongoDB\BSON\UTCDateTime();
        $text['updated_at'] = new MongoDB\BSON\UTCDateTime();
        $text['created_by'] = 'system';
        $text['published'] = false;
    }
    
    // Проверяем, есть ли уже тексты
    $existingCount = $textsCollection->countDocuments();
    
    if ($existingCount > 0) {
        echo "В базе данных уже есть $existingCount текстов. Пропускаем инициализацию.\n";
        echo "Если хотите пересоздать тексты, сначала очистите коллекцию.\n";
    } else {
        // Вставляем базовые тексты
        $result = $textsCollection->insertMany($baseTexts);
        
        echo "Инициализация завершена!\n";
        echo "Добавлено " . $result->getInsertedCount() . " базовых текстов.\n";
        echo "Категории: " . implode(', ', array_unique(array_column($baseTexts, 'category'))) . "\n";
    }
    
    // Показываем статистику
    $totalCount = $textsCollection->countDocuments();
    $categories = $textsCollection->distinct('category');
    
    echo "\nТекущая статистика:\n";
    echo "Всего текстов: $totalCount\n";
    echo "Категорий: " . count($categories) . "\n";
    echo "Категории: " . implode(', ', $categories) . "\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
