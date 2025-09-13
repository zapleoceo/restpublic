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
    $authToken = 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
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

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">

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
        
        .s-header__content {
            position: relative;
        }
        
        .s-header__block {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            width: 100%;
        }
        
        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 100;
        }
        
        .header-auth,
        .header-cart {
            position: relative;
        }
        
        .auth-icon,
        .cart-icon {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
            padding: 0;
            box-sizing: border-box;
        }
        
        .auth-icon .auth-icon-img,
        .cart-icon .cart-icon-img {
            width: 32px;
            height: 32px;
            transition: all 0.3s ease;
            object-fit: contain;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Icons are now controlled by JavaScript src attribute */
        
        .auth-icon:hover,
        .cart-icon:hover {
            transform: scale(1.1);
        }
        
        .auth-icon:hover .auth-icon-img,
        .cart-icon:hover .cart-icon-img {
            filter: drop-shadow(0 4px 8px rgba(54, 107, 91, 0.4));
            transform: translate(-50%, -50%) scale(1.05);
        }
        
        /* Icons are now controlled by JavaScript src attribute */
        
        /* Active states for better UX */
        .auth-icon:active,
        .cart-icon:active,
        .add-to-cart-btn:active {
            transform: scale(0.95);
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: #fff;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(231, 76, 60, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        /* Mobile menu toggle - positioned outside s-header__block */
        .header-menu-toggle {
            --toggle-block-width: 44px;
            --toggle-line-width : 28px;
            --toggle-line-height: 1px;

            display             : none !important;
            width               : var(--toggle-block-width) !important;
            height              : var(--toggle-block-width) !important;
            position            : absolute !important;
            top                 : 50% !important;
            right               : calc(var(--gutter, 1rem) * 2) !important;
            transform           : translateY(-50%) !important;
            background          : transparent !important;
            border              : none !important;
            cursor              : pointer !important;
            z-index             : 1001 !important;
            padding             : 0 !important;
            margin              : 0 !important;
        }

        .header-menu-toggle span {
            display         : block;
            background-color: var(--color-white, #ffffff);
            width           : var(--toggle-line-width);
            height          : var(--toggle-line-height);
            margin-top      : -1px;
            font            : 0/0 a;
            text-shadow     : none;
            color           : transparent;
            transition      : all 0.3s ease;
            position        : absolute;
            right           : calc((var(--toggle-block-width) - var(--toggle-line-width)) / 2);
            top             : 50%;
            bottom          : auto;
            left            : auto;
            border          : none;
            outline         : none;
        }

        .header-menu-toggle span::before,
        .header-menu-toggle span::after {
            content         : "";
            width           : 100%;
            height          : 100%;
            background-color: var(--color-white, #ffffff);
            transition      : all 0.3s ease;
            position        : absolute;
            left            : 0;
            border          : none;
            outline         : none;
        }

        .header-menu-toggle span::before {
            top: -8px;
        }

        .header-menu-toggle span::after {
            bottom: -8px;
        }

        /* is clicked */
        .header-menu-toggle.is-clicked span {
            background-color: transparent;
            transition      : all 0.3s ease;
        }

        .header-menu-toggle.is-clicked span::before,
        .header-menu-toggle.is-clicked span::after {
            background-color: var(--color-white, #ffffff);
            transition      : all 0.3s ease;
        }

        .header-menu-toggle.is-clicked span::before {
            top      : 0;
            transform: rotate(45deg);
        }

        .header-menu-toggle.is-clicked span::after {
            bottom   : 0;
            transform: rotate(-45deg);
        }
        
        /* Remove any focus/hover backgrounds */
        .header-menu-toggle:focus,
        .header-menu-toggle:hover,
        .header-menu-toggle:active {
            background: transparent !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }
        
        /* Mobile header-nav styles */
        .header-nav {
            display: none;
            width: 100%;
            background-color: var(--color-bg, #1a1a1a);
            box-shadow: var(--shadow-medium, 0 4px 6px rgba(0,0,0,0.1));
            border-bottom: 1px solid var(--color-bg-neutral-dark, #333);
            padding-top: 80px;
            padding-right: calc(var(--gutter, 1rem) * 2 + 0.2rem);
            padding-left: calc(var(--gutter, 1rem) * 2 + 0.2rem);
            padding-bottom: var(--vspace-1_5, 2rem);
            margin: 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 999;
            transform: scaleY(0);
            transform-origin: center top;
            opacity: 0;
            transition: all 0.3s ease-in-out;
        }
        
        
        .header-nav__links {
            display: block;
            padding-left: 0;
            margin: 0 0 var(--vspace-1_5, 2rem) 0;
            transform: translateY(-2rem);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }
        
        .header-nav__links a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--type-body);
            font-weight: 500;
            color: var(--color-text-light);
            padding: var(--vspace-1) var(--vspace-1);
            margin-bottom: 0.5rem;
            border-radius: var(--border-radius);
            background-color: var(--color-bg-neutral-dark);
            font-size: var(--text-base);
            transition-property: color, background-color;
            transition-duration: 0.3s;
            text-decoration: none;
            width: 100%;
        }
        
        .header-nav__links a:focus,
        .header-nav__links a:hover {
            color: var(--color-white);
            background-color: var(--color-bg-primary);
        }
        
        .header-nav__links .current a {
            color: var(--color-white);
            background-color: #366b5b;
        }
        
        /* Menu open animations (like header-nav) */
        .menu-is-open .header-nav {
            transform: scaleY(1);
            transition: transform 0.3s var(--ease-quick-out);
            transition-delay: 0s;
        }
        
        .menu-is-open .header-nav__links {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
            transition: all 0.6s var(--ease-quick-out);
            transition-delay: 0.3s;
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
            align-items: center;
            padding: var(--vspace-1);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            transition: all 0.6s var(--ease-smooth-in-out);
            cursor: pointer;
            min-height: 80px;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .menu-list__item:nth-child(odd) {
            background-color: var(--color-bg-neutral-dark);
        }
        
        .menu-list__item:hover {
            background-color: var(--color-bg-primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .menu-list__item:hover h4 {
            color: var(--color-white);
        }
        
        .menu-list__item:hover p {
            color: var(--color-white);
        }
        
        .menu-list__item:hover .menu-list__item-price {
            color: var(--color-white);
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
            display: flex;
            align-items: center;
            height: 32px;
        }
        
        .menu-list__item-price span {
            font-size: 0.8em;
            position: relative;
            bottom: 0.2em;
            left: -1px;
        }
        
        .menu-list__item-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 1rem;
            height: 100%;
            min-height: 60px;
        }
        
        .add-to-cart-btn {
            border: none;
            background: transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
            position: relative;
            box-sizing: border-box;
            font-size: 18px;
            font-weight: bold;
            color: #366b5b;
            padding: 8px 12px;
            border-radius: 6px;
            min-width: 40px;
            height: 32px;
        }
        
        .add-to-cart-btn:hover {
            color: #fff;
            background: #366b5b;
            transform: scale(1.05);
        }
        
        .add-to-cart-btn::before {
            content: '+';
            transition: all 0.3s ease;
        }
        
        .add-to-cart-btn:hover::before {
            content: 'в заказ';
            font-size: 14px;
        }
        
        
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }
        
        .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 10001;
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c2c2c;
            font-size: 1.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Order Type Selection */
        .order-type-selection {
            margin-bottom: 24px;
        }
        
        .order-type-selection h3 {
            margin: 0 0 16px 0;
            color: #2c2c2c;
            font-size: 1.2rem;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .radio-label input[type="radio"] {
            display: none;
        }
        
        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .radio-label input[type="radio"]:checked + .radio-custom {
            border-color: #366b5b;
        }
        
        .radio-label input[type="radio"]:checked + .radio-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: #366b5b;
            border-radius: 50%;
        }
        
        /* Cart Items */
        .cart-items-section {
            margin-bottom: 24px;
        }
        
        .cart-items-section h3 {
            margin: 0 0 16px 0;
            color: #2c2c2c;
            font-size: 1.2rem;
        }
        
        .cart-items-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-name {
            flex: 1;
            font-weight: 500;
            color: #2c2c2c;
        }
        
        .cart-item-price {
            color: #366b5b;
            font-weight: 600;
            margin-right: 12px;
        }
        
        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cart-item-quantity button {
            width: 24px;
            height: 24px;
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .cart-item-quantity button:hover {
            background: #f5f5f5;
        }
        
        .cart-item-quantity span {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
        }
        
        .cart-total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #366b5b;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .total-amount {
            color: #366b5b;
        }
        
        /* Form Groups */
        .order-fields h3 {
            margin: 0 0 16px 0;
            color: #2c2c2c;
            font-size: 1.2rem;
        }
        
        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group--half {
            flex: 1;
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c2c2c;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #366b5b;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 4px;
            color: #666;
            font-size: 12px;
        }
        
        /* Phone check status */
        .phone-check-status {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .phone-check-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .phone-check-status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .phone-check-status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        /* Checkbox Styles */
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 500;
            width: auto;
        }
        
        .checkbox-label input[type="checkbox"] {
            display: none;
        }
        
        .checkbox-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
            background: #366b5b;
            border-color: #366b5b;
        }
        
        .checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #366b5b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2d5a4d;
        }
        
        .btn-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .modal {
                padding: 10px;
            }
            
            .modal-content {
                max-height: 95vh;
            }
            
            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 16px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 12px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 12px;
            }
            
            .form-group--half {
                margin-bottom: 20px;
            }
            
            .modal-footer {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
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
        
        @media (max-width: 900px) {
            .header-menu-toggle {
                display: block !important;
            }
        }

        @media (max-width: 768px) {
            .menu-page {
                padding-top: 1rem;
            }
            
            .menu-categories {
                display: none; /* Hide desktop categories on mobile */
            }
            
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .menu-section h2 {
                font-size: 2rem;
            }
            
            .sort-controls {
                margin-top: 1rem;
                padding: 0.3rem;
                gap: 0.3rem;
            }
            
            .sort-btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
            
            .sort-btn svg {
                width: 12px;
                height: 12px;
            }
            
            /* Mobile header actions */
            .header-actions {
                gap: 0.5rem;
                right: 10px;
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .auth-icon,
            .cart-icon {
                width: 36px;
                height: 36px;
            }
            
            .auth-icon .auth-icon-img,
            .cart-icon .cart-icon-img {
                width: 28px;
                height: 28px;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            
            .auth-icon:hover .auth-icon-img,
            .cart-icon:hover .cart-icon-img {
                transform: translate(-50%, -50%) scale(1.05);
            }
            
            .cart-count {
                width: 18px;
                height: 18px;
                font-size: 0.7rem;
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
                                <span class="cart-count" id="cartCount" style="display: none;">0</span>
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
                        <h1 class="text-display-title" style="text-align: center; margin-bottom: 3rem;">Наше меню v2</h1>
                    </div>
                </div>

                <!-- Mobile Category Navigation -->
                <nav class="header-nav" id="mobileCategoryNav">
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
                        <div class="menu-section <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category['category_id']); ?>" style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                            
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
                                                    <button class="add-to-cart-btn" 
                                                            data-product='<?php echo json_encode([
                                                                'id' => $product['product_id'] ?? 0,
                                                                'name' => $product['product_name'] ?? 'Без названия',
                                                                'price' => $product['price_normalized'] ?? $product['price'] ?? 0,
                                                                'image' => $product['image_url'] ?? ''
                                                            ]); ?>'
                                                            title="Добавить в корзину">
                                                    </button>
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
        
    </div>

    <!-- Cart Modal -->
    <div id="cartModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Оформление заказа</h2>
                <button class="modal-close" id="cartModalClose">&times;</button>
            </div>
            
            <div class="modal-body">
                <!-- Order Type Selection -->
                <div class="order-type-selection">
                    <h3>Тип заказа</h3>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="orderType" value="1" checked>
                            <span class="radio-custom"></span>
                            В заведении
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="orderType" value="3">
                            <span class="radio-custom"></span>
                            Доставка
                        </label>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="cart-items-section">
                    <h3>Ваш заказ</h3>
                    <div class="cart-items-list" id="cartItemsList">
                        <!-- Cart items will be populated here -->
                    </div>
                    <div class="cart-total">
                        <div class="total-row">
                            <span>Итого:</span>
                            <span class="total-amount" id="cartTotalAmount">0 ₫</span>
                        </div>
                    </div>
                </div>

                <!-- In Restaurant Fields -->
                <div id="inRestaurantFields" class="order-fields">
                    <h3>Детали заказа</h3>
                    <div class="form-row">
                        <div class="form-group form-group--half">
                            <label for="tableSelect">Номер стола:</label>
                            <select name="table_id" id="tableSelect" required>
                                <option value="">Выберите стол</option>
                                <!-- Tables will be loaded via API -->
                            </select>
                        </div>
                        <div class="form-group form-group--half">
                            <label for="guestsCount">Количество гостей:</label>
                            <input type="number" name="guests_count" id="guestsCount" 
                                   min="1" max="20" value="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="takeaway" id="takeawayCheckbox">
                            <span class="checkbox-custom"></span>
                            Еда с собой
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="comment">Комментарий к заказу:</label>
                        <textarea name="comment" id="comment" placeholder="Особые пожелания..."></textarea>
                    </div>
                </div>

                <!-- Delivery Fields -->
                <div id="deliveryFields" class="order-fields" style="display: none;">
                    <h3>Детали доставки</h3>
                    <div class="form-group">
                        <label for="deliveryAddress">Адрес доставки (ссылка на Google Maps):</label>
                        <input type="url" name="delivery_address" id="deliveryAddress" 
                               placeholder="https://maps.google.com/..." required>
                        <small>Вставьте ссылку на ваше местоположение в Google Maps</small>
                    </div>
                    <div class="form-group">
                        <label for="deliveryComment">Комментарий к заказу:</label>
                        <textarea name="comment" id="deliveryComment" placeholder="Особые пожелания..."></textarea>
                    </div>
                </div>

                <!-- Guest Information (shown when not authenticated) -->
                <div id="guestInfoFields" class="order-fields" style="display: none;">
                    <h3>Информация о заказчике</h3>
                    <div class="form-row">
                        <div class="form-group form-group--half">
                            <label for="guestName">Имя:</label>
                            <input type="text" name="guest_name" id="guestName" required>
                        </div>
                        <div class="form-group form-group--half">
                            <label for="guestPhone">Телефон:</label>
                            <input type="tel" name="guest_phone" id="guestPhone" 
                                   placeholder="+84 123 456 789" required>
                            <div class="phone-check-status" id="phoneCheckStatus" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                <!-- Notification Checkbox -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="readyNotificationCheckbox">
                        <span class="checkbox-custom"></span>
                        Уведомить, когда заказ будет приготовлен
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cartModalCancel">Отмена</button>
                <button class="btn btn-primary" id="cartModalSubmit">Оформить заказ</button>
            </div>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay" style="display: none;"></div>

    <!-- JavaScript -->
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>
    
    <style>
        /* Анимации появления блюд */
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
        
        /* Sort Controls - Minimal Design */
        .sort-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding: 0.5rem;
            background: var(--color-bg-neutral-dark);
            border-radius: 25px;
            flex-wrap: wrap;
        }
        
        .sort-btn {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.4rem 0.8rem;
            background: transparent;
            border: none;
            border-radius: 20px;
            color: var(--color-text-light);
            font-size: var(--text-sm);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: auto;
        }
        
        .sort-btn:hover {
            background: var(--color-bg-primary);
            color: var(--color-white);
        }
        
        .sort-btn.active {
            background: var(--color-bg-primary);
            color: var(--color-white);
        }
        
        .sort-btn svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
        
        /* Sort Dropdown Styles - Minimal */
        .sort-dropdown {
            position: relative;
            display: inline-block;
            margin-left: 1rem;
        }
        
        .sort-dropdown__trigger {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: transparent;
            border: 2px solid transparent;
            border-radius: 50%;
            color: var(--color-text-dark);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sort-dropdown__trigger:hover {
            background: #1c1e1d;
            color: var(--color-white);
            border-color: #1c1e1d;
        }
        
        .sort-dropdown__icon {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }
        
        .sort-dropdown__menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--color-bg-neutral-dark);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            min-width: 180px;
            margin-top: 0.5rem;
        }
        
        .sort-dropdown:hover .sort-dropdown__menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .sort-dropdown__item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            background: none;
            border: none;
            color: var(--color-text);
            font-size: var(--text-sm);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }
        
        .sort-dropdown__item:first-child {
            border-radius: 8px 8px 0 0;
        }
        
        .sort-dropdown__item:last-child {
            border-radius: 0 0 8px 8px;
        }
        
        .sort-dropdown__item:hover {
            background: var(--color-bg-primary);
            color: var(--color-white);
        }
        
        .sort-dropdown__item.active {
            background: var(--color-bg-primary);
            color: var(--color-white);
        }
        
        .sort-dropdown__item svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sort-dropdown {
                margin-left: 0.5rem;
                margin-top: 0;
            }
            
            .sort-dropdown__trigger {
                width: 36px;
                height: 36px;
            }
            
            .sort-dropdown__icon {
                width: 14px;
                height: 14px;
            }
            
            .sort-dropdown__menu {
                right: 0;
                left: auto;
                min-width: 160px;
            }
        }
    </style>
    
    <script>
        // Cart functionality
        class Cart {
            constructor() {
                this.items = JSON.parse(localStorage.getItem('cart') || '[]');
                if (!Array.isArray(this.items)) {
                    this.items = [];
                }
                this.init();
            }

            init() {
                this.bindEvents();
                this.updateCartDisplay();
            }

            bindEvents() {
                // Cart icon click
                document.getElementById('cartIcon')?.addEventListener('click', () => {
                    this.toggleCart();
                });

                // Add to cart buttons
                document.addEventListener('click', (e) => {
                    if (e.target.closest('.add-to-cart-btn')) {
                        const btn = e.target.closest('.add-to-cart-btn');
                        const productData = JSON.parse(btn.dataset.product);
                        this.addItem(productData);
                        this.showToast(`${productData.name} добавлен в корзину!`, 'success');
                    }
                });

                // Auth icon click
                document.getElementById('authIcon')?.addEventListener('click', () => {
                    this.showAuthModal();
                });
            }

            addItem(product) {
                const existingItem = this.items.find(item => item.id === product.id);
                
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    this.items.push({
                        id: product.id,
                        name: product.name,
                        price: product.price,
                        quantity: 1,
                        image: product.image
                    });
                }
                
                this.saveCart();
                this.updateCartDisplay();
            }

            removeItem(productId) {
                this.items = this.items.filter(item => item.id !== productId);
                this.saveCart();
                this.updateCartDisplay();
            }

            updateQuantity(productId, quantity) {
                const item = this.items.find(item => item.id === productId);
                if (item) {
                    if (quantity <= 0) {
                        this.removeItem(productId);
                    } else {
                        item.quantity = quantity;
                        this.saveCart();
                        this.updateCartDisplay();
                    }
                }
            }

            clearCart() {
                this.items = [];
                this.saveCart();
                this.updateCartDisplay();
            }

            getTotal() {
                return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
            }

            saveCart() {
                localStorage.setItem('cart', JSON.stringify(this.items));
            }

            updateCartDisplay() {
                const cartCount = document.getElementById('cartCount');
                const cartIcon = document.getElementById('cartIcon');
                const cartIconImg = document.querySelector('.cart-icon-img');

                const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
                
                console.log('updateCartDisplay called, totalItems:', totalItems);
                console.log('cartIconImg found:', cartIconImg);
                
                if (cartCount) {
                    cartCount.textContent = totalItems;
                    cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
                }

                if (cartIcon) {
                    if (totalItems > 0) {
                        cartIcon.classList.add('has-items');
                        if (cartIconImg) {
                            cartIconImg.src = 'images/icons/cart green.png';
                            console.log('Changed to green icon');
                        } else {
                            console.log('cartIconImg not found!');
                        }
                    } else {
                        cartIcon.classList.remove('has-items');
                        if (cartIconImg) {
                            cartIconImg.src = 'images/icons/cart gray.png';
                            console.log('Changed to gray icon');
                        } else {
                            console.log('cartIconImg not found!');
                        }
                    }
                }
            }

            toggleCart() {
                if (this.items.length === 0) {
                    this.showToast('Корзина пуста', 'info');
                    return;
                }
                this.showCartModal();
            }

            showCartModal() {
                this.populateCartModal();
                this.showModal();
                this.showGuestFields();
            }

            showGuestFields() {
                // Показываем поля гостя (в реальном приложении здесь была бы проверка авторизации)
                const guestFields = document.getElementById('guestInfoFields');
                if (guestFields) {
                    guestFields.style.display = 'block';
                }
            }

            populateCartModal() {
                const cartItemsList = document.getElementById('cartItemsList');
                const cartTotalAmount = document.getElementById('cartTotalAmount');
                
                if (this.items.length === 0) {
                    cartItemsList.innerHTML = '<p style="text-align: center; color: #666;">Корзина пуста</p>';
                    cartTotalAmount.textContent = '0 ₫';
                    return;
                }

                cartItemsList.innerHTML = this.items.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${item.price.toFixed(0)} ₫</div>
                        <div class="cart-item-quantity">
                            <button onclick="cart.updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="cart.updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                `).join('');

                cartTotalAmount.textContent = `${this.getTotal().toFixed(0)} ₫`;
            }

            showModal() {
                const modal = document.getElementById('cartModal');
                const overlay = document.getElementById('modalOverlay');
                
                modal.style.display = 'flex';
                overlay.style.display = 'block';
                
                // Bind modal events
                this.bindModalEvents();
            }

            hideModal() {
                const modal = document.getElementById('cartModal');
                const overlay = document.getElementById('modalOverlay');
                
                modal.style.display = 'none';
                overlay.style.display = 'none';
            }

            bindModalEvents() {
                // Close modal events
                document.getElementById('cartModalClose')?.addEventListener('click', () => {
                    this.hideModal();
                });
                
                document.getElementById('cartModalCancel')?.addEventListener('click', () => {
                    this.hideModal();
                });
                
                document.getElementById('modalOverlay')?.addEventListener('click', () => {
                    this.hideModal();
                });

                // Order type change
                document.querySelectorAll('input[name="orderType"]').forEach(radio => {
                    radio.addEventListener('change', (e) => {
                        this.toggleOrderFields(e.target.value);
                    });
                });

                // Phone number check on blur
                document.getElementById('guestPhone')?.addEventListener('blur', (e) => {
                    this.checkPhoneNumber(e.target.value);
                });

                // Submit order
                document.getElementById('cartModalSubmit')?.addEventListener('click', () => {
                    this.submitOrder();
                });
            }

            toggleOrderFields(orderType) {
                const inRestaurantFields = document.getElementById('inRestaurantFields');
                const deliveryFields = document.getElementById('deliveryFields');
                
                if (orderType === '1') {
                    inRestaurantFields.style.display = 'block';
                    deliveryFields.style.display = 'none';
                } else if (orderType === '3') {
                    inRestaurantFields.style.display = 'none';
                    deliveryFields.style.display = 'block';
                }
            }

            submitOrder() {
                if (this.items.length === 0) {
                    this.showToast('Корзина пуста', 'error');
                    return;
                }

                const orderType = document.querySelector('input[name="orderType"]:checked').value;
                const orderData = {
                    items: this.items,
                    orderType: orderType,
                    total: this.getTotal()
                };

                // TODO: Implement order submission
                this.showToast('Заказ будет отправлен в Poster API', 'info');
                console.log('Order data:', orderData);
            }

            async checkPhoneNumber(phone) {
                if (!phone || !phone.match(/^\+[0-9]{10,12}$/)) {
                    this.showPhoneCheckResult('', 'error');
                    return;
                }

                const statusDiv = document.getElementById('phoneCheckStatus');
                statusDiv.style.display = 'block';
                statusDiv.className = 'phone-check-status info';
                statusDiv.textContent = 'Проверяем номер...';

                try {
                    const response = await fetch('/api/check-phone.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ phone: phone })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.showPhoneCheckResult(data.message, data.found ? 'success' : 'info', data);
                    } else {
                        this.showPhoneCheckResult(data.error || 'Ошибка проверки номера', 'error');
                    }
                } catch (error) {
                    console.error('Phone check error:', error);
                    this.showPhoneCheckResult('Ошибка соединения', 'error');
                }
            }

            showPhoneCheckResult(message, type, data = null) {
                const statusDiv = document.getElementById('phoneCheckStatus');
                statusDiv.style.display = 'block';
                statusDiv.className = `phone-check-status ${type}`;
                statusDiv.textContent = message;

                // Сохраняем данные о клиенте для использования при оформлении заказа
                if (data) {
                    this.phoneCheckData = data;
                }
            }

            showAuthModal() {
                // TODO: Implement auth modal
                this.showToast('Модалка авторизации будет реализована', 'info');
            }

            showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.textContent = message;
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 4px;
                    z-index: 10000;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                `;
                
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateX(0)';
                }, 100);
                
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        }

        // Initialize cart when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            window.cart = new Cart();
        });

        // Category filtering with animations
        document.addEventListener('DOMContentLoaded', function() {
            const categoryBtns = document.querySelectorAll('.category-btn, .header-nav__links a');
            const menuSections = document.querySelectorAll('.menu-section');
            const mobileToggle = document.getElementById('mobileCategoryToggle');
            const mobileNav = document.getElementById('mobileCategoryNav');
            
            console.log('Elements found:');
            console.log('- mobileToggle:', mobileToggle);
            console.log('- mobileNav:', mobileNav);
            console.log('- categoryBtns:', categoryBtns.length);
            console.log('- menuSections:', menuSections.length);
            
            // Mobile category navigation functionality (like header-nav)
            if (mobileToggle) {
                console.log('Mobile toggle button found:', mobileToggle);
                mobileToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Mobile toggle clicked!');
                    
                    // Toggle button state
                    const isOpen = mobileNav.style.display === 'block';
                    
                    if (isOpen) {
                        // Close menu with animation
                        const links = mobileNav.querySelector('.header-nav__links');
                        if (links) {
                            links.style.transform = 'translateY(-2rem)';
                            links.style.opacity = '0';
                            links.style.visibility = 'hidden';
                        }
                        
                        mobileNav.style.transform = 'scaleY(0)';
                        mobileNav.style.opacity = '0';
                        mobileToggle.classList.remove('is-clicked');
                        document.body.classList.remove('menu-is-open');
                        
                        setTimeout(() => {
                            mobileNav.style.display = 'none';
                        }, 300);
                    } else {
                        // Open menu with animation
                        mobileNav.style.display = 'block';
                        mobileNav.style.transform = 'scaleY(0)';
                        mobileNav.style.opacity = '0';
                        mobileToggle.classList.add('is-clicked');
                        document.body.classList.add('menu-is-open');
                        
                        // Trigger animation
                        setTimeout(() => {
                            mobileNav.style.transform = 'scaleY(1)';
                            mobileNav.style.opacity = '1';
                            
                            // Animate links
                            const links = mobileNav.querySelector('.header-nav__links');
                            if (links) {
                                setTimeout(() => {
                                    links.style.transform = 'translateY(0)';
                                    links.style.opacity = '1';
                                    links.style.visibility = 'visible';
                                }, 150);
                            }
                        }, 10);
                    }
                    
                    console.log('Button is-clicked:', mobileToggle.classList.contains('is-clicked'));
                    console.log('Menu display:', mobileNav.style.display);
                });
            } else {
                console.error('Mobile toggle button not found!');
            }
            
            // Set initial active state
            if (categoryBtns.length > 0) {
                categoryBtns[0].classList.add('active');
            }
            if (menuSections.length > 0) {
                menuSections.forEach((section, index) => {
                    if (index === 0) {
                        section.style.display = 'block';
                        section.classList.add('active');
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
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const category = this.dataset.category;
                    const li = this.closest('li');
                    
                    // Update active button
                    categoryBtns.forEach(b => {
                        b.classList.remove('active');
                        const parentLi = b.closest('li');
                        if (parentLi) {
                            parentLi.classList.remove('current');
                        }
                    });
                    this.classList.add('active');
                    if (li) {
                        li.classList.add('current');
                    }
                    
                    // Close mobile category navigation with animation
                    const links = mobileNav.querySelector('.header-nav__links');
                    if (links) {
                        links.style.transform = 'translateY(-2rem)';
                        links.style.opacity = '0';
                        links.style.visibility = 'hidden';
                    }
                    
                    mobileNav.style.transform = 'scaleY(0)';
                    mobileNav.style.opacity = '0';
                    mobileToggle.classList.remove('is-clicked');
                    document.body.classList.remove('menu-is-open');
                    
                    setTimeout(() => {
                        mobileNav.style.display = 'none';
                    }, 300);
                    
                    // Show/hide sections with animation
                    menuSections.forEach(section => {
                        if (section.dataset.category === category) {
                            section.style.display = 'block';
                            section.classList.add('active');
                            section.classList.remove('animate-in');
                            
                            // Анимация появления секции
                            setTimeout(() => {
                                section.classList.add('animate-in');
                                animateMenuItems(section);
                            }, 50);
                        } else {
                            section.classList.remove('active');
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
            
            // Sort functionality - both old sort buttons and new dropdown
            const sortBtns = document.querySelectorAll('.sort-btn');
            const sortDropdownItems = document.querySelectorAll('.sort-dropdown__item');
            
            // Handle old sort buttons (if they exist)
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
            
            // Handle new sort dropdown items
            sortDropdownItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sortType = this.dataset.sort;
                    const dropdown = this.closest('.sort-dropdown');
                    const trigger = dropdown.querySelector('.sort-dropdown__trigger');
                    const icon = trigger.querySelector('.sort-dropdown__icon');
                    
                    // Update active dropdown item
                    sortDropdownItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update trigger icon based on sort type
                    const sortIcons = {
                        'popularity': '<path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/>',
                        'price': '<path d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z"/>',
                        'alphabet': '<path d="M14,17H7v-2h7V17z M17,13H7v-2h10V13z M17,9H7V7h10V9z M3,5V3h18v2H3z"/>'
                    };
                    icon.innerHTML = sortIcons[sortType] || sortIcons['popularity'];
                    
                    // Sort all menu sections with the selected type
                    const menuSections = document.querySelectorAll('.menu-section');
                    menuSections.forEach(section => {
                        sortMenuItems(section, sortType);
                    });
                    
                    // Close dropdown by removing hover state
                    dropdown.classList.remove('hover');
                });
            });
            
            // Function to sort menu items
            function sortMenuItems(section, sortType) {
                const menuList = section.querySelector('.menu-list');
                if (!menuList) return;
                
                const items = Array.from(menuList.querySelectorAll('.menu-list__item'));
                
                items.sort((a, b) => {
                    const nameA = a.dataset.productName || a.querySelector('h4').textContent.trim();
                    const nameB = b.dataset.productName || b.querySelector('h4').textContent.trim();
                    const priceA = parseFloat(a.dataset.price || a.querySelector('.menu-list__item-price').textContent.replace(/[^\d]/g, ''));
                    const priceB = parseFloat(b.dataset.price || b.querySelector('.menu-list__item-price').textContent.replace(/[^\d]/g, ''));
                    const popularityA = parseInt(a.dataset.popularity || a.dataset.sortOrder || 0);
                    const popularityB = parseInt(b.dataset.popularity || b.dataset.sortOrder || 0);
                    
                    switch(sortType) {
                        case 'alphabet':
                            // Сортировка по алфавиту от А до Я
                            return nameA.localeCompare(nameB, 'ru');
                        case 'price':
                            // Сортировка по цене - самые дорогие вверху
                            return priceB - priceA;
                        case 'popularity':
                            // Сортировка по популярности - используем data-popularity или data-sort-order
                            const popA = parseInt(a.dataset.popularity || a.dataset.sortOrder || 0);
                            const popB = parseInt(b.dataset.popularity || b.dataset.sortOrder || 0);
                            if (popA !== popB) {
                                return popB - popA; // Большее значение = более популярный
                            }
                            // Если популярность одинаковая, сортируем по цене (дорогие вверху)
                            return priceB - priceA;
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