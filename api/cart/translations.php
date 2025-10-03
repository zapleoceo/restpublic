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
    
    // Получаем язык из параметра запроса, сессии или cookie
    $currentLanguage = 'ru'; // По умолчанию
    
    // 1. Проверяем параметр lang в URL
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['ru', 'en', 'vi'])) {
        $currentLanguage = $_GET['lang'];
    }
    // 2. Проверяем сессию
    elseif (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['language'])) {
        $currentLanguage = $_SESSION['language'];
    }
    // 3. Проверяем cookie
    elseif (isset($_COOKIE['language'])) {
        $currentLanguage = $_COOKIE['language'];
    }
    
    // Устанавливаем язык в TranslationService
    $translationService->currentLanguage = $currentLanguage;
    
    // Логируем текущий язык для отладки
    error_log("Cart translations API: Current language = " . $currentLanguage);
    
    // Получаем переводы для всех категорий
    $cartTranslations = $translationService->getCategory('cart');
    $validationTranslations = $translationService->getCategory('validation');
    $orderTranslations = $translationService->getCategory('order');
    $generalTranslations = $translationService->getCategory('general');
    
    // Объединяем все переводы
    $allTranslations = array_merge(
        $cartTranslations,
        $validationTranslations,
        $orderTranslations,
        $generalTranslations
    );
    
    // Если переводы не найдены или их мало, дополняем дефолтными на текущем языке
    if (empty($allTranslations) || count($allTranslations) < 10) {
        $defaultTranslations = getDefaultTranslations($currentLanguage);
        $allTranslations = array_merge($allTranslations, $defaultTranslations);
    }
    
    // Логируем количество переводов для отладки
    error_log("Cart translations API: Found " . count($allTranslations) . " translations for language " . $currentLanguage);
    
    echo json_encode([
        'success' => true,
        'translations' => $allTranslations,
        'language' => $currentLanguage
    ]);
    
} catch (Exception $e) {
    error_log('Cart translations API error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load translations',
        'translations' => getDefaultTranslations('ru'),
        'language' => 'ru'
    ]);
}

/**
 * Получить дефолтные переводы для языка
 */
function getDefaultTranslations($language) {
        $translations = [
            'ru' => [
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
                'error_adding_to_existing_order' => 'Ошибка при добавлении товаров к заказу',
                'your_order' => 'Ваш заказ',
                'for_table' => 'На столик',
                'takeaway' => 'С собой',
                'delivery' => 'Доставка',
                'total' => 'Итого:',
                'phone' => 'Телефон',
                'table' => 'Стол',
                'delivery_address' => 'Адрес доставки (ссылка на Google карту)',
                'delivery_address_placeholder' => 'https://maps.google.com/...',
                'delivery_time' => 'Время доставки',
                'comment' => 'Комментарий',
                'comment_placeholder' => 'Сюда можно написать все, что вы хотели бы, чтобы мы учли',
                'cancel' => 'Отмена',
                'place_order' => 'Оформить заказ',
                'enter_name_placeholder' => 'Введите ваше имя',
                'phone_placeholder' => '+'
            ],
            'en' => [
                'cart_empty' => 'Cart is empty',
                'tables_not_found' => 'Tables not found',
                'enter_name' => 'Enter your name',
                'enter_phone' => 'Enter phone number',
                'enter_correct_phone' => 'Enter correct phone number',
                'select_table' => 'Select table number',
                'enter_address' => 'Enter delivery address',
                'select_delivery_time' => 'Select delivery time',
                'delivery_time_too_soon' => 'We won\'t be able to deliver so quickly, but we\'ll try!',
                'sending_order' => 'Sending order...',
                'order_success' => 'Order successfully sent!',
                'order_error' => 'Error sending order',
                'price_load_error' => 'Error loading product prices',
                'adding_to_existing_order' => 'Adding items to existing order...',
                'added_to_existing_order' => 'Items successfully added to existing order!',
                'error_adding_to_existing_order' => 'Error adding items to order',
                'your_order' => 'Your Order',
                'for_table' => 'For Table',
                'takeaway' => 'Takeaway',
                'delivery' => 'Delivery',
                'total' => 'Total:',
                'phone' => 'Phone',
                'table' => 'Table',
                'delivery_address' => 'Delivery Address (Google Maps link)',
                'delivery_address_placeholder' => 'https://maps.google.com/...',
                'delivery_time' => 'Delivery Time',
                'comment' => 'Comment',
                'comment_placeholder' => 'You can write here everything you would like us to consider',
                'cancel' => 'Cancel',
                'place_order' => 'Place Order',
                'enter_name_placeholder' => 'Enter your name',
                'phone_placeholder' => '+'
            ],
            'vi' => [
                'cart_empty' => 'Giỏ hàng trống',
                'tables_not_found' => 'Không tìm thấy bàn',
                'enter_name' => 'Nhập tên của bạn',
                'enter_phone' => 'Nhập số điện thoại',
                'enter_correct_phone' => 'Nhập số điện thoại chính xác',
                'select_table' => 'Chọn số bàn',
                'enter_address' => 'Nhập địa chỉ giao hàng',
                'select_delivery_time' => 'Chọn thời gian giao hàng',
                'delivery_time_too_soon' => 'Chúng tôi không thể giao nhanh như vậy, nhưng sẽ cố gắng!',
                'sending_order' => 'Đang gửi đơn hàng...',
                'order_success' => 'Đơn hàng đã được gửi thành công!',
                'order_error' => 'Lỗi khi gửi đơn hàng',
                'price_load_error' => 'Lỗi tải giá sản phẩm',
                'adding_to_existing_order' => 'Đang thêm sản phẩm vào đơn hàng hiện tại...',
                'added_to_existing_order' => 'Sản phẩm đã được thêm vào đơn hàng hiện tại!',
                'error_adding_to_existing_order' => 'Lỗi khi thêm sản phẩm vào đơn hàng',
                'your_order' => 'Đơn hàng của bạn',
                'for_table' => 'Tại bàn',
                'takeaway' => 'Mang về',
                'delivery' => 'Giao hàng',
                'total' => 'Tổng cộng:',
                'phone' => 'Điện thoại',
                'table' => 'Bàn',
                'delivery_address' => 'Địa chỉ giao hàng (liên kết Google Maps)',
                'delivery_address_placeholder' => 'https://maps.google.com/...',
                'delivery_time' => 'Thời gian giao hàng',
                'comment' => 'Bình luận',
                'comment_placeholder' => 'Bạn có thể viết ở đây tất cả những gì bạn muốn chúng tôi xem xét',
                'cancel' => 'Hủy',
                'place_order' => 'Đặt hàng',
                'enter_name_placeholder' => 'Nhập tên của bạn',
                'phone_placeholder' => '+'
            ]
    ];
    
    return $translations[$language] ?? $translations['ru'];
}
?>
