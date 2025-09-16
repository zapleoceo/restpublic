<?php
/**
 * PageContentService - сервис для работы с полным HTML контентом страниц
 * Заменяет TranslationService для новой архитектуры
 */

require_once __DIR__ . '/../vendor/autoload.php';

class PageContentService {
    private $client;
    private $db;
    private $collection;
    private $currentLanguage;
    private $defaultLanguage = 'ru';
    private $availableLanguages = ['ru', 'en', 'vi'];
    // Кеширование отключено - используется Cloudflare

    public function __construct() {
        try {
            // Подключение к MongoDB
            $this->client = new MongoDB\Client($_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017');
            $this->db = $this->client->selectDatabase($_ENV['MONGODB_DB'] ?? 'northrepublic');
            $this->collection = $this->db->page_content;
            
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
            $document = $this->collection->findOne([
                'page' => $page,
                'language' => $language,
                'status' => 'published'
            ]);
            
            if ($document) {
                return [
                    'content' => $document['content'] ?? '',
                    'meta' => $document['meta'] ?? [],
                    'updated_at' => $document['updated_at'] ?? null
                ];
            }
            
            // Если не найден опубликованный контент, ищем черновик
            $draft = $this->collection->findOne([
                'page' => $page,
                'language' => $language,
                'status' => 'draft'
            ]);
            
            if ($draft) {
                return [
                    'content' => $draft['content'] ?? '',
                    'meta' => $draft['meta'] ?? [],
                    'updated_at' => $draft['updated_at'] ?? null
                ];
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
     * Сохранение контента страницы (для админки)
     */
    public function savePageContent($page, $language, $content, $meta = [], $status = 'draft', $updatedBy = 'admin') {
        try {
            $document = [
                'page' => $page,
                'language' => $language,
                'content' => $content,
                'meta' => array_merge([
                    'title' => '',
                    'description' => '',
                    'keywords' => ''
                ], $meta),
                'status' => $status,
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_by' => $updatedBy
            ];
            
            // Обновляем или создаем запись
            $result = $this->collection->replaceOne(
                ['page' => $page, 'language' => $language],
                $document,
                ['upsert' => true]
            );
            
            // Кеширование отключено - используется Cloudflare
            
            return $result->getUpsertedCount() > 0 || $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log("PageContentService savePageContent error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Публикация страницы
     */
    public function publishPage($page, $language, $updatedBy = 'admin') {
        try {
            // Сначала сохраняем как опубликованную
            $result = $this->collection->updateOne(
                ['page' => $page, 'language' => $language],
                [
                    '$set' => [
                        'status' => 'published',
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'updated_by' => $updatedBy
                    ]
                ]
            );
            
            // Кеширование отключено - используется Cloudflare
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log("PageContentService publishPage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение списка всех страниц
     */
    public function getAllPages() {
        try {
            $pages = $this->collection->distinct('page');
            return array_values($pages);
        } catch (Exception $e) {
            error_log("PageContentService getAllPages error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получение статистики страниц
     */
    public function getPagesStats() {
        try {
            $pipeline = [
                [
                    '$group' => [
                        '_id' => ['page' => '$page', 'language' => '$language'],
                        'status' => ['$first' => '$status'],
                        'updated_at' => ['$first' => '$updated_at'],
                        'updated_by' => ['$first' => '$updated_by']
                    ]
                ],
                [
                    '$group' => [
                        '_id' => '$_id.page',
                        'languages' => [
                            '$push' => [
                                'language' => '$_id.language',
                                'status' => '$status',
                                'updated_at' => '$updated_at',
                                'updated_by' => '$updated_by'
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $this->collection->aggregate($pipeline);
            return iterator_to_array($result);
            
        } catch (Exception $e) {
            error_log("PageContentService getPagesStats error: " . $e->getMessage());
            return [];
        }
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
