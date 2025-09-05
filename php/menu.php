<?php
// API configuration
$api_base_url = 'https://northrepublic.me/api';

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
    $category_id = $product['menu_category_id'] ?? $product['category_id'] ?? 'default';
    if (!isset($products_by_category[$category_id])) {
        $products_by_category[$category_id] = [];
    }
    $products_by_category[$category_id][] = $product;
}
?>
<!DOCTYPE html>
<html lang="ru" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Меню - Республика Север</title>
    <meta name="description" content="Полное меню ресторана Республика Север - изысканные блюда и напитки">
    <meta name="keywords" content="меню, ресторан, блюда, напитки, Нячанг, Вьетнам">
    <meta name="author" content="Республика Север">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS -->
    <link rel="stylesheet" href="template/css/vendor.css">
    <link rel="stylesheet" href="template/css/styles.css">
    <link rel="stylesheet" href="template/css/custom.css">

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

    <style>
        /* Menu page specific styles */
        .menu-page {
            padding-top: 8rem;
        }
        
        .menu-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 3rem;
            justify-content: center;
        }
        
        .category-btn {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: 2px solid transparent;
            border-radius: 25px;
            color: var(--color-text-dark);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: #1c1e1d;
            color: var(--color-white);
            border-color: #1c1e1d;
        }
        
        .menu-section {
            margin-bottom: 4rem;
        }
        
        .menu-section h2 {
            font-size: 2.5rem;
            color: var(--color-bg-primary);
            margin-bottom: 2rem;
            text-align: center;
            font-family: var(--font-2);
        }
        
        .products-grid {
            margin-top: 2rem;
        }
        
        .menu-list {
            list-style: none;
            margin-left: 0;
        }
        
        .menu-list__item {
            display: flex;
            justify-content: space-between;
            padding-top: var(--vspace-1);
            padding-inline: var(--vspace-1);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        
        .menu-list__item:nth-child(odd) {
            background-color: var(--color-bg-neutral-dark);
        }
        
        .menu-list__item h4 {
            font-family: var(--type-body);
            margin-top: 0;
            margin-bottom: var(--vspace-0_25);
            color: var(--color-text-dark);
        }
        
        .menu-list__item p {
            font-weight: 300;
            font-size: var(--text-sm);
            line-height: var(--vspace-0_75);
            margin-bottom: var(--vspace-1);
            color: var(--color-text-light);
        }
        
        .menu-list__item-desc {
            max-width: min(100%, 90rem);
            padding-right: calc(var(--gutter) * 2);
        }
        
        .menu-list__item-price {
            font-family: var(--type-body);
            font-weight: 500;
            font-size: var(--text-base);
            padding-right: 0.2rem;
            color: var(--color-bg-primary);
        }
        
        .menu-list__item-price span {
            font-size: 0.8em;
            position: relative;
            bottom: 0.2em;
            left: -1px;
        }
        
        .no-products {
            text-align: center;
            padding: 3rem;
            color: var(--color-text-light);
        }
        
        .no-products h3 {
            margin-bottom: 1rem;
            color: var(--color-text-dark);
        }
        
        @media (max-width: 768px) {
            .menu-page {
                padding-top: 6rem;
            }
            
            .menu-categories {
                flex-direction: column;
                align-items: center;
            }
            
            .category-btn {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .menu-section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body id="top">
    
    <!-- Preloader -->
    <div id="preloader">
        <div id="loader" class="dots-fade">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <!-- Page wrap -->
    <div id="page" class="s-pagewrap">
        
        <!-- Simple Header for Menu Page -->
        <header class="s-header">
            <div class="container s-header__content">
                <div class="s-header__block">
                    <div class="header-logo">
                        <a class="logo" href="/">
                            <img src="images/logo.png" alt="North Republic">
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Menu Content -->
        <main class="menu-page">
            <div class="container">
                <!-- Page Title -->
                <div class="row">
                    <div class="column xl-12">
                        <h1 class="text-display-title" style="text-align: center; margin-bottom: 3rem;">Наше меню</h1>
                    </div>
                </div>

                <!-- Category Navigation -->
                <?php if (!empty($categories)): ?>
                <div class="row">
                    <div class="column xl-12">
                        <div class="menu-categories">
                            <?php foreach ($categories as $index => $category): ?>
                                <button class="category-btn <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                                    <?php echo htmlspecialchars($category['category_name'] ?? $category['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menu Sections -->
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="menu-section <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category['category_id']); ?>" style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                            
                            <?php 
                            $category_products = $products_by_category[$category['category_id']] ?? [];
                            if (!empty($category_products)): 
                            ?>
                                <div class="products-grid">
                                    <ul class="menu-list">
                                        <?php foreach ($category_products as $product): ?>
                                            <li class="menu-list__item">
                                                <div class="menu-list__item-desc">
                                                    <h4><?php echo htmlspecialchars($product['product_name'] ?? 'Без названия'); ?></h4>
                                                    <?php if (!empty($product['description'])): ?>
                                                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="menu-list__item-price">
                                                    <?php echo number_format($product['price_normalized'] ?? $product['price'] ?? 0, 0, ',', ' '); ?> ₫
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="no-products">
                                    <h3>В этой категории пока нет блюд</h3>
                                    <p>Мы работаем над пополнением меню</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback content -->
                    <div class="menu-section">
                        <h2>Кофе и напитки</h2>
                        <div class="products-grid">
                            <ul class="menu-list">
                                <li class="menu-list__item">
                                    <div class="menu-list__item-desc">
                                        <h4>Эспрессо</h4>
                                        <p>Классический крепкий кофе</p>
                                    </div>
                                    <div class="menu-list__item-price">
                                        15,000 ₫
                                    </div>
                                </li>
                                
                                <li class="menu-list__item">
                                    <div class="menu-list__item-desc">
                                        <h4>Латте</h4>
                                        <p>Кофе с молоком и пенкой</p>
                                    </div>
                                    <div class="menu-list__item-price">
                                        20,000 ₫
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Back to Home -->
                <div class="row" style="margin-top: 4rem; text-align: center;">
                    <div class="column xl-12">
                        <a href="/" class="btn btn--primary">Вернуться на главную</a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
        
        <!-- Cart Component -->
        <?php include 'components/cart.php'; ?>
    </div>

    <!-- JavaScript -->
    <script src="template/js/plugins.js"></script>
    <script src="template/js/main.js"></script>
    
    <style>
        /* Анимации появления блюд */
        .menu-list__item {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s var(--ease-smooth-in-out);
        }
        
        .menu-list__item.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .menu-section {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s var(--ease-smooth-in-out);
        }
        
        .menu-section.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .category-btn {
            transition: all 0.3s var(--ease-smooth-in-out);
        }
    </style>
    
    <script>
        // Category filtering with animations
        document.addEventListener('DOMContentLoaded', function() {
            const categoryBtns = document.querySelectorAll('.category-btn');
            const menuSections = document.querySelectorAll('.menu-section');
            
            // Set initial active state
            if (categoryBtns.length > 0) {
                categoryBtns[0].classList.add('active');
            }
            if (menuSections.length > 0) {
                menuSections.forEach((section, index) => {
                    if (index === 0) {
                        section.style.display = 'block';
                        // Анимация появления первой секции
                        setTimeout(() => {
                            section.classList.add('animate-in');
                            animateMenuItems(section);
                        }, 100);
                    } else {
                        section.style.display = 'none';
                    }
                });
            }

            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const category = this.dataset.category;
                    
                    // Update active button
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show/hide sections with animation
                    menuSections.forEach(section => {
                        if (section.dataset.category === category) {
                            section.style.display = 'block';
                            section.classList.remove('animate-in');
                            
                            // Анимация появления секции
                            setTimeout(() => {
                                section.classList.add('animate-in');
                                animateMenuItems(section);
                            }, 50);
                        } else {
                            section.classList.remove('animate-in');
                            setTimeout(() => {
                                section.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
            
            // Функция анимации элементов меню
            function animateMenuItems(section) {
                const menuItems = section.querySelectorAll('.menu-list__item');
                menuItems.forEach((item, index) => {
                    item.classList.remove('animate-in');
                    setTimeout(() => {
                        item.classList.add('animate-in');
                    }, index * 100); // Задержка между элементами
                });
            }
        });
    </script>
</body>
</html>