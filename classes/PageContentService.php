<?php
/**
 * PageContentService - сервис для работы с полным HTML контентом страниц
 * Заменяет TranslationService для новой архитектуры
 */

// require_once __DIR__ . '/../vendor/autoload.php'; // Убрано для локального тестирования

class PageContentService {
    private $currentLanguage;
    private $defaultLanguage = 'ru';
    private $availableLanguages = ['ru', 'en', 'vi'];
    // Кеширование отключено - используется Cloudflare

    public function __construct() {
        try {
            // Определяем текущий язык
            $this->currentLanguage = $this->detectLanguage();
            
        } catch (Exception $e) {
            error_log("PageContentService error: " . $e->getMessage());
            $this->currentLanguage = $this->defaultLanguage;
        }
    }

    /**
     * Определение языка пользователя
     */
    private function detectLanguage() {
        // 1. Проверяем параметр lang в URL (приоритет)
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->availableLanguages)) {
            // Сохраняем в сессию для последующих запросов
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['language'] = $_GET['lang'];
            return $_GET['lang'];
        }
        
        // 2. Проверяем сессию
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->availableLanguages)) {
            return $_SESSION['language'];
        }
        
        // 3. Проверяем cookie
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->availableLanguages)) {
            return $_COOKIE['language'];
        }
        
        // 4. Проверяем Accept-Language заголовок браузера
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            
            // Парсим заголовок Accept-Language
            $languages = [];
            $parts = explode(',', $acceptLang);
            
            foreach ($parts as $part) {
                $part = trim($part);
                if (strpos($part, ';') !== false) {
                    list($lang, $q) = explode(';', $part, 2);
                    $q = floatval(str_replace('q=', '', $q));
                } else {
                    $lang = $part;
                    $q = 1.0;
                }
                
                $lang = strtolower(trim($lang));
                $languages[$lang] = $q;
            }
            
            // Сортируем по приоритету (q-value)
            arsort($languages);
            
            // Ищем поддерживаемые языки
            foreach ($languages as $lang => $q) {
                if (strpos($lang, 'en') === 0) {
                    return 'en';
                } elseif (strpos($lang, 'vi') === 0) {
                    return 'vi';
                } elseif (strpos($lang, 'ru') === 0) {
                    return 'ru';
                }
            }
        }
        
        // 4. Возвращаем язык по умолчанию
        return $this->defaultLanguage;
    }

    /**
     * Установка языка
     */
    public function setLanguage($lang) {
        if (!in_array($lang, $this->availableLanguages)) {
            return false;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/'); // 1 год
        
        $this->currentLanguage = $lang;
        
        return true;
    }

    /**
     * Получение текущего языка
     */
    public function getLanguage() {
        return $this->currentLanguage;
    }

    /**
     * Получение полного HTML контента страницы
     */
    public function getPageContent($page, $language = null) {
        $language = $language ?? $this->currentLanguage;
        
        try {
            // Используем backend API вместо прямого подключения к MongoDB
            $apiBaseUrl = $_ENV['BACKEND_URL'] ?? 'http://localhost:3003';
            $apiUrl = $apiBaseUrl . '/api/page-content/' . urlencode($page) . '/' . urlencode($language);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json'
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if ($data && !isset($data['error'])) {
                    return [
                        'content' => $data['content'] ?? '',
                        'meta' => $data['meta'] ?? [],
                        'updated_at' => $data['updated_at'] ?? null
                    ];
                }
            }
            
            // Если ничего не найдено, возвращаем пустой контент
            return [
                'content' => '<div class="alert alert-warning">Контент не найден</div>',
                'meta' => [],
                'updated_at' => null
            ];
            
        } catch (Exception $e) {
            error_log("PageContentService getPageContent error: " . $e->getMessage());
            return [
                'content' => '<div class="alert alert-error">Ошибка загрузки контента</div>',
                'meta' => [],
                'updated_at' => null
            ];
        }
    }

    /**
     * Сохранение контента страницы (для админки) - отключено, используется backend API
     */
    public function savePageContent($page, $language, $content, $meta = [], $status = 'draft', $updatedBy = 'admin') {
        // Метод отключен - используется backend API для сохранения
        error_log("PageContentService savePageContent: Method disabled, use backend API");
        return false;
    }

    /**
     * Публикация страницы - отключено, используется backend API
     */
    public function publishPage($page, $language, $updatedBy = 'admin') {
        // Метод отключен - используется backend API для публикации
        error_log("PageContentService publishPage: Method disabled, use backend API");
        return false;
    }

    /**
     * Получение списка всех страниц - отключено, используется backend API
     */
    public function getAllPages() {
        // Метод отключен - используется backend API
        error_log("PageContentService getAllPages: Method disabled, use backend API");
        return [];
    }

    /**
     * Получение статистики страниц - отключено, используется backend API
     */
    public function getPagesStats() {
        // Метод отключен - используется backend API
        error_log("PageContentService getPagesStats: Method disabled, use backend API");
        return [];
    }


    /**
     * Получение доступных языков
     */
    public function getAvailableLanguages() {
        return $this->availableLanguages;
    }

    /**
     * Получение мета-информации страницы
     */
    public function getPageMeta($page, $language = null) {
        $language = $language ?? $this->currentLanguage;
        $pageContent = $this->getPageContent($page, $language);
        return $pageContent['meta'] ?? [];
    }

}
?>
