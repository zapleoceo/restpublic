<?php
/**
 * Инициализация новой структуры БД для полного HTML контента страниц
 * Создает коллекцию page_content для хранения полного HTML каждой страницы
 */

require_once '../includes/db.php';

try {
    $collection = $db->page_content;
    
    // Создаем индексы для быстрого поиска
    $collection->createIndex(['page' => 1, 'language' => 1], ['unique' => true]);
    $collection->createIndex(['page' => 1]);
    $collection->createIndex(['language' => 1]);
    $collection->createIndex(['updated_at' => -1]);
    
    echo "✅ Коллекция page_content создана с индексами\n";
    
    // Создаем базовые записи для главной страницы
    $pages = [
        'index' => 'Главная страница',
        'menu' => 'Страница меню', 
        'about' => 'О нас',
        'contact' => 'Контакты'
    ];
    
    $languages = ['ru', 'en', 'vi'];
    
    foreach ($pages as $page => $description) {
        foreach ($languages as $lang) {
            $existing = $collection->findOne(['page' => $page, 'language' => $lang]);
            
            if (!$existing) {
                $document = [
                    'page' => $page,
                    'language' => $lang,
                    'content' => getDefaultContent($page, $lang),
                    'meta' => [
                        'title' => getDefaultTitle($page, $lang),
                        'description' => getDefaultDescription($page, $lang),
                        'keywords' => getDefaultKeywords($page, $lang)
                    ],
                    'status' => 'draft', // draft, published
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_by' => 'system'
                ];
                
                $collection->insertOne($document);
                echo "✅ Создана запись: {$page} ({$lang})\n";
            }
        }
    }
    
    echo "\n🎉 Инициализация завершена!\n";
    echo "Создано страниц: " . count($pages) . "\n";
    echo "Языков: " . count($languages) . "\n";
    echo "Всего записей: " . (count($pages) * count($languages)) . "\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

function getDefaultContent($page, $lang) {
    $content = [
        'ru' => [
            'index' => '<div class="intro-header">
                <div class="intro-header__overline">Добро пожаловать в</div>
                <h1 class="intro-header__big-type">North Republic</h1>
                <p class="lead">Добро пожаловать в <strong>North Republic</strong> — место, где встречаются изысканная кухня, уютная атмосфера и незабываемые моменты.</p>
            </div>
            <div class="about-section">
                <h2 class="text-display-title">О нас</h2>
                <p class="lead">Добро пожаловать в <strong>«Республику Север»</strong> — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга.</p>
            </div>',
            'menu' => '<div class="menu-header">
                <h1 class="text-display-title">Наше меню</h1>
                <p class="lead">Откройте для себя изысканные блюда и напитки в нашем ресторане.</p>
            </div>',
            'about' => '<div class="about-header">
                <h1 class="text-display-title">О нас</h1>
                <p class="lead">Узнайте больше о нашей истории и философии.</p>
            </div>',
            'contact' => '<div class="contact-header">
                <h1 class="text-display-title">Контакты</h1>
                <p class="lead">Свяжитесь с нами для бронирования или вопросов.</p>
            </div>'
        ],
        'en' => [
            'index' => '<div class="intro-header">
                <div class="intro-header__overline">Welcome to</div>
                <h1 class="intro-header__big-type">North Republic</h1>
                <p class="lead">Welcome to <strong>North Republic</strong> — where exquisite cuisine, cozy atmosphere and unforgettable moments meet.</p>
            </div>
            <div class="about-section">
                <h2 class="text-display-title">About Us</h2>
                <p class="lead">Welcome to <strong>North Republic</strong> — an oasis of adventure and gastronomic discoveries among the majestic landscapes of northern Nha Trang.</p>
            </div>',
            'menu' => '<div class="menu-header">
                <h1 class="text-display-title">Our Menu</h1>
                <p class="lead">Discover exquisite dishes and drinks in our restaurant.</p>
            </div>',
            'about' => '<div class="about-header">
                <h1 class="text-display-title">About Us</h1>
                <p class="lead">Learn more about our history and philosophy.</p>
            </div>',
            'contact' => '<div class="contact-header">
                <h1 class="text-display-title">Contact</h1>
                <p class="lead">Contact us for reservations or questions.</p>
            </div>'
        ],
        'vi' => [
            'index' => '<div class="intro-header">
                <div class="intro-header__overline">Chào mừng đến với</div>
                <h1 class="intro-header__big-type">North Republic</h1>
                <p class="lead">Chào mừng đến với <strong>North Republic</strong> — nơi ẩm thực tinh tế, không khí ấm cúng và những khoảnh khắc khó quên gặp gỡ.</p>
            </div>
            <div class="about-section">
                <h2 class="text-display-title">Về chúng tôi</h2>
                <p class="lead">Chào mừng đến với <strong>North Republic</strong> — ốc đảo của những cuộc phiêu lưu và khám phá ẩm thực giữa cảnh quan hùng vĩ của miền bắc Nha Trang.</p>
            </div>',
            'menu' => '<div class="menu-header">
                <h1 class="text-display-title">Thực đơn của chúng tôi</h1>
                <p class="lead">Khám phá những món ăn và đồ uống tinh tế trong nhà hàng của chúng tôi.</p>
            </div>',
            'about' => '<div class="about-header">
                <h1 class="text-display-title">Về chúng tôi</h1>
                <p class="lead">Tìm hiểu thêm về lịch sử và triết lý của chúng tôi.</p>
            </div>',
            'contact' => '<div class="contact-header">
                <h1 class="text-display-title">Liên hệ</h1>
                <p class="lead">Liên hệ với chúng tôi để đặt bàn hoặc câu hỏi.</p>
            </div>'
        ]
    ];
    
    return $content[$lang][$page] ?? $content['ru'][$page];
}

function getDefaultTitle($page, $lang) {
    $titles = [
        'ru' => [
            'index' => 'North Republic - Ресторан в Нячанге',
            'menu' => 'Меню - North Republic',
            'about' => 'О нас - North Republic',
            'contact' => 'Контакты - North Republic'
        ],
        'en' => [
            'index' => 'North Republic - Restaurant in Nha Trang',
            'menu' => 'Menu - North Republic',
            'about' => 'About Us - North Republic',
            'contact' => 'Contact - North Republic'
        ],
        'vi' => [
            'index' => 'North Republic - Nhà hàng tại Nha Trang',
            'menu' => 'Thực đơn - North Republic',
            'about' => 'Về chúng tôi - North Republic',
            'contact' => 'Liên hệ - North Republic'
        ]
    ];
    
    return $titles[$lang][$page] ?? $titles['ru'][$page];
}

function getDefaultDescription($page, $lang) {
    $descriptions = [
        'ru' => [
            'index' => 'North Republic - изысканный ресторан в Нячанге с великолепной кухней и уютной атмосферой.',
            'menu' => 'Откройте для себя изысканные блюда и напитки в нашем ресторане North Republic.',
            'about' => 'Узнайте больше о нашей истории, философии и команде в North Republic.',
            'contact' => 'Свяжитесь с нами для бронирования столика или получения дополнительной информации.'
        ],
        'en' => [
            'index' => 'North Republic - exquisite restaurant in Nha Trang with magnificent cuisine and cozy atmosphere.',
            'menu' => 'Discover exquisite dishes and drinks at our North Republic restaurant.',
            'about' => 'Learn more about our history, philosophy and team at North Republic.',
            'contact' => 'Contact us to book a table or get additional information.'
        ],
        'vi' => [
            'index' => 'North Republic - nhà hàng tinh tế tại Nha Trang với ẩm thực tuyệt vời và không khí ấm cúng.',
            'menu' => 'Khám phá những món ăn và đồ uống tinh tế tại nhà hàng North Republic của chúng tôi.',
            'about' => 'Tìm hiểu thêm về lịch sử, triết lý và đội ngũ của chúng tôi tại North Republic.',
            'contact' => 'Liên hệ với chúng tôi để đặt bàn hoặc nhận thông tin bổ sung.'
        ]
    ];
    
    return $descriptions[$lang][$page] ?? $descriptions['ru'][$page];
}

function getDefaultKeywords($page, $lang) {
    $keywords = [
        'ru' => [
            'index' => 'ресторан, нячанг, вьетнам, кухня, еда, ужин, обед',
            'menu' => 'меню, блюда, напитки, ресторан, нячанг',
            'about' => 'о нас, история, команда, ресторан, нячанг',
            'contact' => 'контакты, адрес, телефон, бронирование, нячанг'
        ],
        'en' => [
            'index' => 'restaurant, nha trang, vietnam, cuisine, food, dinner, lunch',
            'menu' => 'menu, dishes, drinks, restaurant, nha trang',
            'about' => 'about us, history, team, restaurant, nha trang',
            'contact' => 'contact, address, phone, reservation, nha trang'
        ],
        'vi' => [
            'index' => 'nhà hàng, nha trang, việt nam, ẩm thực, thức ăn, tối, trưa',
            'menu' => 'thực đơn, món ăn, đồ uống, nhà hàng, nha trang',
            'about' => 'về chúng tôi, lịch sử, đội ngũ, nhà hàng, nha trang',
            'contact' => 'liên hệ, địa chỉ, điện thoại, đặt bàn, nha trang'
        ]
    ];
    
    return $keywords[$lang][$page] ?? $keywords['ru'][$page];
}
?>
