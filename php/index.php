<?php
// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        // Group products by category for quick access and sort by popularity
        if ($products) {
            foreach ($products as $product) {
                $categoryId = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                if (!isset($productsByCategory[$categoryId])) {
                    $productsByCategory[$categoryId] = [];
                }
                
                // Check if product is visible
                $isVisible = true;
                if (isset($product['spots']) && is_array($product['spots'])) {
                    foreach ($product['spots'] as $spot) {
                        if (isset($spot['visible']) && $spot['visible'] == '0') {
                            $isVisible = false;
                            break;
                        }
                    }
                }
                
                // Only add visible products
                if ($isVisible) {
                    $productsByCategory[$categoryId][] = $product;
                }
            }
            
            // Sort products by popularity (visible first, then by sort_order, then by price)
            foreach ($productsByCategory as $categoryId => $categoryProducts) {
                usort($categoryProducts, function($a, $b) {
                    // First: visible products
                    $aVisible = isset($a['spots']) ? $a['spots'][0]['visible'] ?? '1' : '1';
                    $bVisible = isset($b['spots']) ? $b['spots'][0]['visible'] ?? '1' : '1';
                    
                    if ($aVisible != $bVisible) {
                        return $bVisible <=> $aVisible; // visible first
                    }
                    
                    // Second: sort_order (higher is more popular - reverse order)
                    $aSort = (int)($a['sort_order'] ?? 0);
                    $bSort = (int)($b['sort_order'] ?? 0);
                    
                    if ($aSort != $bSort) {
                        return $bSort <=> $aSort; // higher sort_order first (more popular)
                    }
                    
                    // Third: by name (alphabetical order as final tiebreaker)
                    $aName = $a['product_name'] ?? $a['name'] ?? '';
                    $bName = $b['product_name'] ?? $b['name'] ?? '';
                    
                    return $aName <=> $bName; // alphabetical order
                });
                
                // Take only top 5 most popular products
                $productsByCategory[$categoryId] = array_slice($categoryProducts, 0, 5);
            }
        }
    }
} catch (Exception $e) {
    error_log("MongoDB not available, using fallback: " . $e->getMessage());
    
    // Fallback to API if MongoDB fails
    $api_base_url = 'https://northrepublic.me:3002/api';
    
    function fetchFromAPI($endpoint) {
        global $api_base_url;
        $url = $api_base_url . $endpoint;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        return json_decode($response, true);
    }

    // Try to fetch from API as fallback
    $menu_data = fetchFromAPI('/menu');
    if ($menu_data) {
        $categories = $menu_data['categories'] ?? [];
        $products = $menu_data['products'] ?? [];
        
        // Group products by category and sort by popularity
        if (!empty($products)) {
            foreach ($products as $product) {
                $categoryId = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                if (!isset($productsByCategory[$categoryId])) {
                    $productsByCategory[$categoryId] = [];
                }
                
                // Check if product is visible
                $isVisible = true;
                if (isset($product['spots']) && is_array($product['spots'])) {
                    foreach ($product['spots'] as $spot) {
                        if (isset($spot['visible']) && $spot['visible'] == '0') {
                            $isVisible = false;
                            break;
                        }
                    }
                }
                
                // Only add visible products
                if ($isVisible) {
                    $productsByCategory[$categoryId][] = $product;
                }
            }
            
            // Sort products by popularity (visible first, then by sort_order, then by price)
            foreach ($productsByCategory as $categoryId => $categoryProducts) {
                usort($categoryProducts, function($a, $b) {
                    // First: visible products
                    $aVisible = isset($a['spots']) ? $a['spots'][0]['visible'] ?? '1' : '1';
                    $bVisible = isset($b['spots']) ? $b['spots'][0]['visible'] ?? '1' : '1';
                    
                    if ($aVisible != $bVisible) {
                        return $bVisible <=> $aVisible; // visible first
                    }
                    
                    // Second: sort_order (higher is more popular - reverse order)
                    $aSort = (int)($a['sort_order'] ?? 0);
                    $bSort = (int)($b['sort_order'] ?? 0);
                    
                    if ($aSort != $bSort) {
                        return $bSort <=> $aSort; // higher sort_order first (more popular)
                    }
                    
                    // Third: by name (alphabetical order as final tiebreaker)
                    $aName = $a['product_name'] ?? $a['name'] ?? '';
                    $bName = $b['product_name'] ?? $b['name'] ?? '';
                    
                    return $aName <=> $bName; // alphabetical order
                });
                
                // Take only top 5 most popular products
                $productsByCategory[$categoryId] = array_slice($categoryProducts, 0, 5);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <!--- basic page needs
    ================================================== -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>North Republic</title>

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="../template/css/vendor.css">
    <link rel="stylesheet" href="../template/css/styles.css">

    <!-- favicons
    ================================================== -->
    <link rel="apple-touch-icon" sizes="180x180" href="../template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../template/favicon-16x16.png">
    <link rel="manifest" href="../template/site.webmanifest">

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
                    <div class="intro-header__overline">Добро пожаловать в</div>
                    <h1 class="intro-header__big-type">
                        North <br>
                        Republic
                    </h1>
                </div> <!-- end intro-header -->

                <figure class="intro-pic-primary">
                    <img src="../template/images/intro-pic-primary.jpg" 
                         srcset="../template/images/intro-pic-primary.jpg 1x, 
                         ../template/images/intro-pic-primary@2x.jpg 2x" alt="">  
                </figure> <!-- end intro-pic-primary -->    
                    
                <div class="intro-block-content">
                    <figure class="intro-block-content__pic">
                        <img src="../template/images/intro-pic-secondary.jpg" 
                             srcset="../template/images/intro-pic-secondary.jpg 1x, 
                             ../template/images/intro-pic-secondary@2x.jpg 2x" alt=""> 
                    </figure> <!-- end intro-pic-secondary -->   

                    <div class="intro-block-content__text-wrap">
                        <p class="intro-block-content__text">
                            Откройте для себя уникальный мир развлечений и отдыха в North Republic.
                        </p>
                        
                        <ul class="intro-block-content__social">
                            <li><a href="https://facebook.com/vngamezone" target="_blank">FB</a></li>
                            <li><a href="https://www.instagram.com/gamezone.vn/" target="_blank">IG</a></li>
                            <li><a href="https://www.tiktok.com/@gamezone.vn" target="_blank">TT</a></li>
                            <li><a href="https://t.me/gamezone_vietnam" target="_blank">TG</a></li>
                        </ul>
                    </div> <!-- end intro-block-content__social -->   
                </div> <!-- end intro-block-content -->

                <div class="intro-scroll">
                    <a class="smoothscroll" href="#about">                            
                        <span class="intro-scroll__circle-text"></span>
                        <span class="intro-scroll__text u-screen-reader-text">Прокрутить вниз</span>
                        <div class="intro-scroll__icon">
                            <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m5.214 14.522s4.505 4.502 6.259 6.255c.146.147.338.22.53.22s.384-.073.53-.22c1.754-1.752 6.249-6.244 6.249-6.244.144-.144.216-.334.217-.523 0-.193-.074-.386-.221-.534-.293-.293-.766-.294-1.057-.004l-4.968 4.968v-14.692c0-.414-.336-.75-.75-.75s-.75.336-.75.75v14.692l-4.979-4.978c-.289-.289-.761-.287-1.054.006-.148.148-.222.341-.221.534 0 .189.071.377.215.52z" fill-rule="nonzero"/></svg>
                        </div>
                    </a>
                </div> <!-- end intro-scroll -->
            </div> <!-- grid-block -->            
        </section> <!-- end s-intro -->

        <!-- # about
        ================================================== -->
        <section id="about" class="container s-about target-section">
            <div class="row s-about__content">
                <div class="column xl-4 lg-5 md-12 s-about__content-start">
                    <div class="section-header" data-num="01">
                        <h2 class="text-display-title">Наша история</h2>
                    </div>  

                    <figure class="about-pic-primary">
                        <img src="../template/images/about-pic-primary.jpg" 
                             srcset="../template/images/about-pic-primary.jpg 1x, 
                             ../template/images/about-pic-primary@2x.jpg 2x" alt=""> 
                    </figure>
                </div> <!-- end s-about__content-start -->

                <div class="column xl-6 lg-6 md-12 s-about__content-end">                   
                    <p>
                    North Republic - это уникальное место, где каждый найдет что-то для себя. Мы предлагаем широкий спектр развлечений и услуг для всех возрастов.
                    </p>

                    <p>
                    Experience the perfect blend of modern comfort and traditional charm at North Republic. Our carefully curated menu and welcoming atmosphere create an unforgettable dining experience.
                    </p>
                </div> <!--end column -->
            </div> <!-- end s-about__content-end -->
        </section> <!-- end s-about -->   

        <!-- # menu
        ================================================== -->
        <section id="menu" class="container s-menu target-section">
            <div class="row s-menu__content">
                <div class="column xl-4 lg-5 md-12 s-menu__content-start">
                    <div class="section-header" data-num="02">
                        <h2 class="text-display-title">Наше меню</h2>
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
                                        Упс, что-то с меню не так
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav> <!-- end tab-nav -->
                </div> <!-- end s-menu__content-start -->

                <div class="column xl-6 lg-6 md-12 s-menu__content-end">
                    <div class="tab-content menu-block" id="menu-content">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <?php 
                                $categoryId = (string)$category['category_id'];
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
                                                    <h4>Нет товаров</h4>
                                                    <p>В этой категории пока нет товаров</p>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="menu-block__group tab-content__item active">
                                <h6 class="menu-block__cat-name">Ошибка загрузки</h6>
                                <ul class="menu-list">
                                    <li class="menu-list__item">
                                        <div class="menu-list__item-desc">                                            
                                            <h4>Упс, что-то с меню не так</h4>
                                            <p>Попробуйте обновить страницу или зайти позже</p>
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
                            Открыть полное меню
                        </a>
                </div>
            </div> <!-- end s-menu__footer -->
        </section> <!-- end s-menu -->  

        <!-- # gallery
        ================================================== -->
        <section id="gallery" class="container s-gallery target-section">
            <div class="row s-gallery__header">
                <div class="column xl-12 section-header-wrap">
                    <div class="section-header" data-num="03">
                        <h2 class="text-display-title">Галерея</h2>
                    </div>               
                </div> <!-- end section-header-wrap -->   
            </div> <!-- end s-gallery__header -->   

            <div class="gallery-items grid-cols grid-cols--wrap">
                <?php for ($i = 1; $i <= 8; $i++): ?>
                    <div class="gallery-items__item grid-cols__column">
                        <a href="../template/images/gallery/large/l-gallery-<?php echo sprintf('%02d', $i); ?>.jpg" class="gallery-items__item-thumb glightbox">
                            <img src="../template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>.jpg" 
                                srcset="../template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>.jpg 1x, 
                                        ../template/images/gallery/gallery-<?php echo sprintf('%02d', $i); ?>@2x.jpg 2x" alt="">                                
                        </a>
                    </div> <!-- end gallery-items__item-->
                <?php endfor; ?>
            </div> <!-- end grid-list-items -->
        </section> <!-- end s-gallery -->  

        <!-- # testimonials
        ================================================== -->
        <section id="testimonials" class="container s-testimonials">
            <div class="row s-testimonials__content">
                <div class="column xl-12">
                    <h3 class="testimonials-title u-text-center">Что говорят наши клиенты</h3>
    
                    <div class="swiper-container testimonials-slider">    
                        <div class="swiper-wrapper">
                            <div class="testimonials-slider__slide swiper-slide">
                                <div class="testimonials-slider__author">
                                    <img src="../template/images/avatars/user-02.jpg" alt="Author image" class="testimonials-slider__avatar">
                                    <cite class="testimonials-slider__cite">
                                        Анна Петрова
                                        <span>Москва</span>
                                    </cite>
                                </div>
                                <p>
                                Отличное место для встреч с друзьями! Кофе просто восхитительный, а атмосфера очень уютная. 
                                Обязательно вернемся снова.
                                </p>
                            </div> <!-- end testimonials-slider__slide -->
            
                            <div class="testimonials-slider__slide swiper-slide">
                                <div class="testimonials-slider__author">
                                    <img src="../template/images/avatars/user-03.jpg" alt="Author image" class="testimonials-slider__avatar">
                                    <cite class="testimonials-slider__cite">
                                        Дмитрий Иванов
                                        <span>Санкт-Петербург</span>
                                    </cite>
                                </div>
                                <p>
                                Прекрасное место для работы! Быстрый Wi-Fi, вкусный кофе и тихая атмосфера. 
                                Персонал очень дружелюбный и внимательный.
                                </p>
                            </div> <!-- end testimonials-slider__slide -->
            
                            <div class="testimonials-slider__slide swiper-slide">
                                <div class="testimonials-slider__author">
                                    <img src="../template/images/avatars/user-01.jpg" alt="Author image" class="testimonials-slider__avatar">
                                    <cite class="testimonials-slider__cite">
                                        Елена Смирнова
                                        <span>Екатеринбург</span>
                                    </cite>
                                </div>
                                <p>
                                Очень понравились десерты! Особенно чизкейк - просто тает во рту. 
                                Рекомендую всем любителям качественного кофе и сладостей.
                                </p>
                            </div> <!-- end testimonials-slider__slide -->
                        </div> <!-- end swiper-wrapper -->
    
                        <div class="swiper-pagination"></div>
                    </div> <!-- end testimonials-slider -->
                </div> <!-- end column -->
            </div> <!-- end s-testimonials__content -->
        </section> <!-- end s-testimonials --> 
        
        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
    </div> <!-- end page-wrap -->

    <!-- Java Script
    ================================================== -->
    <script src="../template/js/plugins.js"></script>
    <script src="../template/js/main.js"></script>
    
    <!-- Menu Tab Switching Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers for tab switching
        const categoryTabs = document.querySelectorAll('.tab-nav__list a');
        const tabContents = document.querySelectorAll('.tab-content__item');
        
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                const categoryId = href.replace('#tab-', '');
                
                // Remove active class from all tabs and contents
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });
                
                categoryTabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                const targetTab = document.getElementById(`tab-${categoryId}`);
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            });
        });
    });
    </script>

</body>
</html>
