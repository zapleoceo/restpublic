<?php
/**
 * Миграция существующих переводов в новую систему полного HTML контента
 * Переносит данные из коллекции translations в page_content
 */

require_once '../includes/db.php';
require_once '../../classes/PageContentService.php';

try {
    $pageContentService = new PageContentService();
    $translationsCollection = $db->translations;
    $pageContentCollection = $db->page_content;
    
    echo "🚀 Начинаем миграцию переводов в новую систему...\n\n";
    
    // Получаем все существующие переводы
    $translations = $translationsCollection->find([]);
    $translationsArray = iterator_to_array($translations);
    
    echo "📊 Найдено переводов: " . count($translationsArray) . "\n";
    
    if (empty($translationsArray)) {
        echo "⚠️ Нет переводов для миграции. Запустите init-translations.php сначала.\n";
        exit;
    }
    
    // Группируем переводы по языкам
    $translationsByLang = [];
    foreach ($translationsArray as $translation) {
        $key = $translation['key'] ?? '';
        foreach (['ru', 'en', 'vi'] as $lang) {
            if (isset($translation[$lang])) {
                $translationsByLang[$lang][$key] = $translation[$lang];
            }
        }
    }
    
    echo "📝 Переводы по языкам:\n";
    foreach ($translationsByLang as $lang => $translations) {
        echo "  - {$lang}: " . count($translations) . " переводов\n";
    }
    echo "\n";
    
    // Создаем полный HTML контент для каждой страницы и языка
    $pages = [
        'index' => [
            'title' => 'Главная страница',
            'content_template' => 'index'
        ],
        'menu' => [
            'title' => 'Страница меню',
            'content_template' => 'menu'
        ],
        'about' => [
            'title' => 'О нас',
            'content_template' => 'about'
        ],
        'contact' => [
            'title' => 'Контакты',
            'content_template' => 'contact'
        ]
    ];
    
    $migratedCount = 0;
    
    foreach ($pages as $pageKey => $pageInfo) {
        echo "📄 Обрабатываем страницу: {$pageInfo['title']}\n";
        
        foreach (['ru', 'en', 'vi'] as $lang) {
            $translations = $translationsByLang[$lang] ?? [];
            
            // Создаем HTML контент на основе переводов
            $htmlContent = generatePageContent($pageKey, $translations, $lang);
            
            // Создаем мета-данные
            $meta = [
                'title' => $translations["meta.title.{$pageKey}"] ?? getDefaultTitle($pageKey, $lang),
                'description' => $translations["meta.description.{$pageKey}"] ?? getDefaultDescription($pageKey, $lang),
                'keywords' => $translations["meta.keywords.{$pageKey}"] ?? getDefaultKeywords($pageKey, $lang)
            ];
            
            // Сохраняем в новую коллекцию
            $result = $pageContentService->savePageContent(
                $pageKey, 
                $lang, 
                $htmlContent, 
                $meta, 
                'published', 
                'migration'
            );
            
            if ($result) {
                echo "  ✅ {$lang}: контент создан\n";
                $migratedCount++;
            } else {
                echo "  ❌ {$lang}: ошибка создания\n";
            }
        }
        echo "\n";
    }
    
    echo "🎉 Миграция завершена!\n";
    echo "📊 Создано записей: {$migratedCount}\n";
    echo "📝 Проверьте результат в админке: /admin/pages/\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка миграции: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . "\n";
    echo "📍 Строка: " . $e->getLine() . "\n";
}

/**
 * Генерация HTML контента на основе переводов
 */
function generatePageContent($pageKey, $translations, $lang) {
    switch ($pageKey) {
        case 'index':
            return generateIndexContent($translations, $lang);
        case 'menu':
            return generateMenuContent($translations, $lang);
        case 'about':
            return generateAboutContent($translations, $lang);
        case 'contact':
            return generateContactContent($translations, $lang);
        default:
            return '<div class="alert alert-warning">Контент не найден</div>';
    }
}

function generateIndexContent($translations, $lang) {
    $welcome = $translations['intro.welcome'] ?? 'Добро пожаловать в';
    $title = $translations['intro.title'] ?? 'North Republic';
    $description = $translations['intro.description'] ?? 'Добро пожаловать в <strong>North Republic</strong> — место, где встречаются изысканная кухня, уютная атмосфера и незабываемые моменты.';
    $aboutTitle = $translations['about.title'] ?? 'О нас';
    $aboutText = $translations['about.paragraph1'] ?? 'Добро пожаловать в <strong>«Республику Север»</strong> — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга.';
    
    return "
    <div class=\"intro-header\">
        <div class=\"intro-header__overline\">{$welcome}</div>
        <h1 class=\"intro-header__big-type\">{$title}</h1>
        <p class=\"lead\">{$description}</p>
    </div>
    
    <div class=\"about-section\">
        <h2 class=\"text-display-title\">{$aboutTitle}</h2>
        <p class=\"lead\">{$aboutText}</p>
    </div>
    ";
}

function generateMenuContent($translations, $lang) {
    $title = $translations['menu.title'] ?? 'Наше меню';
    $description = $translations['menu.description'] ?? 'Откройте для себя изысканные блюда и напитки в нашем ресторане.';
    
    return "
    <div class=\"menu-header\">
        <h1 class=\"text-display-title\">{$title}</h1>
        <p class=\"lead\">{$description}</p>
    </div>
    ";
}

function generateAboutContent($translations, $lang) {
    $title = $translations['about.title'] ?? 'О нас';
    $description = $translations['about.description'] ?? 'Узнайте больше о нашей истории и философии.';
    
    return "
    <div class=\"about-header\">
        <h1 class=\"text-display-title\">{$title}</h1>
        <p class=\"lead\">{$description}</p>
    </div>
    ";
}

function generateContactContent($translations, $lang) {
    $title = $translations['contact.title'] ?? 'Контакты';
    $description = $translations['contact.description'] ?? 'Свяжитесь с нами для бронирования или вопросов.';
    
    return "
    <div class=\"contact-header\">
        <h1 class=\"text-display-title\">{$title}</h1>
        <p class=\"lead\">{$description}</p>
    </div>
    ";
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
