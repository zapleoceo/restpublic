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
                    
                    // Try to get products from API
                    try {
                        $api_url = $api_base_url . '/menu/categories/' . $categoryId . '/products';
                        $api_response = @file_get_contents($api_url, false, $context);
                        
                        if ($api_response !== false) {
                            $api_data = json_decode($api_response, true);
                            if (isset($api_data['products']) && is_array($api_data['products'])) {
                                $products_by_category[$categoryId] = $api_data['products'];
                                error_log("Menu3: Loaded " . count($api_data['products']) . " products for category " . $categoryId . " from API");
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Menu3: API error for category " . $categoryId . ": " . $e->getMessage());
                    }
                    
                    // Fallback to cache if API failed
                    if (empty($products_by_category[$categoryId]) && !empty($products)) {
                        foreach ($products as $product) {
                            if ((string)($product['category_id'] ?? '') === $categoryId) {
                                $products_by_category[$categoryId][] = $product;
                            }
                        }
                        error_log("Menu3: Fallback to cache for category " . $categoryId . ": " . count($products_by_category[$categoryId]) . " products");
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Menu3: Error loading menu: " . $e->getMessage());
}

// Helper function for safe HTML output
function safeHtml($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

// getCurrentLanguage() function is already defined in category-translator.php
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>" class="no-js">
<head>
    <meta charset="utf-8">
    <title><?php echo safeHtml($translationService->get('menu.title_v3', 'Наше меню v3')); ?> - Veranda</title>
    <meta name="description" content="<?php echo safeHtml($translationService->get('menu.description', 'Меню ресторана Veranda в Нячанге')); ?>">
    <meta name="author" content="Veranda">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/menu.css">
    
    <!-- Menu3 specific styles -->
    <style>
        /* Menu3 Layout Styles */
        .menu3-container {
            display: flex;
            min-height: calc(100vh - 200px);
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
            gap: 2rem;
        }
        
        /* Categories Sidebar */
        .menu3-categories {
            width: 300px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .menu3-categories h2 {
            margin: 0 0 1.5rem 0;
            color: #252322;
            font-size: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid #b88746;
            padding-bottom: 0.5rem;
        }
        
        .menu3-category-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu3-category-item {
            margin-bottom: 0.5rem;
        }
        
        .menu3-category-link {
            display: block;
            padding: 1rem 1.25rem;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .menu3-category-link:hover {
            background: #e9ecef;
            color: #252322;
            border-left-color: #b88746;
        }
        
        .menu3-category-link.active {
            background: #252322;
            color: white;
            border-left-color: #b88746;
        }
        
        /* Products Content */
        .menu3-products {
            flex: 1;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }
        
        .menu3-products h1 {
            margin: 0 0 2rem 0;
            color: #252322;
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
        }
        
        .menu3-category-section {
            margin-bottom: 3rem;
        }
        
        .menu3-category-title {
            color: #252322;
            font-size: 2rem;
            font-weight: 600;
            margin: 0 0 1.5rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #b88746;
        }
        
        .menu3-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .menu3-product-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .menu3-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .menu3-product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .menu3-product-name {
            color: #252322;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        
        .menu3-product-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0 0 1rem 0;
        }
        
        .menu3-product-price {
            color: #b88746;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: right;
        }
        
        /* Loading and Empty States */
        .menu3-loading, .menu3-empty {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .menu3-loading p, .menu3-empty h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .menu3-empty p {
            font-size: 1rem;
            color: #999;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .menu3-container {
                flex-direction: column;
                padding: 1rem;
                gap: 1rem;
            }
            
            .menu3-categories {
                width: 100%;
                position: static;
                order: 2;
            }
            
            .menu3-category-list {
                display: flex;
                overflow-x: auto;
                gap: 0.5rem;
                padding-bottom: 0.5rem;
            }
            
            .menu3-category-item {
                flex-shrink: 0;
                margin-bottom: 0;
            }
            
            .menu3-category-link {
                white-space: nowrap;
                padding: 0.75rem 1rem;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .menu3-category-link:hover,
            .menu3-category-link.active {
                border-left: none;
                border-bottom-color: #b88746;
            }
            
            .menu3-products {
                order: 1;
                max-height: none;
            }
            
            .menu3-products h1 {
                font-size: 2rem;
            }
            
            .menu3-products-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .menu3-category-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .menu3-container {
                padding: 0.5rem;
            }
            
            .menu3-categories {
                padding: 1rem;
            }
            
            .menu3-product-card {
                padding: 1rem;
            }
            
            .menu3-product-image {
                height: 150px;
            }
        }
    </style>
</head>

<body id="top">
    <!-- Header -->
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <section class="s-content">
        <div class="container">
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
                                <span style="color: #e74c3c; font-style: italic;">
                                    <?php echo safeHtml($translationService->get('menu.no_categories', 'Нет категорий')); ?>
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
        </div>
    </section>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- JavaScript -->
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>
    
    <!-- Menu3 specific JavaScript -->
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