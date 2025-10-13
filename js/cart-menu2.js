// Cart functionality for menu2.php with order/check management
class CartMenu2 {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('cart') || '[]');
        if (!Array.isArray(this.items)) {
            this.items = [];
        }

        // Флаг для предотвращения дублирования заказов
        this.isSubmittingOrder = false;

        // Хранение номера текущего чека
        this.currentOrderId = localStorage.getItem('currentOrderId') || null;

        // Инициализируем переводы
        this.translations = null;
        this.init();
    }
    
    // Функция для форматирования чисел с пробелами
    formatNumber(num) {
        // Обрабатываем null, undefined и NaN
        if (num === null || num === undefined || isNaN(num)) {
            console.warn('⚠️ formatNumber received invalid value:', num);
            return '0';
        }
        return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    // Мгновенные API запросы без ограничений
    async executeApiCall(apiCall) {
        return await apiCall();
    }
    
    async init() {
        // Загружаем переводы
        await this.loadTranslations();
        this.bindEvents();
        this.updateCartDisplay();
        
        // Обновляем переводы модального окна корзины
        this.updateCartModalTranslations();
        
        // Принудительно обновляем переводы при инициализации
        setTimeout(async () => {
            await this.reloadTranslations();
            // Повторно обновляем переводы модального окна после перезагрузки
            this.updateCartModalTranslations();
        }, 100);
        
        // Дополнительная попытка обновления переводов через 2 секунды
        setTimeout(async () => {
            await this.reloadTranslations();
            this.updateCartModalTranslations();
        }, 2000);
    }

    // Загрузка переводов
    async loadTranslations() {
        try {
            const response = await fetch('/api/language/translations.php');
            if (response.ok) {
                this.translations = await response.json();
            }
        } catch (error) {
            console.warn('Failed to load translations:', error);
        }
    }

    // Перезагрузка переводов
    async reloadTranslations() {
        try {
            const response = await fetch('/api/language/translations.php');
            if (response.ok) {
                this.translations = await response.json();
            }
        } catch (error) {
            console.warn('Failed to reload translations:', error);
        }
    }

    // Получение перевода
    t(key, defaultValue = '') {
        if (this.translations && this.translations[key]) {
            return this.translations[key];
        }
        return defaultValue || key;
    }

    // Привязка событий
    bindEvents() {
        // Обработчик для кнопки корзины
        const cartButton = document.getElementById('cart-button');
        if (cartButton) {
            cartButton.addEventListener('click', () => this.toggleCart());
        }

        // Обработчик для кнопки закрытия корзины
        const cartClose = document.getElementById('cart-close');
        if (cartClose) {
            cartClose.addEventListener('click', () => this.hideCart());
        }

        // Обработчик для кнопки очистки корзины
        const clearCartBtn = document.getElementById('clear-cart');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => this.clearCart());
        }

        // Обработчик для кнопки оформления заказа
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => this.showOrderModal());
        }

        // Обработчик для кнопки закрытия модального окна
        const modalClose = document.getElementById('cartModalClose');
        if (modalClose) {
            modalClose.addEventListener('click', () => this.hideModal());
        }

        // Обработчик для кнопки отправки заказа
        const submitBtn = document.getElementById('cartModalSubmit');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitOrder());
        }

        // Обработчик для изменения типа заказа
        const orderTypeInputs = document.querySelectorAll('input[name="orderType"]');
        orderTypeInputs.forEach(input => {
            input.addEventListener('change', () => this.updateOrderTypeFields());
        });
    }

    // Показать/скрыть корзину
    toggleCart() {
        const cartSidebar = document.getElementById('cart-sidebar');
        const cartOverlay = document.getElementById('cart-overlay');
        
        if (cartSidebar && cartOverlay) {
            cartSidebar.classList.toggle('open');
            cartOverlay.classList.toggle('active');
        }
    }

    // Скрыть корзину
    hideCart() {
        const cartSidebar = document.getElementById('cart-sidebar');
        const cartOverlay = document.getElementById('cart-overlay');
        
        if (cartSidebar && cartOverlay) {
            cartSidebar.classList.remove('open');
            cartOverlay.classList.remove('active');
        }
    }

    // Добавить товар в корзину
    addItem(product) {
        const existingItem = this.items.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1,
                image: product.image
            });
        }
        
        this.saveCart();
        this.updateCartDisplay();
    }

    // Удалить товар из корзины
    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartDisplay();
    }

    // Обновить количество товара
    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartDisplay();
            }
        }
    }

    // Очистить корзину
    clearCart() {
        this.items = [];
        this.currentOrderId = null;
        this.saveCart();
        this.updateCartDisplay();
    }

    // Получить общую сумму
    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    // Сохранить корзину
    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.items));
        if (this.currentOrderId) {
            localStorage.setItem('currentOrderId', this.currentOrderId);
        } else {
            localStorage.removeItem('currentOrderId');
        }
    }

    // Обновить отображение корзины
    updateCartDisplay() {
        const cartCount = document.getElementById('cart-count');
        const cartItems = document.getElementById('cart-items');
        const cartEmpty = document.getElementById('cart-empty');
        const cartTotal = document.getElementById('cart-total');

        // Update count
        const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
        if (cartCount) {
            cartCount.textContent = totalItems;
        }

        // Show/hide empty state
        if (this.items.length === 0) {
            if (cartItems) cartItems.style.display = 'none';
            if (cartEmpty) cartEmpty.style.display = 'block';
        } else {
            if (cartItems) cartItems.style.display = 'block';
            if (cartEmpty) cartEmpty.style.display = 'none';
        }

        // Update items list
        if (cartItems && this.items.length > 0) {
            cartItems.innerHTML = this.items.map(item => `
                <div class="cart-item">
                    <div class="cart-item__image">
                        <img src="${item.image || '/images/placeholder.jpg'}" alt="${item.name}">
                    </div>
                    <div class="cart-item__content">
                        <h4>${item.name}</h4>
                        <div class="cart-item__controls">
                            <button class="cart-item__btn" onclick="window.cartMenu2.updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                            <span class="cart-item__quantity">${item.quantity}</span>
                            <button class="cart-item__btn" onclick="window.cartMenu2.updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                    <div class="cart-item__price">
                        <span>${this.formatNumber(item.price * item.quantity)} ₫</span>
                        <button class="cart-item__remove" onclick="window.cartMenu2.removeItem(${item.id})">×</button>
                    </div>
                </div>
            `).join('');
        }

        // Update total
        if (cartTotal) {
            cartTotal.textContent = `${this.formatNumber(this.getTotal())} ₫`;
        }
    }

    // Показать модальное окно заказа
    showOrderModal() {
        if (this.items.length === 0) {
            this.showToast('Корзина пуста', 'error');
            return;
        }

        const modal = document.getElementById('cartModal');
        if (modal) {
            modal.classList.remove('modal-hidden');
            this.updateOrderTypeFields();
        }
    }

    // Скрыть модальное окно
    hideModal() {
        const modal = document.getElementById('cartModal');
        if (modal) {
            modal.classList.add('modal-hidden');
        }
    }

    // Обновить поля в зависимости от типа заказа
    updateOrderTypeFields() {
        const orderType = document.querySelector('input[name="orderType"]:checked')?.value;
        
        // Показать/скрыть поля в зависимости от типа заказа
        const tableFields = document.getElementById('tableFields');
        const deliveryFields = document.getElementById('deliveryFields');
        
        if (tableFields) {
            tableFields.style.display = orderType === 'table' ? 'block' : 'none';
        }
        
        if (deliveryFields) {
            deliveryFields.style.display = orderType === 'delivery' ? 'block' : 'none';
        }
    }

    // Валидация формы заказа
    validateOrderForm() {
        const name = document.getElementById('customerName')?.value.trim();
        const phone = document.getElementById('customerPhone')?.value.trim();
        const orderType = document.querySelector('input[name="orderType"]:checked')?.value;

        if (!name) {
            this.showToast('Введите имя', 'error');
            return false;
        }

        if (orderType === 'delivery' && !phone) {
            this.showToast('Введите номер телефона для доставки', 'error');
            return false;
        }

        return true;
    }

    // Проверить открытые чеки для клиента
    async checkOpenOrders(clientId) {
        try {
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
            const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
            
            const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.getTransactions&client_id=${clientId}&date_from=${today}&date_to=${today}&token=${window.API_TOKEN}`, {
                method: 'GET',
                headers: {
                    'X-API-Token': window.API_TOKEN
                }
            });
            
            if (response.ok) {
                const transactions = await response.json();
                console.log('All transactions:', transactions);
                
                // Ищем незакрытые заказы (date_close пустое или null)
                if (Array.isArray(transactions)) {
                    const openOrder = transactions.find(transaction => 
                        !transaction.date_close || 
                        transaction.date_close === '' || 
                        transaction.date_close === '0000-00-00 00:00:00'
                    );
                    
                    return openOrder || null;
                }
            }
        } catch (error) {
            console.warn('Error checking open orders:', error);
        }
        return null;
    }

    // Добавить товары к существующему чеку
    async addToExistingOrder(orderId) {
        try {
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
            
            // Добавляем каждый товар к существующему чеку
            for (const item of this.items) {
                const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.addTransactionProduct`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Token': window.API_TOKEN
                    },
                    body: JSON.stringify({
                        transaction_id: orderId,
                        product_id: parseInt(item.id),
                        count: item.quantity,
                        price: Math.round(item.price * 100) // Convert to minor units
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to add product ${item.name} to order`);
                }
            }
            
            // Сохраняем номер чека
            this.currentOrderId = orderId;
            this.saveCart();
            
            this.showToast('Товары успешно добавлены к существующему заказу!', 'success');
            this.clearCart();
            this.hideModal();
            
        } catch (error) {
            console.error('Error adding to existing order:', error);
            this.showToast('Ошибка при добавлении товаров к заказу', 'error');
        }
    }

    // Создать новый чек
    async createNewOrder(orderData) {
        try {
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
            
            const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/orders/create-check`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Token': window.API_TOKEN
                },
                body: JSON.stringify(orderData)
            });

            if (response.ok) {
                const result = await response.json();
                
                // Сохраняем номер чека
                if (result.order && result.order.response && result.order.response.id) {
                    this.currentOrderId = result.order.response.id;
                    this.saveCart();
                }
                
                this.showToast('Заказ успешно создан!', 'success');
                this.clearCart();
                this.hideModal();
                console.log('Order created:', result);
            } else {
                const error = await response.json();
                throw new Error(error.message || 'Failed to create order');
            }
        } catch (error) {
            console.error('Order creation error:', error);
            this.showToast('Ошибка при создании заказа', 'error');
        }
    }

    // Отправить заказ
    async submitOrder() {
        // Защита от повторных отправок
        if (this.isSubmittingOrder) {
            console.log('⚠️ Order submission already in progress, ignoring duplicate request');
            return;
        }

        this.isSubmittingOrder = true;
        
        // Блокируем кнопку и показываем индикатор загрузки
        const submitBtn = document.getElementById('cartModalSubmit');
        const originalBtnText = submitBtn ? submitBtn.textContent : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
            submitBtn.innerHTML = '<span style="display: inline-block; width: 12px; height: 12px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 0.6s linear infinite; margin-right: 8px;"></span>Отправляем заказ...';
        }

        if (this.items.length === 0) {
            this.showToast('Корзина пуста', 'error');
            this.isSubmittingOrder = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                submitBtn.textContent = originalBtnText;
            }
            return;
        }

        if (!this.validateOrderForm()) {
            this.isSubmittingOrder = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                submitBtn.textContent = originalBtnText;
            }
            return;
        }

        const orderType = document.querySelector('input[name="orderType"]:checked').value;
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        
        const orderData = {
            spotId: 1, // Default spot
            tableId: 1, // Default table
            waiterId: 4, // Default waiter
            guestsCount: 1,
            serviceMode: orderType === 'table' ? 1 : (orderType === 'takeaway' ? 2 : 3),
            autoAccept: false,
            client: {
                firstName: name,
                phone: phone,
                email: '',
                address: {
                    street: '',
                    additionalInfo: '',
                    comment: '',
                    lat: '',
                    lng: ''
                }
            },
            comment: this.getOrderComment(orderType),
            products: this.items.map(item => ({
                id: parseInt(item.id),
                count: item.quantity,
                price: item.price, // Price in major units
                comment: ''
            }))
        };

        try {
            this.showToast('Отправляем заказ...', 'info');
            
            // Если пользователь авторизован, проверяем открытые чеки
            if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
                try {
                    const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
                    const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/clients.getClients&phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                    
                    if (response.ok) {
                        const clientsData = await response.json();
                        if (clientsData && clientsData.length > 0) {
                            const clientId = clientsData[0].client_id;
                            console.log('Found client_id:', clientId);
                            
                            // Проверяем открытые чеки
                            const openOrder = await this.checkOpenOrders(clientId);
                            if (openOrder) {
                                console.log('Found open order:', openOrder);
                                // Добавляем товары к существующему чеку
                                await this.addToExistingOrder(openOrder.transaction_id);
                                return;
                            }
                        }
                    }
                } catch (error) {
                    console.warn('Could not find client_id:', error);
                }
            }

            // Создаем новый чек
            await this.createNewOrder(orderData);
            
        } catch (error) {
            console.error('Order submission error:', error);
            this.showToast('Ошибка при отправке заказа', 'error');
        } finally {
            // Сбрасываем флаг и разблокируем кнопку
            this.isSubmittingOrder = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
                submitBtn.textContent = 'Оформить заказ';
            }
        }
    }

    // Получить комментарий к заказу
    getOrderComment(orderType) {
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        
        if (orderType === 'table') {
            const table = document.getElementById('tableNumber')?.value;
            const comment = document.getElementById('tableComment')?.value.trim();
            return `Стол: ${table || 'Не указан'}, Имя: ${name}, Телефон: ${phone}${comment ? ', Комментарий: ' + comment : ''}`;
        } else if (orderType === 'delivery') {
            const address = document.getElementById('deliveryAddress')?.value.trim();
            const time = document.getElementById('deliveryTime')?.value;
            return `Доставка, Имя: ${name}, Телефон: ${phone}, Адрес: ${address || 'Не указан'}${time ? ', Время: ' + time : ''}`;
        } else {
            return `Навынос, Имя: ${name}, Телефон: ${phone}`;
        }
    }

    // Показать уведомление
    showToast(message, type = 'info') {
        // Создаем элемент уведомления
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        // Стили в зависимости от типа
        if (type === 'success') {
            toast.style.backgroundColor = '#10b981';
        } else if (type === 'error') {
            toast.style.backgroundColor = '#ef4444';
        } else {
            toast.style.backgroundColor = '#3b82f6';
        }
        
        document.body.appendChild(toast);
        
        // Удаляем через 3 секунды
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }

    // Обновить переводы модального окна
    updateCartModalTranslations() {
        // Обновляем переводы для модального окна корзины
        const elements = document.querySelectorAll('[data-translate]');
        elements.forEach(element => {
            const key = element.getAttribute('data-translate');
            const translation = this.t(key);
            if (translation && translation !== key) {
                element.textContent = translation;
            }
        });
    }
}

// Инициализация корзины для menu2
document.addEventListener('DOMContentLoaded', function() {
    window.cartMenu2 = new CartMenu2();
});

// Функция для добавления товара в корзину (для совместимости)
function addToCart(product) {
    if (window.cartMenu2) {
        window.cartMenu2.addItem(product);
        window.cartMenu2.toggleCart();
    }
}
