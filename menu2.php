<?php
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Load category translator
require_once __DIR__ . '/category-translator.php';

// Обновляем кеш меню при заходе на страницу (в фоновом режиме)
function updateMenuCacheAsync() {
    $cacheUrl = 'http://localhost:3002/api/cache/update-menu';
    
    // Создаем контекст для асинхронного запроса
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 5, // Короткий таймаут, чтобы не блокировать загрузку страницы
            'header' => 'Content-Type: application/json',
            'ignore_errors' => true // Игнорируем ошибки, чтобы не влиять на отображение страницы
        ]
    ]);
    
    // Выполняем запрос в фоновом режиме
    @file_get_contents($cacheUrl, false, $context);
}

// Проверяем, нужно ли обновлять кеш (например, раз в 10 минут)
$cacheUpdateFile = __DIR__ . '/data/menu_cache_last_update.txt';
$shouldUpdateCache = true;

if (file_exists($cacheUpdateFile)) {
    $lastUpdate = (int)file_get_contents($cacheUpdateFile);
    $currentTime = time();
    $timeDiff = $currentTime - $lastUpdate;
    
    // Обновляем кеш только если прошло больше 10 минут
    if ($timeDiff < 600) { // 600 секунд = 10 минут
        $shouldUpdateCache = false;
    }
}

if ($shouldUpdateCache) {
    // Обновляем кеш асинхронно
    updateMenuCacheAsync();
    
    // Записываем время последнего обновления
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0755, true);
    }
    file_put_contents($cacheUpdateFile, time());
}

// Load menu from MongoDB cache for fast rendering
$categories = [];
$products = [];
$products_by_category = [];
$menu_loaded = false;

