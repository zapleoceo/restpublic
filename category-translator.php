<?php
/**
 * Функция для перевода названий категорий
 */
function translateCategoryName($categoryName, $language = 'ru') {
    $translations = [
        'Food' => [
            'ru' => 'Еда',
            'en' => 'Food',
            'vi' => 'Thức ăn'
        ],
        'Beverages' => [
            'ru' => 'Напитки',
            'en' => 'Beverages',
            'vi' => 'Đồ uống'
        ],
        'Alcohol' => [
            'ru' => 'Алкоголь',
            'en' => 'Alcohol',
            'vi' => 'Rượu'
        ],
        'Hot drinks' => [
            'ru' => 'Горячие напитки',
            'en' => 'Hot drinks',
            'vi' => 'Đồ uống nóng'
        ],
        'Hookah' => [
            'ru' => 'Кальян',
            'en' => 'Hookah',
            'vi' => 'Shisha'
        ]
    ];
    
    if (isset($translations[$categoryName][$language])) {
        return $translations[$categoryName][$language];
    }
    
    // Fallback на оригинальное название
    return $categoryName;
}

/**
 * Получить текущий язык пользователя
 */
function getCurrentLanguage() {
    // Проверяем сессию
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    // Проверяем cookie
    if (isset($_COOKIE['language'])) {
        $lang = $_COOKIE['language'];
        if (in_array($lang, ['ru', 'en', 'vi'])) {
            $_SESSION['language'] = $lang;
            return $lang;
        }
    }
    
    // По умолчанию русский
    return 'ru';
}
?>
