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

// Initialize page content service
require_once __DIR__ . '/classes/PageContentService.php';
$pageContentService = new PageContentService();

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
    
    <!-- DNS prefetch для внешних ресурсов -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    
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
        }
        
        .date-slide:hover {
            background: var(--color-1-100);
            transform: translateY(-2px);
        }
        
        .date-slide.active {
            background: var(--color-1-600);
            color: var(--color-white);
            border-color: var(--color-1-700);
            box-shadow: 0 4px 16px rgba(54, 107, 91, 0.3);
        }
        
        .date-day {
            display: block;
            font-size: var(--text-lg);
            font-weight: 700;
            margin-bottom: var(--vspace-0_25);
            line-height: 1;
        }
        
        .date-month {
            display: block;
            font-size: var(--text-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            line-height: 1;
        }
        
        /* Events Slider */
        .events-slider {
            position: relative;
        }
        
        .event-card {
            background: var(--color-white);
            border: 1px solid var(--color-2-200);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
            border-color: var(--color-1-300);
        }
        
        .event-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--color-2-100);
        }
        
        .event-info {
            padding: var(--vspace-1_5);
        }
        
        .event-title {
            font-size: var(--text-lg);
            font-weight: 700;
            margin: 0 0 var(--vspace-0_5);
            color: var(--color-1-800);
            line-height: 1.3;
            font-family: var(--font-1);
        }
        
        .event-description {
            font-size: var(--text-sm);
            color: var(--color-text-light);
            margin: 0 0 var(--vspace-1);
            line-height: 1.5;
        }
        
        .event-price {
            color: var(--color-1-600);
            font-weight: 700;
            font-size: var(--text-lg);
            margin: 0 0 var(--vspace-1);
        }
        
        .event-link {
            display: inline-flex;
            align-items: center;
            gap: var(--vspace-0_5);
            text-decoration: none;
            color: var(--color-1-600);
            font-weight: 600;
            font-size: var(--text-sm);
            transition: all 0.3s ease;
        }
        
        .event-link:hover {
            color: var(--color-1-700);
            transform: translateX(4px);
        }
        
        .event-link svg {
            width: 16px;
            height: 16px;
            transition: transform 0.3s ease;
        }
        
        .event-link:hover svg {
            transform: translateX(2px);
        }
        
        /* Swiper Navigation */
        .swiper-button-next,
        .swiper-button-prev {
            color: var(--color-1-600);
            background: var(--color-white);
            border-radius: 50%;
            width: 48px;
            height: 48px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: var(--color-1-600);
            color: var(--color-white);
            transform: scale(1.1);
        }
        
        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .date-slide {
                padding: var(--vspace-0_5) var(--vspace-0_25);
                font-size: var(--text-xs);
            }
            
            .date-day {
                font-size: var(--text-base);
            }
            
            .event-info {
                padding: var(--vspace-1);
            }
            
            .event-title {
                font-size: var(--text-base);
            }
        }
    </style>

    <!-- Preload критических ресурсов для ускорения загрузки
    ================================================== -->
    <link rel="preload" href="css/vendor.css" as="style">
    <link rel="preload" href="css/styles.css" as="style">
    <link rel="preload" href="js/plugins.js" as="script">
    <link rel="preload" href="js/main.js" as="script">
    
    <!-- Preload шрифтов -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,300;8..144,400;8..144,500;8..144,600;8..144,700&display=swap" as="style" crossorigin>
    <link rel="preload" href="fonts/Serati.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="fonts/SeratiItalic.ttf" as="font" type="font/ttf" crossorigin>
    
    <!-- Preload критических изображений -->
    <link rel="preload" href="<?php echo $pageMeta['intro_image_primary'] ?? 'template/images/shawa.png'; ?>" as="image">
    <link rel="preload" href="images/logo.png" as="image">

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
                             <?php echo $pageMeta['about_image_primary_2x'] ?? 'template/images/about-pic-primary@2x.jpg'; ?> 2x" 
                             alt=""
                             loading="lazy"
                             decoding="async"> 
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
                                            <span><?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Без названия'); ?></span>
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
                                    <h6 class="menu-block__cat-name"><?php echo htmlspecialchars($category['category_name'] ?? $category['name'] ?? 'Без названия'); ?></h6>
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
                                            <?php echo htmlspecialchars($thumb2x); ?> 2x" 
                                    alt="<?php echo htmlspecialchars($alt); ?>"
                                    loading="lazy"
                                    decoding="async">                                
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
                                            template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>@2x.jpg 2x" 
                                    alt=""
                                    loading="lazy"
                                    decoding="async">                                
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
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>
    
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
        });

        // Events Widget
        document.addEventListener('DOMContentLoaded', function() {
            // Данные событий из PHP (MongoDB)
            const events = <?php echo json_encode($eventsService->getEventsForWidget(8), JSON_UNESCAPED_UNICODE); ?>;

            // Инициализируем Swiper для событий
            const datesSwiper = new Swiper('#dates-slider', {
                slidesPerView: 'auto',
                spaceBetween: 12,
                freeMode: true,
                watchSlidesProgress: true,
                breakpoints: {
                    320: {
                        slidesPerView: 4,
                        spaceBetween: 8
                    },
                    768: {
                        slidesPerView: 6,
                        spaceBetween: 12
                    },
                    1024: {
                        slidesPerView: 7,
                        spaceBetween: 16
                    }
                }
            });

            const eventsSwiper = new Swiper('#events-slider', {
                slidesPerView: 1,
                spaceBetween: 20,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev'
                },
                breakpoints: {
                    768: {
                        slidesPerView: 2,
                        spaceBetween: 24
                    },
                    1024: {
                        slidesPerView: 4,
                        spaceBetween: 32
                    }
                }
            });

            // Рендерим события
            function renderEvents() {
                const dates = [];
                
                events.forEach((event, index) => {
                    // Создаем слайд даты
                    const dateSlide = document.createElement('div');
                    dateSlide.className = 'swiper-slide date-slide';
                    dateSlide.innerHTML = `
                        <span class="date-day">${event.day}</span>
                        <span class="date-month">${event.month}</span>
                    `;
                    dateSlide.dataset.index = index;
                    datesSwiper.appendSlide(dateSlide);
                    
                    // Создаем карточку события
                    const eventCard = document.createElement('div');
                    eventCard.className = 'swiper-slide';
                    eventCard.innerHTML = `
                        <div class="event-card">
                            <img src="${event.image}" alt="${event.title}" class="event-image" 
                                 onerror="this.src='template/images/sample-image.jpg'">
                            <div class="event-info">
                                <h3 class="event-title">${event.title}</h3>
                                <p class="event-description">${event.description}</p>
                                <div class="event-price">${event.price}</div>
                                <a href="${event.link}" class="event-link">
                                    Подробнее
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 12h14M12 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    `;
                    eventsSwiper.appendSlide(eventCard);
                    
                    dates.push(dateSlide);
                });
                
                // Обработчики кликов по датам
                dates.forEach(slide => {
                    slide.addEventListener('click', () => {
                        // Убираем активный класс со всех дат
                        dates.forEach(d => d.classList.remove('active'));
                        // Добавляем активный класс к выбранной дате
                        slide.classList.add('active');
                        // Переключаем на соответствующую карточку
                        eventsSwiper.slideTo(slide.dataset.index);
                    });
                });
                
                // Активируем первую дату
                if (dates[0]) {
                    dates[0].classList.add('active');
                }
            }
            
            // Запускаем рендеринг событий
            renderEvents();
        });
    </script>

</body>
</html>
