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

// Initialize translation service for fallback
require_once __DIR__ . '/classes/TranslationService.php';
$translationService = new TranslationService();

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
                    $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=5';
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

// Set page title and meta tags
$pageTitle = $pageMeta['title'] ?? 'North Republic - Ресторан в Нячанге';
$pageDescription = $pageMeta['description'] ?? 'North Republic - изысканный ресторан в Нячанге с великолепной кухней и уютной атмосферой.';
$pageKeywords = $pageMeta['keywords'] ?? 'ресторан, нячанг, вьетнам, кухня, еда, ужин, обед';
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
                    <div class="intro-header__overline"><?php echo $translationService->get('intro.welcome', 'Добро пожаловать в'); ?></div>
                    <h1 class="intro-header__big-type">
                        North <br>
                        Republic
                    </h1>
                </div> <!-- end intro-header -->

                <figure class="intro-pic-primary">
                    <img src="template/images/shawa.png" 
                         srcset="template/images/shawa.png 1x, 
                         template/images/shawa.png 2x" alt="">  
                </figure> <!-- end intro-pic-primary -->    
                    
                <div class="intro-block-content">
                    <figure class="intro-block-content__pic">
                        <img src="template/images/intro-pic-secondary.jpg" 
                             srcset="template/images/intro-pic-secondary.jpg 1x, 
                             template/images/intro-pic-secondary@2x.jpg 2x" alt=""> 
                    </figure>
                    <div class="intro-block-content__text">
                        <p class="lead">
                            <?php 
                            // Используем контент из БД, если есть, иначе fallback на переводы
                            if (!empty($pageContent['content'])) {
                                echo $pageContent['content'];
                            } else {
                                echo $translationService->get('intro.description', 'Добро пожаловать в <strong>North Republic</strong> — место, где встречаются изысканная кухня, уютная атмосфера и незабываемые моменты.');
                            }
                            ?>
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
                        <h2 class="text-display-title"><?php echo $translationService->get('about.title', 'О нас'); ?></h2>
                    </div>  

                    <figure class="about-pic-primary">
                        <img src="template/images/about-pic-primary.jpg" 
                             srcset="template/images/about-pic-primary.jpg 1x, 
                             template/images/about-pic-primary@2x.jpg 2x" alt=""> 
                    </figure>
                </div> <!-- end s-about__content-start -->

                <div class="column xl-6 lg-6 md-12 s-about__content-end">                   
                    <p class="lead">
                        <?php echo $translationService->get('about.paragraph1', 'Добро пожаловать в <strong>«Республику Север»</strong> — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга. Здесь, в объятиях первозданной природы, у подножия легендарной горы Ко Тьен, современность встречается с дикой красотой тропического края, создавая пространство безграничных возможностей.'); ?>
                    </p>

                    <p>
                        <?php echo $translationService->get('about.paragraph2', 'Взгляните вверх — перед вами раскинулись склоны Горы Феи, той самой Ко Тьен, чья мифическая красота веками вдохновляла поэтов и путешественников. Панорамные виды на изумрудные холмы и сверкающий залив превращают каждый момент здесь в кадр из волшебной сказки. Это место, где время замедляет свой бег, а душа находит долгожданный покой.'); ?>
                    </p>

                    <p>
                        <?php echo $translationService->get('about.paragraph3', '<strong>«Республика Север»</strong> — это калейдоскоп впечатлений под открытым небом. Адреналиновые баталии в лазертаге и захватывающие дуэли с луками в арчеритаге соседствуют с уютными беседками для семейных пикников. Интеллектуальные квесты переплетаются с ароматами барбекю, а вечерние мероприятия наполняют воздух музыкой и смехом до поздней ночи.'); ?>
                    </p>

                    <p>
                        <?php echo $translationService->get('about.paragraph4', 'Наш ресторан и кофейня — это кулинарное путешествие, где авторские блюда рождаются из слияния русских традиций и вьетнамской экзотики. Здесь каждое блюдо — произведение искусства, а каждый глоток кофе — мост между культурами. Творческие ярмарки, музыкальные вечера и тематические фестивали превращают каждый день в маленький праздник.'); ?>
                    </p>

                    <p>
                        <?php echo $translationService->get('about.paragraph5', 'В <strong>«Республике Север»</strong> каждый найдет свой идеальный способ провести время: от корпоративных приключений до романтических ужинов под звездным небом, от детских праздников до философских бесед у камина. Это место, где рождаются новые дружбы, крепнут семейные узы и создаются воспоминания на всю жизнь.'); ?>
                    </p>
                </div> <!-- end s-about__content-end -->
            </div> <!-- end s-about__content -->
        </section> <!-- end s-about -->

        <!-- # menu
        ================================================== -->
        <section id="menu" class="container s-menu target-section">
            <div class="row s-menu__content">
                <div class="column xl-4 lg-5 md-12 s-menu__content-start">
                    <div class="section-header" data-num="02">
                        <h2 class="text-display-title"><?php echo $translationService->get('menu.title', 'Наше меню'); ?></h2>
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
                                        <?php echo $translationService->get('menu.error', 'Упс, что-то с меню не так'); ?>
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
                                                    <h4><?php echo $translationService->get('menu.no_items', 'В этой категории пока нет блюд'); ?></h4>
                                                    <p><?php echo $translationService->get('menu.working_on_it', 'Мы работаем над пополнением меню'); ?></p>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="menu-block__group tab-content__item active">
                                <h6 class="menu-block__cat-name"><?php echo $translationService->get('menu.title', 'Меню'); ?></h6>
                                <ul class="menu-list">
                                    <li class="menu-list__item">
                                        <div class="menu-list__item-desc">
                                            <h4><?php echo $translationService->get('menu.error', 'Упс, что-то с меню не так'); ?></h4>
                                            <p><?php echo $translationService->get('menu.unavailable', 'К сожалению, меню временно недоступно. Попробуйте обновить страницу.'); ?></p>
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
                        <?php echo $translationService->get('menu.full_menu_button', 'Открыть полное меню'); ?>
                    </a>
                </div>
            </div> <!-- end s-menu__footer -->
        </section> <!-- end s-menu -->

        <!-- # gallery
        ================================================== -->
        <section id="gallery" class="container s-gallery target-section">
            <div class="row s-gallery__header">
                <div class="column xl-12 section-header-wrap">
                    <div class="section-header" data-num="04">
                        <h2 class="text-display-title"><?php echo $translationService->get('gallery.title', 'Галерея'); ?></h2>
                    </div>               
                </div> <!-- end section-header-wrap -->   
            </div> <!-- end s-gallery__header -->   

            <div class="gallery-items grid-cols grid-cols--wrap">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <div class="gallery-items__item grid-cols__column">
                        <a href="template/images/gallery/large/l-gallery-<?php echo sprintf('%02d', $i); ?>.jpg" class="gallery-items__item-thumb glightbox">
                            <img src="template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>.jpg" 
                                srcset="template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>.jpg 1x, 
                                        template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>@2x.jpg 2x" alt="">                                
                        </a>
                    </div>
                <?php endfor; ?>
            </div> <!-- end gallery-items -->
        </section> <!-- end s-gallery -->

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
    </div>

    <!-- JavaScript
    ================================================== -->
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
    </script>

</body>
</html>
