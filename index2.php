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
    $cacheUrl = 'http://localhost:3002/api/cache/update-menu';
    
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
    @file_get_contents($cacheUrl, false, $context);
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
        $api_base_url = 'http://localhost:3002/api';
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
                    $authToken = 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
                    $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=5&token=' . urlencode($authToken);
                    $popularResponse = @file_get_contents($popularUrl, false, $context);
                    
                    if ($popularResponse !== false) {
                        $popularData = json_decode($popularResponse, true);
                        if ($popularData && isset($popularData['popular_products'])) {
                            $productsByCategory[$categoryId] = $popularData['popular_products'];
                        }
                    }
                } catch (Exception $e) {
                    // Fallback to empty array if API fails
                    $productsByCategory[$categoryId] = [];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Menu loading error: " . $e->getMessage());
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
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    
    <style>
        :root {
            --intro-text-opacity: 0.01; /* 1% прозрачности по умолчанию */
        }
        
        .intro-header__big-type {
            opacity: var(--intro-text-opacity);
        }
        
        .intro-header__overline {
            font-family: "Roboto Flex", sans-serif;
        }

        /* Events Widget Styles */
        .s-events {
            --content-padding-top: calc(var(--vspace-2) * 0.7);
            --content-padding-bottom: calc(var(--vspace-2) * 0.7);

            padding-top: var(--content-padding-top);
            padding-bottom: var(--content-padding-bottom);
        }

        .events-widget {
            margin-top: calc(var(--vspace-2) * 0.7);
        }

        .events-widget__title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Слайдер дат */
        .dates-swiper {
            margin-bottom: 30px;
            overflow: hidden;
        }

        .dates-swiper .swiper-wrapper {
            transition-timing-function: linear;
        }

        .dates-swiper .swiper-slide {
            width: auto;
            text-align: center;
            padding: 12px 20px;
            color: var(--muted-text);
            background: transparent;
            border: 1px solid var(--card-border);
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            user-select: none;
            position: relative;
            min-width: 60px;
        }

        .dates-swiper .swiper-slide:hover {
            color: var(--text-color);
            border-color: var(--accent-color);
        }

        .dates-swiper .swiper-slide.active {
            background: var(--accent-color);
            color: var(--bg-color);
            border-color: var(--accent-color);
            font-weight: 600;
        }

        .dates-swiper .swiper-slide.has-event::after {
            content: '';
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 6px;
            height: 6px;
            background: var(--accent-color);
            border-radius: 50%;
        }

        .dates-swiper .swiper-slide.active.has-event::after {
            background: var(--bg-color);
        }

        /* Контейнер событий */
        .events-container {
            position: relative;
        }

        #events-display {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        /* Карточка события */
        .event-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            overflow: hidden;
            width: 300px;
            height: 200px;
            flex-shrink: 0;
            transition: border-color 0.3s ease;
            position: relative;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .event-card:hover {
            border-color: var(--accent-color);
        }

        .event-card__overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(1px);
        }

        .event-card__content {
            position: relative;
            z-index: 2;
            padding: 12px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .event-card__title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .event-card__price {
            font-size: 14px;
            color: var(--accent-color);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .event-card__description {
            font-size: 12px;
            color: var(--muted-text);
            margin-bottom: 8px;
            line-height: 1.3;
            flex-grow: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .event-card__link {
            display: inline-block;
            color: var(--text-color);
            text-decoration: none;
            font-size: 12px;
            padding: 6px 12px;
            border: 1px solid var(--card-border);
            border-radius: 4px;
            transition: all 0.3s ease;
            align-self: flex-start;
        }

        .event-card__link:hover {
            background: var(--accent-color);
            color: var(--bg-color);
            border-color: var(--accent-color);
        }

        /* Сообщение об отсутствии событий */
        .no-events {
            text-align: center;
            color: var(--muted-text);
            font-size: 16px;
            padding: 40px;
            width: 100%;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .events-widget__title {
                font-size: 2rem;
            }
            
            .dates-swiper .swiper-slide {
                padding: 10px 16px;
                font-size: 13px;
                min-width: 50px;
            }
            
            .event-card {
                width: 280px;
            }
            
            #events-display {
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .events-widget__title {
                font-size: 1.5rem;
            }
            
            .dates-swiper .swiper-slide {
                padding: 8px 12px;
                font-size: 12px;
                min-width: 45px;
            }
            
            .event-card {
                width: 260px;
            }
            
            #events-display {
                gap: 10px;
            }
            
            .event-card__content {
                padding: 10px;
            }
            
            .event-card__title {
                font-size: 14px;
            }
            
            .event-card__price {
                font-size: 12px;
            }
            
            .event-card__description {
                font-size: 10px;
                -webkit-line-clamp: 2;
            }
            
            .event-card__link {
                font-size: 10px;
                padding: 4px 8px;
            }
        }
    </style>

    <!-- favicons
    ================================================== -->
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://northrepublic.me/">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="https://northrepublic.me/images/logo.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://northrepublic.me/">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="twitter:image" content="https://northrepublic.me/images/logo.png">
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
                        <?php echo $pageMeta['intro_title'] ?? 'North <br>Republic'; ?>
                    </h1>
                </div> <!-- end intro-header -->

                <figure class="intro-pic-primary">
                    <img src="<?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?>" 
                         srcset="<?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?> 1x, 
                         <?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?> 2x" alt="">  
                </figure> <!-- end intro-pic-primary -->    
                    
                <div class="intro-block-content">
                    <figure class="intro-block-content__pic">
                        <img src="<?php echo $pageMeta['intro_image_secondary'] ?? 'template/images/intro-pic-secondary.jpg'; ?>" 
                             srcset="<?php echo $pageMeta['intro_image_secondary'] ?? 'template/images/intro-pic-secondary.jpg'; ?> 1x, 
                             <?php echo $pageMeta['intro_image_secondary_2x'] ?? 'template/images/intro-pic-secondary@2x.jpg'; ?> 2x" alt=""> 
                    </figure>
                    <div class="intro-block-content__text">
                        <p class="lead">
                            <?php echo $pageContent['content'] ?? ''; ?>
                        </p>
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
                             <?php echo $pageMeta['about_image_primary_2x'] ?? 'template/images/about-pic-primary@2x.jpg'; ?> 2x" alt=""> 
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
                                        <a href="#tab-<?php echo htmlspecialchars($category['category_id']); ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>">
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
                    <div class="section-header" data-num="Top 5 позиций">
                    </div>
                    
                    <div class="tab-content menu-block" id="menu-content">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <?php 
                                $categoryId = (string)($category['category_id']);
                                $categoryProducts = $productsByCategory[$categoryId] ?? [];
                                $topProducts = array_slice($categoryProducts, 0, 5); // Top 5 products
                                ?>
                                <div id="tab-<?php echo htmlspecialchars($category['category_id']); ?>" class="menu-block__group tab-content__item <?php echo $index === 0 ? 'active' : ''; ?>">
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
                            <div class="menu-block__group tab-content__item active">
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
            
            <!-- Full Menu Button -->
            <div class="row s-menu__footer">
                <div class="column xl-12 text-center">
                    <a href="/menu" class="btn btn--primary">
                        <?php echo $pageMeta['menu_full_button'] ?? ''; ?>
                    </a>
                    
                    <!-- Menu Update Time -->
                    <?php 
                    $lastUpdateTime = $menuCache->getLastUpdateTimeFormatted();
                    if ($lastUpdateTime): 
                    ?>
                    <div class="menu-update-time">
                        <small style="color: #1e1e1e; font-size: 0.75rem; margin-top: 0.5rem; display: block;">
                            Обновлено: <?php echo htmlspecialchars($lastUpdateTime); ?> (Нячанг)
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
                        <h2 class="text-display-title">События</h2>
                    </div>
                </div> <!-- end section-header-wrap -->
            </div> <!-- end s-events__header -->

            <div class="events-widget">
                <h2 class="events-widget__title">Афиша событий</h2>
                
                <!-- Слайдер дат -->
                <div class="swiper dates-swiper">
                    <div class="swiper-wrapper" id="dates-wrapper">
                        <!-- Даты будут добавлены динамически -->
                    </div>
                </div>

                <!-- Контейнер отображения событий -->
                <div class="events-container">
                    <div id="events-display">
                        <!-- События будут добавлены динамически -->
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
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    
    <script>
        // Tab switching for menu categories
        document.addEventListener('DOMContentLoaded', function() {
            const categoryLinks = document.querySelectorAll('#menu-categories a');
            const menuItems = document.querySelectorAll('#menu-content .tab-content__item');
            
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links and items
                    categoryLinks.forEach(l => l.classList.remove('active'));
                    menuItems.forEach(item => item.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Show corresponding menu item
                    const targetId = this.getAttribute('href').substring(1);
                    const targetItem = document.getElementById(targetId);
                    if (targetItem) {
                        targetItem.classList.add('active');
                    }
                });
            });

            // Events Widget JavaScript
            class EventsWidget {
                constructor() {
                    this.datesSwiper = null;
                    this.events = [];
                    this.calendarDays = [];
                    this.eventsByDate = {};
                    this.init();
                }

                init() {
                    this.initSwiper();
                    this.loadEvents();
                }

                initSwiper() {
                    // Инициализация слайдера дат
                    this.datesSwiper = new Swiper('.dates-swiper', {
                        slidesPerView: 'auto',
                        spaceBetween: 12,
                        freeMode: true,
                        mousewheel: {
                            enabled: true
                        },
                        speed: 300,
                        breakpoints: {
                            320: {
                                slidesPerView: 'auto',
                                spaceBetween: 10
                            },
                            768: {
                                slidesPerView: 'auto',
                                spaceBetween: 12
                            }
                        }
                    });
                }

                async loadEvents() {
                    try {
                        // Загружаем события из API
                        const response = await fetch('/api/events.php');
                        const events = await response.json();
                        this.events = events;
                        this.processEvents();
                        this.generateCalendar();
                        this.renderDates();
                        this.renderEvents();
                        this.bindEvents();
                    } catch (error) {
                        console.error('Ошибка загрузки событий:', error);
                        // Загружаем тестовые данные
                        this.loadTestData();
                    }
                }

                loadTestData() {
                    // Если нет данных из API, показываем пустой календарь
                    this.events = [];
                    this.processEvents();
                    this.generateCalendar();
                    this.renderDates();
                    this.renderEvents();
                    this.bindEvents();
                }

                getDateString(daysFromToday) {
                    const date = new Date();
                    date.setDate(date.getDate() + daysFromToday);
                    return date.toISOString().split('T')[0];
                }

                processEvents() {
                    // Группируем события по дате
                    this.eventsByDate = {};
                    this.events.forEach(event => {
                        const date = event.event_date;
                        if (!this.eventsByDate[date]) {
                            this.eventsByDate[date] = [];
                        }
                        this.eventsByDate[date].push(event);
                    });
                }

                generateCalendar() {
                    // Генерируем массив из 14 дней, начиная с сегодня
                    this.calendarDays = [];
                    const today = new Date();
                    
                    for (let i = 0; i < 14; i++) {
                        const date = new Date(today);
                        date.setDate(today.getDate() + i);
                        
                        const day = date.getDate();
                        const month = this.getMonthShort(date.getMonth());
                        const dateString = date.toISOString().split('T')[0];
                        const hasEvent = this.eventsByDate[dateString] && this.eventsByDate[dateString].length > 0;
                        
                        this.calendarDays.push({
                            day: day,
                            month: month,
                            date: dateString,
                            hasEvent: hasEvent
                        });
                    }
                }

                getMonthShort(monthIndex) {
                    const months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 
                                   'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
                    return months[monthIndex];
                }

                renderDates() {
                    const datesWrapper = document.getElementById('dates-wrapper');
                    datesWrapper.innerHTML = '';
                    
                    this.calendarDays.forEach((day, index) => {
                        const slideEl = document.createElement('div');
                        slideEl.className = 'swiper-slide';
                        slideEl.dataset.date = day.date;
                        slideEl.dataset.index = index;
                        
                        if (day.hasEvent) {
                            slideEl.classList.add('has-event');
                        }
                        
                        if (index === 0) {
                            slideEl.classList.add('active');
                        }
                        
                        slideEl.innerHTML = `
                            <div>${day.day}</div>
                            <div style="font-size: 10px; margin-top: 2px;">${day.month}</div>
                        `;
                        
                        datesWrapper.appendChild(slideEl);
                    });
                    
                    this.datesSwiper.update();
                }

                renderEvents() {
                    const eventsDisplay = document.getElementById('events-display');
                    const activeDate = document.querySelector('.dates-swiper .swiper-slide.active');
                    
                    if (!activeDate) return;
                    
                    const selectedDate = activeDate.dataset.date;
                    const eventsForDate = this.eventsByDate[selectedDate] || [];
                    
                    eventsDisplay.innerHTML = '';
                    
                    if (eventsForDate.length === 0) {
                        eventsDisplay.innerHTML = '<div class="no-events">На эту дату событий нет</div>';
                        return;
                    }
                    
                    eventsForDate.forEach(event => {
                        const eventCard = document.createElement('div');
                        eventCard.className = 'event-card';
                        
                        const formattedPrice = Number(event.price).toLocaleString('ru-RU') + '₽';
                        const backgroundImage = event.image || 'images/logo.png';
                        
                        eventCard.style.backgroundImage = `url('${backgroundImage}')`;
                        eventCard.innerHTML = `
                            <div class="event-card__overlay"></div>
                            <div class="event-card__content">
                                <h3 class="event-card__title">${event.title}</h3>
                                <div class="event-card__price">${formattedPrice}</div>
                                <p class="event-card__description">${event.description}</p>
                                <a href="/events/${event.id}" class="event-card__link">Подробнее</a>
                            </div>
                        `;
                        
                        eventsDisplay.appendChild(eventCard);
                    });
                }

                bindEvents() {
                    // Обработка кликов по датам
                    const dateSlides = document.querySelectorAll('.dates-swiper .swiper-slide');
                    dateSlides.forEach(slide => {
                        slide.addEventListener('click', (e) => {
                            // Убираем активный класс у всех дат
                            dateSlides.forEach(s => s.classList.remove('active'));
                            // Добавляем активный класс выбранной дате
                            e.currentTarget.classList.add('active');
                            // Отрисовываем события для выбранной даты
                            this.renderEvents();
                        });
                    });
                }
            }

            // Инициализация виджета
            new EventsWidget();
        });
    </script>

</body>
</html>