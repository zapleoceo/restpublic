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

        /* Date Slider */
        .dates-slider {
            margin-bottom: calc(var(--vspace-2) * 0.7);
        }

        .date-slide {
            text-align: center;
            font-size: var(--text-sm);
            padding: var(--vspace-0_75) var(--vspace-0_5);
            cursor: pointer;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            background: var(--color-2-100);
            color: var(--color-1-800);
            font-weight: 600;
            border: 2px solid transparent;
            font-family: var(--font-1);
            min-width: 80px;
        }

        .date-slide:hover {
            background: var(--color-1-100);
            border-color: var(--color-1-200);
        }

        .date-slide.active {
            background: var(--color-1-600);
            color: var(--color-1-100);
            border-color: var(--color-1-600);
        }

        .date-slide .day {
            display: block;
            font-size: var(--text-lg);
            font-weight: 700;
            line-height: 1;
        }

        .date-slide .month {
            display: block;
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }

        /* Events Slider */
        .events-slider {
            position: relative;
        }

        .event-card {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 8px;
            overflow: hidden;
            width: 300px;
            height: 200px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .event-card:hover {
            border-color: var(--color-1-400);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .event-card__image {
            width: 100%;
            height: 100px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .event-card__content {
            padding: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
        }

        .event-card__title {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 6px;
        }

        .event-card__price {
            font-size: 14px;
            color: #4fb17d;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .event-card__description {
            font-size: 12px;
            color: #ccc;
            flex-grow: 1;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .event-card__link {
            display: inline-block;
            font-size: 12px;
            color: #fff;
            text-decoration: none;
            padding: 6px 12px;
            border: 1px solid #333;
            border-radius: 4px;
            transition: background 0.3s, color 0.3s, border-color 0.3s;
        }

        .event-card__link:hover {
            background: #4fb17d;
            color: #000;
            border-color: #4fb17d;
        }

        /* Placeholder card styles */
        .event-card--placeholder {
            background: #1e1e1e;
            border: 2px dashed #333;
        }

        .event-card--placeholder:hover {
            border-color: #4fb17d;
            background: #1e1e1e;
        }

        .event-card__placeholder-image {
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #2a2a2a;
            color: #666;
        }

        /* Swiper Navigation */
        .swiper-button-next,
        .swiper-button-prev {
            color: var(--color-1-600);
            background: var(--color-1-100);
            border: 1px solid var(--color-1-200);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: var(--color-1-600);
            color: var(--color-1-100);
            border-color: var(--color-1-600);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .date-slide {
                min-width: 70px;
                padding: var(--vspace-0_5);
            }

            .date-slide .day {
                font-size: var(--text-base);
            }

            .event-card__image {
                height: 160px;
            }

            .swiper-button-next,
            .swiper-button-prev {
                display: none;
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
                <!-- Слайдер Дат -->
                <div class="swiper dates-slider" id="dates-slider">
                    <div class="swiper-wrapper"></div>
                </div>

                <!-- Слайдер Событий -->
                <div class="swiper events-slider" id="events-slider">
                    <div class="swiper-wrapper"></div>
                    <!-- Навигация -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
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

            // Events Widget
            let currentStartDate = new Date();
            let datesSwiper, eventsSwiper;
            
            // Initialize Swiper instances
            datesSwiper = new Swiper('#dates-slider', {
                slidesPerView: 7,
                spaceBetween: 12,
                freeMode: true,
                watchSlidesProgress: true,
                breakpoints: {
                    320: {
                        slidesPerView: 4,
                        spaceBetween: 8
                    },
                    768: {
                        slidesPerView: 7,
                        spaceBetween: 12
                    }
                }
            });

            eventsSwiper = new Swiper('#events-slider', {
                slidesPerView: 1,
                spaceBetween: 20,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev'
                },
                breakpoints: {
                    320: {
                        slidesPerView: 1,
                        spaceBetween: 15
                    },
                    768: {
                        slidesPerView: 2,
                        spaceBetween: 20
                    },
                    1024: {
                        slidesPerView: 4,
                        spaceBetween: 20
                    }
                }
            });

            // Load calendar and events
            async function loadCalendarAndEvents(startDate = null) {
                try {
                    const response = await fetch(`/api/events-calendar.php?start_date=${startDate || ''}`);
                    const data = await response.json();
                    
                    renderCalendar(data.calendar);
                    renderEvents(data.events);
                } catch (error) {
                    console.error('Error loading events:', error);
                    // Fallback to default data
                    loadDefaultData();
                }
            }

            // Render calendar (7 days)
            function renderCalendar(calendarDays) {
                const datesWrapper = document.querySelector('#dates-slider .swiper-wrapper');
                datesWrapper.innerHTML = '';
                
                calendarDays.forEach((day, index) => {
                    const dateSlide = document.createElement('div');
                    dateSlide.className = 'swiper-slide date-slide';
                    dateSlide.innerHTML = `
                        <span class="day">${day.day}</span>
                        <span class="month">${day.month}</span>
                    `;
                    dateSlide.dataset.date = day.date;
                    dateSlide.dataset.index = index;
                    datesWrapper.appendChild(dateSlide);
                });
                
                datesSwiper.update();
                
                // Bind date click events
                const dateSlides = document.querySelectorAll('.date-slide');
                dateSlides.forEach(slide => {
                    slide.addEventListener('click', () => {
                        // Remove active class from all dates
                        dateSlides.forEach(d => d.classList.remove('active'));
                        // Add active class to clicked date
                        slide.classList.add('active');
                        // Load events for selected date
                        const selectedDate = slide.dataset.date;
                        loadEventsForDate(selectedDate);
                    });
                });
                
                // Activate first date
                if (dateSlides[0]) {
                    dateSlides[0].classList.add('active');
                    loadEventsForDate(dateSlides[0].dataset.date);
                }
            }

            // Load events for specific date
            async function loadEventsForDate(date) {
                try {
                    const response = await fetch(`/api/events-calendar.php?start_date=${date}&events_only=true`);
                    const data = await response.json();
                    renderEvents(data.events);
                } catch (error) {
                    console.error('Error loading events for date:', error);
                }
            }

            // Render events (4 cards)
            function renderEvents(events) {
                const eventsWrapper = document.querySelector('#events-slider .swiper-wrapper');
                eventsWrapper.innerHTML = '';
                
                // Always show 4 cards
                for (let i = 0; i < 4; i++) {
                    const eventCard = document.createElement('div');
                    eventCard.className = 'swiper-slide';
                    
                    if (events[i]) {
                        // Real event card
                        const event = events[i];
                        eventCard.innerHTML = `
                            <div class="event-card">
                                <img src="${event.image || 'template/images/sample-image.jpg'}" alt="${event.title}" class="event-card__image">
                                <div class="event-card__content">
                                    <h3 class="event-card__title">${event.title}</h3>
                                    <div class="event-card__price">${event.price}</div>
                                    <p class="event-card__description">${event.description || ''}</p>
                                    <a href="${event.link || '#'}" class="event-card__link">Подробнее</a>
                                </div>
                            </div>
                        `;
                    } else {
                        // Placeholder card for days without events
                        eventCard.innerHTML = `
                            <div class="event-card event-card--placeholder">
                                <div class="event-card__placeholder-image">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                                <div class="event-card__content">
                                    <h3 class="event-card__title">Событий на эту дату еще нет</h3>
                                    <p class="event-card__description">Мы работаем над новыми мероприятиями. Следите за обновлениями!</p>
                                    <a href="#footer" class="event-card__link">Связаться с нами</a>
                                </div>
                            </div>
                        `;
                    }
                    
                    eventsWrapper.appendChild(eventCard);
                }
                
                eventsSwiper.update();
            }

            // Load default data (fallback)
            function loadDefaultData() {
                const today = new Date();
                const calendarDays = [];
                
                for (let i = 0; i < 7; i++) {
                    const date = new Date(today);
                    date.setDate(today.getDate() + i);
                    calendarDays.push({
                        day: date.getDate().toString().padStart(2, '0'),
                        month: date.toLocaleDateString('ru', { month: 'short' }),
                        date: date.toISOString().split('T')[0]
                    });
                }
                
                renderCalendar(calendarDays);
                renderEvents([]); // Empty events array will show placeholder cards
            }

            // Initialize
            loadCalendarAndEvents();
        });
    </script>

</body>
</html>