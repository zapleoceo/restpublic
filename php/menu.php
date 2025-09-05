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
    $category_id = $product['category_id'] ?? $product['menu_category_id'] ?? 'default';
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
            background: #f8f8f8;
            border: 2px solid transparent;
            border-radius: 25px;
            color: #2c2c2c;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: #d4af37;
            color: #fff;
            border-color: #d4af37;
        }
        
        .menu-section {
            margin-bottom: 4rem;
        }
        
        .menu-section h2 {
            font-size: 2.5rem;
            color: #d4af37;
            margin-bottom: 2rem;
            text-align: center;
            font-family: var(--font-2);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .product-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #999;
            position: relative;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-content {
            padding: 1.5rem;
        }
        
        .product-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c2c2c;
            margin-bottom: 0.5rem;
            font-family: var(--font-2);
        }
        
        .product-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #d4af37;
        }
        
        .add-to-cart-btn {
            background: #d4af37;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .add-to-cart-btn:hover {
            background: #b8941f;
            transform: translateY(-2px);
        }
        
        .add-to-cart-btn:active {
            transform: translateY(0);
        }
        
        .no-products {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .no-products h3 {
            margin-bottom: 1rem;
            color: #2c2c2c;
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
        
        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- Menu Content -->
        <main class="menu-page">
            <div class="container">
                <!-- Page Title -->
                <div class="row">
                    <div class="column xl-12">
                        <div class="section-header" data-num="02">
                            <h1 class="text-display-title">Наше меню</h1>
                            <p class="lead" style="text-align: center; margin-top: 1rem; color: #666;">
                                Откройте для себя изысканные блюда и напитки Республики Север
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Category Navigation -->
                <?php if (!empty($categories)): ?>
                <div class="row">
                    <div class="column xl-12">
                        <div class="menu-categories">
                            <button class="category-btn active" data-category="all">Все блюда</button>
                            <?php foreach ($categories as $category): ?>
                                <button class="category-btn" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                                    <?php echo htmlspecialchars($category['category_name'] ?? $category['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menu Sections -->
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="menu-section" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                            <h2><?php echo htmlspecialchars($category['category_name'] ?? $category['name']); ?></h2>
                            
                            <?php 
                            $category_products = $products_by_category[$category['category_id']] ?? [];
                            if (!empty($category_products)): 
                            ?>
                                <div class="products-grid">
                                    <?php foreach ($category_products as $product): ?>
                                        <div class="product-card">
                                            <div class="product-image">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                <?php else: ?>
                                                    🍽️
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-content">
                                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                                <?php if (!empty($product['description'])): ?>
                                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="product-footer">
                                                    <div class="product-price">
                                                        <?php echo number_format($product['price_normalized'] ?? $product['price'] ?? 0, 0, ',', ' '); ?> ₽
                                                    </div>
                                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                                                            <path d="M9 8V17H11V8H9ZM13 8V17H15V8H13Z"/>
                                                        </svg>
                                                        В корзину
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
                            <div class="product-card">
                                <div class="product-image">☕</div>
                                <div class="product-content">
                                    <h3 class="product-name">Эспрессо</h3>
                                    <p class="product-description">Классический крепкий кофе</p>
                                    <div class="product-footer">
                                        <div class="product-price">150 ₽</div>
                                        <button class="add-to-cart-btn" onclick="addToCart({product_id: 'espresso', name: 'Эспрессо', price_normalized: 150})">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                                                <path d="M9 8V17H11V8H9ZM13 8V17H15V8H13Z"/>
                                            </svg>
                                            В корзину
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="product-card">
                                <div class="product-image">🥛</div>
                                <div class="product-content">
                                    <h3 class="product-name">Латте</h3>
                                    <p class="product-description">Кофе с молоком и пенкой</p>
                                    <div class="product-footer">
                                        <div class="product-price">200 ₽</div>
                                        <button class="add-to-cart-btn" onclick="addToCart({product_id: 'latte', name: 'Латте', price_normalized: 200})">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                                                <path d="M9 8V17H11V8H9ZM13 8V17H15V8H13Z"/>
                                            </svg>
                                            В корзину
                                        </button>
                                    </div>
                                </div>
                            </div>
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
    
    <script>
        // Category filtering
        document.addEventListener('DOMContentLoaded', function() {
            const categoryBtns = document.querySelectorAll('.category-btn');
            const menuSections = document.querySelectorAll('.menu-section');
            
            categoryBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const category = this.dataset.category;
                    
                    // Update active button
                    categoryBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show/hide sections
                    menuSections.forEach(section => {
                        if (category === 'all' || section.dataset.category === category) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>