try {
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        
        if ($menuData) {
            $categories = $menuData['categories'] ?? [];
            $products = $menuData['products'] ?? [];
            $menu_loaded = !empty($categories) && !empty($products);
            
            // API configuration for popular products
            $api_base_url = 'http://localhost:3002/api';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json'
                ]
            ]);
            
            // Get all products by category from API
            if ($categories) {
                foreach ($categories as $category) {
                    $categoryId = (string)($category['category_id']);
                    $products_by_category[$categoryId] = [];
                    
                    // Try to get popular products from API (sorted by real sales)
                    try {
                        $authToken = $_ENV['API_AUTH_TOKEN'] ?? getenv('API_AUTH_TOKEN');
                        $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=20&token=' . urlencode($authToken);
                        $popularResponse = @file_get_contents($popularUrl, false, $context);
                        
                        if ($popularResponse !== false) {
                            $popularData = json_decode($popularResponse, true);
                            if ($popularData && isset($popularData['popular_products'])) {
                                $products_by_category[$categoryId] = $popularData['popular_products'];
                                continue;
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Failed to get popular products for category $categoryId: " . $e->getMessage());
                    }
                    
                    // Fallback: get all products from category and sort by sort_order (if API failed)
                    $categoryProducts = [];
                    foreach ($products as $product) {
                        if (($product['menu_category_id'] ?? $product['category_id']) == $categoryId) {
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
                            
                            if ($isVisible) {
                                $categoryProducts[] = $product;
                            }
                        }
                    }
                    
                    // Sort by sort_order as fallback
                    usort($categoryProducts, function($a, $b) {
                        $aSort = (int)($a['sort_order'] ?? 0);
                        $bSort = (int)($b['sort_order'] ?? 0);
                        return $bSort <=> $aSort;
                    });
                    
                    $products_by_category[$categoryId] = $categoryProducts;
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
    
    // Добавляем токен авторизации
    $authToken = $_ENV['API_AUTH_TOKEN'] ?? getenv('API_AUTH_TOKEN');
    $url .= (strpos($url, '?') !== false ? '&' : '?') . 'token=' . urlencode($authToken);
    
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
                    
                    // Second: sort_order (higher is more popular - reverse order)
                    $aSort = (int)($a['sort_order'] ?? 0);
                    $bSort = (int)($b['sort_order'] ?? 0);
                    
                    if ($aSort != $bSort) {
                        return $bSort <=> $aSort; // higher sort_order first (more popular)
                    }
                    
                    // Third: by price (higher price first for premium items)
                    $aPrice = (int)($a['price_normalized'] ?? 0);
                    $bPrice = (int)($b['price_normalized'] ?? 0);
                    
                    return $bPrice <=> $aPrice; // higher price first
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
    <title>Меню v2 - Республика Север</title>
    <meta name="description" content="Новая версия меню ресторана Республика Север - изысканные блюда и напитки">
    <meta name="keywords" content="меню, ресторан, блюда, напитки, Нячанг, Вьетнам">
    <meta name="author" content="Республика Север">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="css/menu.css">
    <link rel="stylesheet" href="css/cart-modal.css">
    <link rel="stylesheet" href="css/auth-modal.css">

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

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
                    
                    <!-- Header Actions -->
                    <div class="header-actions">
                        <!-- Authorization Icon -->
                        <div class="header-auth">
                            <button class="auth-icon" id="authIcon" title="Авторизация">
                                <img src="images/icons/auth-gray.png" alt="Авторизация" class="auth-icon-img">
                            </button>
                            
                            <!-- Auth Dropdown Menu -->
                            <div class="auth-dropdown" id="authDropdown">
                                <div class="auth-dropdown__content">
                                    <!-- For non-authenticated users -->
                                    <div class="auth-dropdown__guest" id="authGuestMenu">
                                        <a href="#" class="auth-dropdown__item" id="authLoginBtn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
                                            </svg>
                                            Войти
                                        </a>
                                    </div>
                                    
                                    <!-- For authenticated users -->
                                    <div class="auth-dropdown__user" id="authUserMenu" style="display: none;">
                                        <a href="#" class="auth-dropdown__item" id="authProfileBtn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                            </svg>
                                            Профиль
                                        </a>
                                        <a href="#" class="auth-dropdown__item" id="authOrdersBtn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                                            </svg>
                                            Заказы
                                        </a>
                                        <a href="#" class="auth-dropdown__item" id="authLogoutBtn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                                            </svg>
                                            Выйти
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cart Icon -->
                        <div class="header-cart">
                            <button class="cart-icon" id="cartIcon" title="Корзина">
                                <img src="images/icons/cart gray.png" alt="Корзина" class="cart-icon-img">
                                <span class="cart-count cart-count-hidden" id="cartCount">0</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Category Toggle -->
                <button class="header-menu-toggle" id="mobileCategoryToggle">
                    <span>Menu</span>
                </button>
            </div>
        </header>

        <!-- Menu Content -->
        <main class="menu-page">
            <div class="container">
                <!-- Page Title -->
                <div class="row">
                    <div class="column xl-12">
                        <h1 class="text-display-title page-title">Наше меню v2</h1>
                    </div>
                </div>

                <!-- Mobile Category Navigation -->
                <nav class="header-nav mobile-nav-hidden" id="mobileCategoryNav">
                    <!-- Categories List -->
                    <ul class="header-nav__links">
                        <?php if ($menu_loaded && !empty($categories)): ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <li <?php echo $index === 0 ? 'class="current"' : ''; ?>>
                                    <a href="#" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                                        <?php echo htmlspecialchars(translateCategoryName($category['category_name'] ?? $category['name'], getCurrentLanguage())); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Category Navigation -->
                <?php if ($menu_loaded && !empty($categories)): ?>
                <div class="row">
                    <div class="column xl-12">
                        <div class="menu-categories">
                            <?php foreach ($categories as $index => $category): ?>
                                <button class="category-btn <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                                    <?php echo htmlspecialchars(translateCategoryName($category['category_name'] ?? $category['name'], getCurrentLanguage())); ?>
                                </button>
                            <?php endforeach; ?>
                            
                            <!-- Minimal Sort Dropdown -->
                            <div class="sort-dropdown">
                                <div class="sort-dropdown__trigger">
                                    <svg class="sort-dropdown__icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/>
                                    </svg>
                                </div>
                                <div class="sort-dropdown__menu">
                                    <button class="sort-dropdown__item active" data-sort="popularity">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/>
                                        </svg>
                                        Популярные
                                    </button>
                                    <button class="sort-dropdown__item" data-sort="price">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z"/>
                                        </svg>
                                        По цене
                                    </button>
                                    <button class="sort-dropdown__item" data-sort="alphabet">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M14,17H7v-2h7V17z M17,13H7v-2h10V13z M17,9H7V7h10V9z M3,5V3h18v2H3z"/>
                                        </svg>
                                        А-Я
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menu Sections -->
                <?php if ($menu_loaded && !empty($categories)): ?>
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="menu-section <?php echo $index === 0 ? 'active' : 'menu-section-hidden'; ?>" data-category="<?php echo htmlspecialchars($category['category_id']); ?>">
                            
                            <?php 
                            $category_products = $products_by_category[$category['category_id']] ?? [];
                            if (!empty($category_products)): 
                            ?>
                                <div class="products-grid">
                                    <ul class="menu-list">
                                        <?php foreach ($category_products as $product): ?>
                                            <li class="menu-list__item" 
                                                data-product-name="<?php echo htmlspecialchars($product['product_name'] ?? 'Без названия'); ?>"
                                                data-price="<?php echo $product['price_normalized'] ?? $product['price'] ?? 0; ?>"
                                                data-sort-order="<?php echo $product['sort_order'] ?? 0; ?>"
                                                data-popularity="<?php echo $product['sales_count'] ?? 0; ?>"
                                                data-product-id="<?php echo $product['product_id'] ?? 0; ?>">
                                                <div class="menu-list__item-desc">
                                                    <h4><?php echo htmlspecialchars($product['product_name'] ?? 'Без названия'); ?></h4>
                                                    <?php if (!empty($product['description'])): ?>
                                                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="menu-list__item-actions">
                                                    <div class="menu-list__item-price">
                                                        <?php echo number_format($product['price_normalized'] ?? $product['price'] ?? 0, 0, ',', ' '); ?> ₫
                                                    </div>
                                                    <div class="add-to-cart-wrapper">
                                                        <button class="add-to-cart-btn" 
                                                                data-product='<?php echo json_encode([
                                                                    'id' => $product['product_id'] ?? 0,
                                                                    'name' => $product['product_name'] ?? 'Без названия',
                                                                    'price' => $product['price_normalized'] ?? $product['price'] ?? 0,
                                                                    'image' => $product['image_url'] ?? ''
                                                                ]); ?>'
                                                                title="Добавить в корзину">
                                                            <span class="add-text">+</span>
                                                        </button>
                                                    </div>
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
                    <div class="menu-section error-section">
                        <h2 class="error-title">Упс, что-то с меню не так</h2>
                        <p class="error-text">
                            К сожалению, меню временно недоступно. Попробуйте обновить страницу или зайти позже.
                        </p>
                        <button onclick="window.location.reload()" class="btn btn--primary">
                            Обновить страницу
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Back to Home -->
                <div class="row back-to-home">
                    <div class="column xl-12">
                        <a href="/" class="btn btn--primary">Вернуться на главную</a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include 'components/footer.php'; ?>
        
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="modal modal-hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ваш заказ</h2>
                <div class="order-type-options">
                    <label class="order-type-option">
                        <input type="radio" name="orderType" value="table" checked>
                        <span class="order-type-label">На столик</span>
                    </label>
                    <label class="order-type-option">
                        <input type="radio" name="orderType" value="takeaway">
                        <span class="order-type-label">С собой</span>
                    </label>
                    <label class="order-type-option">
                        <input type="radio" name="orderType" value="delivery">
                        <span class="order-type-label">Доставка</span>
                    </label>
                </div>
                <button class="modal-close" id="cartModalClose">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="cart-items-list" id="cartItemsList">
                    <!-- Cart items will be populated here -->
                </div>
                <div class="cart-total">
                    <div class="total-row">
                        <span>Итого:</span>
                        <span class="total-amount" id="cartTotalAmount">0 ₫</span>
                    </div>
                </div>

                <!-- Order Fields -->
                <div class="order-fields" id="orderFields">
                    <!-- Общие поля для всех типов заказов -->
                    <div class="form-row form-row-three">
                        <div class="form-group form-group-name">
                            <label for="customerName">Ваше имя</label>
                            <input type="text" id="customerName" name="customerName" placeholder="Введите ваше имя" required>
                        </div>
                        <div class="form-group form-group-phone">
                            <label for="customerPhone">Телефон</label>
                            <input type="tel" id="customerPhone" name="customerPhone" placeholder="+" required>
                        </div>
                        <div class="form-group form-group-table" id="tableFieldGroup">
                            <label for="tableNumber">Стол</label>
                            <select id="tableNumber" name="tableNumber" required>
                                <option value=""></option>
                                <!-- Table options will be populated here -->
                            </select>
                        </div>
                    </div>

                    <!-- Table Order Fields -->
                    <div class="order-field-group" id="tableOrderFields">
                        <div class="form-group">
                            <label for="tableComment">Комментарий</label>
                            <textarea id="tableComment" name="tableComment" rows="3" placeholder="Сюда можно написать все, что вы хотели бы, чтобы мы учли"></textarea>
                        </div>
                    </div>

                    <!-- Takeaway Order Fields -->
                    <div class="order-field-group" id="takeawayOrderFields" style="display: none;">
                        <div class="form-group">
                            <label for="takeawayComment">Комментарий</label>
                            <textarea id="takeawayComment" name="takeawayComment" rows="3" placeholder="Сюда можно написать все, что вы хотели бы, чтобы мы учли"></textarea>
                        </div>
                    </div>

                    <!-- Delivery Order Fields -->
                    <div class="order-field-group" id="deliveryOrderFields" style="display: none;">
                        <div class="form-group">
                            <label for="deliveryAddress">Адрес доставки (ссылка на Google карту)</label>
                            <input type="url" id="deliveryAddress" name="deliveryAddress" placeholder="https://maps.google.com/..." required>
                        </div>
                        <div class="form-group">
                            <label for="deliveryTime">Время доставки</label>
                            <input type="datetime-local" id="deliveryTime" name="deliveryTime" required>
                        </div>
                        <div class="form-group">
                            <label for="deliveryComment">Комментарий</label>
                            <textarea id="deliveryComment" name="deliveryComment" rows="3" placeholder="Сюда можно написать все, что вы хотели бы, чтобы мы учли"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button class="btn btn-secondary" id="cartModalCancel">Отмена</button>
                <button class="btn btn-primary" id="cartModalSubmit">Оформить заказ</button>
            </div>
            
            <div class="modal-footer">
                <div class="discount-info">
                    <span class="discount-text">-20% на первый заказ каждому новому гостю</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay overlay-hidden"></div>

    <!-- JavaScript -->
    <script src="js/main.js"></script>
    
    <script>
        // API Configuration
        window.API_TOKEN = '<?php echo $_ENV['API_AUTH_TOKEN'] ?? ''; ?>';
    </script>
    <script src="js/cart.js"></script>
    <script src="js/menu.js"></script>
    
    <style>
        /* Стили для оригинальной цены в cart-total (зачеркнутая) */
        .cart-total .original-price {
            color: #999;
            text-decoration: line-through;
        }
        
        /* Стили для скидки в cart-total */
        .cart-total .discount-row {
            color: #366b5b;
            font-weight: 500;
            font-size: 14px;
        }
        
        .cart-total .total-final {
            font-weight: bold;
            font-size: 1.1em;
        }

        /* Стили для заказов */
        .order-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            background: #fff;
        }

        .order-info h4 {
            margin: 0 0 12px 0;
            color: #333;
        }

        .order-info p {
            margin: 4px 0;
            color: #666;
        }

        .order-products {
            margin-top: 12px;
        }

        .order-products ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
        }

        .order-products li {
            margin: 4px 0;
            color: #666;
        }

        .status-paid {
            color: #28a745;
            font-weight: bold;
        }

        .status-unpaid {
            color: #dc3545;
            font-weight: bold;
        }

        .status-partial {
            color: #ffc107;
            font-weight: bold;
        }
    </style>

    <!-- Auth System JavaScript -->
    <script>
        // Auth System
        class AuthSystem {
            constructor() {
                this.isAuthenticated = false;
                this.userData = null;
                this.sessionToken = localStorage.getItem('auth_session_token');
                this.init();
            }

            init() {
                this.bindEvents();
                this.checkAuthStatus();
            }

            bindEvents() {
                // Auth icon click
                document.getElementById('authIcon').addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleAuthDropdown();
                });

                // Auth dropdown items
                document.getElementById('authLoginBtn').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openAuthModal();
                    this.closeAuthDropdown();
                });

                document.getElementById('authProfileBtn').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openProfileModal();
                    this.closeAuthDropdown();
                });

                document.getElementById('authOrdersBtn').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openOrdersModal();
                    this.closeAuthDropdown();
                });

                document.getElementById('authLogoutBtn').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.logout();
                    this.closeAuthDropdown();
                });

                // Telegram auth button
                document.getElementById('telegramAuthBtn').addEventListener('click', () => {
                    this.authenticateWithTelegram();
                });

                // Modal close buttons
                document.getElementById('authModalClose').addEventListener('click', () => {
                    this.closeAuthModal();
                });

                document.getElementById('profileModalClose').addEventListener('click', () => {
                    this.closeProfileModal();
                });

                document.getElementById('ordersModalClose').addEventListener('click', () => {
                    this.closeOrdersModal();
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.header-auth')) {
                        this.closeAuthDropdown();
                    }
                });

                // Close modals when clicking overlay
                document.getElementById('modalOverlay').addEventListener('click', () => {
                    this.closeAllModals();
                });
            }

            toggleAuthDropdown() {
                const dropdown = document.getElementById('authDropdown');
                dropdown.classList.toggle('auth-dropdown--active');
            }

            closeAuthDropdown() {
                const dropdown = document.getElementById('authDropdown');
                dropdown.classList.remove('auth-dropdown--active');
            }

            openAuthModal() {
                document.getElementById('authModal').classList.remove('modal-hidden');
                document.getElementById('modalOverlay').classList.remove('overlay-hidden');
                document.body.style.overflow = 'hidden';
            }

            closeAuthModal() {
                document.getElementById('authModal').classList.add('modal-hidden');
                document.getElementById('modalOverlay').classList.add('overlay-hidden');
                document.body.style.overflow = '';
            }

            openProfileModal() {
                this.loadProfileData();
                document.getElementById('profileModal').classList.remove('modal-hidden');
                document.getElementById('modalOverlay').classList.remove('overlay-hidden');
                document.body.style.overflow = 'hidden';
            }

            closeProfileModal() {
                document.getElementById('profileModal').classList.add('modal-hidden');
                document.getElementById('modalOverlay').classList.add('overlay-hidden');
                document.body.style.overflow = '';
            }

            openOrdersModal() {
                this.loadOrdersData();
                document.getElementById('ordersModal').classList.remove('modal-hidden');
                document.getElementById('modalOverlay').classList.remove('overlay-hidden');
                document.body.style.overflow = 'hidden';
            }

            closeOrdersModal() {
                document.getElementById('ordersModal').classList.add('modal-hidden');
                document.getElementById('modalOverlay').classList.add('overlay-hidden');
                document.body.style.overflow = '';
            }

            closeAllModals() {
                this.closeAuthModal();
                this.closeProfileModal();
                this.closeOrdersModal();
            }

            authenticateWithTelegram() {
                // Generate unique session token
                const sessionToken = this.generateSessionToken();
                this.sessionToken = sessionToken;
                localStorage.setItem('auth_session_token', sessionToken);
                
                // Create Telegram auth URL
                const telegramUrl = `https://t.me/RestPublic_bot?start=auth_${sessionToken}`;
                
                // Open Telegram
                window.open(telegramUrl, '_blank');
                
                // Close auth modal
                this.closeAuthModal();
                
                // Show loading message
                this.showNotification('Переходим в Telegram для авторизации...', 'info');
            }

            generateSessionToken() {
                return Date.now().toString(36) + Math.random().toString(36).substr(2);
            }

            checkAuthStatus() {
                if (!this.sessionToken) {
                    this.isAuthenticated = false;
                    this.updateAuthUI();
                    return;
                }

                // Check if user is authenticated
                fetch('/api/auth/status', {
                    headers: {
                        'X-Session-Token': this.sessionToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.authenticated) {
                            this.isAuthenticated = true;
                            this.userData = data.user;
                            this.updateAuthUI();
                        } else {
                            this.isAuthenticated = false;
                            this.userData = null;
                            this.sessionToken = null;
                            localStorage.removeItem('auth_session_token');
                            this.updateAuthUI();
                        }
                    })
                    .catch(error => {
                        console.error('Auth status check failed:', error);
                        this.isAuthenticated = false;
                        this.updateAuthUI();
                    });
            }

            updateAuthUI() {
                const guestMenu = document.getElementById('authGuestMenu');
                const userMenu = document.getElementById('authUserMenu');
                const authIcon = document.querySelector('.auth-icon-img');

                if (this.isAuthenticated) {
                    guestMenu.style.display = 'none';
                    userMenu.style.display = 'block';
                    authIcon.src = 'images/icons/auth-green.png';
                } else {
                    guestMenu.style.display = 'block';
                    userMenu.style.display = 'none';
                    authIcon.src = 'images/icons/auth-gray.png';
                }
            }

            loadProfileData() {
                if (!this.isAuthenticated || !this.sessionToken) return;

                fetch('/api/user/profile', {
                    headers: {
                        'X-Session-Token': this.sessionToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.userData = data.user; // Сохраняем данные для корзины
                            this.displayProfileData(data.user);
                            this.fillCartFields(data.user);
                        } else {
                            this.showNotification('Ошибка загрузки профиля', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Profile load failed:', error);
                        this.showNotification('Ошибка загрузки профиля', 'error');
                    });
            }

            async loadUserData() {
                if (!this.isAuthenticated || !this.sessionToken) return null;

                try {
                    const response = await fetch('/api/user/profile', {
                        headers: {
                            'X-Session-Token': this.sessionToken
                        }
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        this.userData = data.user; // Сохраняем данные для корзины
                        return data.user;
                    } else {
                        console.error('Failed to load user data:', data.message);
                        return null;
                    }
                } catch (error) {
                    console.error('User data load failed:', error);
                    return null;
                }
            }

            fillCartFields(user) {
                // Заполняем поля корзины данными из профиля
                const nameField = document.getElementById('customerName');
                const phoneField = document.getElementById('customerPhone');
                
                if (nameField && user.firstname && user.lastname) {
                    nameField.value = `${user.firstname} ${user.lastname}`.trim();
                }
                
                if (phoneField && user.phone) {
                    phoneField.value = user.phone;
                }
            }

            displayProfileData(user) {
                const profileInfo = document.getElementById('profileInfo');
                profileInfo.innerHTML = `
                    <div class="profile-field">
                        <label>Имя:</label>
                        <span>${user.firstname || 'Не указано'}</span>
                    </div>
                    <div class="profile-field">
                        <label>Фамилия:</label>
                        <span>${user.lastname || 'Не указано'}</span>
                    </div>
                    <div class="profile-field">
                        <label>Телефон:</label>
                        <span>${user.phone || 'Не указано'}</span>
                    </div>
                    <div class="profile-field">
                        <label>Email:</label>
                        <span>${user.email || 'Не указано'}</span>
                    </div>
                    <div class="profile-field">
                        <label>Скидка:</label>
                        <span>${user.max_discount || Math.max(user.discount_per || 0, user.client_groups_discount || 0)}%</span>
                    </div>
                    <div class="profile-field">
                        <label>Бонусы:</label>
                        <span>${user.bonus || 0} ₫</span>
                    </div>
                    <div class="profile-field">
                        <label>Дата регистрации:</label>
                        <span>${user.date_activale ? new Date(user.date_activale).toLocaleDateString('ru-RU') : 'Не указано'}</span>
                    </div>
                    <div class="profile-field">
                        <label>Общая сумма покупок:</label>
                        <span>${user.total_payed_sum ? (user.total_payed_sum / 100).toFixed(0) + ' ₫' : '0 ₫'}</span>
                    </div>
                    <div class="profile-footer">
                        <p>Нашли ошибку? <a href="https://t.me/zapleosoft" target="_blank">Свяжитесь с нами</a></p>
                    </div>
                `;
            }

            loadOrdersData() {
                if (!this.isAuthenticated || !this.sessionToken) return;

                fetch('/api/user/orders', {
                    headers: {
                        'X-Session-Token': this.sessionToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.displayOrdersData(data.orders);
                        } else {
                            this.showNotification('Ошибка загрузки заказов', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Orders load failed:', error);
                        this.showNotification('Ошибка загрузки заказов', 'error');
                    });
            }

            displayOrdersData(orders) {
                const ordersList = document.getElementById('ordersList');
                
                if (!orders || orders.length === 0) {
                    ordersList.innerHTML = '<p>У вас нет открытых заказов</p>';
                    return;
                }

                ordersList.innerHTML = orders.map(order => {
                    // Форматируем дату создания заказа (для открытых заказов date_close = 0)
                    const orderDate = order.date_close_date ? 
                        new Date(order.date_close_date).toLocaleDateString('ru-RU', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : 
                        'Заказ открыт';
                    
                    // Форматируем сумму (уже в донгах для dash.getTransactions)
                    const totalSum = parseFloat(order.sum).toFixed(0);
                    const paidSum = parseFloat(order.payed_sum).toFixed(0);
                    
                    // Для открытых заказов статус всегда "Не оплачен"
                    const status = 'Не оплачен';
                    const statusClass = 'status-unpaid';
                    
                    // Получаем информацию о столе
                    const tableInfo = order.table_name ? `Стол: ${order.table_name}` : 
                                    order.table_id ? `Стол: ${order.table_id}` : 'Доставка';
                    
                    // Получаем комментарий к заказу
                    const comment = order.transaction_comment ? 
                        `<p><strong>Комментарий:</strong> ${order.transaction_comment}</p>` : '';
                    
                    return `
                        <div class="order-item">
                            <div class="order-info">
                                <h4>Заказ #${order.transaction_id}</h4>
                                <p><strong>Дата:</strong> ${orderDate}</p>
                                <p><strong>Сумма:</strong> ${totalSum} ₫</p>
                                <p><strong>Оплачено:</strong> ${paidSum} ₫</p>
                                <p><strong>Статус:</strong> <span class="${statusClass}">${status}</span></p>
                                <p><strong>${tableInfo}</strong></p>
                                ${order.discount > 0 ? `<p><strong>Скидка:</strong> ${order.discount}%</p>` : ''}
                                ${comment}
                            </div>
                            <div class="order-actions">
                                <button class="btn btn--primary" onclick="authSystem.repeatOrder(${order.transaction_id})">
                                    Повторить заказ
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            logout() {
                fetch('/api/auth/logout', { 
                    method: 'POST',
                    headers: {
                        'X-Session-Token': this.sessionToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.isAuthenticated = false;
                            this.userData = null;
                            this.sessionToken = null;
                            localStorage.removeItem('auth_session_token');
                            this.updateAuthUI();
                            this.showNotification('Вы вышли из системы', 'success');
                        } else {
                            this.showNotification('Ошибка выхода', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Logout failed:', error);
                        this.showNotification('Ошибка выхода', 'error');
                    });
            }

            showNotification(message, type = 'info') {
                // Simple notification system
                const notification = document.createElement('div');
                notification.className = `notification notification--${type}`;
                notification.textContent = message;
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    border-radius: 4px;
                    color: white;
                    z-index: 10000;
                    font-size: 14px;
                    max-width: 300px;
                `;

                // Set background color based on type
                switch (type) {
                    case 'success':
                        notification.style.backgroundColor = '#4CAF50';
                        break;
                    case 'error':
                        notification.style.backgroundColor = '#f44336';
                        break;
                    case 'info':
                    default:
                        notification.style.backgroundColor = '#2196F3';
                        break;
                }

                document.body.appendChild(notification);

                // Remove after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 3000);
            }
        }

        // Initialize auth system when DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                window.authSystem = new AuthSystem();
                // Check for auth success in URL
                checkAuthSuccessInURL();
            });
        } else {
            window.authSystem = new AuthSystem();
            // Check for auth success in URL
            checkAuthSuccessInURL();
        }

        // Function to check for auth success in URL and move session to localStorage
        function checkAuthSuccessInURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const authSuccess = urlParams.get('auth');
            const sessionToken = urlParams.get('session');
            
            if (authSuccess === 'success' && sessionToken) {
                console.log('🔐 Auth success detected in URL, moving session to localStorage');
                
                // Save session token to localStorage
                localStorage.setItem('auth_session_token', sessionToken);
                
                // Clean URL by removing auth parameters
                const newUrl = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
                
                // Show success notification
                if (window.authSystem) {
                    window.authSystem.showNotification('Авторизация успешна!', 'success');
                    // Check auth status to update UI
                    window.authSystem.checkAuthStatus();
                }
                
                console.log('✅ Session moved to localStorage and URL cleaned');
            }
        }
    </script>

    <!-- Auth Modal -->
    <div id="authModal" class="modal modal-hidden">
        <div class="modal-content auth-modal-content">
            <div class="modal-header">
                <h2>Авторизация</h2>
                <button class="modal-close" id="authModalClose">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="auth-providers">
                    <button class="auth-provider-btn" id="telegramAuthBtn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"/>
                        </svg>
                        <span>Telegram</span>
                    </button>
                    
                    <button class="auth-provider-btn auth-provider-btn--disabled" disabled>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span>Google</span>
                    </button>
                    
                    <button class="auth-provider-btn auth-provider-btn--disabled" disabled>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20,3H4C3.447,3,3,3.448,3,4v16c0,0.552,0.447,1,1,1h8.615v-6.96h-2.338v-2.725h2.338v-2c0-2.325,1.42-3.592,3.5-3.592 c0.699-0.002,1.399,0.034,2.095,0.107v2.42h-1.435c-1.128,0-1.348,0.538-1.348,1.325v1.735h2.697l-0.35,2.725h-2.348V21H20 c0.553,0,1-0.448,1-1V4C21,3.448,20.553,3,20,3z"/>
                        </svg>
                        <span>Facebook</span>
                    </button>
                    
                    <button class="auth-provider-btn auth-provider-btn--disabled" disabled>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71,19.5C17.88,20.74 17,21.95 15.66,21.97C14.32,22 13.89,21.18 12.37,21.18C10.84,21.18 10.37,21.95 9.1,22C7.79,22.05 6.8,20.68 5.96,19.47C4.25,17 2.94,12.45 4.7,9.39C5.57,7.87 7.13,6.91 8.82,6.88C10.1,6.86 11.32,7.75 12.11,7.75C12.89,7.75 14.37,6.68 15.92,6.84C16.57,6.87 18.39,7.1 19.56,8.82C19.47,8.88 17.39,10.1 17.41,12.63C17.44,15.65 20.06,16.66 20.09,16.67C20.06,16.74 19.67,18.11 18.71,19.5M13,3.5C13.73,2.67 14.94,2.04 15.94,2C16.07,3.17 15.6,4.35 14.9,5.19C14.21,6.04 13.07,6.7 11.95,6.61C11.8,5.46 12.36,4.26 13,3.5Z"/>
                        </svg>
                        <span>Apple</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal modal-hidden">
        <div class="modal-content profile-modal-content">
            <div class="modal-header">
                <h2>Профиль</h2>
                <button class="modal-close" id="profileModalClose">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="profile-info" id="profileInfo">
                    <!-- Profile data will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Modal -->
    <div id="ordersModal" class="modal modal-hidden">
        <div class="modal-content orders-modal-content">
            <div class="modal-header">
                <h2>Мои заказы</h2>
                <button class="modal-close" id="ordersModalClose">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="orders-list" id="ordersList">
                    <!-- Orders will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay overlay-hidden"></div>

</body>
</html>
