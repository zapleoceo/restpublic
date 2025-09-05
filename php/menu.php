<?php
// API configuration
$api_base_url = 'http://localhost:3002/api';

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
    <title>Полное меню - Республика Север</title>

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
                        <a class="logo" href="/">
                            <img src="images/logo.png" alt="Республика Север">
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
                        <h2 class="text-display-title">Наше меню</h2>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 4rem;">
                <!-- Categories Sidebar -->
                <div class="column xl-4 lg-5 md-12">
                    <div class="menu-categories">
                        <h3 style="font-size: 2rem; margin-bottom: 2rem; color: #d4af37;">Категории</h3>
                        <ul class="category-list">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <li class="category-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        data-category-id="<?php echo htmlspecialchars($category['category_id']); ?>">
                                        <a href="#" class="category-link">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="category-item active" data-category-id="default">
                                    <a href="#" class="category-link">Кофе</a>
                                </li>
                                <li class="category-item" data-category-id="default2">
                                    <a href="#" class="category-link">Десерты</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Popular Products -->
                <div class="column xl-8 lg-7 md-12">
                    <div class="popular-products">
                        <h3 style="font-size: 2rem; margin-bottom: 2rem; color: #d4af37;">Популярные блюда</h3>
                        <div id="popular-products-content">
                            <div class="loading" style="text-align: center; padding: 2rem;">
                                <p>Загрузка популярных блюд...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                        <a class="logo" href="/">
                            <img src="images/logo.png" alt="Республика Север">
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
                        <li>
                            <a href="https://t.me/gamezone_vietnam" target="_blank" rel="noopener noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"></path></svg>
                                <span class="u-screen-reader-text">Telegram</span>
                            </a>
                        </li>
                    </ul> <!--end s-footer__social -->  
                </div>
                
                <div class="column xl-9 lg-12 s-footer__main-end grid-cols grid-cols--wrap">
                    <div class="grid-cols__column footer-block">
                        <h6>Адрес</h6>
                        <p>
                        Нячанг, Вьетнам<br>
                        У подножия горы Ко Тьен
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
                </div>
            </div>
            
            <div class="row s-footer__bottom">       
                <div class="column xl-6 lg-12">
                    <p class="ss-copyright">
                        <span>© 2024 Республика Север. Все права защищены.</span>
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
    
    <style>
        .category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .category-item {
            margin-bottom: 1rem;
        }
        
        .category-link {
            display: block;
            padding: 1rem 1.5rem;
            background: #f8f8f8;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .category-item.active .category-link {
            background: #d4af37;
            color: white;
            border-left-color: #b8941f;
        }
        
        .category-link:hover {
            background: #e8e8e8;
            transform: translateX(5px);
        }
        
        .category-item.active .category-link:hover {
            background: #b8941f;
        }
        
        .popular-products {
            padding-left: 2rem;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .product-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #d4af37;
        }
        
        .loading {
            color: #666;
            font-style: italic;
        }
        
        .error {
            color: #e74c3c;
            text-align: center;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .popular-products {
                padding-left: 0;
                margin-top: 2rem;
            }
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryItems = document.querySelectorAll('.category-item');
            const popularContent = document.getElementById('popular-products-content');
            let currentCategoryId = null;
            
            // Get first active category
            const activeCategory = document.querySelector('.category-item.active');
            if (activeCategory) {
                currentCategoryId = activeCategory.dataset.categoryId;
                loadPopularProducts(currentCategoryId);
            }
            
            // Add click handlers to categories
            categoryItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all items
                    categoryItems.forEach(cat => cat.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Load popular products for this category
                    const categoryId = this.dataset.categoryId;
                    currentCategoryId = categoryId;
                    loadPopularProducts(categoryId);
                });
            });
            
            function loadPopularProducts(categoryId) {
                // Show loading state
                popularContent.innerHTML = '<div class="loading"><p>Загрузка популярных блюд...</p></div>';
                
                // Make API request
                fetch(`/api/menu/categories/${categoryId}/popular?limit=5`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.popular_products && data.popular_products.length > 0) {
                            displayPopularProducts(data.popular_products);
                        } else {
                            // Fallback to regular products if no popular data
                            loadRegularProducts(categoryId);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading popular products:', error);
                        // Fallback to regular products
                        loadRegularProducts(categoryId);
                    });
            }
            
            function loadRegularProducts(categoryId) {
                fetch(`/api/menu/categories/${categoryId}/products`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.products && data.products.length > 0) {
                            // Take first 5 products
                            const topProducts = data.products.slice(0, 5);
                            displayPopularProducts(topProducts);
                        } else {
                            displayNoProducts();
                        }
                    })
                    .catch(error => {
                        console.error('Error loading products:', error);
                        displayError();
                    });
            }
            
            function displayPopularProducts(products) {
                if (!products || products.length === 0) {
                    displayNoProducts();
                    return;
                }
                
                let html = '';
                products.forEach(product => {
                    const price = product.price_normalized || product.price || 0;
                    const formattedPrice = Math.round(price).toLocaleString('ru-RU');
                    
                    html += `
                        <div class="product-item">
                            <div class="product-info">
                                <h4>${escapeHtml(product.product_name || product.name || 'Блюдо')}</h4>
                                <p>${escapeHtml(product.product_description || product.description || '')}</p>
                            </div>
                            <div class="product-price">
                                ${formattedPrice} ₽
                            </div>
                        </div>
                    `;
                });
                
                popularContent.innerHTML = html;
            }
            
            function displayNoProducts() {
                popularContent.innerHTML = '<div class="error"><p>В этой категории пока нет блюд</p></div>';
            }
            
            function displayError() {
                popularContent.innerHTML = '<div class="error"><p>Ошибка загрузки меню. Попробуйте позже.</p></div>';
            }
            
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
</body>
</html>
