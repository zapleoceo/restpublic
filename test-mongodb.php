<?php
require_once 'vendor/autoload.php';

try {
    echo "Testing MongoDB connection...\n";
    $client = new MongoDB\Client("mongodb://localhost:27018");
    $db = $client->northrepublic;
    $collection = $db->menu;
    
    // Test insert
    $result = $collection->insertOne([
        '_id' => 'test_' . time(),
        'test' => 'data',
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);
    
    echo "Insert successful: " . $result->getInsertedId() . "\n";
    
    // Test find
    $doc = $collection->findOne(['_id' => 'current_menu']);
    echo "Current menu exists: " . ($doc ? 'YES' : 'NO') . "\n";
    
    if ($doc) {
        echo "Categories: " . count($doc['categories'] ?? []) . "\n";
        echo "Products: " . count($doc['products'] ?? []) . "\n";
    }
    
} catch (Exception $e) {
    echo "MongoDB error: " . $e->getMessage() . "\n";
}
?>
