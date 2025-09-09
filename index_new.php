<?php
/**
 * Главная страница с новой системой полного HTML контента
 * Заменяет старую систему переводов на систему полных страниц
 */

// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Initialize page content service
require_once __DIR__ . '/classes/PageContentService.php';
$pageContentService = new PageContentService();

// Get current language
$currentLanguage = $pageContentService->getLanguage();

// Get full page content from database
$pageContent = $pageContentService->getPageContent('index', $currentLanguage);
$pageMeta = $pageContent['meta'] ?? [];

// Load menu from MongoDB cache for fast rendering (if available)
$categories = [];
$products = [];
$productsByCategory = [];

try {
    if (class_exists('MongoDB\Client')) {
        require_once __DIR__ . '/classes/MenuCache.php';
        $menuCache = new MenuCache();
        $menuData = $menuCache->getMenu();
        $categories = $menuData ? $menuData['categories'] : [];
        $products = $menuData ? $menuData['products'] : [];
        
        // API configuration for popular products
        $api_base_url = 'http://localhost:3002/api';
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'Content-Type: application/json'
            ]
        ]);
        
        // Get popular products by category using real sales data
        if ($categories) {
            foreach ($categories as $category) {
                $categoryId = (string)($category['category_id']);
                $productsByCategory[$categoryId] = [];
                
                // Try to get popular products from API
                try {
                    $popularUrl = $api_base_url . '/menu/categories/' . $categoryId . '/popular?limit=5';
                    $popularResponse = @file_get_contents($popularUrl, false, $context);
                    
                    if ($popularResponse !== false) {
                        $popularData = json_decode($popularResponse, true);
                        if ($popularData && isset($popularData['popular_products'])) {
                            $productsByCategory[$categoryId] = $popularData['popular_products'];
                        }
                    }
                } catch (Exception $e) {
                    // Fallback to empty array if API fails
                    $productsByCategory[$categoryId] = [];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Menu loading error: " . $e->getMessage());
}

// Set page title and meta tags
$pageTitle = $pageMeta['title'] ?? 'North Republic - Ресторан в Нячанге';
$pageDescription = $pageMeta['description'] ?? 'North Republic - изысканный ресторан в Нячанге с великолепной кухней и уютной атмосферой.';
$pageKeywords = $pageMeta['keywords'] ?? 'ресторан, нячанг, вьетнам, кухня, еда, ужин, обед';
?>

<!DOCTYPE html>
<html lang="<?php echo $currentLanguage; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://northrepublic.me/">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="https://northrepublic.me/images/logo.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://northrepublic.me/">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="twitter:image" content="https://northrepublic.me/images/logo.png">

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- CSS -->
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/custom.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

    <!-- Header -->
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <main class="s-content">
        <!-- Intro Section -->
        <section id="intro" class="s-intro target-section">
            <div class="row intro-content">
                <div class="column large-9 mob-full intro-text">
                    <?php 
                    // Выводим полный HTML контент из БД
                    echo $pageContent['content'] ?? '<div class="alert alert-warning">Контент не найден</div>';
                    ?>
                </div>
                
                <div class="column large-3 mob-full intro-media">
                    <img src="images/intro-pic-primary.jpg" alt="North Republic">
                </div>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="s-about target-section">
            <div class="row about-content">
                <div class="column large-6 tab-full">
                    <h3 class="subhead">О нас</h3>
                    <p class="lead">
                        Добро пожаловать в <strong>«Республику Север»</strong> — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга. Здесь, в объятиях первозданной природы, у подножия легендарной горы Ко Тьен, современность встречается с дикой красотой тропического края, создавая пространство безграничных возможностей.
                    </p>
                    <p>
                        Наш ресторан — это не просто место для трапезы, а целый мир вкусов, ароматов и эмоций. Мы создали уникальное пространство, где каждый гость может насладиться изысканными блюдами, приготовленными с любовью и мастерством наших поваров.
                    </p>
                </div>
                
                <div class="column large-6 tab-full">
                    <img src="images/about-pic-primary.jpg" alt="About North Republic">
                </div>
            </div>
        </section>

        <!-- Menu Section -->
        <section id="menu" class="s-menu target-section">
            <div class="row section-header">
                <div class="column large-6 tab-full">
                    <h3 class="subhead">Наше меню</h3>
                    <h2 class="text-display-title">Вкусные блюда</h2>
                </div>
            </div>

            <div class="row block-large-1-2 block-tab-full">
                <?php if (!empty($categories)): ?>
                    <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                        <div class="column">
                            <div class="menu-item">
                                <h4 class="menu-item-title"><?php echo htmlspecialchars($category['category_name'] ?? 'Категория'); ?></h4>
                                
                                <?php 
                                $categoryId = (string)($category['category_id']);
                                $categoryProducts = $productsByCategory[$categoryId] ?? [];
                                ?>
                                
                                <?php if (!empty($categoryProducts)): ?>
                                    <?php foreach (array_slice($categoryProducts, 0, 3) as $product): ?>
                                        <div class="menu-item-details">
                                            <div class="menu-item-name">
                                                <?php echo htmlspecialchars($product['product_name'] ?? 'Блюдо'); ?>
                                            </div>
                                            <div class="menu-item-price">
                                                <?php echo number_format($product['price'] ?? 0, 0, ',', ' '); ?> ₫
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="menu-item-description">Популярные блюда этой категории</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="column">
                        <div class="menu-item">
                            <h4 class="menu-item-title">Меню временно недоступно</h4>
                            <p class="menu-item-description">Мы работаем над обновлением нашего меню. Пожалуйста, зайдите позже.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row section-header">
                <div class="column large-12">
                    <a href="/menu" class="btn btn--primary">
                        Открыть полное меню
                    </a>
                </div>
            </div>
        </section>

        <!-- Gallery Section -->
        <section id="gallery" class="s-gallery target-section">
            <div class="row section-header">
                <div class="column large-6 tab-full">
                    <h3 class="subhead">Галерея</h3>
                    <h2 class="text-display-title">Наши фотографии</h2>
                </div>
            </div>

            <div class="row block-large-1-2 block-tab-full">
                <div class="column">
                    <img src="images/gallery/gallery-01.jpg" alt="Gallery Image 1">
                </div>
                <div class="column">
                    <img src="images/gallery/gallery-02.jpg" alt="Gallery Image 2">
                </div>
                <div class="column">
                    <img src="images/gallery/gallery-03.jpg" alt="Gallery Image 3">
                </div>
                <div class="column">
                    <img src="images/gallery/gallery-04.jpg" alt="Gallery Image 4">
                </div>
                <div class="column">
                    <img src="images/gallery/gallery-05.jpg" alt="Gallery Image 5">
                </div>
                <div class="column">
                    <img src="images/gallery/gallery-06.jpg" alt="Gallery Image 6">
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <!-- JavaScript -->
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>

    <!-- Language Switcher Script -->
    <script>
        // Language switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const langButtons = document.querySelectorAll('.lang-btn');
            
            langButtons.forEach(button => {
                button.addEventListener('click', async function() {
                    const lang = this.dataset.lang;
                    
                    try {
                        const response = await fetch('/api/language/change.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ lang: lang })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Reload page to apply new language
                            window.location.reload();
                        } else {
                            console.error('Failed to change language:', data.message);
                        }
                    } catch (error) {
                        console.error('Error changing language:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>
