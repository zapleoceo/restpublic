<?php
/**
 * Главная страница с новой системой полного HTML контента
 * Использует PageContentService для получения контента из БД
 * Сохраняет правильную структуру и дизайн из index.php
 */

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Обновляем кеш меню при заходе на главную страницу (в фоновом режиме, реже)
function updateMenuCacheAsync() {
    try {
        $cacheUrl = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/api/cache/update-menu';
        
        // Создаем контекст для асинхронного запроса
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 5, // Короткий таймаут, чтобы не блокировать загрузку страницы
                'header' => 'Content-Type: application/json',
                'ignore_errors' => true // Игнорируем ошибки, чтобы не влиять на отображение страницы
            ]
        ]);
        
        // Выполняем запрос в фоновом режиме
        $result = @file_get_contents($cacheUrl, false, $context);
        
        if ($result === false) {
            error_log("Failed to update menu cache: " . error_get_last()['message'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        error_log("Menu cache update error: " . $e->getMessage());
    }
}

// Инициализируем сервис настроек для работы с MongoDB
require_once __DIR__ . '/classes/SettingsService.php';
$settingsService = new SettingsService();

// Проверяем, нужно ли проверять необходимость обновления (раз в 5 минут)
$shouldCheckForUpdate = $settingsService->shouldCheckForUpdate(300); // 5 минут

if ($shouldCheckForUpdate) {
    // Обновляем время последней проверки
    $settingsService->setLastUpdateCheckTime();
    
    // Проверяем, нужно ли обновлять кеш (раз в час)
    $shouldUpdateCache = $settingsService->shouldUpdateMenu(3600); // 1 час
    
    if ($shouldUpdateCache) {
        // Обновляем кеш асинхронно
        updateMenuCacheAsync();
        
        // Записываем время последнего обновления
        $settingsService->setLastMenuUpdateTime();
    }
}

// Initialize page content service
require_once __DIR__ . '/classes/PageContentService.php';
$pageContentService = new PageContentService();

// Initialize translation service for components
require_once __DIR__ . '/classes/TranslationService.php';
$translationService = new TranslationService();

// Initialize events service
require_once __DIR__ . '/classes/EventsService.php';
$eventsService = new EventsService();

// Load category translator
require_once __DIR__ . '/category-translator.php';

// Get current language
$currentLanguage = $pageContentService->getLanguage();

// Get full page content from database
$pageContent = $pageContentService->getPageContent('index', $currentLanguage);
$pageMeta = $pageContent['meta'] ?? [];

// Helper function for safe HTML output
function safeHtml($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// Helper function for image alt text
function getImageAlt($metaKey, $default) {
    global $pageMeta;
    return safeHtml($pageMeta[$metaKey] ?? $default);
}

// No fallback - only database content

// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

try {
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        // API configuration for popular products
        $api_base_url = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/api';
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        // Get popular products by category using real sales data
        if ($categories) {
            foreach ($categories as $category) {
                $categoryId = (string)($category['category_id']);
                $productsByCategory[$categoryId] = [];
                
                // Try to get popular products from API
                try {
                    $authToken = $_ENV['API_AUTH_TOKEN'] ?? 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
                    $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=5&token=' . urlencode($authToken);
                    $popularResponse = @file_get_contents($popularUrl, false, $context);
                    
                    if ($popularResponse !== false) {
                        $popularData = json_decode($popularResponse, true);
                        if ($popularData && isset($popularData['popular_products'])) {
                            $productsByCategory[$categoryId] = $popularData['popular_products'];
                        } else {
                            error_log("Invalid API response for category {$categoryId}: " . substr($popularResponse, 0, 200));
                            $productsByCategory[$categoryId] = [];
                        }
                    } else {
                        error_log("Failed to fetch popular products for category {$categoryId}");
                        $productsByCategory[$categoryId] = [];
                    }
                } catch (Exception $e) {
                    error_log("API error for category {$categoryId}: " . $e->getMessage());
                    // Fallback to empty array if API fails
                    $productsByCategory[$categoryId] = [];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Menu loading error: " . $e->getMessage());
    // Fallback: ensure we have empty arrays to prevent errors
    $categories = [];
    $products = [];
    $productsByCategory = [];
}

// Set page title and meta tags from database only
$pageTitle = $pageMeta['title'] ?? '';
$pageDescription = $pageMeta['description'] ?? '';
$pageKeywords = $pageMeta['keywords'] ?? '';
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>" class="no-js">
<head>
    <!--- basic page needs
    ================================================== -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo safeHtml($pageTitle); ?></title>
    <meta name="description" content="<?php echo safeHtml($pageDescription); ?>">
    <meta name="keywords" content="<?php echo safeHtml($pageKeywords); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://veranda.my/">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="css/events-widget.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    
    <!-- Preload critical resources -->
    <link rel="preload" href="fonts/MinionPro-Regular.otf" as="font" type="font/otf" crossorigin>
    <link rel="preload" href="js/main.js" as="script">
    
    <style>
        :root {
            --intro-text-opacity: 0.01; /* 1% прозрачности по умолчанию */
        }
        
        .intro-header__big-type {
            opacity: var(--intro-text-opacity);
        }
        
        .intro-header__subtitle {
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: -5px;
            margin-left: 0;
            text-align: left;
            color: var(--color-text-bold);
            opacity: var(--intro-text-opacity);
            font-family: "Roboto Flex", sans-serif;
        }
        
        .intro-header__overline {
            font-family: "Roboto Flex", sans-serif;
        }
    </style>

    <!-- favicons
    ================================================== -->
    <link rel="icon" type="image/svg+xml" href="template/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://veranda.my/">
    <meta property="og:title" content="<?php echo safeHtml($pageTitle); ?>">
    <meta property="og:description" content="<?php echo safeHtml($pageDescription); ?>">
    <meta property="og:image" content="https://veranda.my/images/logo_2_options.svg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://veranda.my/">
    <meta property="twitter:title" content="<?php echo safeHtml($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo safeHtml($pageDescription); ?>">
    <meta property="twitter:image" content="https://veranda.my/images/logo_2_options.svg">
</head>

<body id="top">
    
    <!-- preloader
    ================================================== -->
    <div id="preloader">
        <div id="loader" class="dots-fade">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <!-- page wrap
    ================================================== -->
    <div id="page" class="s-pagewrap ss-home">

        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- # intro
        ================================================== -->
        <section id="intro" class="container s-intro target-section">
            <div class="grid-block s-intro__content">
                <div class="intro-header">
                    <div class="intro-header__overline"><?php echo $pageMeta['intro_welcome'] ?? ''; ?></div>
                    <h1 class="intro-header__big-type">
                        <?php echo $pageMeta['intro_title'] ?? 'Veranda'; ?>
                    </h1>
                    <div class="intro-header__subtitle">BAR & RESTAURANT</div>
                </div> <!-- end intro-header -->

                <figure class="intro-pic-primary">
                    <img src="<?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?>" 
                         srcset="<?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?> 1x, 
                         <?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?> 2x" 
                         alt="<?php echo getImageAlt('intro_image_primary_alt', 'Главное изображение ресторана Veranda'); ?>">  
                </figure> <!-- end intro-pic-primary -->    
                    
                <div class="intro-block-content">
                    <figure class="intro-block-content__pic">
                        <img src="<?php echo $pageMeta['intro_image_secondary'] ?? 'template/images/intro-pic-secondary.jpg'; ?>" 
                             srcset="<?php echo $pageMeta['intro_image_secondary'] ?? 'template/images/intro-pic-secondary.jpg'; ?> 1x, 
                             <?php echo $pageMeta['intro_image_secondary_2x'] ?? 'template/images/intro-pic-secondary@2x.jpg'; ?> 2x" 
                             alt="<?php echo getImageAlt('intro_image_secondary_alt', 'Дополнительное изображение интерьера ресторана'); ?>"> 
                    </figure>
                    <div class="intro-block-content__text">
                        <p class="lead">
                            <?php echo $pageContent['content'] ?? ''; ?>
                        </p>
                        
                        <ul class="intro-block-content__social">
                            <li>
                                <a href="https://facebook.com/vngamezone" target="_blank" rel="noopener noreferrer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M20,3H4C3.447,3,3,3.448,3,4v16c0,0.552,0.447,1,1,1h8.615v-6.96h-2.338v-2.725h2.338v-2c0-2.325,1.42-3.592,3.5-3.592 c0.699-0.002,1.399,0.034,2.095,0.107v2.42h-1.435c-1.128,0-1.348,0.538-1.348,1.325v1.735h2.697l-0.35,2.725h-2.348V21H20 c0.553,0,1-0.448,1-1V4C21,3.448,20.553,3,20,3z"></path></svg>
                                    <span class="u-screen-reader-text">FB</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://t.me/gamezone_vietnam" target="_blank" rel="noopener noreferrer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"></path></svg>
                                    <span class="u-screen-reader-text">TG</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.instagram.com/veranda.my" target="_blank" rel="noopener noreferrer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M11.999,7.377c-2.554,0-4.623,2.07-4.623,4.623c0,2.554,2.069,4.624,4.623,4.624c2.552,0,4.623-2.07,4.623-4.624 C16.622,9.447,14.551,7.377,11.999,7.377L11.999,7.377z M11.999,15.004c-1.659,0-3.004-1.345-3.004-3.003 c0-1.659,1.345-3.003,3.004-3.003s3.002,1.344,3.002,3.003C15.001,13.659,13.658,15.004,11.999,15.004L11.999,15.004z"></path><circle cx="16.806" cy="7.207" r="1.078"></circle><path d="M20.533,6.111c-0.469-1.209-1.424-2.165-2.633-2.632c-0.699-0.263-1.438-0.404-2.186-0.42 c-0.963-0.042-1.268-0.054-3.71-0.054s-2.755,0-3.71,0.054C7.548,3.074,6.809,3.215,6.11,3.479C4.9,3.946,3.945,4.902,3.477,6.111 c-0.263,0.7-0.404,1.438-0.419,2.186c-0.043,0.962-0.056,1.267-0.056,3.71c0,2.442,0,2.753,0.056,3.71 c0.015,0.748,0.156,1.486,0.419,2.187c0.469,1.208,1.424,2.164,2.634,2.632c0.696,0.272,1.435,0.426,2.185,0.45 c0.963,0.042,1.268,0.055,3.71,0.055s2.755,0,3.71-0.055c0.747-0.015,1.486-0.157,2.186-0.419c1.209-0.469,2.164-1.424,2.633-2.633 c0.263-0.7,0.404-1.438,0.419-2.186c0.043-0.962,0.056-1.267,0.056-3.71s0-2.753-0.056-3.71C20.941,7.57,20.801,6.819,20.533,6.111z M19.315,15.643c-0.007,0.576-0.111,1.147-0.311,1.688c-0.305,0.787-0.926,1.409-1.712,1.711c-0.535,0.199-1.099,0.303-1.67,0.311 c-0.95,0.044-1.218,0.055-3.654,0.055c-2.438,0-2.687,0-3.655-0.055c-0.569-0.007-1.135-0.112-1.669-0.311 c-0.789-0.301-1.414-0.923-1.719-1.711c-0.196-0.534-0.302-1.099-0.311-1.669c-0.043-0.95-0.053-1.218-0.053-3.654 c0-2.437,0-2.686,0.053-3.655c0.007-0.576,0.111-1.146,0.311-1.687c0.305-0.789,0.93-1.41,1.719-1.712 c0.534-0.198,1.1-0.303,1.669-0.311c0.951-0.043,1.218-0.055,3.655-0.055c2.437,0,2.687,0,3.654,0.055 c0.571,0.007,1.135,0.112,1.67,0.311c0.786,0.303,1.407,0.925,1.712,1.712c0.196,0.534,0.302,1.099,0.311,1.669 c0.043,0.951,0.054,1.218,0.054,3.655c0,2.436,0,2.698-0.043,3.654H19.315z"></path></svg>
                                    <span class="u-screen-reader-text">IG</span>
                                </a>
                            </li>
                            <li>
                                <a href="https://www.tiktok.com/@gamezone.vn" target="_blank" rel="noopener noreferrer">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"></path></svg>
                                    <span class="u-screen-reader-text">TT</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div> <!-- end intro-block-content -->

                <div class="intro-scroll">
                    <a class="smoothscroll" href="#about">                            
                        <span class="intro-scroll__circle-text"></span>
                        <span class="intro-scroll__text u-screen-reader-text">Scroll Down</span>
                        <div class="intro-scroll__icon">
                            <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m5.214 14.522s4.505 4.502 6.259 6.255c.146.147.338.22.53.22s.384-.073.53-.22c1.754-1.752 6.249-6.244 6.249-6.244.144-.144.216-.334.217-.523 0-.193-.074-.386-.221-.534-.293-.293-.766-.294-1.057-.004l-4.968 4.968v-14.692c0-.414-.336-.75-.75-.75s-.75.336-.75.75v14.692l-4.979-4.978c-.289-.289-.761-.287-1.054.006-.148.148-.222.341-.221.534 0 .189.071.377.215.52z" fill-rule="nonzero"/></svg>
                        </div>
                    </a>
                </div> <!-- end intro-scroll -->

            </div> <!-- end s-intro__content -->
        </section> <!-- end s-intro -->

        <!-- # about
        ================================================== -->
        <section id="about" class="container s-about target-section">
            <div class="row s-about__content">
                <div class="column xl-4 lg-5 md-12 s-about__content-start">
                    <div class="section-header" data-num="01">
                        <h2 class="text-display-title"><?php echo $pageMeta['about_title'] ?? ''; ?></h2>
                    </div>  

                    <figure class="about-pic-primary">
                        <img src="<?php echo $pageMeta['about_image_primary'] ?? 'template/images/about-pic-primary.jpg'; ?>" 
                             srcset="<?php echo $pageMeta['about_image_primary'] ?? 'template/images/about-pic-primary.jpg'; ?> 1x, 
                             <?php echo $pageMeta['about_image_primary_2x'] ?? 'template/images/about-pic-primary@2x.jpg'; ?> 2x" 
                             alt="<?php echo getImageAlt('about_image_primary_alt', 'Фотография интерьера ресторана Veranda'); ?>"> 
                    </figure>
                </div> <!-- end s-about__content-start -->

                <div class="column xl-6 lg-6 md-12 s-about__content-end">                   
                    <?php echo $pageMeta['about_content'] ?? ''; ?>
                </div> <!-- end s-about__content-end -->
            </div> <!-- end s-about__content -->
        </section> <!-- end s-about -->

        <!-- # menu
        ================================================== -->
        <section id="menu" class="container s-menu target-section">
            <div class="row s-menu__content">
                <div class="column xl-4 lg-5 md-12 s-menu__content-start">
                    <div class="section-header" data-num="02">
                        <h2 class="text-display-title"><?php echo $pageMeta['menu_title'] ?? ''; ?></h2>
                    </div>  

                    <nav class="tab-nav">
                        <ul class="tab-nav__list" id="menu-categories">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <li>
                                        <a href="#" 
                                           class="<?php echo $index === 0 ? 'active' : ''; ?>"
                                           data-tab-id="tab-<?php echo htmlspecialchars($category['category_id']); ?>">
                                            <span><?php echo htmlspecialchars(translateCategoryName($category['category_name'] ?? $category['name'] ?? 'Без названия', getCurrentLanguage())); ?></span>
                                            <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>
                                    <span style="color: #e74c3c; font-style: italic;">
                                        <?php echo $pageMeta['menu_error'] ?? ''; ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav> <!-- end tab-nav -->
                </div> <!-- end s-menu__content-start -->

                <div class="column xl-6 lg-6 md-12 s-menu__content-end">
                    <div class="section-header section-header--with-button" data-num="<?php echo safeHtml($pageMeta['menu_top_5'] ?? 'Top 5 позиций'); ?>">
                        <a href="/menu.php" class="btn btn--primary">
                            <?php echo $pageMeta['menu_full_button'] ?? 'Открыть полное меню'; ?>
                        </a>
                    </div>
                    
                    <div class="tab-content menu-block" id="menu-content">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <?php 
                                $categoryId = (string)($category['category_id']);
                                $categoryProducts = $productsByCategory[$categoryId] ?? [];
                                
                                // Применяем автоматический перевод для продуктов (оптимизировано)
                                $topProducts = array_slice($categoryProducts, 0, 5);
                                if ($currentLanguage !== 'ru') {
                                    $translatedProducts = [];
                                    foreach ($topProducts as $product) {
                                        $translatedProducts[] = $menuCache->translateProduct($product, $currentLanguage);
                                    }
                                    $topProducts = $translatedProducts;
                                }
                                ?>
                                <div id="tab-<?php echo htmlspecialchars($category['category_id']); ?>" 
                                     class="menu-block__group tab-content__item" <?php echo $index === 0 ? 'data-tab-active aria-hidden="false"' : 'aria-hidden="true"'; ?>>
                                    <h6 class="menu-block__cat-name"><?php echo htmlspecialchars(translateCategoryName($category['category_name'] ?? $category['name'] ?? 'Без названия', getCurrentLanguage())); ?></h6>
                                    <ul class="menu-list">
                                        <?php if (!empty($topProducts)): ?>
                                            <?php foreach ($topProducts as $product): ?>
                                                <li class="menu-list__item">
                                                    <div class="menu-list__item-desc">                                            
                                                        <h4><?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Без названия'); ?></h4>
                                                        <p><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                                                    </div>
                                                    <div class="menu-list__item-price">
                                                        <?php echo number_format($product['price_normalized'] ?? $product['price'] ?? 0, 0, ',', ' '); ?> ₫
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="menu-list__item">
                                                <div class="menu-list__item-desc">
                                                    <h4><?php echo $pageMeta['menu_no_items'] ?? ''; ?></h4>
                                                    <p><?php echo $pageMeta['menu_working_on_it'] ?? ''; ?></p>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="menu-block__group tab-content__item" data-tab-active aria-hidden="false">
                                <h6 class="menu-block__cat-name"><?php echo $pageMeta['menu_title'] ?? ''; ?></h6>
                                <ul class="menu-list">
                                    <li class="menu-list__item">
                                        <div class="menu-list__item-desc">
                                            <h4><?php echo $pageMeta['menu_error'] ?? ''; ?></h4>
                                            <p><?php echo $pageMeta['menu_unavailable'] ?? ''; ?></p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div> <!-- menu-block -->
                </div> <!-- end s-menu__content-end -->
            </div> <!-- end s-menu__content -->
            
            <!-- Menu Update Time -->
            <div class="row s-menu__footer">
                <div class="column xl-12 text-center">
                    <?php 
                    $lastUpdateTime = $menuCache->getLastUpdateTimeFormatted();
                    if ($lastUpdateTime): 
                    ?>
                    <div class="menu-update-time">
                        <small style="color: #1e1e1e; font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                            <?php echo safeHtml($pageMeta['menu_updated'] ?? 'Обновлено'); ?>: <?php echo htmlspecialchars($lastUpdateTime); ?> (<?php echo safeHtml($pageMeta['location_nha_trang'] ?? 'Нячанг'); ?>)
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div> <!-- end s-menu__footer -->
        </section> <!-- end s-menu -->

        <!-- # events
        ================================================== -->
        <section id="events" class="container s-events target-section">
            <div class="row s-events__header">
                <div class="column xl-12 section-header-wrap">
                    <div class="section-header" data-num="03">
                        <h2 class="text-display-title"><?php echo safeHtml($pageMeta['events_title'] ?? 'События'); ?></h2>
                    </div>
                </div> <!-- end section-header-wrap -->
            </div> <!-- end s-events__header -->

            <div class="events-widget" role="region" aria-label="<?php echo safeHtml($pageMeta['events_widget_title'] ?? 'Афиша событий'); ?>">
                <h2 class="events-widget__title"><?php echo safeHtml($pageMeta['events_widget_title'] ?? 'Афиша событий'); ?></h2>
                
                <!-- Слайдер дат -->
                <div class="swiper dates-swiper" role="tablist" aria-label="<?php echo safeHtml($pageMeta['events_dates_aria'] ?? 'Выбор даты события'); ?>">
                    <div class="swiper-wrapper" id="dates-wrapper" role="tablist">
                        <!-- Даты будут добавлены динамически -->
                    </div>
                </div>

                <!-- Слайдер постеров -->
                <div class="swiper posters-swiper" role="tabpanel" aria-label="<?php echo safeHtml($pageMeta['events_posters_aria'] ?? 'Постеры событий'); ?>">
                    <div class="swiper-wrapper" id="posters-wrapper">
                        <!-- Постеры будут добавлены динамически -->
                    </div>
                </div>
            </div>
        </section> <!-- end s-events -->

        <!-- # gallery
        ================================================== -->
        <section id="gallery" class="container s-gallery target-section">
            <div class="row s-gallery__header">
                <div class="column xl-12 section-header-wrap">
                    <div class="section-header" data-num="04">
                        <h2 class="text-display-title"><?php echo $pageMeta['gallery_title'] ?? ''; ?></h2>
                    </div>               
                </div> <!-- end section-header-wrap -->   
            </div> <!-- end s-gallery__header -->   

            <div class="gallery-items grid-cols grid-cols--wrap">
                <?php 
                // Получаем изображения галереи из БД
                $galleryImages = $pageMeta['gallery_images'] ?? [];
                
                if (!empty($galleryImages)) {
                    // Используем изображения из БД
                    foreach ($galleryImages as $image) {
                        $thumb = $image['thumb'] ?? '';
                        $large = $image['large'] ?? $thumb;
                        $thumb2x = $image['thumb2x'] ?? $thumb;
                        $alt = $image['alt'] ?? '';
                        ?>
                        <div class="gallery-items__item grid-cols__column">
                            <a href="<?php echo htmlspecialchars($large); ?>" class="gallery-items__item-thumb glightbox">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" 
                                    srcset="<?php echo htmlspecialchars($thumb); ?> 1x, 
                                            <?php echo htmlspecialchars($thumb2x); ?> 2x" alt="<?php echo htmlspecialchars($alt); ?>">                                
                            </a>
                        </div>
                        <?php
                    }
                } else {
                    // Fallback на статичные изображения
                    for ($i = 1; $i <= 8; $i++): ?>
                        <div class="gallery-items__item grid-cols__column">
                            <a href="template/images/gallery/large/l-gallery-<?php echo sprintf('%02d', $i); ?>.jpg" class="gallery-items__item-thumb glightbox">
                                <img src="template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>.jpg" 
                                    srcset="template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>.jpg 1x, 
                                            template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>@2x.jpg 2x" alt="">                                
                            </a>
                        </div>
                    <?php endfor;
                }
                ?>
            </div> <!-- end gallery-items -->
        </section> <!-- end s-gallery -->

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
    </div>

    <!-- JavaScript
    ================================================== -->
    <script src="js/plugins.js" defer></script>
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
    <script src="js/main.js" defer></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/css/glightbox.css">
    
    <script>
        // Tab switching for menu categories (исправлено для предотвращения скачков)
        document.addEventListener('DOMContentLoaded', function() {
            const categoryLinks = document.querySelectorAll('#menu-categories a');
            const menuItems = document.querySelectorAll('#menu-content .tab-content__item');
            const menuContainer = document.querySelector('#menu-content');
            
            // Кешируем элементы для быстрого доступа
            const menuItemsMap = new Map();
            menuItems.forEach(item => {
                menuItemsMap.set(item.id, item);
            });
            
            categoryLinks.forEach((link, linkIndex) => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Сохраняем текущую позицию скрола
                    const scrollY = window.pageYOffset;
                    
                    // Reset all the tablinks
                    categoryLinks.forEach(function(l) {
                        l.setAttribute('tabindex', '-1');
                        l.setAttribute('aria-selected', 'false');
                        l.parentNode.removeAttribute('data-tab-active');
                        l.removeAttribute('data-tab-active');
                        l.classList.remove('active');
                    });
                    
                    // Set the active link attributes
                    this.setAttribute('tabindex', '0');
                    this.setAttribute('aria-selected', 'true');
                    this.parentNode.setAttribute('data-tab-active', '');
                    this.setAttribute('data-tab-active', '');
                    this.classList.add('active');
                    
                    // Change tab panel visibility (упрощенная версия с абсолютным позиционированием)
                    const targetTabId = this.getAttribute('data-tab-id');
                    
                    // Убираем data-tab-active со всех табов
                    menuItems.forEach(function(panel) {
                        panel.removeAttribute('data-tab-active');
                    });
                    
                    // Добавляем data-tab-active к нужному табу
                    const targetPanel = document.getElementById(targetTabId);
                    if (targetPanel) {
                        targetPanel.setAttribute('data-tab-active', '');
                    }
                    
                    // Восстанавливаем позицию скрола
                    window.scrollTo(0, scrollY);
                    
                    // Небольшая задержка для корректной работы
                    setTimeout(() => {
                        window.dispatchEvent(new Event('resize'));
                    }, 10);
                });
            });

            // Events Widget JavaScript - Double Slider
            class EventsWidget {
                constructor() {
                    this.datesSwiper = null;
                    this.postersSwiper = null;
                    this.events = [];
                    this.eventsByDate = {};
                    this.allPosters = [];
                    this.isUserScrolling = false;
                    this.debounceTimer = null;
                    this.init();
                }

                init() {
                    this.initSwipers();
                    this.loadEvents();
                }

                // Debounce функция для оптимизации производительности
                debounce(func, wait) {
                    return (...args) => {
                        clearTimeout(this.debounceTimer);
                        this.debounceTimer = setTimeout(() => func.apply(this, args), wait);
                    };
                }

                initSwipers() {
                    // Инициализация слайдера дат - показываем все 14 дней сразу
                    this.datesSwiper = new Swiper('.dates-swiper', {
                        slidesPerView: 14, // Показываем все 14 дней
                        spaceBetween: 8,
                        freeMode: false,
                        centeredSlides: false,
                        mousewheel: {
                            enabled: false // Отключаем прокрутку
                        },
                        speed: 300,
                        breakpoints: {
                            320: { slidesPerView: 7, spaceBetween: 2 },
                            480: { slidesPerView: 7, spaceBetween: 3 },
                            800: { slidesPerView: 7, spaceBetween: 4 },
                            1024: { slidesPerView: 14, spaceBetween: 6 },
                            1200: { slidesPerView: 14, spaceBetween: 8 }
                        },
                        on: {
                            // Убрали обработчики slideChange, так как теперь все даты видны сразу
                        }
                    });

                    // Инициализация слайдера постеров
                    this.postersSwiper = new Swiper('.posters-swiper', {
                        slidesPerView: 'auto',
                        spaceBetween: 20,
                        freeMode: true,
                        mousewheel: {
                            enabled: true
                        },
                        speed: 300,
                        on: {
                            // Убрали обработчики slideChange для постеров
                        }
                    });
                }

                async loadEvents() {
                    try {
                        // Показываем индикатор загрузки
                        const eventsWidget = document.querySelector('.events-widget');
                        if (eventsWidget) {
                            eventsWidget.classList.add('loading');
                        }
                        
                        // Определяем язык из HTML или используем русский по умолчанию
                        const language = document.documentElement.lang || 'ru';
                        
                        // Загружаем события из API на 14 дней вперед
                        const today = new Date().toISOString().split('T')[0];
                        const response = await fetch(`/api/events.php?start_date=${today}&days=14&language=${language}`);
                        const events = await response.json();
                        this.events = events;
                        this.processEvents();
                        this.generateCalendarDays();
                        this.renderDates();
                        this.renderPosters();
                        this.bindEvents();
                        
                        // Убираем индикатор загрузки
                        if (eventsWidget) {
                            eventsWidget.classList.remove('loading');
                        }
                    } catch (error) {
                        console.error('Ошибка загрузки событий:', error);
                        
                        // Убираем индикатор загрузки
                        const eventsWidget = document.querySelector('.events-widget');
                        if (eventsWidget) {
                            eventsWidget.classList.remove('loading');
                        }
                        
                        this.loadTestData();
                    }
                }

                loadTestData() {
                    // Если нет данных из API, показываем пустые слайдеры
                    console.log('Загружаем тестовые данные для виджета событий');
                    this.events = [];
                    this.processEvents();
                    this.generateCalendarDays();
                    this.renderDates();
                    this.renderPosters();
                    this.bindEvents();
                }

                processEvents() {
                    // Группируем события по дате
                    this.eventsByDate = {};
                    this.events.forEach(event => {
                        const date = event.date; // Используем новое поле 'date'
                        if (!this.eventsByDate[date]) {
                            this.eventsByDate[date] = [];
                        }
                        this.eventsByDate[date].push(event);
                    });

                    // Создаем плоский массив всех событий для постеров
                    this.allPosters = [];
                    Object.keys(this.eventsByDate).sort().forEach(date => {
                        this.eventsByDate[date].forEach(event => {
                            this.allPosters.push({
                                ...event,
                                date: date
                            });
                        });
                    });
                }

                generateCalendarDays() {
                    // Генерируем календарь на 14 дней начиная с сегодня
                    this.calendarDays = [];
                    const today = new Date();
                    
                    // Определяем язык для дней недели
                    const language = document.documentElement.lang || 'ru';
                    const dayNames = {
                        'ru': ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                        'en': ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                        'vi': ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
                    };
                    
                    for (let i = 0; i < 14; i++) {
                        const date = new Date(today);
                        date.setDate(today.getDate() + i);
                        const dateStr = date.toISOString().split('T')[0];
                        
                        const day = date.getDate();
                        const month = (date.getMonth() + 1).toString().padStart(2, '0');
                        const dayOfWeek = dayNames[language]?.[date.getDay()] || dayNames['ru'][date.getDay()];
                        
                        this.calendarDays.push({
                            date: dateStr,
                            day: day,
                            month: month,
                            dayOfWeek: dayOfWeek,
                            hasEvents: this.eventsByDate[dateStr] && this.eventsByDate[dateStr].length > 0
                        });
                    }
                }

                getMonthShort(monthIndex) {
                    const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 
                                   'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
                    return months[monthIndex - 1]; // Исправляем индекс (месяцы с 1)
                }

                renderDates() {
                    const datesWrapper = document.getElementById('dates-wrapper');
                    datesWrapper.innerHTML = '';
                    
                    // Используем сгенерированный календарь на 14 дней
                    this.calendarDays.forEach((dayData, index) => {
                        const slideEl = document.createElement('div');
                        slideEl.className = 'swiper-slide';
                        slideEl.dataset.date = dayData.date;
                        slideEl.dataset.index = index;
                        
                        // Первый день (сегодня) активен по умолчанию
                        if (index === 0) {
                            slideEl.classList.add('active');
                        }
                        
                        // Добавляем класс для дат с событиями
                        if (dayData.hasEvents) {
                            slideEl.classList.add('has-event');
                        }
                        
                        slideEl.innerHTML = `
                            <div>${dayData.day}/${dayData.month}</div>
                            <div style="font-size: 10px; margin-top: 2px;">${dayData.dayOfWeek}</div>
                        `;
                        
                        // Add ARIA attributes for accessibility
                        slideEl.setAttribute('role', 'tab');
                        slideEl.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
                        slideEl.setAttribute('tabindex', index === 0 ? '0' : '-1');
                        
                        datesWrapper.appendChild(slideEl);
                    });
                    
                    this.datesSwiper.update();
                }

                renderPosters() {
                    const postersWrapper = document.getElementById('posters-wrapper');
                    postersWrapper.innerHTML = '';
                    
                    // Показываем постеры для всех дней календаря
                    this.calendarDays.forEach((dayData, index) => {
                        const slideEl = document.createElement('div');
                        slideEl.className = 'swiper-slide';
                        slideEl.dataset.date = dayData.date;
                        slideEl.dataset.index = index;
                        
                        if (dayData.hasEvents) {
                            // Показываем события для этого дня
                            const dayEvents = this.eventsByDate[dayData.date] || [];
                            dayEvents.forEach(event => {
                                const eventSlideEl = document.createElement('div');
                                eventSlideEl.className = 'swiper-slide';
                                eventSlideEl.dataset.eventId = event.id;
                                eventSlideEl.dataset.date = event.date;
                                eventSlideEl.dataset.eventLink = event.link || '#';
                                
                                const backgroundImage = event.image || 'images/event-default.png';
                                const dateObj = new Date(event.date);
                                const formattedDate = dateObj.toLocaleDateString('ru-RU');
                                
                                // Определяем язык для перевода лейбла
                                const language = document.documentElement.lang || 'ru';
                                
                                // Добавляем день недели к дате
                                const dayNames = {
                                    'ru': ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                                    'en': ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                                    'vi': ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                                };
                                const dayOfWeek = dayNames[language]?.[dateObj.getDay()] || dayNames['ru'][dateObj.getDay()];
                                const dateWithDay = `${formattedDate} (${dayOfWeek})`;
                                const conditionsLabels = {
                                    'ru': 'Условия участия:',
                                    'en': 'Participation conditions:',
                                    'vi': 'Điều kiện tham gia:'
                                };
                                const conditionsLabel = conditionsLabels[language] || conditionsLabels['ru'];
                                
                                eventSlideEl.innerHTML = `
                                    <div class="poster-card">
                                        <div class="poster-card__image-container">
                                            <img class="poster-card__image" 
                                                 data-src="${backgroundImage}" 
                                                 alt="${event.title}"
                                                 loading="lazy">
                                            <div class="poster-card__image-placeholder">
                                                <div class="loading-spinner"></div>
                                            </div>
                                        </div>
                                        <div class="poster-card__overlay">
                                            <div class="poster-card__title">${event.title}</div>
                                            <div class="poster-card__date">${dateWithDay} ${event.time || '19:00'}</div>
                                            <div class="poster-card__description">
                                                ${event.description || ''}
                                            </div>
                                            <div class="poster-card__conditions">
                                                <strong>${conditionsLabel}</strong><br>
                                                ${event.conditions || ''}
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                postersWrapper.appendChild(eventSlideEl);
                            });
                        } else {
                            // Показываем сообщение для пустых дат
                            const emptySlideEl = document.createElement('div');
                            emptySlideEl.className = 'swiper-slide';
                            emptySlideEl.dataset.date = dayData.date;
                            emptySlideEl.dataset.index = index;
                            
                            const dateObj = new Date(dayData.date);
                            const formattedDate = dateObj.toLocaleDateString('ru-RU');
                            
                            // Определяем язык для сообщения
                            const language = document.documentElement.lang || 'ru';
                            
                            // Добавляем день недели к дате для пустых афиш
                            const dayNames = {
                                'ru': ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                                'en': ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                                'vi': ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy']
                            };
                            const dayOfWeek = dayNames[language]?.[dateObj.getDay()] || dayNames['ru'][dateObj.getDay()];
                            const dateWithDay = `${formattedDate} (${dayOfWeek})`;
                            const messages = {
                                'ru': {
                                    title: '<?php echo addslashes($pageMeta['events_empty_title'] ?? 'Мы еще не придумали что у нас тут будет.'); ?>',
                                    text: '<?php echo addslashes($pageMeta['events_empty_text'] ?? 'Есть идеи?'); ?>',
                                    link: '<?php echo addslashes($pageMeta['events_empty_link'] ?? 'Свяжитесь с нами!'); ?>'
                                },
                                'en': {
                                    title: '<?php echo addslashes($pageMeta['events_empty_title'] ?? 'We haven\'t figured out what we\'ll have here yet.'); ?>',
                                    text: '<?php echo addslashes($pageMeta['events_empty_text'] ?? 'Have ideas?'); ?>',
                                    link: '<?php echo addslashes($pageMeta['events_empty_link'] ?? 'Contact us!'); ?>'
                                },
                                'vi': {
                                    title: '<?php echo addslashes($pageMeta['events_empty_title'] ?? 'Chúng tôi chưa nghĩ ra sẽ có gì ở đây.'); ?>',
                                    text: '<?php echo addslashes($pageMeta['events_empty_text'] ?? 'Có ý tưởng?'); ?>',
                                    link: '<?php echo addslashes($pageMeta['events_empty_link'] ?? 'Liên hệ với chúng tôi!'); ?>'
                                }
                            };
                            
                            const msg = messages[language] || messages['ru'];
                            
                            emptySlideEl.innerHTML = `
                                <div class="poster-card empty-date">
                                    <div class="poster-card__overlay">
                                        <div class="poster-card__title">${msg.title}</div>
                                        <div class="poster-card__date">${dateWithDay}</div>
                                        <div class="poster-card__description">
                                            ${msg.text}
                                        </div>
                                        <div class="poster-card__conditions">
                                            <a href="#footer" class="contact-link">${msg.link}</a>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            postersWrapper.appendChild(emptySlideEl);
                        }
                    });
                    
                    this.postersSwiper.update();
                    
                    // Привязываем события к новым элементам
                    this.bindPosterEvents();
                    
                    // Инициализируем улучшенный lazy loading
                    this.initAdvancedLazyLoading();
                }

                initAdvancedLazyLoading() {
                    // Улучшенный lazy loading с обработкой ошибок
                    if ('IntersectionObserver' in window) {
                        const imageObserver = new IntersectionObserver((entries, observer) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting) {
                                    const img = entry.target;
                                    const container = img.closest('.poster-card__image-container');
                                    const placeholder = container?.querySelector('.poster-card__image-placeholder');
                                    
                                    if (img.dataset.src) {
                                        // Создаем новый Image объект для предзагрузки
                                        const tempImg = new Image();
                                        
                                        tempImg.onload = () => {
                                            img.src = img.dataset.src;
                                            img.classList.add('loaded');
                                            img.removeAttribute('data-src');
                                            
                                            // Скрываем placeholder
                                            if (placeholder) {
                                                placeholder.style.opacity = '0';
                                                setTimeout(() => {
                                                    placeholder.style.display = 'none';
                                                }, 300);
                                            }
                                        };
                                        
                                        tempImg.onerror = () => {
                                            // Fallback на дефолтное изображение
                                            img.src = '/images/event-default.png';
                                            img.classList.add('loaded', 'fallback');
                                            img.removeAttribute('data-src');
                                            
                                            // Скрываем placeholder
                                            if (placeholder) {
                                                placeholder.style.opacity = '0';
                                                setTimeout(() => {
                                                    placeholder.style.display = 'none';
                                                }, 300);
                                            }
                                        };
                                        
                                        // Начинаем загрузку
                                        tempImg.src = img.dataset.src;
                                    }
                                    
                                    observer.unobserve(img);
                                }
                            });
                        }, {
                            rootMargin: '50px 0px',
                            threshold: 0.1
                        });
                        
                        // Наблюдаем за всеми изображениями с data-src
                        document.querySelectorAll('.poster-card__image[data-src]').forEach(img => {
                            imageObserver.observe(img);
                        });
                    } else {
                        // Fallback для старых браузеров
                        document.querySelectorAll('.poster-card__image[data-src]').forEach(img => {
                            const tempImg = new Image();
                            tempImg.onload = () => {
                                img.src = img.dataset.src;
                                img.classList.add('loaded');
                                img.removeAttribute('data-src');
                            };
                            tempImg.onerror = () => {
                                img.src = '/images/event-default.png';
                                img.classList.add('loaded', 'fallback');
                                img.removeAttribute('data-src');
                            };
                            tempImg.src = img.dataset.src;
                        });
                    }
                }

                // Общая функция для центрирования слайдов
                centerSlide(swiper, targetIndex, slideWidth) {
                    const totalSlides = swiper.slides.length;
                    const visibleSlides = Math.floor(swiper.width / slideWidth);
                    let targetSlideIndex = targetIndex;
                    
                    // Центрируем слайд
                    if (targetIndex > Math.floor(visibleSlides / 2)) {
                        targetSlideIndex = targetIndex - Math.floor(visibleSlides / 2);
                    } else {
                        targetSlideIndex = 0;
                    }
                    
                    // Убеждаемся, что не выходим за границы
                    targetSlideIndex = Math.max(0, Math.min(targetSlideIndex, totalSlides - visibleSlides));
                    
                    return targetSlideIndex;
                }


                bindEvents() {
                    // Обработка кликов по датам
                    document.querySelectorAll('.dates-swiper .swiper-slide').forEach(slide => {
                        slide.addEventListener('click', () => {
                            // Убираем активный класс у всех дат
                            document.querySelectorAll('.dates-swiper .swiper-slide').forEach(s => {
                                s.classList.remove('active');
                            });
                            
                            // Добавляем активный класс к выбранной дате
                            slide.classList.add('active');
                            
                            // Убираем выделение у всех постеров
                            document.querySelectorAll('.poster-card').forEach(card => {
                                card.classList.remove('selected');
                            });
                            
                            // Находим постеры для выбранной даты
                            const selectedDate = slide.dataset.date;
                            const posterSlides = document.querySelectorAll('.posters-swiper .swiper-slide');
                            let targetPosterIndex = -1;
                            
                            // Ищем первый постер для этой даты
                            posterSlides.forEach((posterSlide, index) => {
                                if (posterSlide.dataset.date === selectedDate && targetPosterIndex === -1) {
                                    targetPosterIndex = index;
                                }
                            });
                            
                            if (targetPosterIndex !== -1) {
                                // Центрируем первый постер этой даты
                                const centeredPosterIndex = this.centerSlide(this.postersSwiper, targetPosterIndex, 320);
                                this.postersSwiper.slideTo(centeredPosterIndex, 300);
                                
                                // Выделяем все постеры этой даты
                                posterSlides.forEach((posterSlide, index) => {
                                    if (posterSlide.dataset.date === selectedDate) {
                                        const card = posterSlide.querySelector('.poster-card');
                                        if (card) {
                                            card.classList.add('selected');
                                        }
                                    }
                                });
                            }
                        });
                    });

                    // Обработка кликов по постерам
                    this.bindPosterEvents();
                }

                bindPosterEvents() {
                    // Удаляем старые обработчики событий
                    document.querySelectorAll('.posters-swiper .swiper-slide').forEach(slide => {
                        slide.removeEventListener('click', this.handlePosterClick);
                    });

                    // Добавляем новые обработчики
                    document.querySelectorAll('.posters-swiper .swiper-slide').forEach(slide => {
                        slide.addEventListener('click', this.handlePosterClick.bind(this));
                    });
                }

                handlePosterClick(event) {
                    const slide = event.currentTarget;
                    const eventLink = slide.dataset.eventLink;
                    
                    // Если это пустая дата - прокручиваем к футеру
                    if (slide.querySelector('.empty-date')) {
                        const footer = document.querySelector('#footer');
                        if (footer) {
                            footer.scrollIntoView({ behavior: 'smooth' });
                        }
                        return;
                    }
                    
                    // Переход по ссылке из MongoDB
                    if (eventLink && eventLink !== '#') {
                        window.open(eventLink, '_blank');
                    }
                }

            }

            // Lazy loading виджета - инициализируем после загрузки основного контента
            let eventsWidgetInitialized = false;
            
            function initEventsWidget() {
                if (!eventsWidgetInitialized) {
                    try {
                        // Проверяем, что элементы виджета существуют
                        const eventsSection = document.querySelector('#events');
                        const datesWrapper = document.getElementById('dates-wrapper');
                        const postersWrapper = document.getElementById('posters-wrapper');
                        
                        if (eventsSection && datesWrapper && postersWrapper) {
                            new EventsWidget();
                            eventsWidgetInitialized = true;
                            console.log('Виджет событий успешно инициализирован');
                        } else {
                            console.warn('Элементы виджета событий не найдены');
                        }
                    } catch (error) {
                        console.error('Ошибка инициализации виджета событий:', error);
                    }
                }
            }
            
            // Инициализируем виджет после загрузки DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initEventsWidget);
            } else {
                // DOM уже загружен, но ждем немного для оптимизации
                setTimeout(initEventsWidget, 100);
            }
        });
    </script>

</body>
</html>