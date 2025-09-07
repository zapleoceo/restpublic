<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class MenuCache {
    private $client;
    private $db;
    private $menuCollection;
    
    public function __construct() {
        try {
            $this->client = new MongoDB\Client("mongodb://localhost:27018");
            $this->db = $this->client->northrepublic;
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
     * Получить продукты по категории
     */
    public function getProductsByCategory($categoryId, $limit = 5) {
        $menu = $this->getMenu();
        
        if (!$menu) {
            return [];
        }
        
        $products = $menu['products'] ?? [];
        $categoryProducts = [];
        
        foreach ($products as $product) {
            if (($product['menu_category_id'] ?? $product['category_id']) == $categoryId) {
                $categoryProducts[] = $product;
                if (count($categoryProducts) >= $limit) {
                    break;
                }
            }
        }
        
        return $categoryProducts;
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
     * Обновить меню в фоне через API
     */
    private function updateMenuInBackground() {
        try {
            // Асинхронный запрос к нашему API для обновления кэша
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://northrepublic.me:3002/api/cache/update-menu');
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
}
?>
