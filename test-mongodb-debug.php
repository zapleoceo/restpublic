<?php
require_once 'vendor/autoload.php';

try {
    $client = new MongoDB\Client('mongodb://localhost:27018');
    $db = $client->northrepublic;
    $collection = $db->menu;
    $result = $collection->findOne(['_id' => 'current_menu']);
    
    if ($result) {
        echo "MongoDB connected, menu data found:\n";
        echo "Categories: " . count($result['categories'] ?? []) . "\n";
        echo "Products: " . count($result['products'] ?? []) . "\n";
        echo "Updated at: " . ($result['updated_at'] ?? 'unknown') . "\n";
        
        if (!empty($result['categories'])) {
            echo "First category: " . ($result['categories'][0]['category_name'] ?? $result['categories'][0]['name'] ?? 'no name') . "\n";
        }
    } else {
        echo "MongoDB connected, but no menu data found\n";
    }
} catch (Exception $e) {
    echo "MongoDB error: " . $e->getMessage() . "\n";
}
?>
