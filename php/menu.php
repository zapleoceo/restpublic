<?php
// Load menu from MongoDB cache for fast rendering
$categories = [];
$products = [];
$products_by_category = [];
$menu_loaded = false;

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/../php/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        
        if ($menuData) {
            $categories = $menuData['categories'] ?? [];
            $products = $menuData['products'] ?? [];
            $menu_loaded = !empty($categories) && !empty($products);
            
            // Group products by category for quick access and sort by popularity
            if ($products) {
                foreach ($products as $product) {
                    $category_id = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                    if (!isset($products_by_category[$category_id])) {
                        $products_by_category[$category_id] = [];
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
                        $products_by_category[$category_id][] = $product;
                    }
                }
                
                // Sort products by popularity (visible first, then by sort_order, then by price)
                foreach ($products_by_category as $category_id => $category_products) {
                    usort($category_products, function($a, $b) {
                        // First: visible products
                        $aVisible = isset($a['spots']) ? $a['spots'][0]['visible'] ?? '1' : '1';
                        $bVisible = isset($b['spots']) ? $b['spots'][0]['visible'] ?? '1' : '1';
                        
                        if ($aVisible != $bVisible) {
                            return $bVisible <=> $aVisible; // visible first
                        }
                        
                        // Second: sort_order (lower is more popular)
                        $aSort = (int)($a['sort_order'] ?? 999);
                        $bSort = (int)($b['sort_order'] ?? 999);
                        
                        if ($aSort != $bSort) {
                            return $aSort <=> $bSort;
                        }
                        
                        // Third: by price (lower price is more popular for basic items)
                        $aPrice = (int)($a['price_normalized'] ?? 0);
                        $bPrice = (int)($b['price_normalized'] ?? 0);
                        
                        return $aPrice <=> $bPrice;
                    });
                    
                    $products_by_category[$category_id] = $category_products;
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("MongoDB not available, trying API fallback: " . $e->getMessage());
    
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
        $menu_loaded = !empty($categories) && !empty($products);
        
        // Group products by category and sort by popularity
        if ($menu_loaded) {
            foreach ($products as $product) {
                $category_id = (string)($product['menu_category_id'] ?? $product['category_id'] ?? 'default');
                if (!isset($products_by_category[$category_id])) {
                    $products_by_category[$category_id] = [];
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
                    $products_by_category[$category_id][] = $product;
                }
            }
            
            // Sort products by popularity (visible first, then by sort_order, then by price)
            foreach ($products_by_category as $category_id => $category_products) {
                usort($category_products, function($a, $b) {
                    // First: visible products
                    $aVisible = isset($a['spots']) ? $a['spots'][0]['visible'] ?? '1' : '1';
                    $bVisible = isset($b['spots']) ? $b['spots'][0]['visible'] ?? '1' : '1';
                    
                    if ($aVisible != $bVisible) {
                        return $bVisible <=> $aVisible; // visible first
                    }
                    
                    // Second: sort_order (lower is more popular)
                    $aSort = (int)($a['sort_order'] ?? 999);
                    $bSort = (int)($b['sort_order'] ?? 999);
                    
                    if ($aSort != $bSort) {
                        return $aSort <=> $bSort;
                    }
                    
                    // Third: by price (lower price is more popular for basic items)
                    $aPrice = (int)($a['price_normalized'] ?? 0);
                    $bPrice = (int)($b['price_normalized'] ?? 0);
                    
                    return $aPrice <=> $bPrice;
                });
                
                $products_by_category[$category_id] = $category_products;
            }
        }
    }
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
    <link rel="stylesheet" href="../template/css/vendor.css">
    <link rel="stylesheet" href="../template/css/styles.css">
    <link rel="stylesheet" href="../template/css/custom.css">

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="../template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../template/favicon-16x16.png">
    <link rel="manifest" href="../template/site.webmanifest">

    <style>
        /* Menu page specific styles */
        .menu-page {
            padding-top: 2rem;
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
                padding-top: 1rem;
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
                            <img src="../images/logo.png" alt="North Republic">
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
                <?php if ($menu_loaded && !empty($categories)): ?>
                <div class="row">
                    <div class="column xl-12">
                        <div class="menu-categories">
                            <?php foreach ($categories as $index => $category): ?>
                                <button class="category-btn <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                                    <?php echo htmlspecialchars($category['category_name'] ?? $category['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Sort Controls -->
                        <div class="sort-controls">
                            <span class="sort-label">Сортировка:</span>
                            <button class="sort-btn active" data-sort="default">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/>
                                </svg>
                                По умолчанию
                            </button>
                            <button class="sort-btn" data-sort="alphabet">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M14,17H7v-2h7V17z M17,13H7v-2h10V13z M17,9H7V7h10V9z M3,5V3h18v2H3z"/>
                                </svg>
                                По алфавиту
                            </button>
                            <button class="sort-btn" data-sort="price">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z"/>
                                </svg>
                                По цене
                            </button>
                            <button class="sort-btn" data-sort="popularity">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/>
                                </svg>
                                По популярности
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menu Sections -->
                <?php if ($menu_loaded && !empty($categories)): ?>
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
                    <!-- Error message when menu data is not loaded -->
                    <div class="menu-section" style="text-align: center; padding: 4rem 0;">
                        <h2 style="color: var(--color-text-dark); margin-bottom: 2rem;">Упс, что-то с меню не так</h2>
                        <p style="color: var(--color-text-light); font-size: 1.2rem; margin-bottom: 2rem;">
                            К сожалению, меню временно недоступно. Попробуйте обновить страницу или зайти позже.
                        </p>
                        <button onclick="window.location.reload()" class="btn btn--primary">
                            Обновить страницу
                        </button>
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
    <script src="../template/js/plugins.js"></script>
    <script src="../template/js/main.js"></script>
    
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
        
        /* Sort Controls */
        .sort-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            padding: 1rem;
            background: var(--color-bg-neutral-dark);
            border-radius: var(--border-radius);
            flex-wrap: wrap;
        }
        
        .sort-label {
            font-weight: 600;
            color: var(--color-text-dark);
            margin-right: 0.5rem;
        }
        
        .sort-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: transparent;
            border: 1px solid var(--color-border);
            border-radius: 20px;
            color: var(--color-text-light);
            font-size: var(--text-sm);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sort-btn:hover {
            background: var(--color-bg-primary);
            color: var(--color-white);
            border-color: var(--color-bg-primary);
        }
        
        .sort-btn.active {
            background: var(--color-bg-primary);
            color: var(--color-white);
            border-color: var(--color-bg-primary);
        }
        
        .sort-btn svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
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
            
            // Sort functionality
            const sortBtns = document.querySelectorAll('.sort-btn');
            sortBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const sortType = this.dataset.sort;
                    
                    // Update active sort button
                    sortBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Sort current visible section
                    const activeSection = document.querySelector('.menu-section.active');
                    if (activeSection) {
                        sortMenuItems(activeSection, sortType);
                    }
                });
            });
            
            // Function to sort menu items
            function sortMenuItems(section, sortType) {
                const menuList = section.querySelector('.menu-list');
                if (!menuList) return;
                
                const items = Array.from(menuList.querySelectorAll('.menu-list__item'));
                
                items.sort((a, b) => {
                    const nameA = a.querySelector('h4').textContent.trim();
                    const nameB = b.querySelector('h4').textContent.trim();
                    const priceA = parseFloat(a.querySelector('.menu-list__item-price').textContent.replace(/[^\d]/g, ''));
                    const priceB = parseFloat(b.querySelector('.menu-list__item-price').textContent.replace(/[^\d]/g, ''));
                    
                    switch(sortType) {
                        case 'alphabet':
                            return nameA.localeCompare(nameB, 'ru');
                        case 'price':
                            return priceA - priceB;
                        case 'popularity':
                            // For now, sort by name as we don't have popularity data
                            return nameA.localeCompare(nameB, 'ru');
                        default:
                            return 0; // Keep original order
                    }
                });
                
                // Clear and re-append sorted items
                menuList.innerHTML = '';
                items.forEach(item => {
                    menuList.appendChild(item);
                });
                
                // Re-animate items
                animateMenuItems(section);
            }
        });
    </script>
</body>
</html>