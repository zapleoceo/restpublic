<?php
require_once __DIR__ . '/../vendor/autoload.php';

class TranslationService {
    private $client;
    private $db;
    private $textsCollection;
    private $currentLanguage;
    
    public function __construct() {
        try {
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
            
            $this->client = new MongoDB\Client($mongodbUrl);
            $this->db = $this->client->$dbName;
            $this->textsCollection = $this->db->admin_texts;
            
            // Устанавливаем язык по умолчанию
            $this->currentLanguage = $this->getCurrentLanguage();
        } catch (Exception $e) {
            error_log("Ошибка подключения к MongoDB в TranslationService: " . $e->getMessage());
            $this->currentLanguage = 'ru'; // Fallback
        }
    }
    
    /**
     * Получить текущий язык из сессии или cookie
     */
    public function getCurrentLanguage() {
        // Проверяем сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['language'])) {
            return $_SESSION['language'];
        }
        
        // Проверяем cookie
        if (isset($_COOKIE['language'])) {
            $lang = $_COOKIE['language'];
            if (in_array($lang, ['ru', 'en', 'vi'])) {
                $_SESSION['language'] = $lang;
                return $lang;
            }
        }
        
        // Проверяем Accept-Language заголовок
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            if (strpos($acceptLang, 'en') !== false) {
                return 'en';
            } elseif (strpos($acceptLang, 'vi') !== false) {
                return 'vi';
            }
        }
        
        // По умолчанию русский
        return 'ru';
    }
    
    /**
     * Установить язык
     */
    public function setLanguage($language) {
        if (!in_array($language, ['ru', 'en', 'vi'])) {
            return false;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['language'] = $language;
        setcookie('language', $language, time() + (365 * 24 * 60 * 60), '/'); // 1 год
        $this->currentLanguage = $language;
        
        return true;
    }
    
    /**
     * Получить перевод по ключу
     */
    public function get($key, $default = null) {
        try {
            $text = $this->textsCollection->findOne(['key' => $key]);
            
            if (!$text) {
                return $default ?? $key;
            }
            
            $translation = $text['translations'][$this->currentLanguage] ?? null;
            
            if (empty($translation)) {
                // Fallback на русский, если перевод отсутствует
                $translation = $text['translations']['ru'] ?? $default ?? $key;
            }
            
            return $translation;
        } catch (Exception $e) {
            error_log("Ошибка получения перевода для ключа '$key': " . $e->getMessage());
            return $default ?? $key;
        }
    }
    
    /**
     * Получить все переводы для категории
     */
    public function getCategory($category) {
        try {
            $texts = $this->textsCollection->find(['category' => $category])->toArray();
            $result = [];
            
            foreach ($texts as $text) {
                $key = $text['key'];
                $translation = $text['translations'][$this->currentLanguage] ?? null;
                
                if (empty($translation)) {
                    $translation = $text['translations']['ru'] ?? $key;
                }
                
                $result[$key] = $translation;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Ошибка получения категории переводов '$category': " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получить текущий язык
     */
    public function getLanguage() {
        return $this->currentLanguage;
    }
    
    /**
     * Получить список доступных языков
     */
    public function getAvailableLanguages() {
        return [
            'ru' => ['code' => 'ru', 'name' => 'Русский', 'flag' => '🇷🇺'],
            'en' => ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
            'vi' => ['code' => 'vi', 'name' => 'Tiếng Việt', 'flag' => '🇻🇳']
        ];
    }
    
    /**
     * Проверить, есть ли перевод для ключа
     */
    public function hasTranslation($key, $language = null) {
        $language = $language ?? $this->currentLanguage;
        
        try {
            $text = $this->textsCollection->findOne(['key' => $key]);
            return $text && !empty($text['translations'][$language]);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
