<?php
// API configuration
$api_base_url = 'http://localhost:3001/api';

// Function to fetch data from Node.js backend
function fetchFromAPI($endpoint) {
    global $api_base_url;
    $url = $api_base_url . $endpoint;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
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

// Fetch menu data
$menu_data = fetchFromAPI('/menu');
$categories = $menu_data['categories'] ?? [];
$products = $menu_data['products'] ?? [];

// Group products by category
$products_by_category = [];
foreach ($products as $product) {
    $category_id = $product['category_id'] ?? 'default';
    if (!isset($products_by_category[$category_id])) {
        $products_by_category[$category_id] = [];
    }
    $products_by_category[$category_id][] = $product;
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
    <meta name="description" content="North Republic - уникальное место для отдыха и развлечений в Хошимине">
    <meta name="keywords" content="ресторан, развлечения, лазертаг, квесты, Хошимин, Вьетнам">
    <meta name="author" content="North Republic">

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

        <!-- # site header 
        ================================================== -->
        <header class="s-header">
            <div class="container s-header__content">
                <div class="s-header__block">
                    <div class="header-logo">
                        <a class="logo" href="/">
                            <img src="images/logo.png" alt="North Republic">
                        </a>
                    </div>
                    <a class="header-menu-toggle" href="#0"><span>Menu</span></a>
                </div> <!-- end s-header__block -->
            
                <nav class="header-nav">    
                    <ul class="header-nav__links">
                        <li class="current"><a class="smoothscroll" href="#intro">Главная</a></li>
                        <li><a class="smoothscroll" href="#about">О нас</a></li>
                        <li><a class="smoothscroll" href="#menu">Меню</a></li>
                        <li><a class="smoothscroll" href="#gallery">Галерея</a></li>
                    </ul> <!-- end header-nav__links -->  
                    
                    <div class="header-contact">
                        <a href="tel:+84349338758" class="header-contact__num btn">
                            <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" width="24" height="24" color="#000000"><defs><style>.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}</style></defs><path class="cls-6376396cc3a86d32eae6f0dc-1" d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"></path></svg>
                            +84 349 338 758
                        </a>
                    </div> <!-- end header-contact -->
                </nav> <!-- end header-nav -->         
            </div> <!-- end s-header__content -->
        </header> <!-- end s-header -->

        <!-- # intro
        ================================================== -->
        <section id="intro" class="container s-intro target-section">
            <div class="grid-block s-intro__content">
                <div class="intro-header">
                    <div class="intro-header__overline">Добро пожаловать в мир приключений</div>
                    <h1 class="intro-header__big-type">
                        North <br>
                        Republic
                    </h1>
                    <p class="intro-description">Откройте для себя уникальный мир развлечений и отдыха в North Republic.</p>
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
                        <h2 class="text-display-title">О нас</h2>
                    </div>  

                    <figure class="about-pic-primary">
                        <img src="template/images/about-pic-primary.jpg" 
                             srcset="template/images/about-pic-primary.jpg 1x, 
                             template/images/about-pic-primary@2x.jpg 2x" alt=""> 
                    </figure>
                </div> <!-- end s-about__content-start -->

                <div class="column xl-6 lg-6 md-12 s-about__content-end">                   
                    <p>
                    North Republic - это уникальное место, где каждый найдет что-то для себя. Мы предлагаем широкий спектр развлечений и услуг для всех возрастов.
                    </p>

                    <ul class="about-features">
                        <li>Современные развлечения</li>
                        <li>Профессиональный персонал</li>
                        <li>Уютная атмосфера</li>
                        <li>Доступные цены</li>
                    </ul>
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
                        <ul class="tab-nav__list">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <li>
                                        <a href="#tab-<?php echo htmlspecialchars($category['category_id']); ?>">
                                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                                            <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Fallback menu items -->
                                <li>
                                    <a href="#tab-coffee">
                                        <span>Кофе</span>
                                        <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="#tab-desserts">
                                        <span>Десерты</span>
                                        <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav> <!-- end tab-nav -->
                </div> <!-- end s-menu__content-start -->

                <div class="column xl-6 lg-6 md-12 s-menu__content-end">
                    <div class="tab-content menu-block">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <div id="tab-<?php echo htmlspecialchars($category['category_id']); ?>" class="menu-block__group tab-content__item">
                                    <h6 class="menu-block__cat-name">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </h6>

                                    <ul class="menu-list">
                                        <?php 
                                        $category_products = $products_by_category[$category['category_id']] ?? [];
                                        foreach ($category_products as $product): 
                                        ?>
                                            <li class="menu-list__item">
                                                <div class="menu-list__item-desc">                                            
                                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>
                                                </div>
                                                <div class="menu-list__item-price">
                                                    <span>₽</span><?php echo number_format($product['price_normalized'] ?? $product['price'] ?? 0, 0, ',', ' '); ?>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul> <!-- end menu-list -->
                                </div> <!-- end tab-content__item -->
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback menu content -->
                            <div id="tab-coffee" class="menu-block__group tab-content__item">
                                <h6 class="menu-block__cat-name">Кофе</h6>
                                <ul class="menu-list">
                                    <li class="menu-list__item">
                                        <div class="menu-list__item-desc">                                            
                                            <h4>Эспрессо</h4>
                                            <p>Классический крепкий кофе</p>
                                        </div>
                                        <div class="menu-list__item-price">
                                            <span>₽</span>150
                                        </div>
                                    </li>
                                    <li class="menu-list__item">
                                        <div class="menu-list__item-desc">                                            
                                            <h4>Латте</h4>
                                            <p>Кофе с молоком и пенкой</p>
                                        </div>
                                        <div class="menu-list__item-price">
                                            <span>₽</span>200
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div> <!-- menu-block -->
                </div> <!-- end s-menu__content-end -->
            </div> <!-- end s-menu__content -->
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
                                    <img src="template/images/avatars/user-02.jpg" alt="Author image" class="testimonials-slider__avatar">
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
                                    <img src="template/images/avatars/user-03.jpg" alt="Author image" class="testimonials-slider__avatar">
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
                                    <img src="template/images/avatars/user-01.jpg" alt="Author image" class="testimonials-slider__avatar">
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
        
        <!-- # footer 
        ================================================== -->
        <footer id="footer" class="container s-footer">  
            <div class="row s-footer__top row-x-center">
                <div class="column xl-6 lg-8 md-10 footer-block footer-newsletter">                  
                    <h5>
                    Подпишитесь на нашу рассылку для получения <br>
                    обновлений, новостей и эксклюзивных предложений.
                    </h5>
    
                    <div class="subscribe-form">
                        <form id="mc-form" class="mc-form">
                            <div class="mc-input-wrap">
                                <input type="email" name="EMAIL" id="mce-EMAIL" placeholder="Ваш email адрес" required>
                                <input type="submit" name="subscribe" value="Подписаться" class="btn btn--primary">
                            </div> 
                            <div class="mc-status"></div>
                        </form>
                    </div> <!-- end subscribe-form -->
                </div> <!-- end footer-newsletter -->
            </div> <!-- end s-footer__top -->         

            <div class="row s-footer__main">             
                <div class="column xl-3 lg-12 footer-block s-footer__main-start">     
                    <div class="s-footer__logo">
                        <a class="logo" href="/">
                            <img src="images/logo.png" alt="North Republic">
                        </a>
                    </div>  

                    <ul class="s-footer__social social-list">
                        <li>
                            <a href="https://facebook.com/vngamezone" target="_blank" rel="noopener noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M20,3H4C3.447,3,3,3.448,3,4v16c0,0.552,0.447,1,1,1h8.615v-6.96h-2.338v-2.725h2.338v-2c0-2.325,1.42-3.592,3.5-3.592 c0.699-0.002,1.399,0.034,2.095,0.107v2.42h-1.435c-1.128,0-1.348,0.538-1.348,1.325v1.735h2.697l-0.35,2.725h-2.348V21H20 c0.553,0,1-0.448,1-1V4C21,3.448,20.553,3,20,3z"></path></svg>
                                <span class="u-screen-reader-text">Facebook</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://t.me/gamezone_vietnam" target="_blank" rel="noopener noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"></path></svg>
                                <span class="u-screen-reader-text">Telegram</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.instagram.com/gamezone.vn/" target="_blank" rel="noopener noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M11.999,7.377c-2.554,0-4.623,2.07-4.623,4.623c0,2.554,2.069,4.624,4.623,4.624c2.552,0,4.623-2.07,4.623-4.624 C16.622,9.447,14.551,7.377,11.999,7.377L11.999,7.377z M11.999,15.004c-1.659,0-3.004-1.345-3.004-3.003 c0-1.659,1.345-3.003,3.004-3.003s3.002,1.344,3.002,3.003C15.001,13.659,13.658,15.004,11.999,15.004L11.999,15.004z"></path><circle cx="16.806" cy="7.207" r="1.078"></circle><path d="M20.533,6.111c-0.469-1.209-1.424-2.165-2.633-2.632c-0.699-0.263-1.438-0.404-2.186-0.42 c-0.963-0.042-1.268-0.054-3.71-0.054s-2.755,0-3.71,0.054C7.548,3.074,6.809,3.215,6.11,3.479C4.9,3.946,3.945,4.902,3.477,6.111 c-0.263,0.7-0.404,1.438-0.419,2.186c-0.043,0.962-0.056,1.267-0.056,3.71c0,2.442,0,2.753,0.056,3.71 c0.015,0.748,0.156,1.486,0.419,2.187c0.469,1.208,1.424,2.164,2.634,2.632c0.696,0.272,1.435,0.426,2.185,0.45 c0.963,0.042,1.268,0.055,3.71,0.055s2.755,0,3.71-0.055c0.747-0.015,1.486-0.157,2.186-0.419c1.209-0.469,2.164-1.424,2.633-2.633 c0.263-0.7,0.404-1.438,0.419-2.186c0.043-0.962,0.056-1.267,0.056-3.71s0-2.753-0.056-3.71C20.941,7.57,20.801,6.819,20.533,6.111z M19.315,15.643c-0.007,0.576-0.111,1.147-0.311,1.688c-0.305,0.787-0.926,1.409-1.712,1.711c-0.535,0.199-1.099,0.303-1.67,0.311 c-0.95,0.044-1.218,0.055-3.654,0.055c-2.438,0-2.687,0-3.655-0.055c-0.569-0.007-1.135-0.112-1.669-0.311 c-0.789-0.301-1.414-0.923-1.719-1.711c-0.196-0.534-0.302-1.099-0.311-1.669c-0.043-0.95-0.053-1.218-0.053-3.654 c0-2.437,0-2.686,0.053-3.655c0.007-0.576,0.111-1.146,0.311-1.687c0.305-0.789,0.93-1.41,1.719-1.712 c0.534-0.198,1.1-0.303,1.669-0.311c0.951-0.043,1.218-0.055,3.655-0.055c2.437,0,2.687,0,3.654,0.055 c0.571,0.007,1.135,0.112,1.67,0.311c0.786,0.303,1.407,0.925,1.712,1.712c0.196,0.534,0.302,1.099,0.311,1.669 c0.043,0.951,0.054,1.218,0.054,3.655c0,2.436,0,2.698-0.043,3.654H19.315z"></path></svg>
                                <span class="u-screen-reader-text">Instagram</span>
                            </a>
                        </li>
                        <li>
                            <a href="https://www.tiktok.com/@gamezone.vn" target="_blank" rel="noopener noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"></path></svg>
                                <span class="u-screen-reader-text">TikTok</span>
                            </a>
                        </li>
                    </ul> <!--end s-footer__social -->
                </div> <!-- end s-footer__main-start -->
                
                <div class="column xl-9 lg-12 s-footer__main-end grid-cols grid-cols--wrap">
                    <div class="grid-cols__column footer-block">
                        <h6>Адрес</h6>
                        <p>
                        Хошимин, Вьетнам
                        </p>
                    </div>
                    
                    <div class="grid-cols__column footer-block">     
                        <h6>Контакты</h6>
                        <ul class="link-list">
                            <li><a href="mailto:info@northrepublic.me">info@northrepublic.me</a></li>
                            <li><a href="tel:+84349338758">+84 349 338 758</a></li>
                        </ul> 
                    </div>
                    
                    <div class="grid-cols__column footer-block">                   
                        <h6>Часы работы</h6>
                        <ul class="opening-hours">
                            <li><span class="opening-hours__days">Будни</span><span class="opening-hours__time">10:00 - 22:00</span></li>
                            <li><span class="opening-hours__days">Выходные</span><span class="opening-hours__time">9:00 - 23:00</span></li>
                        </ul> 
                    </div>  
                </div> <!-- s-footer__main-end -->                  
            </div> <!-- end  s-footer__main-content -->                 
            
            <div class="row s-footer__bottom">       
                <div class="column xl-6 lg-12">
                    <p class="ss-copyright">
                        <span>© 2024 North Republic. Все права защищены.</span>
                    </p>
                </div>
            </div> <!-- end s-footer__bottom -->          

            <div class="ss-go-top">
                <a class="smoothscroll" title="Наверх" href="#top">                 
                    <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
                </a>                                
                <span>Наверх</span>   
            </div> <!-- end ss-go-top -->
        </footer> <!-- end s-footer -->
    </div> <!-- end page-wrap -->

    <!-- Java Script
    ================================================== -->
    <script src="template/js/plugins.js"></script>
    <script src="template/js/main.js"></script>

</body>
</html>
