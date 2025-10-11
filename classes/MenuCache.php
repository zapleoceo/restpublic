<?php
require_once __DIR__ . '/../vendor/autoload.php';

class MenuCache {
    private $client;
    private $db;
    private $menuCollection;
    
    public function __construct() {
        try {
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
            
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
    public function getMenu($maxAgeMinutes = 10) {
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
    public function needsUpdate($maxAgeMinutes = 10) {
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
            $apiUrl = $_ENV['BACKEND_URL'] ?? 'http://localhost:3003';
            curl_setopt($ch, CURLOPT_URL, $apiUrl . '/api/cache/update-menu');
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
        
        // Переводим название продукта (поддерживаем оба поля)
        $nameField = isset($product['product_name']) ? 'product_name' : 'name';
        if (isset($product[$nameField])) {
            $translatedName = $this->autoTranslateProductName($product[$nameField], $language);
            if ($translatedName && $translatedName !== $product[$nameField]) {
                $translatedProduct[$nameField] = $translatedName;
            }
        }
        
        // Переводим описание продукта - используем многоязычные описания
        $descriptionField = 'description_' . $language;
        if (isset($product[$descriptionField])) {
            $translatedProduct['description'] = $product[$descriptionField];
        } elseif (isset($product['description'])) {
            // Fallback на старое описание с автоматическим переводом
            $translatedDescription = $this->autoTranslateProductDescription($product['description'], $language);
            if ($translatedDescription && $translatedDescription !== $product['description']) {
                $translatedProduct['description'] = $translatedDescription;
            }
        }
        
        return $translatedProduct;
    }
    
    /**
     * Автоматический перевод названия продукта (только для реальных продуктов)
     */
    private function autoTranslateProductName($name, $language) {
        // Словарь только для реальных продуктов из API меню
        $translations = [
            'en' => [
                '7 ап' => '7 Up',
                'Kabanosy' => 'Kabanosy',
                'Pepsi Raspberry' => 'Pepsi Raspberry',
                'Saigon 0,5' => 'Saigon 0.5L',
                'Айс Кофе' => 'Iced Coffee',
                'Айс ти' => 'Iced Tea',
                'Бивина 0,33' => 'Bivina 0.33L',
                'Вайлд Водка сода' => 'Wild Vodka Soda',
                'Вода' => 'Water',
                'Игристое бутылка' => 'Sparkling Wine Bottle',
                'Кальян "MustHave"' => 'Hookah "MustHave"',
                'Картошка печеная' => 'Baked Potato',
                'Квас' => 'Kvass',
                'Кокос' => 'Coconut',
                'Комбуча' => 'Kombucha',
                'Куриные джерки' => 'Chicken Jerky',
                'Миринда 0,33' => 'Mirinda 0.33L',
                'Музлото' => 'Muzloto',
                'Пепси 0,33' => 'Pepsi 0.33L',
                'Пепси зеро 0,33' => 'Pepsi Zero 0.33L',
                'Пепси лайм 0,33' => 'Pepsi Lime 0.33L',
                'Редбулл' => 'Red Bull',
                'Сайгон 0,33' => 'Saigon 0.33L',
                'Сода' => 'Soda',
                'Фруктовая настойка' => 'Fruit Tincture',
                'Чипсы Lays' => 'Lays Chips',
                'Абсент' => 'Absinthe',
                'Б-52' => 'B-52',
                'Б-53' => 'B-53',
                'Хот-дог баварский' => 'Bavarian Hot Dog',
                'Хот-дог классический' => 'Classic Hot Dog',
                'Шаурма' => 'Shawarma',
                'Шаурма XL' => 'Shawarma XL',
                'Шашлык свиной' => 'Pork Shashlik',
                'Куриный шашлык' => 'Chicken Shashlik',
                'Пиво 0,5' => 'Beer 0.5L',
                'Лимончелло' => 'Limoncello',
                'Лонг Айленд' => 'Long Island',
                'Джин' => 'Gin',
                'Ром Кола' => 'Rum Cola',
                'Зеленая Фея' => 'Green Fairy',
                'Виски Кола' => 'Whiskey Cola',
                'Горячий чай' => 'Hot Tea',
                'Кофе 3в1' => 'Coffee 3in1',
                'Кофе молоко лимон' => 'Coffee Milk Lemon',
                'киноа пашот боул' => 'Quinoa Poached Bowl',
                'Креветка в ананасе' => 'Shrimp in Pineapple',
                'Креветка манго боул' => 'Shrimp Mango Bowl',
                'креветка темпура' => 'Shrimp Tempura',
                'Креветки гриль' => 'Grilled Shrimp',
                'Куриный шашлык' => 'Chicken Shashlik',
                'Хот-дог баварский' => 'Bavarian Hot Dog',
                'Хот-дог сырный' => 'Cheesy Hot Dog',
                'Шакшука' => 'Shakshuka',
                'Кофе молоко гренадин' => 'Coffee with milk and grenadine',
                'Кофе молоко малина' => 'Coffee with milk and raspberry',
                'картошка айдахо' => 'Idaho Potato',
                'шарлотка манго' => 'Mango Charlotte',
                'Авокадо тост' => 'Avocado Toast',
                'Антипасти' => 'Antipasti',
                'Арбуз с Фетой' => 'Watermelon with Feta',
                'Боул с тунцом' => 'Tuna Bowl',
                'брускетта Наполитано' => 'Neapolitan Bruschetta',
                'Будда боул' => 'Buddha Bowl',
                'Бургер грибной' => 'Mushroom Burger',
                'Бургер Классик' => 'Classic Burger',
                'Бургер континенталь' => 'Continental Burger',
                'Бургер Мега' => 'Mega Burger',
                'Греческий' => 'Greek Salad',
                'Камбуча Мята' => 'Mint Kombucha',
                'Камбуча Чабрец' => 'Thyme Kombucha',
                'Камбуча Маракуйя' => 'Passion Fruit Kombucha',
                'Revive белый' => 'White Revive',
                'Revive желтый' => 'Yellow Revive',
                'Тоник' => 'Tonic',
                'Пиво лимончелло' => 'Limoncello Beer',
                'Банановый ликер' => 'Banana Liqueur',
                'Водка' => 'Vodka',
                'Самбука' => 'Sambuca',
                'Текила' => 'Tequila',
                'Бейлиз' => 'Baileys',
                'Single Edition Sauvignon Blanc' => 'Single Edition Sauvignon Blanc'
            ],
            'vi' => [
                '7 ап' => '7 Up',
                'Kabanosy' => 'Kabanosy',
                'Pepsi Raspberry' => 'Pepsi Raspberry',
                'Saigon 0,5' => 'Saigon 0.5L',
                'Айс Кофе' => 'Cà phê đá',
                'Айс ти' => 'Trà đá',
                'Бивина 0,33' => 'Bivina 0.33L',
                'Вайлд Водка сода' => 'Vodka Soda hoang dã',
                'Вода' => 'Nước suối',
                'Игристое бутылка' => 'Rượu vang sủi bọt',
                'Кальян "MustHave"' => 'Shisha "MustHave"',
                'Картошка печеная' => 'Khoai tây nướng',
                'Квас' => 'Kvass (nước lên men)',
                'Кокос' => 'Dừa tươi',
                'Комбуча' => 'Kombucha',
                'Куриные джерки' => 'Thịt gà khô',
                'Миринда 0,33' => 'Mirinda 0.33L',
                'Музлото' => 'Muzloto',
                'Пепси 0,33' => 'Pepsi 0.33L',
                'Пепси зеро 0,33' => 'Pepsi Zero 0.33L',
                'Пепси лайм 0,33' => 'Pepsi Lime 0.33L',
                'Редбулл' => 'Red Bull',
                'Сайгон 0,33' => 'Sài Gòn 0.33L',
                'Сода' => 'Nước ngọt có ga',
                'Фруктовая настойка' => 'Rượu ngâm trái cây',
                'Чипсы Lays' => 'Khoai tây chiên Lays',
                'Абсент' => 'Absinthe',
                'Б-52' => 'B-52',
                'Б-53' => 'B-53',
                'Хот-дог баварский' => 'Hot dog Bavaria',
                'Хот-дог классический' => 'Hot dog cổ điển',
                'Шаурма' => 'Shawarma',
                'Шаурма XL' => 'Shawarma XL',
                'Шашлык свиной' => 'Thịt heo nướng xiên',
                'Куриный шашлык' => 'Thịt gà nướng xiên',
                'Пиво 0,5' => 'Bia 0.5L',
                'Лимончелло' => 'Limoncello',
                'Лонг Айленд' => 'Long Island',
                'Джин' => 'Gin',
                'Ром Кола' => 'Rum Cola',
                'Зеленая Фея' => 'Tiên xanh',
                'Виски Кола' => 'Whiskey Cola',
                'Горячий чай' => 'Trà nóng',
                'Кофе 3в1' => 'Cà phê 3in1',
                'Кофе молоко лимон' => 'Cà phê sữa chanh',
                'киноа пашот боул' => 'Bát quinoa luộc',
                'Креветка в ананасе' => 'Tôm trong dứa',
                'Креветка манго боул' => 'Bát tôm xoài',
                'креветка темпура' => 'Tôm tempura',
                'Креветки гриль' => 'Tôm nướng',
                'Куриный шашлык' => 'Thịt gà nướng xiên',
                'Хот-дог баварский' => 'Hot dog Bavaria',
                'Хот-дог сырный' => 'Hot dog phô mai',
                'Шакшука' => 'Shakshuka',
                'Кофе молоко гренадин' => 'Cà phê sữa grenadine',
                'Кофе молоко малина' => 'Cà phê sữa mâm xôi',
                'картошка айдахо' => 'Khoai tây Idaho',
                'шарлотка манго' => 'Bánh Charlotte xoài',
                'Авокадо тост' => 'Bánh mì bơ',
                'Антипасти' => 'Antipasti',
                'Арбуз с Фетой' => 'Dưa hấu với phô mai Feta',
                'Боул с тунцом' => 'Bát cá ngừ',
                'брускетта Наполитано' => 'Bruschetta Napoli',
                'Будда боул' => 'Bát Buddha',
                'Бургер грибной' => 'Burger nấm',
                'Бургер Классик' => 'Burger cổ điển',
                'Бургер континенталь' => 'Burger lục địa',
                'Бургер Мега' => 'Burger Mega',
                'Греческий' => 'Salad Hy Lạp',
                'Камбуча Мята' => 'Kombucha bạc hà',
                'Камбуча Чабрец' => 'Kombucha húng tây',
                'Камбуча Маракуйя' => 'Kombucha chanh dây',
                'Revive белый' => 'Revive trắng',
                'Revive желтый' => 'Revive vàng',
                'Тоник' => 'Tonic',
                'Пиво лимончелло' => 'Bia Limoncello',
                'Банановый ликер' => 'Rượu chuối',
                'Водка' => 'Vodka',
                'Самбука' => 'Sambuca',
                'Текила' => 'Tequila',
                'Бейлиз' => 'Baileys',
                'Single Edition Sauvignon Blanc' => 'Single Edition Sauvignon Blanc'
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
     * Автоматический перевод описания продукта (базовые слова)
     */
    private function autoTranslateProductDescription($description, $language) {
        // Только базовые слова, которые могут встречаться в описаниях
        $translations = [
            'en' => [
                'с' => 'with',
                'и' => 'and',
                'или' => 'or',
                'без' => 'without'
            ],
            'vi' => [
                'с' => 'với',
                'и' => 'và',
                'или' => 'hoặc',
                'без' => 'không có'
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
