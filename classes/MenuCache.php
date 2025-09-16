<?php
require_once __DIR__ . '/../vendor/autoload.php';

class MenuCache {
    private $client;
    private $db;
    private $menuCollection;
    
    public function __construct() {
        try {
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
            
            $this->client = new MongoDB\Client($mongodbUrl);
            $this->db = $this->client->$dbName;
            $this->menuCollection = $this->db->menu;
        } catch (Exception $e) {
            error_log("Ошибка подключения к MongoDB: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Получить меню из кэша с автоматическим обновлением
     */
    public function getMenu($maxAgeMinutes = 30) {
        try {
            $menu = $this->menuCollection->findOne(['_id' => 'current_menu']);
            
            // Если кэша нет или он устарел - обновляем в фоне
            if (!$menu || $this->needsUpdate($maxAgeMinutes)) {
                $this->updateMenuInBackground();
                
                // Если кэша все еще нет - возвращаем null
                if (!$menu) {
                    return null;
                }
            }
            
            // Convert MongoDB BSONArray to PHP arrays
            $categories = $menu['categories'] ?? [];
            $products = $menu['products'] ?? [];
            
            // Convert BSONArray to PHP array if needed
            if (is_object($categories)) {
                if (method_exists($categories, 'toArray')) {
                    $categories = $categories->toArray();
                } else {
                    // Fallback: convert to array manually
                    $categories = iterator_to_array($categories);
                }
            }
            if (is_object($products)) {
                if (method_exists($products, 'toArray')) {
                    $products = $products->toArray();
                } else {
                    // Fallback: convert to array manually
                    $products = iterator_to_array($products);
                }
            }
            
            return [
                'categories' => $categories,
                'products' => $products,
                'updated_at' => $menu['updated_at'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("Ошибка получения меню из кэша: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получить категории
     */
    public function getCategories() {
        $menu = $this->getMenu();
        return $menu ? $menu['categories'] : [];
    }
    
    /**
     * Получить продукты по категории с сортировкой по популярности и автоматическим переводом
     */
    public function getProductsByCategory($categoryId, $limit = 5, $language = 'ru') {
        $menu = $this->getMenu();
        
        if (!$menu) {
            return [];
        }
        
        $products = $menu['products'] ?? [];
        $categoryProducts = [];
        
        // Собираем все продукты категории
        foreach ($products as $product) {
            if (($product['menu_category_id'] ?? $product['category_id']) == $categoryId) {
                // Проверяем видимость продукта
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
        
        // Сортируем по популярности (та же логика, что и в index.php)
        usort($categoryProducts, function($a, $b) {
            // First: visible products (already filtered above, but double-check)
            $aVisible = true;
            if (isset($a['spots']) && is_array($a['spots'])) {
                foreach ($a['spots'] as $spot) {
                    if (isset($spot['visible']) && $spot['visible'] == '0') {
                        $aVisible = false;
                        break;
                    }
                }
            }
            
            $bVisible = true;
            if (isset($b['spots']) && is_array($b['spots'])) {
                foreach ($b['spots'] as $spot) {
                    if (isset($spot['visible']) && $spot['visible'] == '0') {
                        $bVisible = false;
                        break;
                    }
                }
            }
            
            if ($aVisible != $bVisible) {
                return $bVisible <=> $aVisible; // visible first
            }
            
            // Second: sort_order (higher is more popular - reverse order)
            $aSort = (int)($a['sort_order'] ?? 0);
            $bSort = (int)($b['sort_order'] ?? 0);
            
            return $bSort <=> $aSort; // higher sort_order first (more popular)
        });
        
        // Применяем автоматический перевод для каждого продукта
        $translatedProducts = [];
        foreach (array_slice($categoryProducts, 0, $limit) as $product) {
            $translatedProducts[] = $this->translateProduct($product, $language);
        }
        
        return $translatedProducts;
    }
    
    /**
     * Проверить, нужно ли обновить кэш
     */
    public function needsUpdate($maxAgeMinutes = 30) {
        try {
            $menu = $this->menuCollection->findOne(['_id' => 'current_menu']);
            
            if (!$menu || !isset($menu['updated_at'])) {
                return true;
            }
            
            $lastUpdate = $menu['updated_at']->toDateTime();
            $now = new DateTime();
            $diff = $now->diff($lastUpdate);
            
            return $diff->i >= $maxAgeMinutes;
            
        } catch (Exception $e) {
            error_log("Ошибка проверки времени обновления: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Обновить кэш (вызывается из update-menu.php)
     */
    public function updateCache($menuData) {
        try {
            $result = $this->menuCollection->replaceOne(
                ['_id' => 'current_menu'],
                [
                    '_id' => 'current_menu',
                    'data' => $menuData,
                    'updated_at' => new MongoDB\BSON\UTCDateTime(),
                    'categories' => $menuData['categories'] ?? [],
                    'products' => $menuData['products'] ?? []
                ],
                ['upsert' => true]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log("Ошибка обновления кэша: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить время последнего обновления меню из настроек
     */
    public function getLastUpdateTime() {
        try {
            require_once __DIR__ . '/SettingsService.php';
            $settingsService = new SettingsService();
            $timestamp = $settingsService->getLastMenuUpdateTime();
            
            if (!$timestamp) {
                return null;
            }
            
            $updateTime = new DateTime();
            $updateTime->setTimestamp($timestamp);
            return $updateTime;
            
        } catch (Exception $e) {
            error_log("Ошибка получения времени обновления: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Получить время последнего обновления в формате для отображения (Нячанг)
     */
    public function getLastUpdateTimeFormatted() {
        try {
            require_once __DIR__ . '/SettingsService.php';
            $settingsService = new SettingsService();
            return $settingsService->getLastMenuUpdateTimeFormatted();
        } catch (Exception $e) {
            error_log("Ошибка получения форматированного времени обновления: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Обновить меню в фоне через API
     */
    private function updateMenuInBackground() {
        try {
            // Асинхронный запрос к нашему API для обновления кэша
            $ch = curl_init();
            $apiUrl = $_ENV['CORS_ORIGIN'] ?? 'https://northrepublic.me';
            $port = $_ENV['PORT'] ?? '3002';
            curl_setopt($ch, CURLOPT_URL, $apiUrl . ':' . $port . '/api/cache/update-menu');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Увеличиваем таймаут
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // Не ждать ответа
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            // Выполняем запрос в фоне
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                error_log("Фоновое обновление меню успешно завершено");
            } else {
                error_log("Фоновое обновление меню завершилось с кодом: " . $httpCode);
            }
            
        } catch (Exception $e) {
            error_log("Ошибка фонового обновления меню: " . $e->getMessage());
        }
    }
    
    /**
     * Перевести продукт на указанный язык
     */
    public function translateProduct($product, $language) {
        if ($language === 'ru') {
            return $product; // Русский - исходный язык
        }
        
        $translatedProduct = $product;
        
        // Переводим название продукта
        if (isset($product['name'])) {
            $translatedName = $this->autoTranslateProductName($product['name'], $language);
            if ($translatedName && $translatedName !== $product['name']) {
                $translatedProduct['name'] = $translatedName;
            }
        }
        
        // Переводим описание продукта
        if (isset($product['description'])) {
            $translatedDescription = $this->autoTranslateProductDescription($product['description'], $language);
            if ($translatedDescription && $translatedDescription !== $product['description']) {
                $translatedProduct['description'] = $translatedDescription;
            }
        }
        
        return $translatedProduct;
    }
    
    /**
     * Автоматический перевод названия продукта
     */
    private function autoTranslateProductName($name, $language) {
        $translations = [
            'en' => [
                'Борщ' => 'Borscht',
                'Пельмени' => 'Dumplings',
                'Пицца' => 'Pizza',
                'Паста' => 'Pasta',
                'Стейк' => 'Steak',
                'Рыба' => 'Fish',
                'Курица' => 'Chicken',
                'Суп' => 'Soup',
                'Салат' => 'Salad',
                'Десерт' => 'Dessert',
                'Кофе' => 'Coffee',
                'Чай' => 'Tea',
                'Сок' => 'Juice',
                'Вода' => 'Water',
                'Пиво' => 'Beer',
                'Вино' => 'Wine',
                'Коктейль' => 'Cocktail',
                'Маргарита' => 'Margarita',
                'Мохито' => 'Mojito',
                'Космополитен' => 'Cosmopolitan',
                'Мартини' => 'Martini',
                'Джин' => 'Gin',
                'Водка' => 'Vodka',
                'Виски' => 'Whiskey',
                'Ром' => 'Rum',
                'Текила' => 'Tequila',
                'Коньяк' => 'Cognac',
                'Ликер' => 'Liqueur',
                'Шампанское' => 'Champagne',
                'Просекко' => 'Prosecco'
            ],
            'vi' => [
                'Борщ' => 'Borscht',
                'Пельмени' => 'Bánh bao',
                'Пицца' => 'Pizza',
                'Паста' => 'Mì Ý',
                'Стейк' => 'Thịt bò nướng',
                'Рыба' => 'Cá',
                'Курица' => 'Thịt gà',
                'Суп' => 'Canh',
                'Салат' => 'Salad',
                'Десерт' => 'Tráng miệng',
                'Кофе' => 'Cà phê',
                'Чай' => 'Trà',
                'Сок' => 'Nước ép',
                'Вода' => 'Nước',
                'Пиво' => 'Bia',
                'Вино' => 'Rượu vang',
                'Коктейль' => 'Cocktail',
                'Маргарита' => 'Margarita',
                'Мохито' => 'Mojito',
                'Космополитен' => 'Cosmopolitan',
                'Мартини' => 'Martini',
                'Джин' => 'Gin',
                'Водка' => 'Vodka',
                'Виски' => 'Whiskey',
                'Ром' => 'Rum',
                'Текила' => 'Tequila',
                'Коньяк' => 'Cognac',
                'Ликер' => 'Rượu mùi',
                'Шампанское' => 'Champagne',
                'Просекко' => 'Prosecco'
            ]
        ];
        
        $translatedName = $name;
        if (isset($translations[$language])) {
            foreach ($translations[$language] as $ru => $translated) {
                $translatedName = str_replace($ru, $translated, $translatedName);
            }
        }
        
        return $translatedName;
    }
    
    /**
     * Автоматический перевод описания продукта
     */
    private function autoTranslateProductDescription($description, $language) {
        $translations = [
            'en' => [
                'свежий' => 'fresh',
                'домашний' => 'homemade',
                'традиционный' => 'traditional',
                'авторский' => 'signature',
                'острый' => 'spicy',
                'сладкий' => 'sweet',
                'соленый' => 'salty',
                'горячий' => 'hot',
                'холодный' => 'cold',
                'натуральный' => 'natural',
                'органический' => 'organic',
                'вегетарианский' => 'vegetarian',
                'веганский' => 'vegan',
                'без глютена' => 'gluten-free',
                'без лактозы' => 'lactose-free',
                'подается' => 'served',
                'с' => 'with',
                'без' => 'without',
                'и' => 'and',
                'или' => 'or'
            ],
            'vi' => [
                'свежий' => 'tươi',
                'домашний' => 'nhà làm',
                'традиционный' => 'truyền thống',
                'авторский' => 'đặc biệt',
                'острый' => 'cay',
                'сладкий' => 'ngọt',
                'соленый' => 'mặn',
                'горячий' => 'nóng',
                'холодный' => 'lạnh',
                'натуральный' => 'tự nhiên',
                'органический' => 'hữu cơ',
                'вегетарианский' => 'chay',
                'веганский' => 'thuần chay',
                'без глютена' => 'không gluten',
                'без лактозы' => 'không lactose',
                'подается' => 'phục vụ',
                'с' => 'với',
                'без' => 'không có',
                'и' => 'và',
                'или' => 'hoặc'
            ]
        ];
        
        $translatedDescription = $description;
        if (isset($translations[$language])) {
            foreach ($translations[$language] as $ru => $translated) {
                $translatedDescription = str_replace($ru, $translated, $translatedDescription);
            }
        }
        
        return $translatedDescription;
    }
}
?>
