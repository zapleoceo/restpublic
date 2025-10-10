<?php
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Load translation service
require_once __DIR__ . '/classes/TranslationService.php';
$translationService = new TranslationService();

// Load category translator
require_once __DIR__ . '/category-translator.php';

// Обновляем кеш меню при заходе на страницу (в фоновом режиме)
function updateMenuCacheAsync() {
    $cacheUrl = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/api/cache/update-menu';
    
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
        
        // Получаем текущий язык для перевода блюд
        $currentLanguage = $translationService->getLanguage();
        
        // Логируем текущий язык для отладки
        error_log("Menu3: Current language = " . $currentLanguage);
        
        if ($menuData) {
            $categories = $menuData['categories'] ?? [];
            $products = $menuData['products'] ?? [];
            $menu_loaded = !empty($categories) && !empty($products);
            
            // API configuration for popular products
            $api_base_url = ($_ENV['BACKEND_URL'] ?? 'http://localhost:3003') . '/api';
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
                        $authToken = $_ENV['API_AUTH_TOKEN'] ?? 'nr_api_2024_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6';
                        $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=50&token=' . urlencode($authToken);
                        $popularResponse = @file_get_contents($popularUrl, false, $context);
                        
                        if ($popularResponse !== false) {
                            $popularData = json_decode($popularResponse, true);
                            if ($popularData && isset($popularData['popular_products'])) {
                                $products_by_category[$categoryId] = $popularData['popular_products'];
                            } else {
                                error_log("Invalid API response for category {$categoryId}: " . substr($popularResponse, 0, 200));
                                $products_by_category[$categoryId] = [];
                            }
                        } else {
                            error_log("Failed to fetch popular products for category {$categoryId}");
                            $products_by_category[$categoryId] = [];
                        }
                    } catch (Exception $e) {
                        error_log("API error for category {$categoryId}: " . $e->getMessage());
                        // Fallback to empty array if API fails
                        $products_by_category[$categoryId] = [];
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Menu loading error: " . $e->getMessage());
    // Fallback: ensure we have empty arrays to prevent errors
    $categories = [];
    $products = [];
    $products_by_category = [];
}

// Get current language
$currentLanguage = $translationService->getLanguage();

// Helper function for safe HTML output
function safeHtml($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// Helper function to get current language
function getCurrentLanguage() {
    global $translationService;
    return $translationService->getLanguage();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>" class="no-js">
<head>
    <!--- basic page needs
    ================================================== -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo safeHtml($translationService->get('menu.title_v3', 'Наше меню v3')); ?> - Veranda</title>
    <meta name="description" content="<?php echo safeHtml($translationService->get('menu.description_v3', 'Полное меню ресторана Veranda в Нячанге')); ?>">
    <meta name="keywords" content="меню, ресторан, Нячанг, Veranda, еда, напитки">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://veranda.my/menu3.php">

    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>

    <!-- CSS
    ================================================== -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="css/menu.css">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="fonts/MinionPro-Regular.otf" as="font" type="font/otf" crossorigin>
    <link rel="preload" href="js/main.js" as="script">
    
    <style>
        /* Menu3 specific styles */
        .menu3-container {
            display: flex;
            min-height: 100vh;
            background: #fafafa;
        }
        
        .menu3-categories {
            width: 300px;
            background: white;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        .menu3-categories h2 {
            padding: 20px;
            margin: 0;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .menu3-category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu3-category-item {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .menu3-category-link {
            display: block;
            padding: 16px 20px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .menu3-category-link:hover {
            background: #f8f8f8;
            color: #1976d2;
        }
        
        .menu3-category-link.active {
            background: #e3f2fd;
            color: #1976d2;
            border-left-color: #1976d2;
            font-weight: 600;
        }
        
        .menu3-products {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .menu3-products h1 {
            margin: 0 0 30px 0;
            font-size: 28px;
            color: #333;
        }
        
        .menu3-category-section {
            margin-bottom: 40px;
        }
        
        .menu3-category-title {
            font-size: 24px;
            font-weight: 600;
            color: #1976d2;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #1976d2;
        }
        
        .menu3-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .menu3-product-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .menu3-product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .menu3-product-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0 0 8px 0;
        }
        
        .menu3-product-description {
            color: #666;
            font-size: 14px;
            margin: 0 0 12px 0;
            line-height: 1.4;
        }
        
        .menu3-product-price {
            font-size: 20px;
            font-weight: 700;
            color: #1976d2;
        }
        
        .menu3-product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        /* Loading state */
        .menu3-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            color: #666;
        }
        
        /* Empty state */
        .menu3-empty {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .menu3-container {
                flex-direction: column;
            }
            
            .menu3-categories {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
                max-height: 200px;
            }
            
            .menu3-categories h2 {
                padding: 15px 20px;
                font-size: 16px;
            }
            
            .menu3-category-list {
                display: flex;
                overflow-x: auto;
                padding: 10px;
                scrollbar-width: thin;
            }
            
            .menu3-category-list::-webkit-scrollbar {
                height: 4px;
            }
            
            .menu3-category-list::-webkit-scrollbar-track {
                background: #f1f1f1;
            }
            
            .menu3-category-list::-webkit-scrollbar-thumb {
                background: #c1c1c1;
                border-radius: 2px;
            }
            
            .menu3-category-item {
                flex-shrink: 0;
                border-bottom: none;
                border-right: 1px solid #f0f0f0;
            }
            
            .menu3-category-link {
                white-space: nowrap;
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .menu3-products {
                padding: 15px;
            }
            
            .menu3-products h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .menu3-category-title {
                font-size: 20px;
                margin-bottom: 15px;
            }
            
            .menu3-products-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .menu3-product-card {
                padding: 15px;
            }
            
            .menu3-product-name {
                font-size: 16px;
            }
            
            .menu3-product-price {
                font-size: 18px;
            }
        }
        
        @media (max-width: 480px) {
            .menu3-products {
                padding: 10px;
            }
            
            .menu3-products h1 {
                font-size: 20px;
            }
            
            .menu3-category-title {
                font-size: 18px;
            }
            
            .menu3-product-card {
                padding: 12px;
            }
        }
    </style>

    <!-- favicons
    ================================================== -->
    <link rel="icon" type="image/svg+xml" href="template/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="template/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="template/favicon-16x16.png">
    <link rel="manifest" href="template/site.webmanifest">
</head>

<body id="top">
    
    <!-- Header -->
    <?php include 'components/header.php'; ?>

    <!-- Menu3 Container -->
    <div class="menu3-container">
        <!-- Categories Sidebar -->
        <div class="menu3-categories">
            <h2><?php echo safeHtml($translationService->get('menu.categories', 'Категории')); ?></h2>
            <ul class="menu3-category-list" id="category-list">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $index => $category): ?>
                        <li class="menu3-category-item">
                            <a href="#" 
                               class="menu3-category-link <?php echo $index === 0 ? 'active' : ''; ?>"
                               data-category-id="<?php echo htmlspecialchars($category['category_id']); ?>">
                                <?php echo htmlspecialchars(translateCategoryName($category['category_name'] ?? $category['name'] ?? 'Без названия', getCurrentLanguage())); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="menu3-category-item">
                        <span class="menu3-category-link" style="color: #e74c3c; font-style: italic;">
                            <?php echo safeHtml($translationService->get('menu.error', 'Меню недоступно')); ?>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Products Content -->
        <div class="menu3-products">
            <h1><?php echo safeHtml($translationService->get('menu.title_v3', 'Наше меню v3')); ?></h1>
            
            <div id="products-content">
                <?php if (!$menu_loaded): ?>
                    <div class="menu3-loading">
                        <div>
                            <p><?php echo safeHtml($translationService->get('menu.loading', 'Загрузка меню...')); ?></p>
                        </div>
                    </div>
                <?php elseif (!empty($categories) && !empty($products_by_category)): ?>
                    <?php 
                    $hasProducts = false;
                    foreach ($categories as $category) {
                        $categoryId = (string)($category['category_id']);
                        if (!empty($products_by_category[$categoryId])) {
                            $hasProducts = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($hasProducts): ?>
                        <?php foreach ($categories as $index => $category): ?>
                            <?php 
                            $categoryId = (string)($category['category_id']);
                            $categoryProducts = $products_by_category[$categoryId] ?? [];
                            
                            // Применяем автоматический перевод для продуктов
                            if ($currentLanguage !== 'ru' && !empty($categoryProducts)) {
                                $translatedProducts = [];
                                foreach ($categoryProducts as $product) {
                                    $translatedProducts[] = $menuCache->translateProduct($product, $currentLanguage);
                                }
                                $categoryProducts = $translatedProducts;
                            }
                            ?>
                            
                            <?php if (!empty($categoryProducts)): ?>
                                <div class="menu3-category-section" data-category-id="<?php echo htmlspecialchars($category['category_id']); ?>">
                                    <h2 class="menu3-category-title">
                                        <?php echo htmlspecialchars(translateCategoryName($category['category_name'] ?? $category['name'] ?? 'Без названия', getCurrentLanguage())); ?>
                                    </h2>
                                    
                                    <div class="menu3-products-grid">
                                        <?php foreach ($categoryProducts as $product): ?>
                                            <div class="menu3-product-card">
                                                <?php if (!empty($product['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? ''); ?>"
                                                         class="menu3-product-image"
                                                         loading="lazy"
                                                         onerror="this.style.display='none'">
                                                <?php endif; ?>
                                                
                                                <h3 class="menu3-product-name">
                                                    <?php echo htmlspecialchars($product['product_name'] ?? $product['name'] ?? 'Без названия'); ?>
                                                </h3>
                                                
                                                <?php if (!empty($product['description'])): ?>
                                                    <p class="menu3-product-description">
                                                        <?php echo htmlspecialchars($product['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="menu3-product-price">
                                                    <?php echo number_format($product['price_normalized'] ?? $product['price'] ?? 0, 0, ',', ' '); ?> ₫
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="menu3-empty">
                            <h2><?php echo safeHtml($translationService->get('menu.empty', 'Меню пусто')); ?></h2>
                            <p><?php echo safeHtml($translationService->get('menu.empty_description', 'В данный момент в меню нет доступных блюд.')); ?></p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="menu3-empty">
                        <h2><?php echo safeHtml($translationService->get('menu.error', 'Меню недоступно')); ?></h2>
                        <p><?php echo safeHtml($translationService->get('menu.unavailable', 'Меню временно недоступно. Попробуйте позже.')); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- JavaScript
    ================================================== -->
    <script src="js/plugins.js" defer></script>
    <script src="js/main.js" defer></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryLinks = document.querySelectorAll('.menu3-category-link');
            const categorySections = document.querySelectorAll('.menu3-category-section');
            const productsContent = document.querySelector('.menu3-products');
            
            let isScrolling = false;
            let isClicking = false;
            
            // Обработка кликов по категориям
            categoryLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const categoryId = this.dataset.categoryId;
                    const targetSection = document.querySelector(`[data-category-id="${categoryId}"]`);
                    
                    if (targetSection) {
                        isClicking = true;
                        
                        // Убираем активный класс у всех категорий
                        categoryLinks.forEach(l => l.classList.remove('active'));
                        
                        // Добавляем активный класс к выбранной категории
                        this.classList.add('active');
                        
                        // Прокручиваем к нужной секции
                        const targetPosition = targetSection.offsetTop - productsContent.offsetTop - 20;
                        
                        productsContent.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                        
                        // Сбрасываем флаг через некоторое время
                        setTimeout(() => {
                            isClicking = false;
                        }, 1000);
                    }
                });
            });
            
            // Обработка скролла для изменения активной категории
            productsContent.addEventListener('scroll', function() {
                if (isScrolling || isClicking) return;
                
                isScrolling = true;
                requestAnimationFrame(() => {
                    const scrollTop = productsContent.scrollTop;
                    const containerHeight = productsContent.clientHeight;
                    let activeCategory = null;
                    let minDistance = Infinity;
                    
                    // Находим секцию, которая находится ближе всего к верху контейнера
                    categorySections.forEach(section => {
                        const sectionTop = section.offsetTop - productsContent.offsetTop;
                        const sectionHeight = section.offsetHeight;
                        const sectionCenter = sectionTop + sectionHeight / 2;
                        const viewportCenter = scrollTop + containerHeight / 2;
                        
                        // Проверяем, видна ли секция в области видимости
                        if (sectionTop <= scrollTop + containerHeight && sectionTop + sectionHeight >= scrollTop) {
                            const distance = Math.abs(sectionTop - scrollTop);
                            
                            if (distance < minDistance) {
                                minDistance = distance;
                                activeCategory = section.dataset.categoryId;
                            }
                        }
                    });
                    
                    // Обновляем активную категорию
                    if (activeCategory) {
                        categoryLinks.forEach(link => {
                            link.classList.remove('active');
                            if (link.dataset.categoryId === activeCategory) {
                                link.classList.add('active');
                                
                                // Прокручиваем активную категорию в область видимости в сайдбаре
                                const categoryList = document.getElementById('category-list');
                                const linkRect = link.getBoundingClientRect();
                                const listRect = categoryList.getBoundingClientRect();
                                
                                if (linkRect.top < listRect.top || linkRect.bottom > listRect.bottom) {
                                    link.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'nearest'
                                    });
                                }
                            }
                        });
                    }
                    
                    isScrolling = false;
                });
            });
            
            // Инициализация: устанавливаем первую категорию как активную
            if (categoryLinks.length > 0) {
                categoryLinks[0].classList.add('active');
            }
            
            // Обработка изменения размера окна
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    // Пересчитываем позиции после изменения размера
                    isScrolling = true;
                    setTimeout(() => {
                        isScrolling = false;
                    }, 100);
                }, 250);
            });
        });
    </script>

</body>
</html>
