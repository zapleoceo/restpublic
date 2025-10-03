<?php
require_once __DIR__ . '/../vendor/autoload.php';

class TablesCache {
    private $client;
    private $db;
    private $tablesCollection;
    
    public function __construct() {
        try {
            $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
            $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
            
            $this->client = new MongoDB\Client($mongodbUrl);
            $this->db = $this->client->$dbName;
            $this->tablesCollection = $this->db->tables;
        } catch (Exception $e) {
            error_log("Ошибка подключения к MongoDB для столов: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Получить все столы из MongoDB
     */
    public function getTables() {
        try {
            $tables = $this->tablesCollection->find([], [
                'sort' => ['name' => 1] // Сортируем по названию
            ]);
            
            $tablesArray = [];
            foreach ($tables as $table) {
                $tablesArray[] = [
                    'id' => (string)$table['_id'],
                    'table_id' => $table['table_id'] ?? (string)$table['_id'],
                    'name' => $table['name'] ?? $table['table_name'] ?? 'Стол ' . ($table['table_id'] ?? (string)$table['_id']),
                    'capacity' => $table['capacity'] ?? null,
                    'status' => $table['status'] ?? 'available'
                ];
            }
            
            return $tablesArray;
        } catch (Exception $e) {
            error_log("Ошибка получения столов из MongoDB: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Получить стол по ID
     */
    public function getTableById($tableId) {
        try {
            $table = $this->tablesCollection->findOne(['table_id' => $tableId]);
            
            if ($table) {
                return [
                    'id' => (string)$table['_id'],
                    'table_id' => $table['table_id'] ?? (string)$table['_id'],
                    'name' => $table['name'] ?? $table['table_name'] ?? 'Стол ' . ($table['table_id'] ?? (string)$table['_id']),
                    'capacity' => $table['capacity'] ?? null,
                    'status' => $table['status'] ?? 'available'
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Ошибка получения стола по ID: " . $e->getMessage());
            return null;
        }
    }
}
