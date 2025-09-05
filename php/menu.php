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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Полное меню - North Republic</title>

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <link rel="stylesheet" href="template/css/vendor.css">
    <link rel="stylesheet" href="template/css/styles.css">
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">
</head>

<body id="top">
    
    <div id="preloader">
        <div id="loader" class="dots-fade">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <div id="page" class="s-pagewrap">

        <!-- Header -->
        <header class="s-header">
            <div class="container s-header__content">
                <div class="s-header__block">
                    <div class="header-logo">
                        <a class="logo" href="index.php">
                            <img src="template/images/logo.svg" alt="Homepage">
                        </a>
                    </div>
                    <a class="header-menu-toggle" href="#0"><span>Menu</span></a>
                </div>
            
                <nav class="header-nav">    
                    <ul class="header-nav__links">
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="index.php#about">О нас</a></li>
                        <li class="current"><a href="menu.php">Меню</a></li>
                        <li><a href="index.php#gallery">Галерея</a></li>
                    </ul>
                    
                    <div class="header-contact">
                        <a href="tel:+84349338758" class="header-contact__num btn">
                            <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" width="24" height="24" color="#000000"><defs><style>.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}</style></defs><path class="cls-6376396cc3a86d32eae6f0dc-1" d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"></path></svg>
                            +84 349 338 758
                        </a>
                    </div>
                </nav>         
            </div>
        </header>

        <!-- Menu Content -->
        <section class="container s-menu" style="padding-top: 8rem;">
            <div class="row">
                <div class="column xl-12">
                    <div class="section-header" data-num="02">
                        <h2 class="text-display-title">Полное меню</h2>
                    </div>
                </div>
            </div>

            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="row" style="margin-top: 4rem;">
                        <div class="column xl-12">
                            <h3 class="menu-category-title" style="font-size: 2.4rem; margin-bottom: 2rem; color: #d4af37;">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h3>
                            
                            <div class="menu-block">
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
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback content -->
                <div class="row" style="margin-top: 4rem;">
                    <div class="column xl-12">
                        <h3 class="menu-category-title" style="font-size: 2.4rem; margin-bottom: 2rem; color: #d4af37;">
                            Кофе
                        </h3>
                        <div class="menu-block">
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
                    </div>
                </div>
            <?php endif; ?>

            <div class="row" style="margin-top: 4rem; text-align: center;">
                <div class="column xl-12">
                    <a href="index.php" class="btn btn--primary">Вернуться на главную</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="footer" class="container s-footer">  
            <div class="row s-footer__main">             
                <div class="column xl-3 lg-12 footer-block s-footer__main-start">     
                    <div class="s-footer__logo">
                        <a class="logo" href="index.php">
                            <img src="template/images/logo.svg" alt="Homepage">
                        </a>
                    </div>  
                </div>
                
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
                            <li><span class="opening-hours__days">Будни</span><span class="opening-hours__time">8:00 - 22:00</span></li>
                            <li><span class="opening-hours__days">Выходные</span><span class="opening-hours__time">9:00 - 23:00</span></li>
                        </ul> 
                    </div>  
                </div>
            </div>
            
            <div class="row s-footer__bottom">       
                <div class="column xl-6 lg-12">
                    <p class="ss-copyright">
                        <span>© 2024 North Republic. Все права защищены.</span> 
                        <span>Design by <a href="https://styleshout.com/">StyleShout</a></span>
                        Distributed by <a href="https://themewagon.com" target="_blank">ThemeWagon</a>
                    </p>
                </div>
            </div>

            <div class="ss-go-top">
                <a class="smoothscroll" title="Наверх" href="#top">                 
                    <svg clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fill-rule="nonzero"/></svg>
                </a>                                
                <span>Наверх</span>   
            </div>
        </footer>
    </div>

    <script src="template/js/plugins.js"></script>
    <script src="template/js/main.js"></script>
</body>
</html>
