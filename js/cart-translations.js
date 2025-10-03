// Cart Translations Manager
class CartTranslations {
    constructor() {
        this.translations = {};
        this.language = 'ru';
        this.loaded = false;
    }

    async load() {
        if (this.loaded) {
            return this.translations;
        }

        try {
            const response = await fetch('/api/cart/translations.php');
            const data = await response.json();
            
            if (data.success) {
                this.translations = data.translations;
                this.language = data.language;
                this.loaded = true;
            } else {
                console.error('Failed to load cart translations:', data.error);
                this.setDefaultTranslations();
            }
        } catch (error) {
            console.error('Error loading cart translations:', error);
            this.setDefaultTranslations();
        }

        return this.translations;
    }

    setDefaultTranslations() {
        this.translations = {
            'cart_empty': 'Корзина пуста',
            'tables_not_found': 'Столы не найдены',
            'enter_name': 'Введите ваше имя',
            'enter_phone': 'Введите номер телефона',
            'enter_correct_phone': 'Введите корректный номер телефона',
            'select_table': 'Выберите номер стола',
            'enter_address': 'Введите адрес доставки',
            'select_delivery_time': 'Выберите время доставки',
            'delivery_time_too_soon': 'Мы не успеем так быстро, но постараемся!',
            'sending_order': 'Отправляем заказ...',
            'order_success': 'Заказ успешно отправлен!',
            'order_error': 'Ошибка при отправке заказа',
            'price_load_error': 'Ошибка загрузки цен товаров',
            'adding_to_existing_order': 'Добавляем товары к существующему заказу...',
            'added_to_existing_order': 'Товары успешно добавлены к существующему заказу!',
            'error_adding_to_existing_order': 'Ошибка при добавлении товаров к заказу'
        };
        this.language = 'ru';
        this.loaded = true;
    }

    get(key, fallback = null) {
        return this.translations[key] || fallback || `[${key}]`;
    }

    async getAsync(key, fallback = null) {
        if (!this.loaded) {
            await this.load();
        }
        return this.get(key, fallback);
    }
}

// Глобальный экземпляр
window.cartTranslations = new CartTranslations();
