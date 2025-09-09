<?php
require_once "vendor/autoload.php";
echo class_exists("MongoDB\Client") ? "OK" : "FAIL";
?>
