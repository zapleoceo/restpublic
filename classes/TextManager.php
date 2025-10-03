<?php
/**
 * Класс для управления текстами сайта из базы данных
 * Поддерживает мультиязычность с fallback на русский
 */
class TextManager {
    private $db;
    private $language;
    private $texts = [];
    private $cache = [];
    
    public function __construct($language = null) {
        try {
            // Загружаем переменные окружения
            if (file_exists(__DIR__ . '/../.env')) {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
                $dotenv->load();
            }
            
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
            
            $client = new MongoDB\Client($mongodbUrl);
            $this->db = $client->$dbName;
            
            // Определяем язык
            if ($language) {
                $this->language = $language;
            } else {
                $this->language = $this->detectLanguage();
            }
            
            // Загружаем все тексты в кэш
            $this->loadTexts();
            
        } catch (Exception $e) {
            error_log("TextManager error: " . $e->getMessage());
            $this->language = 'ru'; // Fallback на русский
        }
    }
    
    /**
     * Определяет язык пользователя
     */
    private function detectLanguage() {
        // 1. Проверяем параметр URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['ru', 'en', 'vi'])) {
            return $_GET['lang'];
        }
        
        // 2. Проверяем сессию
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], ['ru', 'en', 'vi'])) {
            return $_SESSION['language'];
        }
        
        // 3. Определяем по языку браузера
        $browserLang = $this->getBrowserLanguage();
        if (in_array($browserLang, ['ru', 'en', 'vi'])) {
            return $browserLang;
        }
        
        // 4. Fallback на русский
        return 'ru';
    }
    
    /**
     * Получает язык браузера
     */
    private function getBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return 'ru';
        }
        
        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $primaryLang = explode('-', $languages[0])[0];
        
        // Маппинг языков
        $langMap = [
            'ru' => 'ru',
            'en' => 'en', 
            'vi' => 'vi'
        ];
        
        return $langMap[$primaryLang] ?? 'ru';
    }
    
    /**
     * Загружает все тексты из базы данных
     */
    private function loadTexts() {
        try {
            $textsCollection = $this->db->admin_texts;
            $texts = $textsCollection->find(['published' => true])->toArray();
            
            foreach ($texts as $text) {
                $this->texts[$text['key']] = $text['translations'];
            }
            
        } catch (Exception $e) {
            error_log("Error loading texts: " . $e->getMessage());
        }
    }
    
    /**
     * Получает текст по ключу
     */
    public function get($key, $default = null) {
        // Проверяем кэш
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        
        // Получаем текст
        $text = $this->getTextFromDB($key, $default);
        
        // Кэшируем
        $this->cache[$key] = $text;
        
        return $text;
    }
    
    /**
     * Получает текст из базы данных с fallback
     */
    private function getTextFromDB($key, $default = null) {
        if (!isset($this->texts[$key])) {
            return $default ?? "[$key]";
        }
        
        $translations = $this->texts[$key];
        
        // 1. Пытаемся получить текст на текущем языке
        if (!empty($translations[$this->language])) {
            return $translations[$this->language];
        }
        
        // 2. Fallback на русский
        if (!empty($translations['ru'])) {
            return $translations['ru'];
        }
        
        // 3. Fallback на английский
        if (!empty($translations['en'])) {
            return $translations['en'];
        }
        
        // 4. Fallback на вьетнамский
        if (!empty($translations['vi'])) {
            return $translations['vi'];
        }
        
        // 5. Возвращаем ключ или дефолтное значение
        return $default ?? "[$key]";
    }
    
    /**
     * Получает текущий язык
     */
    public function getCurrentLanguage() {
        return $this->language;
    }
    
    /**
     * Устанавливает язык
     */
    public function setLanguage($language) {
        if (in_array($language, ['ru', 'en', 'vi'])) {
            $this->language = $language;
            $_SESSION['language'] = $language;
            $this->cache = []; // Очищаем кэш
        }
    }
    
    /**
     * Получает все доступные языки
     */
    public function getAvailableLanguages() {
        return [
            'ru' => 'Русский',
            'en' => 'English', 
            'vi' => 'Tiếng Việt'
        ];
    }
    
    /**
     * Проверяет, есть ли перевод для ключа
     */
    public function hasTranslation($key, $language = null) {
        $lang = $language ?? $this->language;
        
        if (!isset($this->texts[$key])) {
            return false;
        }
        
        return !empty($this->texts[$key][$lang]);
    }
    
    /**
     * Получает статистику переводов
     */
    public function getTranslationStats() {
        $stats = [
            'total' => count($this->texts),
            'complete' => 0,
            'incomplete' => 0,
            'by_language' => ['ru' => 0, 'en' => 0, 'vi' => 0]
        ];
        
        foreach ($this->texts as $text) {
            $hasAll = true;
            
            foreach (['ru', 'en', 'vi'] as $lang) {
                if (!empty($text[$lang])) {
                    $stats['by_language'][$lang]++;
                } else {
                    $hasAll = false;
                }
            }
            
            if ($hasAll) {
                $stats['complete']++;
            } else {
                $stats['incomplete']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Получает тексты по категории
     */
    public function getTextsByCategory($category) {
        try {
            $textsCollection = $this->db->admin_texts;
            $texts = $textsCollection->find([
                'category' => $category,
                'published' => true
            ])->toArray();
            
            $result = [];
            foreach ($texts as $text) {
                $result[$text['key']] = $this->get($text['key']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error getting texts by category: " . $e->getMessage());
            return [];
        }
    }
}

// Глобальная функция для удобства
function t($key, $default = null) {
    global $textManager;
    if (!$textManager) {
        $textManager = new TextManager();
    }
    return $textManager->get($key, $default);
}

// Инициализируем глобальный менеджер текстов
$textManager = new TextManager();
?>
