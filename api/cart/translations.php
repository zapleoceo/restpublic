<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обрабатываем preflight OPTIONS запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../classes/TranslationService.php';

try {
    $translationService = new TranslationService();
    
    // Получаем переводы для категории "cart"
    $cartTranslations = $translationService->getCategory('cart');
    
    // Если переводы не найдены, создаем дефолтные
    if (empty($cartTranslations)) {
        $cartTranslations = [
            'cart_empty' => 'Корзина пуста',
            'tables_not_found' => 'Столы не найдены',
            'enter_name' => 'Введите ваше имя',
            'enter_phone' => 'Введите номер телефона',
            'enter_correct_phone' => 'Введите корректный номер телефона',
            'select_table' => 'Выберите номер стола',
            'enter_address' => 'Введите адрес доставки',
            'select_delivery_time' => 'Выберите время доставки',
            'delivery_time_too_soon' => 'Мы не успеем так быстро, но постараемся!',
            'sending_order' => 'Отправляем заказ...',
            'order_success' => 'Заказ успешно отправлен!',
            'order_error' => 'Ошибка при отправке заказа',
            'price_load_error' => 'Ошибка загрузки цен товаров',
            'adding_to_existing_order' => 'Добавляем товары к существующему заказу...',
            'added_to_existing_order' => 'Товары успешно добавлены к существующему заказу!',
            'error_adding_to_existing_order' => 'Ошибка при добавлении товаров к заказу'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'translations' => $cartTranslations,
        'language' => $translationService->getLanguage()
    ]);
    
} catch (Exception $e) {
    error_log('Cart translations API error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load translations',
        'translations' => [
            'cart_empty' => 'Корзина пуста',
            'tables_not_found' => 'Столы не найдены',
            'enter_name' => 'Введите ваше имя',
            'enter_phone' => 'Введите номер телефона',
            'enter_correct_phone' => 'Введите корректный номер телефона',
            'select_table' => 'Выберите номер стола',
            'enter_address' => 'Введите адрес доставки',
            'select_delivery_time' => 'Выберите время доставки',
            'delivery_time_too_soon' => 'Мы не успеем так быстро, но постараемся!',
            'sending_order' => 'Отправляем заказ...',
            'order_success' => 'Заказ успешно отправлен!',
            'order_error' => 'Ошибка при отправке заказа',
            'price_load_error' => 'Ошибка загрузки цен товаров',
            'adding_to_existing_order' => 'Добавляем товары к существующему заказу...',
            'added_to_existing_order' => 'Товары успешно добавлены к существующему заказу!',
            'error_adding_to_existing_order' => 'Ошибка при добавлении товаров к заказу'
        ],
        'language' => 'ru'
    ]);
}
?>
