<?php
require_once 'classes/TranslationService.php';

echo "=== Language Change Test ===\n";

try {
    // Test setting language to English
    $ts = new TranslationService();
    echo "Initial language: " . $ts->getLanguage() . "\n";
    
    // Set language to English
    $result = $ts->setLanguage('en');
    echo "Set language result: " . ($result ? 'true' : 'false') . "\n";
    
    // Check current language after setting
    $currentLang = $ts->getLanguage();
    echo "Current language after setting: " . $currentLang . "\n";
    
    // Test translations in English
    $cartTranslations = $ts->getCategory('cart');
    echo "Cart translations count for EN: " . count($cartTranslations) . "\n";
    
    if (!empty($cartTranslations)) {
        echo "Sample EN cart translations:\n";
        foreach (array_slice($cartTranslations, 0, 3, true) as $key => $value) {
            echo "  $key: $value\n";
        }
    }
    
    // Test single translation
    $testTranslation = $ts->get('cart_empty', 'Default cart empty text');
    echo "Test translation for 'cart_empty' in EN: $testTranslation\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
