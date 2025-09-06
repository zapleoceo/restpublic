<?php
// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

try {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        // Group products by category for quick access
        if ($products) {
            foreach ($products as $product) {
                $categoryId = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                if (!isset($productsByCategory[$categoryId])) {
                    $productsByCategory[$categoryId] = [];
                }
                $productsByCategory[$categoryId][] = $product;
            }
        }
    }
} catch (Exception $e) {
    error_log("MongoDB not available, using fallback: " . $e->getMessage());
    // MongoDB не доступен, используем fallback
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
    <link rel="stylesheet" href="template/css/vendor.css">
    <link rel="stylesheet" href="template/css/styles.css">

    <!-- favicons
    ================================================== -->
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

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
        <?php include 'php/components/header.php'; ?>

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
                    <img src="template/images/intro-pic-primary.jpg" 
                         srcset="template/images/intro-pic-primary.jpg 1x, 
                         template/images/intro-pic-primary@2x.jpg 2x" alt="">  
                </figure> <!-- end intro-pic-primary -->    
                    
                <div class="intro-block-content">
                    <figure class="intro-block-content__pic">
                        <img src="template/images/intro-pic-secondary.jpg" 
                             srcset="template/images/intro-pic-secondary.jpg 1x, 
                             template/images/intro-pic-secondary@2x.jpg 2x" alt=""> 
                    </figure>
                    <div class="intro-block-content__text">
                        <p class="lead">
                            Добро пожаловать в <strong>North Republic</strong> — место, где встречаются 
                            изысканная кухня, уютная атмосфера и незабываемые моменты.
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
                        <h2 class="text-display-title">О нас</h2>
                    </div>  

                    <figure class="about-pic-primary">
                        <img src="template/images/about-pic-primary.jpg" 
                             srcset="template/images/about-pic-primary.jpg 1x, 
                             template/images/about-pic-primary@2x.jpg 2x" alt=""> 
                    </figure>
                </div> <!-- end s-about__content-start -->

                <div class="column xl-6 lg-6 md-12 s-about__content-end">                   
                    <p class="lead">
                        <strong>Республика Север</strong> — уникальное место в северной части Нячанга, где сочетаются активные развлечения, изысканная кухня и захватывающие виды на гору Ко Тьен. Это мультиформатное пространство под открытым небом, которое объединило в себе лазертаг, арчеритаг (лучный бой), квесты и зоны для барбекю с уютными беседками среди зелёных холмов.
                    </p>

                    <p>
                        Локация располагается у подножия горы Ко Тьен (Angel Mountain/Fairy Mountain), известной также как Гора Феи — одной из самых живописных природных достопримечательностей северного Нячанга. С территории комплекса открываются великолепные панорамные виды на горные хребты и залив, создавая неповторимую атмосферу единения с природой вдали от городской суеты.
                    </p>

                    <p>
                        Помимо активных развлечений, здесь работает ресторан и кофейня, где можно насладиться как традиционной русской, так и вьетнамской кухней. Каждый вечер в «Республике Север» проходят различные мероприятия — от творческих ярмарок и мастер-классов до музыкальных вечеринок и тематических событий. Это место стало настоящим хабом для русскоговорящего сообщества Нячанга.
                    </p>

                    <p>
                        Уникальность комплекса заключается в органичном сочетании различных форматов отдыха на одной территории: здесь можно провести корпоративное мероприятие с лазертагом, устроить семейный пикник в беседке с видом на горы, посетить творческую ярмарку или просто расслабиться в ресторане после активного дня. Атмосфера свободы и творчества, живописная природа и разнообразие активностей делают это место идеальным для любого времяпрепровождения.
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
                                                    <h4>В этой категории пока нет блюд</h4>
                                                    <p>Мы работаем над пополнением меню</p>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="menu-block__group tab-content__item active">
                                <h6 class="menu-block__cat-name">Меню</h6>
                                <ul class="menu-list">
                                    <li class="menu-list__item">
                                        <div class="menu-list__item-desc">
                                            <h4>Упс, что-то с меню не так</h4>
                                            <p>К сожалению, меню временно недоступно. Попробуйте обновить страницу.</p>
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
        <?php include 'php/components/footer.php'; ?>
        
        <!-- Cart Component -->
        <?php include 'php/components/cart.php'; ?>
    </div>

    <!-- JavaScript
    ================================================== -->
    <script src="template/js/plugins.js"></script>
    <script src="template/js/main.js"></script>
    
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
