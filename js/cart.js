// Cart functionality
class Cart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('cart') || '[]');
        if (!Array.isArray(this.items)) {
            this.items = [];
        }
        
        // Защита от флуда - не более одного запроса в секунду
        this.lastApiCall = 0;
        this.apiCallQueue = [];
        this.isProcessingQueue = false;
        
        this.init();
    }
    
    // Функция для форматирования чисел с пробелами
    formatNumber(num) {
        return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    // Защита от флуда - throttle механизм
    async throttleApiCall(apiCall) {
        const now = Date.now();
        const timeSinceLastCall = now - this.lastApiCall;
        
        // Если прошло меньше секунды, добавляем в очередь
        if (timeSinceLastCall < 1000) {
            return new Promise((resolve, reject) => {
                this.apiCallQueue.push({ apiCall, resolve, reject });
                this.processQueue();
            });
        }
        
        // Если прошла секунда, выполняем сразу
        this.lastApiCall = now;
        return await apiCall();
    }

    // Обработка очереди запросов
    async processQueue() {
        if (this.isProcessingQueue || this.apiCallQueue.length === 0) {
            return;
        }
        
        this.isProcessingQueue = true;
        
        while (this.apiCallQueue.length > 0) {
            const now = Date.now();
            const timeSinceLastCall = now - this.lastApiCall;
            
            // Ждем до следующей секунды
            if (timeSinceLastCall < 1000) {
                await new Promise(resolve => setTimeout(resolve, 1000 - timeSinceLastCall));
            }
            
            const { apiCall, resolve, reject } = this.apiCallQueue.shift();
            this.lastApiCall = Date.now();
            
            try {
                const result = await apiCall();
                resolve(result);
            } catch (error) {
                reject(error);
            }
        }
        
        this.isProcessingQueue = false;
    }
    
    init() {
        this.bindEvents();
        this.updateCartDisplay();
    }

    bindEvents() {
        // Cart icon click
        document.getElementById('cartIcon')?.addEventListener('click', () => {
            this.toggleCart();
        });

        // Quantity buttons (delegated event handling)
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('quantity-btn')) {
                e.preventDefault();
                const btn = e.target;
                const cartItem = btn.closest('.cart-item');
                const productId = cartItem.dataset.productId;
                const isIncrease = btn.textContent === '+';
                
                if (productId) {
                    const currentQuantity = parseInt(cartItem.querySelector('.cart-item-quantity span').textContent);
                    const newQuantity = isIncrease ? currentQuantity + 1 : currentQuantity - 1;
                    await this.updateQuantity(productId, newQuantity);
                }
            }
        });

        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-btn')) {
                const btn = e.target.closest('.add-to-cart-btn');
                const productData = JSON.parse(btn.dataset.product);
                this.addItem(productData);
                this.highlightCart();
            }
        });

        // Auth icon click - handled by AuthSystem
        // document.getElementById('authIcon')?.addEventListener('click', () => {
        //     this.showAuthModal();
        // });
    }

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

    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartDisplay();
    }

    async updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            const oldQuantity = item.quantity;
            item.quantity = quantity;
            this.saveCart();
            
            // Обновляем визуальное отображение счетчика
            this.updateQuantityDisplay(productId, quantity);
            
            // Обновляем общее отображение корзины
            this.updateCartDisplay();
            
            // Отправляем изменение на сервер если есть открытая транзакция
            await this.syncQuantityChange(productId, oldQuantity, quantity);
        }
    }

    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();
    }

    // Обновление визуального отображения счетчика
    updateQuantityDisplay(productId, quantity) {
        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        if (cartItem) {
            const quantitySpan = cartItem.querySelector('.cart-item-quantity span');
            if (quantitySpan) {
                quantitySpan.textContent = quantity;
            }
            
            // Обновляем общую сумму корзины
            this.updateTotalDisplay();
        }
    }

    // Синхронизация изменения количества с сервером
    async syncQuantityChange(productId, oldQuantity, newQuantity) {
        // Проверяем, есть ли открытая транзакция для авторизованного пользователя
        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
            try {
                const phone = window.authSystem.userData.phone;
                if (phone) {
                    // Получаем client_id
                    const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3002' : 'https://northrepublic.me';
                    const clientsResponse = await fetch(`${apiUrl}/api/poster/clients.getClients?phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                    
                    if (clientsResponse.ok) {
                        const clientsData = await clientsResponse.json();
                        if (clientsData && clientsData.length > 0) {
                            const clientId = clientsData[0].client_id;
                            
                            // Проверяем total_payed_sum клиента
                            const clientData = clientsData[0];
                            const totalPaidSum = clientData.total_payed_sum || 0;
                            
                            // Проверяем открытые транзакции
                            const openTransaction = await this.checkOpenTransactions(clientId);
                            if (openTransaction) {
                                // Если новый клиент (total_payed_sum = 0), применяем скидку
                                if (totalPaidSum === 0) {
                                    await this.throttleApiCall(async () => {
                                        const response = await fetch(`${apiUrl}/api/poster/transactions.changeFiscalStatus`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-API-Token': window.API_TOKEN
                                            },
                                            body: JSON.stringify({
                                                transaction_id: openTransaction.transaction_id,
                                                fiscal_status: 1 // Применяем скидку 20%
                                            })
                                        });
                                        
                                        if (!response.ok) {
                                            throw new Error(`Failed to apply discount: ${response.statusText}`);
                                        }
                                        
                                        console.log(`✅ Discount applied for new client: ${clientId}`);
                                        return await response.json();
                                    });
                                }
                                
                                // Отправляем изменение количества с защитой от флуда
                                await this.throttleApiCall(async () => {
                                    const response = await fetch(`${apiUrl}/api/poster/transactions.changeTransactionProductCount`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-API-Token': window.API_TOKEN
                                        },
                                        body: JSON.stringify({
                                            transaction_id: openTransaction.transaction_id,
                                            product_id: parseInt(productId),
                                            count: newQuantity
                                        })
                                    });
                                    
                                    if (!response.ok) {
                                        throw new Error(`Failed to update product count: ${response.statusText}`);
                                    }
                                    
                                    console.log(`✅ Product count synced: ${productId} = ${newQuantity}`);
                                    return await response.json();
                                });
                            }
                        }
                    }
                }
            } catch (error) {
                console.warn('Failed to sync quantity change:', error);
            }
        }
    }

    getTotal() {
        const subtotal = this.items
            .filter(item => item.quantity > 0)
            .reduce((total, item) => total + (item.price * item.quantity), 0);
        
        // Применяем скидку если есть
        if (this.promotionId === 1) {
            return subtotal * 0.8; // 20% скидка
        }
        
        return subtotal;
    }

    getSubtotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    updateTotalDisplay() {
        const cartTotal = document.querySelector('.cart-total');
        if (!cartTotal) return;

        const subtotal = this.getSubtotal();
        const total = this.getTotal();
        
        if (this.promotionId === 1 && subtotal > 0) {
            // Показываем субтотал, скидку и итого
            cartTotal.innerHTML = `
                <div class="total-row">
                    <span>Субтотал:</span>
                    <span>${this.formatNumber(subtotal)} ₫</span>
                </div>
                <div class="total-row discount-row">
                    <span>Скидка 20%:</span>
                    <span>-${this.formatNumber(subtotal - total)} ₫</span>
                </div>
                <div class="total-row total-final">
                    <span>Итого:</span>
                    <span class="total-amount">${this.formatNumber(total)} ₫</span>
                </div>
            `;
        } else {
            // Показываем только итого
            cartTotal.innerHTML = `
                <div class="total-row">
                    <span>Итого:</span>
                    <span class="total-amount">${this.formatNumber(total)} ₫</span>
                </div>
            `;
        }
    }

    saveCart() {
        localStorage.setItem('cart', JSON.stringify(this.items));
    }

    updateCartDisplay() {
        const cartCount = document.getElementById('cartCount');
        const cartIcon = document.getElementById('cartIcon');
        const cartIconImg = document.querySelector('.cart-icon-img');
        const cartItemsList = document.getElementById('cartItemsList');
        const cartTotalAmount = document.getElementById('cartTotalAmount');

        const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
        
        // Обновляем иконку корзины
        if (cartCount) {
            cartCount.textContent = totalItems;
            if (totalItems > 0) {
                cartCount.classList.remove('cart-count-hidden');
            } else {
                cartCount.classList.add('cart-count-hidden');
            }
        }

        if (cartIcon && cartIconImg) {
            if (totalItems > 0) {
                cartIcon.classList.add('has-items');
                cartIconImg.src = 'images/icons/cart green.png';
            } else {
                cartIcon.classList.remove('has-items');
                cartIconImg.src = 'images/icons/cart gray.png';
            }
        }

        // Обновляем содержимое корзины
        if (cartItemsList && cartTotalAmount) {
            cartItemsList.innerHTML = this.items.map(item => `
                <div class="cart-item" data-product-id="${item.id}">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${this.formatNumber(item.price)} ₫</div>
                    <div class="cart-item-quantity">
                        <a href="#" class="quantity-btn">-</a>
                        <span>${item.quantity}</span>
                        <a href="#" class="quantity-btn">+</a>
                    </div>
                </div>
            `).join('');

            // Обновляем отображение суммы с учетом скидки
            this.updateTotalDisplay();
        }
    }

    async toggleCart() {
        if (this.items.length === 0) {
            this.showToast('Корзина пуста', 'info');
            return;
        }
        await this.showCartModal();
    }

    async showCartModal() {
        this.populateCartModal();
        this.showModal();
        this.showGuestFields();
        
        // Заполняем поля данными из профиля, если пользователь авторизован
        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
            this.fillFieldsFromProfile(window.authSystem.userData);
            await this.checkAndApplyDiscount(window.authSystem.userData);
        } else {
            // Если пользователь не авторизован, но есть данные в localStorage, пытаемся их использовать
            this.tryFillFromStoredData();
        }
    }

    showGuestFields() {
        // Показываем поля гостя (в реальном приложении здесь была бы проверка авторизации)
        const guestFields = document.getElementById('guestInfoFields');
        if (guestFields) {
            guestFields.style.display = 'block';
        }
    }

    populateCartModal() {
        const cartItemsList = document.getElementById('cartItemsList');
        const cartTotalAmount = document.getElementById('cartTotalAmount');
        
        // Фильтруем товары с количеством > 0
        const visibleItems = this.items.filter(item => item.quantity > 0);
        
        if (visibleItems.length === 0) {
            if (cartItemsList) {
                cartItemsList.innerHTML = '<p class="cart-empty-message">Корзина пуста</p>';
            }
            if (cartTotalAmount) {
                cartTotalAmount.textContent = '0 ₫';
            }
            return;
        }

        if (cartItemsList) {
            cartItemsList.innerHTML = visibleItems.map(item => `
                <div class="cart-item" data-product-id="${item.id}">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${this.formatNumber(item.price)} ₫</div>
                    <div class="cart-item-quantity">
                        <a href="#" class="quantity-btn">-</a>
                        <span>${item.quantity}</span>
                        <a href="#" class="quantity-btn">+</a>
                    </div>
                </div>
            `).join('');
        }

        if (cartTotalAmount) {
            cartTotalAmount.textContent = `${this.formatNumber(this.getTotal())} ₫`;
        }
    }

    showModal() {
        const modal = document.getElementById('cartModal');
        const overlay = document.getElementById('modalOverlay');
        
        modal.classList.remove('modal-hidden');
        overlay.classList.remove('overlay-hidden');
        
        // Bind modal events
        this.bindModalEvents();
    }

    hideModal() {
        const modal = document.getElementById('cartModal');
        const overlay = document.getElementById('modalOverlay');
        
        modal.classList.add('modal-hidden');
        overlay.classList.add('overlay-hidden');
    }

    bindModalEvents() {
        // Close modal events
        document.getElementById('cartModalClose')?.addEventListener('click', () => {
            this.hideModal();
        });
        
        document.getElementById('cartModalCancel')?.addEventListener('click', () => {
            this.hideModal();
        });
        
        document.getElementById('modalOverlay')?.addEventListener('click', () => {
            this.hideModal();
        });

        // Order type change events
        document.querySelectorAll('input[name="orderType"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.toggleOrderFields(e.target.value);
            });
        });

        // Phone validation for unified phone field
        document.getElementById('customerPhone')?.addEventListener('input', (e) => {
            this.applyPhoneMask(e.target);
            this.validatePhone(e.target);
        });
        
        // Предотвращаем удаление + в начале
        document.getElementById('customerPhone')?.addEventListener('keydown', (e) => {
            if (e.target.selectionStart === 1 && e.key === 'Backspace') {
                e.preventDefault();
            }
        });

        // Delivery time validation
        document.getElementById('deliveryTime')?.addEventListener('change', (e) => {
            this.validateDeliveryTime(e.target);
        });

        // Submit order
        document.getElementById('cartModalSubmit')?.addEventListener('click', () => {
            this.submitOrder();
        });

        // Load tables when modal opens
        this.loadTables();
    }

    toggleOrderFields(orderType) {
        const tableFields = document.getElementById('tableOrderFields');
        const takeawayFields = document.getElementById('takeawayOrderFields');
        const deliveryFields = document.getElementById('deliveryOrderFields');
        const tableFieldGroup = document.getElementById('tableFieldGroup');
        
        // Скрываем все поля
        tableFields.style.display = 'none';
        takeawayFields.style.display = 'none';
        deliveryFields.style.display = 'none';
        
        if (orderType === 'table') {
            tableFields.style.display = 'block';
            tableFieldGroup.style.display = 'block'; // Показываем поле стола
        } else if (orderType === 'takeaway') {
            takeawayFields.style.display = 'block';
            tableFieldGroup.style.display = 'none'; // Скрываем поле стола
        } else if (orderType === 'delivery') {
            deliveryFields.style.display = 'block';
            tableFieldGroup.style.display = 'none'; // Скрываем поле стола
        }
    }

    async loadTables() {
        try {
            // Используем PHP API для загрузки столов
            const response = await fetch('/api/tables.php');
            
            if (response.ok) {
                const data = await response.json();
                console.log('Tables loaded from MongoDB:', data);
                this.populateTableSelect(data.tables);
            } else {
                console.warn('Failed to load tables from MongoDB, using fallback');
                this.populateTableSelect([]);
            }
        } catch (error) {
            console.error('Error loading tables:', error);
            this.populateTableSelect([]);
        }
    }

    populateTableSelect(tables) {
        const select = document.getElementById('tableNumber');
        if (!select) return;

        // Clear existing options except the first one
        select.innerHTML = '<option value=""></option>';
        
        if (tables && tables.length > 0) {
            tables.forEach(table => {
                const option = document.createElement('option');
                option.value = table.table_id || table.id;
                // Показываем только название стола
                option.textContent = table.name || table.table_name || `Стол ${table.table_id || table.id}`;
                select.appendChild(option);
            });
            console.log(`Loaded ${tables.length} tables from MongoDB`);
        } else {
            console.warn('No tables received from MongoDB');
            // Не добавляем fallback столы - только из MongoDB
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Столы не найдены';
            option.disabled = true;
            select.appendChild(option);
        }
    }

    applyPhoneMask(input) {
        let value = input.value;
        
        // Если поле пустое, устанавливаем +
        if (value === '') {
            input.value = '+';
            return;
        }
        
        // Если не начинается с +, добавляем +
        if (!value.startsWith('+')) {
            value = '+' + value.replace(/\D/g, '');
        } else {
            // Оставляем + и только цифры после него
            value = '+' + value.substring(1).replace(/\D/g, '');
        }
        
        // Ограничиваем длину (максимум 15 символов включая +)
        if (value.length > 15) {
            value = value.substring(0, 15);
        }
        
        input.value = value;
    }

    validatePhone(input) {
        const phone = input.value;
        
        // Простая проверка: должен начинаться с + и содержать минимум 7 цифр
        if (phone && (phone.length < 8 || !phone.startsWith('+'))) {
            input.setCustomValidity('Введите номер телефона в формате +код_страны_номер');
        } else {
            input.setCustomValidity('');
        }
    }

    validateDeliveryTime(input) {
        const selectedTime = new Date(input.value);
        const now = new Date();
        const oneHourFromNow = new Date(now.getTime() + 60 * 60 * 1000);
        
        if (selectedTime < oneHourFromNow) {
            input.setCustomValidity('Время доставки должно быть не менее чем через час');
            this.showToast('Мы не успеем так быстро, но постараемся!', 'warning');
        } else {
            input.setCustomValidity('');
        }
    }

    validateOrderForm() {
        const orderType = document.querySelector('input[name="orderType"]:checked').value;
        
        // Общие поля для всех типов заказов
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        
        if (!name) {
            this.showToast('Введите ваше имя', 'error');
            return false;
        }
        
        if (!phone) {
            this.showToast('Введите номер телефона', 'error');
            return false;
        }
        
        // Проверяем валидность телефона
        if (phone.length < 8 || !phone.startsWith('+')) {
            this.showToast('Введите корректный номер телефона', 'error');
            return false;
        }
        
        if (orderType === 'table') {
            const table = document.getElementById('tableNumber').value;
            
            if (!table) {
                this.showToast('Выберите номер стола', 'error');
                return false;
            }
        } else if (orderType === 'takeaway') {
            // Для заказа с собой дополнительных полей не требуется
        } else if (orderType === 'delivery') {
            const address = document.getElementById('deliveryAddress').value.trim();
            const deliveryTime = document.getElementById('deliveryTime').value;
            
            if (!address) {
                this.showToast('Введите адрес доставки', 'error');
                return false;
            }
            
            if (!deliveryTime) {
                this.showToast('Выберите время доставки', 'error');
                return false;
            }
        }
        
        return true;
    }

    async submitOrder() {
        if (this.items.length === 0) {
            this.showToast('Корзина пуста', 'error');
            return;
        }

        if (!this.validateOrderForm()) {
            return;
        }

        const orderType = document.querySelector('input[name="orderType"]:checked').value;
        
        // Получаем данные из единых полей
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        
        const orderData = {
            spot_id: 1, // Default spot
            phone: phone, // Обязательный параметр согласно документации
            service_mode: orderType === 'table' ? 1 : (orderType === 'takeaway' ? 2 : 3), // 1 - в заведении, 2 - навынос, 3 - доставка
            products: this.items.map(item => ({
                product_id: parseInt(item.id),
                count: item.quantity,
                price: Math.round(item.price * 100) // Convert to minor units (kopecks)
            })),
            comment: this.getOrderComment(orderType)
        };

        // Если пользователь авторизован, пытаемся найти его client_id в Poster
        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
            try {
                const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3002' : 'https://northrepublic.me';
                const response = await fetch(`${apiUrl}/api/poster/clients.getClients?phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                
                if (response.ok) {
                    const clientsData = await response.json();
                    if (clientsData && clientsData.length > 0) {
                        // Берем первого найденного клиента
                        orderData.client_id = clientsData[0].client_id;
                        console.log('Found client_id:', orderData.client_id);
                        
                        // Проверяем незакрытые заказы
                        const openTransaction = await this.checkOpenTransactions(orderData.client_id);
                        if (openTransaction) {
                            console.log('Found open transaction:', openTransaction);
                            // Добавляем товары к существующему заказу
                            await this.addToExistingOrder(openTransaction.transaction_id);
                            return;
                        }
                    }
                }
            } catch (error) {
                console.warn('Could not find client_id:', error);
            }
        }

        // Добавляем promotion_id если есть скидка
        if (this.promotionId) {
            orderData.promotion_id = this.promotionId;
        }

        // Для заказа на столик добавляем имя стола
        if (orderType === 'table') {
            const tableSelect = document.getElementById('tableNumber');
            const selectedTableId = tableSelect.value;
            if (selectedTableId) {
                // Находим выбранную опцию и получаем текст (имя стола)
                const selectedOption = tableSelect.options[tableSelect.selectedIndex];
                const tableName = selectedOption.text;
                
                // Передаем имя стола в комментарии (Poster API может не поддерживать table_id)
                orderData.comment = orderData.comment.replace(/Стол: \d+/, `Стол: ${tableName}`);
            }
        }

        // Для заказов на доставку добавляем адрес и время
        if (orderType === 'delivery') {
            const address = document.getElementById('deliveryAddress').value.trim();
            const deliveryTime = document.getElementById('deliveryTime').value;
            
            if (address) {
                orderData.client_address = {
                    address1: address,
                    comment: 'Адрес для доставки'
                };
            }
            
            if (deliveryTime) {
                // Конвертируем в формат YYYY-MM-DD HH:MM:SS
                const date = new Date(deliveryTime);
                const formattedTime = date.getFullYear() + '-' + 
                    String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(date.getDate()).padStart(2, '0') + ' ' + 
                    String(date.getHours()).padStart(2, '0') + ':' + 
                    String(date.getMinutes()).padStart(2, '0') + ':00';
                orderData.delivery_time = formattedTime;
            }
        }

        try {
            this.showToast('Отправляем заказ...', 'info');
            
            // Используем основной домен для API запросов
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3002' : 'https://northrepublic.me';
            const response = await fetch(`${apiUrl}/api/poster/orders/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Token': window.API_TOKEN
                },
                body: JSON.stringify(orderData)
            });

            if (response.ok) {
                const result = await response.json();
                this.showToast('Заказ успешно отправлен!', 'success');
                
                // Сохраняем данные клиента для будущих заказов
                this.saveCustomerData(name, phone);
                
                this.clearCart();
                this.hideModal();
                console.log('Order created:', result);
            } else {
                const error = await response.json();
                this.showToast(`Ошибка: ${error.message}`, 'error');
            }
        } catch (error) {
            console.error('Order submission error:', error);
            this.showToast('Ошибка при отправке заказа', 'error');
        }
    }

    getOrderComment(orderType) {
        // Используем единые поля для имени и телефона
        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        
        if (orderType === 'table') {
            const table = document.getElementById('tableNumber').value;
            const comment = document.getElementById('tableComment').value.trim();
            
            let commentText = `Заказ на столик. Имя: ${name}, Телефон: ${phone}, Стол: ${table}`;
            if (comment) {
                commentText += `. Комментарий: ${comment}`;
            }
            return commentText;
        } else if (orderType === 'takeaway') {
            const comment = document.getElementById('takeawayComment').value.trim();
            
            let commentText = `Заказ с собой. Имя: ${name}, Телефон: ${phone}`;
            if (comment) {
                commentText += `. Комментарий: ${comment}`;
            }
            return commentText;
        } else if (orderType === 'delivery') {
            const address = document.getElementById('deliveryAddress').value.trim();
            const deliveryTime = document.getElementById('deliveryTime').value;
            const comment = document.getElementById('deliveryComment').value.trim();
            
            let commentText = `Заказ на доставку. Имя: ${name}, Телефон: ${phone}, Адрес: ${address}, Время: ${deliveryTime}`;
            if (comment) {
                commentText += `. Комментарий: ${comment}`;
            }
            return commentText;
        }
    }

    highlightCart() {
        const cartIcon = document.getElementById('cartIcon');
        if (cartIcon) {
            cartIcon.classList.add('cart-highlight');
            setTimeout(() => {
                cartIcon.classList.remove('cart-highlight');
            }, 1000);
        }
    }

    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();
    }

    showAuthModal() {
        // Auth modal is now handled by AuthSystem in menu2.php
        // This method is no longer used
        console.log('Auth modal handled by AuthSystem');
    }

    fillFieldsFromProfile(userData) {
        // Заполняем поля корзины данными из профиля
        const nameField = document.getElementById('customerName');
        const phoneField = document.getElementById('customerPhone');
        
        if (nameField && userData.firstname && userData.lastname) {
            nameField.value = `${userData.firstname} ${userData.lastname}`.trim();
        }
        
        if (phoneField && userData.phone) {
            phoneField.value = userData.phone;
        }
    }

    tryFillFromStoredData() {
        // Пытаемся заполнить поля из localStorage (для неавторизованных пользователей)
        const nameField = document.getElementById('customerName');
        const phoneField = document.getElementById('customerPhone');
        
        // Проверяем, есть ли сохраненные данные
        const storedName = localStorage.getItem('last_customer_name');
        const storedPhone = localStorage.getItem('last_customer_phone');
        
        if (nameField && storedName && !nameField.value) {
            nameField.value = storedName;
        }
        
        if (phoneField && storedPhone && !phoneField.value) {
            phoneField.value = storedPhone;
        }
    }

    saveCustomerData(name, phone) {
        // Сохраняем данные клиента в localStorage для будущих заказов
        if (name) {
            localStorage.setItem('last_customer_name', name);
        }
        if (phone) {
            localStorage.setItem('last_customer_phone', phone);
        }
    }

    async checkAndApplyDiscount(userData) {
        // Проверяем сумму предыдущих заказов
        const totalPaidSum = userData.total_payed_sum || 0;
        
        if (totalPaidSum === 0) {
            // Новый клиент - применяем скидку 20% (акция ID 1)
            this.applyDiscount(1, '-20% на первый заказ каждому новому гостю');
            this.showDiscountText(true);
        } else {
            // Существующий клиент - скрываем текст скидки
            this.showDiscountText(false);
        }
    }

    applyDiscount(promotionId, description) {
        // Применяем скидку к корзине
        this.promotionId = promotionId;
        this.discountDescription = description;
        
        // Обновляем отображение корзины с учетом скидки
        this.updateCartDisplay();
        
        // Показываем уведомление о примененной скидке только если есть описание
        if (description && description.trim() !== '') {
            this.showToast(description, 'info');
        }
    }

    showDiscountText(show) {
        // Удаляем динамически созданный элемент discountText если он существует
        const oldDiscountText = document.getElementById('discountText');
        if (oldDiscountText) {
            oldDiscountText.remove();
        }
        
        // Показываем или скрываем текст скидки в футере модалки
        const discountInfoElement = document.querySelector('.discount-info');
        
        if (discountInfoElement) {
            if (show) {
                discountInfoElement.style.display = 'block';
                discountInfoElement.style.backgroundColor = '#366b5b';
            } else {
                discountInfoElement.style.display = 'none';
            }
        }
    }

    async checkOpenTransactions(clientId) {
        // Проверяем незакрытые заказы клиента
        try {
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3002' : 'https://northrepublic.me';
            const response = await fetch(`${apiUrl}/api/poster/transactions.getTransactions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Token': window.API_TOKEN
                },
                body: JSON.stringify({
                    client_id: clientId
                })
            });
            
            if (response.ok) {
                const transactions = await response.json();
                console.log('All transactions:', transactions);
                
                // Ищем незакрытые заказы (date_close пустое или null)
                const openTransaction = transactions.find(transaction => 
                    !transaction.date_close || transaction.date_close === '' || transaction.date_close === '0000-00-00 00:00:00'
                );
                
                return openTransaction || null;
            }
        } catch (error) {
            console.warn('Error checking open transactions:', error);
        }
        return null;
    }

    async addToExistingOrder(transactionId) {
        // Добавляем товары к существующему заказу
        try {
            this.showToast('Добавляем товары к существующему заказу...', 'info');
            
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3002' : 'https://northrepublic.me';
            
            // Проверяем total_payed_sum клиента и применяем скидку если нужно
            if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
                const phone = window.authSystem.userData.phone;
                if (phone) {
                    const clientsResponse = await fetch(`${apiUrl}/api/poster/clients.getClients?phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                    if (clientsResponse.ok) {
                        const clientsData = await clientsResponse.json();
                        if (clientsData && clientsData.length > 0) {
                            const clientData = clientsData[0];
                            const totalPaidSum = clientData.total_payed_sum || 0;
                            
                            // Если новый клиент (total_payed_sum = 0), применяем скидку
                            if (totalPaidSum === 0) {
                                const discountResponse = await fetch(`${apiUrl}/api/poster/transactions.changeFiscalStatus`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-API-Token': window.API_TOKEN
                                    },
                                    body: JSON.stringify({
                                        transaction_id: transactionId,
                                        fiscal_status: 1 // Применяем скидку 20%
                                    })
                                });
                                
                                if (discountResponse.ok) {
                                    console.log('✅ Discount applied for new client in existing order');
                                } else {
                                    console.warn('Failed to apply discount to existing order');
                                }
                            }
                        }
                    }
                }
            }
            
            // Обновляем номер стола в существующем заказе, если новый заказ на столик
            const orderType = document.querySelector('input[name="orderType"]:checked').value;
            if (orderType === 'table') {
                const tableSelect = document.getElementById('tableNumber');
                const selectedTableId = tableSelect.value;
                if (selectedTableId) {
                    const selectedOption = tableSelect.options[tableSelect.selectedIndex];
                    const tableName = selectedOption.text;
                    const newComment = `Стол: ${tableName}`;
                    
                    // Обновляем комментарий заказа с новым номером стола
                    const updateResponse = await fetch(`${apiUrl}/api/poster/transactions.updateTransaction`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-Token': window.API_TOKEN
                        },
                        body: JSON.stringify({
                            transaction_id: transactionId,
                            comment: newComment
                        })
                    });
                    
                    if (updateResponse.ok) {
                        console.log('Table number updated in existing order');
                    } else {
                        console.warn('Failed to update table number in existing order');
                    }
                }
            }
            
            // Добавляем каждый товар к существующему заказу
            for (const item of this.items) {
                const response = await fetch(`${apiUrl}/api/poster/transactions.addTransactionProduct`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Token': window.API_TOKEN
                    },
                    body: JSON.stringify({
                        transaction_id: transactionId,
                        product_id: parseInt(item.id),
                        count: item.quantity,
                        price: Math.round(item.price * 100) // Convert to minor units
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to add product ${item.name} to transaction`);
                }
            }
            
            this.showToast('Товары успешно добавлены к существующему заказу!', 'success');
            this.clearCart();
            this.hideModal();
            
        } catch (error) {
            console.error('Error adding to existing order:', error);
            this.showToast('Ошибка при добавлении товаров к заказу', 'error');
        }
    }

    showDiscountInfo() {
        // Показываем информацию о скидке для существующих клиентов
        const cartTotal = document.querySelector('.cart-total');
        if (cartTotal) {
            const discountInfo = document.createElement('div');
            discountInfo.className = 'discount-info';
            discountInfo.innerHTML = `
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 8px; margin-top: 8px; font-size: 12px; color: #856404;">
                    💡 -20% на первый заказ каждому новому гостю
                </div>
            `;
            cartTotal.appendChild(discountInfo);
        }
    }

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#366b5b' : type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#366b5b'};
            color: white;
            padding: 12px 20px;
            border-radius: 4px;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.cart = new Cart();
});
