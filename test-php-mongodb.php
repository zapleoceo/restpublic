<?php
require_once 'vendor/autoload.php';

echo "Testing PHP MongoDB extension...\n";

if (extension_loaded('mongodb')) {
    echo "✅ MongoDB extension is loaded\n";
    
    try {
        $client = new MongoDB\Client("mongodb://localhost:27018");
        echo "✅ MongoDB client created\n";
        
        $db = $client->northrepublic;
        $collection = $db->menu;
        
        $doc = $collection->findOne(['_id' => 'current_menu']);
        if ($doc) {
            echo "✅ Found menu in MongoDB\n";
            echo "Categories: " . count($doc['categories'] ?? []) . "\n";
            echo "Products: " . count($doc['products'] ?? []) . "\n";
        } else {
            echo "❌ No menu found in MongoDB\n";
        }
        
    } catch (Exception $e) {
        echo "❌ MongoDB error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ MongoDB extension is NOT loaded\n";
}

echo "PHP version: " . PHP_VERSION . "\n";
echo "Loaded extensions: " . implode(', ', get_loaded_extensions()) . "\n";
?>
