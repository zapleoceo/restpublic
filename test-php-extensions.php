<?php
echo "MongoDB extension loaded: " . (extension_loaded('mongodb') ? 'YES' : 'NO') . "\n";
echo "MongoDB\\Client class exists: " . (class_exists('MongoDB\\Client') ? 'YES' : 'NO') . "\n";

if (class_exists('MongoDB\\Client')) {
    echo "Trying to create MongoDB client...\n";
    try {
        $client = new MongoDB\Client('mongodb://localhost:27018');
        echo "MongoDB client created successfully\n";
    } catch (Exception $e) {
        echo "MongoDB client error: " . $e->getMessage() . "\n";
    }
} else {
    echo "MongoDB\\Client class not available\n";
}
?>
