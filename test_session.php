<?php
session_start();

echo "=== Session Test ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session language: " . (isset($_SESSION['language']) ? $_SESSION['language'] : 'not set') . "\n";
echo "Cookie language: " . (isset($_COOKIE['language']) ? $_COOKIE['language'] : 'not set') . "\n";

// Test setting language
$_SESSION['language'] = 'en';
echo "Set session language to: en\n";
echo "Session language after setting: " . $_SESSION['language'] . "\n";
?>
