<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/DatabaseConfig.php';

class SettingsService {
    private $client;
    private $db;
    private $settingsCollection;
    private $isPrimaryDb;
    
    public function __construct() {
        try {
            $config = DatabaseConfig::getCollection('settings');
            $this->settingsCollection = $config['collection'];
            $this->client = $config['client'];
            $this->db = $config['db'];
            $this->isPrimaryDb = $config['isPrimary'];
            
            if (!$this->isPrimaryDb) {
                error_log("⚠️ SettingsService: Используется резервная БД");
            }
        } catch (Exception $e) {
            error_log("Ошибка подключения к MongoDB в SettingsService: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Получить настройку по ключу
     */
    public function getSetting($key, $default = null) {
        try {
            $setting = $this->settingsCollection->findOne(['key' => $key]);
            return $setting ? $setting['value'] : $default;
        } catch (Exception $e) {
            error_log("Ошибка получения настройки '$key': " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Установить настройку
     */
    public function setSetting($key, $value) {
        try {
            $result = $this->settingsCollection->replaceOne(
                ['key' => $key],
                [
                    'key' => $key,
                    'value' => $value,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ],
                ['upsert' => true]
            );
            
            return $result->getModifiedCount() > 0 || $result->getUpsertedCount() > 0;
        } catch (Exception $e) {
            error_log("Ошибка установки настройки '$key': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить время последнего обновления меню
     */
    public function getLastMenuUpdateTime() {
        $timestamp = $this->getSetting('menu_last_update_time');
        return $timestamp ? (int)$timestamp : null;
    }
    
    /**
     * Установить время последнего обновления меню
     */
    public function setLastMenuUpdateTime($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return $this->setSetting('menu_last_update_time', $timestamp);
    }
    
    /**
     * Получить время последней проверки необходимости обновления
     */
    public function getLastUpdateCheckTime() {
        $timestamp = $this->getSetting('menu_last_check_time');
        return $timestamp ? (int)$timestamp : null;
    }
    
    /**
     * Установить время последней проверки необходимости обновления
     */
    public function setLastUpdateCheckTime($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return $this->setSetting('menu_last_check_time', $timestamp);
    }
    
    /**
     * Проверить, нужно ли обновлять меню (раз в час)
     */
    public function shouldUpdateMenu($maxAgeSeconds = 3600) {
        $lastUpdate = $this->getLastMenuUpdateTime();
        
        if (!$lastUpdate) {
            return true; // Если никогда не обновлялось
        }
        
        $currentTime = time();
        $timeDiff = $currentTime - $lastUpdate;
        
        return $timeDiff >= $maxAgeSeconds;
    }
    
    /**
     * Проверить, нужно ли проверять необходимость обновления (раз в 5 минут)
     */
    public function shouldCheckForUpdate($maxAgeSeconds = 300) {
        $lastCheck = $this->getLastUpdateCheckTime();
        
        if (!$lastCheck) {
            return true; // Если никогда не проверялось
        }
        
        $currentTime = time();
        $timeDiff = $currentTime - $lastCheck;
        
        return $timeDiff >= $maxAgeSeconds;
    }
    
    /**
     * Получить время последнего обновления в формате для отображения
     */
    public function getLastMenuUpdateTimeFormatted() {
        $timestamp = $this->getLastMenuUpdateTime();
        
        if (!$timestamp) {
            return null;
        }
        
        $updateTime = new DateTime();
        $updateTime->setTimestamp($timestamp);
        
        // Устанавливаем часовой пояс Нячанга (UTC+7)
        $updateTime->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        
        return $updateTime->format('d.m.Y H:i');
    }
    
    /**
     * Получить статистику обновлений
     */
    public function getUpdateStats() {
        $lastUpdate = $this->getLastMenuUpdateTime();
        $lastCheck = $this->getLastUpdateCheckTime();
        
        $stats = [
            'last_update_time' => $lastUpdate,
            'last_update_formatted' => $this->getLastMenuUpdateTimeFormatted(),
            'last_check_time' => $lastCheck,
            'should_update' => $this->shouldUpdateMenu(),
            'should_check' => $this->shouldCheckForUpdate()
        ];
        
        if ($lastUpdate) {
            $stats['time_since_update'] = time() - $lastUpdate;
            $stats['time_since_update_formatted'] = $this->formatTimeDiff(time() - $lastUpdate);
        }
        
        if ($lastCheck) {
            $stats['time_since_check'] = time() - $lastCheck;
            $stats['time_since_check_formatted'] = $this->formatTimeDiff(time() - $lastCheck);
        }
        
        return $stats;
    }
    
    /**
     * Форматировать разность времени в читаемый вид
     */
    private function formatTimeDiff($seconds) {
        if ($seconds < 60) {
            return $seconds . ' сек.';
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . ' мин.';
        } elseif ($seconds < 86400) {
            return floor($seconds / 3600) . ' ч.';
        } else {
            return floor($seconds / 86400) . ' дн.';
        }
    }
}
?>
