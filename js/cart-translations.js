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
            // Получаем текущий язык из URL или cookie
            const currentLang = this.getCurrentLanguage();
            const response = await fetch(`/api/cart/translations.php?lang=${currentLang}`);
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

    getCurrentLanguage() {
        // 1. Проверяем параметр lang в URL
        const urlParams = new URLSearchParams(window.location.search);
        const langFromUrl = urlParams.get('lang');
        if (langFromUrl && ['ru', 'en', 'vi'].includes(langFromUrl)) {
            return langFromUrl;
        }
        
        // 2. Проверяем cookie
        const langFromCookie = document.cookie
            .split('; ')
            .find(row => row.startsWith('language='))
            ?.split('=')[1];
        if (langFromCookie && ['ru', 'en', 'vi'].includes(langFromCookie)) {
            return langFromCookie;
        }
        
        // 3. Проверяем Accept-Language заголовок
        const acceptLang = navigator.language || navigator.userLanguage;
        if (acceptLang) {
            if (acceptLang.startsWith('en')) return 'en';
            if (acceptLang.startsWith('vi')) return 'vi';
            if (acceptLang.startsWith('ru')) return 'ru';
        }
        
        // По умолчанию русский
        return 'ru';
    }
}

// Глобальный экземпляр
window.cartTranslations = new CartTranslations();
