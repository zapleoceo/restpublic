<?php
/**
 * Конфигурация подключения к MongoDB с поддержкой основной и резервной БД
 * Основная БД: veranda2026 (порт 27026)
 * Резервная БД: veranda (порт 27017) - для fallback
 */

class DatabaseConfig {
    // Основная БД (новая)
    const PRIMARY_DB_NAME = 'veranda2026';
    const PRIMARY_DB_PORT = 27026;
    const PRIMARY_DB_URL = 'mongodb://localhost:27026';
    
    // Резервная БД (старая, для fallback)
    const FALLBACK_DB_NAME = 'veranda';
    const FALLBACK_DB_PORT = 27017;
    const FALLBACK_DB_URL = 'mongodb://localhost:27017';
    
    /**
     * Получить конфигурацию основной БД
     */
    public static function getPrimaryConfig() {
        // Проверяем переменные окружения, если они заданы - используем их
        $url = $_ENV['MONGODB_URL'] ?? self::PRIMARY_DB_URL;
        $name = $_ENV['MONGODB_DB_NAME'] ?? self::PRIMARY_DB_NAME;
        
        return [
            'url' => $url,
            'name' => $name,
            'port' => self::PRIMARY_DB_PORT
        ];
    }
    
    /**
     * Получить конфигурацию резервной БД
     */
    public static function getFallbackConfig() {
        return [
            'url' => self::FALLBACK_DB_URL,
            'name' => self::FALLBACK_DB_NAME,
            'port' => self::FALLBACK_DB_PORT
        ];
    }
    
    /**
     * Подключиться к БД с fallback
     * Возвращает массив [client, db, isPrimary]
     */
    public static function connectWithFallback() {
        $primaryConfig = self::getPrimaryConfig();
        $fallbackConfig = self::getFallbackConfig();
        
        // Пытаемся подключиться к основной БД
        try {
            $client = new MongoDB\Client($primaryConfig['url']);
            $db = $client->selectDatabase($primaryConfig['name']);
            
            // Проверяем подключение
            $db->command(['ping' => 1]);
            
            return [$client, $db, true, $primaryConfig['name']];
        } catch (Exception $e) {
            error_log("⚠️ Основная БД недоступна ({$primaryConfig['name']}): " . $e->getMessage());
            
            // Пытаемся подключиться к резервной БД
            try {
                $client = new MongoDB\Client($fallbackConfig['url']);
                $db = $client->selectDatabase($fallbackConfig['name']);
                
                // Проверяем подключение
                $db->command(['ping' => 1]);
                
                error_log("✅ Используется резервная БД: {$fallbackConfig['name']}");
                return [$client, $db, false, $fallbackConfig['name']];
            } catch (Exception $e2) {
                error_log("❌ Резервная БД также недоступна: " . $e2->getMessage());
                throw new Exception("Обе БД недоступны. Primary: {$e->getMessage()}, Fallback: {$e2->getMessage()}");
            }
        }
    }
    
    /**
     * Получить коллекцию с fallback
     */
    public static function getCollection($collectionName) {
        list($client, $db, $isPrimary, $dbName) = self::connectWithFallback();
        
        return [
            'collection' => $db->selectCollection($collectionName),
            'client' => $client,
            'db' => $db,
            'isPrimary' => $isPrimary,
            'dbName' => $dbName
        ];
    }
}

