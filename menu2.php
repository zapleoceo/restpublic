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
                        <span class="order-type-label">Заказ на столик</span>
                    </label>
                    <label class="order-type-option">
                        <input type="radio" name="orderType" value="takeaway">
                        <span class="order-type-label">Заказать с собой</span>
                    </label>
                    <label class="order-type-option">
                        <input type="radio" name="orderType" value="delivery">
                        <span class="order-type-label">Заказать доставку</span>
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
                                <option value="">Выберите стол</option>
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
                    <span class="discount-text">-20% на первый заказ при регистрации нового гостя</span>
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

</body>
</html>
