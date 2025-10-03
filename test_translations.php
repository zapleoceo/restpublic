<?php
require_once 'classes/TranslationService.php';

echo "=== Translation System Test ===\n";

try {
    $ts = new TranslationService();
    echo "Current language: " . $ts->getLanguage() . "\n";
    
    // Test cart translations
    $cartTranslations = $ts->getCategory('cart');
    echo "Cart translations count: " . count($cartTranslations) . "\n";
    
    if (!empty($cartTranslations)) {
        echo "Sample cart translations:\n";
        foreach (array_slice($cartTranslations, 0, 3, true) as $key => $value) {
            echo "  $key: $value\n";
        }
    } else {
        echo "No cart translations found!\n";
    }
    
    // Test validation translations
    $validationTranslations = $ts->getCategory('validation');
    echo "Validation translations count: " . count($validationTranslations) . "\n";
    
    // Test order translations
    $orderTranslations = $ts->getCategory('order');
    echo "Order translations count: " . count($orderTranslations) . "\n";
    
    // Test general translations
    $generalTranslations = $ts->getCategory('general');
    echo "General translations count: " . count($generalTranslations) . "\n";
    
    // Test single translation
    $testTranslation = $ts->get('cart_empty', 'Default cart empty text');
    echo "Test translation for 'cart_empty': $testTranslation\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
