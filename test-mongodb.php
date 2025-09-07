<?php
echo "Testing MongoDB class...\n";
require_once 'vendor/autoload.php';
echo "MongoDB\Client exists: " . (class_exists('MongoDB\Client') ? 'YES' : 'NO') . "\n";
?>
