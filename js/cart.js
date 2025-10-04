// Cart functionality
class Cart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('cart') || '[]');
        if (!Array.isArray(this.items)) {
            this.items = [];
        }

        // Защита от флуда удалена - мгновенные обновления

        // Флаг для предотвращения дублирования заказов
        this.isSubmittingOrder = false;

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
        setTimeout(() => {
            console.log('🛒 Cart: Final attempt to update modal translations');
            this.updateCartModalTranslations();
        }, 2000);
    }

    async loadTranslations() {
        // Ждем, пока CartTranslations будет доступен
        let attempts = 0;
        while (!window.cartTranslations && attempts < 10) {
            await new Promise(resolve => setTimeout(resolve, 100));
            attempts++;
        }
        
        if (window.cartTranslations) {
            this.translations = await window.cartTranslations.load();
            console.log('🛒 Cart: Loaded translations:', this.translations);
            console.log('🛒 Cart: Current language:', window.cartTranslations.language);
        } else {
            console.error('🛒 Cart: CartTranslations not available after 1 second');
            this.setDefaultTranslations();
        }
    }
    
    setDefaultTranslations() {
        this.translations = {
            'your_order': 'Ваш заказ',
            'for_table': 'На столик',
            'takeaway': 'С собой',
            'delivery': 'Доставка',
            'total': 'Итого:',
            'enter_name': 'Ваше имя',
            'phone': 'Телефон',
            'table': 'Стол',
            'delivery_address': 'Адрес доставки (ссылка на Google карту)',
            'delivery_address_placeholder': 'https://maps.google.com/...',
            'delivery_time': 'Время доставки',
            'comment': 'Комментарий',
            'comment_placeholder': 'Сюда можно написать все, что вы хотели бы, чтобы мы учли',
            'cancel': 'Отмена',
            'place_order': 'Оформить заказ',
            'enter_name_placeholder': 'Введите ваше имя',
            'phone_placeholder': '+'
        };
        console.log('🛒 Cart: Using default translations');
    }

    // Метод для обновления переводов при смене языка
    async reloadTranslations() {
        if (window.cartTranslations) {
            this.translations = await window.cartTranslations.reload();
            console.log('🛒 Cart: Reloaded translations:', this.translations);
            // Обновляем отображение корзины с новыми переводами
            this.updateCartDisplay();
            // Всегда обновляем переводы модального окна корзины
            this.updateCartModalTranslations();
        }
    }

    t(key, fallback = null) {
        console.log(`🛒 Cart: Looking for translation key '${key}'`);
        console.log(`🛒 Cart: Available translations:`, this.translations);
        
        if (this.translations && this.translations[key]) {
            console.log(`🛒 Cart: Found translation for '${key}':`, this.translations[key]);
            return this.translations[key];
        }
        console.warn(`🛒 Cart: Missing translation for '${key}', using fallback:`, fallback || key);
        return fallback || key;
    }

    // Подсветка поля при ошибке валидации
    highlightField(fieldId, duration = 3000) {
        const field = document.getElementById(fieldId);
        if (!field) return;

        // Добавляем класс для подсветки
        field.classList.add('validation-error');
        
        // Убираем подсветку через указанное время
        setTimeout(() => {
            field.classList.remove('validation-error');
        }, duration);
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
        const wasNewItem = !existingItem;
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            // Устанавливаем цену 0 - она будет загружена из Poster API с учетом персональных скидок
            this.items.push({
                id: product.id,
                name: product.name,
                price: 0, // Цена будет загружена из Poster API
                quantity: 1,
                image: product.image
            });
        }
        
        this.saveCart();
        
        // Если добавили новый товар - нужна полная перерисовка
        if (wasNewItem) {
            this.updateCartDisplay();
        } else {
            // Если обновили существующий - только обновляем элементы
            this.updateAllCartElements();
        }
    }

    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartDisplay();
    }

    async updateQuantity(productId, quantity) {
        console.log(`updateQuantity called: productId=${productId}, quantity=${quantity}`);
        const item = this.items.find(item => item.id == productId); // Используем == для сравнения строки и числа
        console.log(`Found item:`, item);
        
        if (item) {
            const oldQuantity = item.quantity;
            console.log(`Old quantity: ${oldQuantity}, new quantity: ${quantity}`);
            
            // Если количество отрицательное - удаляем товар
            if (quantity < 0) {
                this.removeItem(productId);
                return;
            }
            
            // Если количество 0 - оставляем товар, но не синхронизируем с сервером
            item.quantity = quantity;
            this.saveCart();
            
            console.log(`Item quantity updated to: ${item.quantity}`);
            
            // Обновляем все элементы корзины
            try {
                console.log('About to call updateAllCartElements()');
                this.updateAllCartElements();
                console.log('updateAllCartElements() completed successfully');
            } catch (error) {
                console.error('Error in updateAllCartElements():', error);
            }
            
            // Синхронизируем с сервером только если количество > 0
            if (quantity > 0) {
                await this.syncQuantityChange(productId, oldQuantity, quantity);
            }
        } else {
            console.log(`Item not found for productId: ${productId}`);
        }
    }

    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();
    }

    // Обновление всех элементов корзины без перезаписи HTML
    updateAllCartElements() {
        try {
            console.log('updateAllCartElements called');
            console.log('Current items:', this.items);
        
        // 1. Обновляем счетчики товаров в корзине (только если модалка открыта)
        const cartItemsList = document.getElementById('cartItemsList');
        console.log('cartItemsList found:', !!cartItemsList);
        
        if (cartItemsList) {
            // Находим все элементы корзины
            const allCartItems = cartItemsList.querySelectorAll('.cart-item');
            console.log('Found cart items in DOM:', allCartItems.length);
            
            this.items.forEach(item => {
                console.log(`Looking for product ID: ${item.id} (type: ${typeof item.id})`);
                
                // Ищем элемент по ID ТОЛЬКО в модалке корзины (приводим к строке для сравнения)
                const cartItem = cartItemsList.querySelector(`[data-product-id="${String(item.id)}"]`);
                console.log(`Cart item found for ${item.id}:`, !!cartItem);
                
                if (cartItem) {
                    console.log(`Cart item HTML structure for ${item.id}:`, cartItem.outerHTML);
                    
                    const quantitySpan = cartItem.querySelector('.cart-item-quantity span');
                    console.log(`Quantity span found for ${item.id}:`, !!quantitySpan);
                    
                    if (quantitySpan) {
                        const oldValue = quantitySpan.textContent;
                        quantitySpan.textContent = item.quantity;
                        console.log(`Updated quantity for product ${item.id}: ${oldValue} -> ${item.quantity}`);
                    } else {
                        console.log(`Quantity span not found for product ${item.id}`);
                        // Попробуем найти другие возможные селекторы
                        const altSelectors = [
                            '.cart-item-quantity',
                            '.quantity',
                            '.qty',
                            '[class*="quantity"]',
                            '[class*="qty"]'
                        ];
                        
                        for (const selector of altSelectors) {
                            const element = cartItem.querySelector(selector);
                            if (element) {
                                console.log(`Found alternative element with selector "${selector}":`, element);
                                console.log(`Element HTML:`, element.outerHTML);
                            }
                        }
                    }
                    
                    // Обновляем цену товара (показываем оригинальную цену)
                    const priceElement = cartItem.querySelector('.cart-item-price');
                    if (priceElement) {
                        const displayPrice = item.originalPrice || item.price;
                        priceElement.textContent = `${this.formatNumber(displayPrice)} ₫`;
                        console.log(`Updated price for product ${item.id}: ${displayPrice} (original price displayed)`);
                    }
                } else {
                    console.log(`Cart item not found for product ID: ${item.id}`);
                    // Попробуем найти по другому селектору
                    const alternativeItem = cartItemsList.querySelector(`[data-product-id="${item.id}"]`);
                    console.log(`Alternative search for ${item.id}:`, !!alternativeItem);
                }
            });
        }

        // 2. Обновляем счетчик в иконке корзины
        this.updateCartIcon();

        // 3. Обновляем общую сумму (только если модалка открыта)
        const cartTotal = document.querySelector('.cart-total');
        if (cartTotal) {
            this.updateTotalDisplay();
        }
        
        // 4. Обновляем информацию о скидке
        this.updateDiscountInfo();
        } catch (error) {
            console.error('Error in updateAllCartElements:', error);
        }
    }

    // Обновление информации о скидке
    updateDiscountInfo() {
        const discountInfo = document.querySelector('.discount-info .discount-text');
        if (!discountInfo) return;
        
        // Проверяем, есть ли авторизованный пользователь со скидкой
        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
            const clientDiscount = window.authSystem.userData.max_discount || 0;
            if (clientDiscount > 0) {
                discountInfo.textContent = `-${clientDiscount}% скидка для вас`;
                discountInfo.style.color = '#4CAF50'; // Зеленый цвет для активной скидки
            } else {
                // Скидка удалена
                discountInfo.style.display = 'none';
            }
        } else {
            // Скидка удалена
            discountInfo.style.display = 'none';
        }
    }

    // Обновление только иконки корзины (счетчик товаров)
    updateCartIcon() {
        const cartCount = document.getElementById('cartCount');
        const cartIcon = document.getElementById('cartIcon');
        const cartIconImg = document.querySelector('.cart-icon-img');

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
    }

    // Синхронизация изменения количества с сервером
    async syncQuantityChange(productId, oldQuantity, newQuantity) {
        // Проверяем, есть ли открытая транзакция для авторизованного пользователя
        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
            try {
                const phone = window.authSystem.userData.phone;
                if (phone) {
                    // Получаем client_id
                    const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
                    const clientsResponse = await fetch(`${apiUrl}/api/proxy.php?path=poster/clients.getClients&phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                    
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
                                    const discountResponse = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.changeFiscalStatus`, {
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
                                    
                                    if (!discountResponse.ok) {
                                        throw new Error(`Failed to apply discount: ${discountResponse.statusText}`);
                                    }
                                    
                                    console.log(`✅ Discount applied for new client: ${clientId}`);
                                }
                                
                                // Отправляем изменение количества мгновенно
                                const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.changeTransactionProductCount`, {
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
        
        // Проверяем, есть ли скидка клиента
        let clientDiscount = 0;
        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
            clientDiscount = window.authSystem.userData.max_discount || 0;
        }
        
        // Вычисляем оригинальную сумму (без скидки клиента)
        const originalTotal = clientDiscount > 0 ? total / (1 - clientDiscount / 100) : total;
        
        if (clientDiscount > 0 && originalTotal > total) {
            // Показываем скидку клиента и зачеркнутую оригинальную цену
            cartTotal.innerHTML = `
                <div class="total-row discount-row">
                    <span>Скидка ${clientDiscount}%:</span>
                    <span class="original-price">${this.formatNumber(originalTotal)} ₫</span>
                </div>
                <div class="total-row total-final">
                    <span>Итого:</span>
                    <span class="total-amount">${this.formatNumber(total)} ₫</span>
                </div>
            `;
        } else if (this.promotionId === 1 && subtotal > 0) {
            // Показываем субтотал, скидку и итого (старая логика для промо)
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

        // Обновляем содержимое корзины (показываем все товары, включая с количеством 0)
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
            this.showToast(this.t('cart_empty', 'Корзина пуста'), 'info');
            return;
        }
        await this.showCartModal();
    }

    async showCartModal() {
        this.populateCartModal();
        this.showModal();
        this.showGuestFields();
        
        // Сначала загружаем данные пользователя, если он авторизован
        if (window.authSystem && window.authSystem.isAuthenticated) {
            // Загружаем данные пользователя если их нет
            if (!window.authSystem.userData) {
                console.log('🔄 Loading user data for cart...');
                await window.authSystem.loadUserData();
            }
            
            if (window.authSystem.userData) {
                // Проверяем, есть ли полные данные клиента (firstname, lastname)
                if (!window.authSystem.userData.firstname || !window.authSystem.userData.lastname) {
                    console.log('🔄 Loading full client data from Poster API...');
                    await this.loadClientDataFromPoster();
                }
                
                this.fillFieldsFromProfile(window.authSystem.userData);
                await this.checkAndApplyDiscount(window.authSystem.userData);
            } else {
                // Если не удалось загрузить данные, используем localStorage
                this.tryFillFromStoredData();
            }
        } else {
            // Если пользователь не авторизован, но есть данные в localStorage, пытаемся их использовать
            this.tryFillFromStoredData();
        }
        
        // Теперь загружаем актуальные цены от Poster API (с учетом скидки)
        await this.loadCurrentPricesFromPoster();
        
        // Обновляем информацию о скидке
        this.updateDiscountInfo();
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
        
        // Обновляем статичные элементы модального окна с переводами
        this.updateCartModalTranslations();
        
        // Фильтруем товары с количеством > 0
        const visibleItems = this.items.filter(item => item.quantity > 0);
        
        if (visibleItems.length === 0) {
            if (cartItemsList) {
                cartItemsList.innerHTML = `<p class="cart-empty-message">${this.t('cart_empty', 'Корзина пуста')}</p>`;
            }
            if (cartTotalAmount) {
                cartTotalAmount.textContent = '0 ₫';
            }
            return;
        }

        if (cartItemsList) {
            cartItemsList.innerHTML = visibleItems.map(item => {
                // Показываем оригинальную цену в cart-item-price
                const displayPrice = item.originalPrice || item.price;
                
                return `
                    <div class="cart-item" data-product-id="${item.id}">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${this.formatNumber(displayPrice)} ₫</div>
                        <div class="cart-item-quantity">
                            <a href="#" class="quantity-btn">-</a>
                            <span>${item.quantity}</span>
                            <a href="#" class="quantity-btn">+</a>
                        </div>
                    </div>
                `;
            }).join('');
        }

        if (cartTotalAmount) {
            cartTotalAmount.textContent = `${this.formatNumber(this.getTotal())} ₫`;
        }
    }

    // Обновление переводов в модальном окне корзины
    updateCartModalTranslations() {
        // Если переводы не загружены, пытаемся загрузить их
        if (!this.translations || Object.keys(this.translations).length === 0) {
            console.log('🛒 Cart: Translations not loaded yet, attempting to load...');
            this.loadTranslations().then(() => {
                console.log('🛒 Cart: Translations loaded, retrying modal update');
                this.updateCartModalTranslations();
            }).catch(error => {
                console.error('🛒 Cart: Failed to load translations:', error);
                // Используем дефолтные переводы
                this.setDefaultTranslations();
                this.updateCartModalTranslations();
            });
            return;
        }
        
        console.log('🛒 Cart: Updating cart modal translations with:', this.translations);
        
        // Автоматически переводим все элементы с атрибутом data-translate
        const elementsToTranslate = document.querySelectorAll('[data-translate]');
        console.log('🛒 Cart: Found', elementsToTranslate.length, 'elements to translate');
        
        elementsToTranslate.forEach((element, index) => {
            const key = element.getAttribute('data-translate');
            const translation = this.t(key);
            console.log(`🛒 Cart: Element ${index}: '${key}' -> '${translation}' (current text: '${element.textContent}')`);
            if (translation && translation !== key) {
                element.textContent = translation;
                console.log(`🛒 Cart: Updated element ${index} text to: '${element.textContent}'`);
            } else {
                console.warn(`🛒 Cart: No translation found for key '${key}'`);
            }
        });
        
        // Автоматически переводим все placeholder'ы с атрибутом data-translate-placeholder
        const inputsToTranslate = document.querySelectorAll('[data-translate-placeholder]');
        console.log('🛒 Cart: Found', inputsToTranslate.length, 'inputs to translate');
        
        inputsToTranslate.forEach(input => {
            const key = input.getAttribute('data-translate-placeholder');
            const translation = this.t(key);
            console.log(`🛒 Cart: Translating placeholder '${key}' to '${translation}'`);
            if (translation && translation !== key) {
                input.placeholder = translation;
            }
        });
    }

    showModal() {
        const modal = document.getElementById('cartModal');
        const overlay = document.getElementById('modalOverlay');
        
        modal.classList.remove('modal-hidden');
        overlay.classList.remove('overlay-hidden');
        
        // Принудительно загружаем переводы при открытии корзины
        this.forceLoadTranslations();
        
        // Bind modal events
        this.bindModalEvents();
    }
    
    async forceLoadTranslations() {
        // Если переводы не загружены, загружаем их принудительно
        if (!this.translations || Object.keys(this.translations).length === 0) {
            console.log('🛒 Cart: Force loading translations for modal');
            await this.loadTranslations();
        }
        
        // Обновляем переводы модального окна
        this.updateCartModalTranslations();
    }

    hideModal() {
        const modal = document.getElementById('cartModal');
        const overlay = document.getElementById('modalOverlay');
        
        modal.classList.add('modal-hidden');
        overlay.classList.add('overlay-hidden');
        
        // Удаляем товары с количеством 0 при закрытии корзины
        this.cleanupZeroQuantityItems();
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
            option.textContent = this.t('tables_not_found', 'Столы не найдены');
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
            this.showToast(this.t('delivery_time_too_soon', 'Мы не успеем так быстро, но постараемся!'), 'warning');
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
            this.showToast(this.t('enter_name', 'Введите ваше имя'), 'error');
            this.highlightField('customerName');
            return false;
        }
        
        if (!phone) {
            this.showToast(this.t('enter_phone', 'Введите номер телефона'), 'error');
            this.highlightField('customerPhone');
            return false;
        }
        
        // Проверяем валидность телефона
        if (phone.length < 8 || !phone.startsWith('+')) {
            this.showToast(this.t('enter_correct_phone', 'Введите корректный номер телефона'), 'error');
            this.highlightField('customerPhone');
            return false;
        }
        
        if (orderType === 'table') {
            const table = document.getElementById('tableNumber').value;
            
            if (!table) {
                this.showToast(this.t('select_table', 'Выберите номер стола'), 'error');
                this.highlightField('tableNumber');
                return false;
            }
        } else if (orderType === 'takeaway') {
            // Для заказа с собой дополнительных полей не требуется
        } else if (orderType === 'delivery') {
            const address = document.getElementById('deliveryAddress').value.trim();
            const deliveryTime = document.getElementById('deliveryTime').value;
            
            if (!address) {
                this.showToast(this.t('enter_address', 'Введите адрес доставки'), 'error');
                this.highlightField('deliveryAddress');
                return false;
            }
            
            if (!deliveryTime) {
                this.showToast(this.t('select_delivery_time', 'Выберите время доставки'), 'error');
                this.highlightField('deliveryTime');
                return false;
            }
        }
        
        return true;
    }

    async submitOrder() {
        // Защита от повторных отправок
        if (this.isSubmittingOrder) {
            console.log('⚠️ Order submission already in progress, ignoring duplicate request');
            return;
        }

        this.isSubmittingOrder = true;

        if (this.items.length === 0) {
            this.showToast(this.t('cart_empty', 'Корзина пуста'), 'error');
            this.isSubmittingOrder = false;
            return;
        }

        if (!this.validateOrderForm()) {
            this.isSubmittingOrder = false;
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
                const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
                const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/clients.getClients&phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                
                if (response.ok) {
                    const clientsData = await response.json();
                    if (clientsData && clientsData.length > 0) {
                        // Берем первого найденного клиента
                        orderData.client_id = clientsData[0].client_id;
                        console.log('Found client_id:', orderData.client_id);
                        
                        // ОТКЛЮЧЕНО: Проверяем незакрытые заказы
                        // const openTransaction = await this.checkOpenTransactions(orderData.client_id);
                        // if (openTransaction) {
                        //     console.log('Found open transaction:', openTransaction);
                        //     // Добавляем товары к существующему заказу
                        //     await this.addToExistingOrder(openTransaction.transaction_id);
                        //     return;
                        // }
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
            this.showToast(this.t('sending_order', 'Отправляем заказ...'), 'info');
            
            // Используем основной домен для API запросов
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
            const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/orders/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Token': window.API_TOKEN
                },
                body: JSON.stringify(orderData)
            });

            if (response.ok) {
                const result = await response.json();
                this.showToast(this.t('order_success', 'Заказ успешно отправлен!'), 'success');
                
                // Сохраняем данные клиента для будущих заказов
                this.saveCustomerData(name, phone);
                
                this.clearCart();
                this.hideModal();
                console.log('Order created:', result);
            } else {
                const error = await response.json();
                this.showToast(`${this.t('order_error', 'Ошибка при отправке заказа')}: ${error.message}`, 'error');
            }
        } catch (error) {
            console.error('Order submission error:', error);
            this.showToast(this.t('order_error', 'Ошибка при отправке заказа'), 'error');
        } finally {
            // Сбрасываем флаг в любом случае
            this.isSubmittingOrder = false;
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

    // Удаление товаров с количеством 0 при закрытии корзины
    cleanupZeroQuantityItems() {
        const initialLength = this.items.length;
        this.items = this.items.filter(item => item.quantity > 0);
        
        // Если что-то удалили, сохраняем изменения
        if (this.items.length !== initialLength) {
            this.saveCart();
            this.updateCartDisplay();
        }
    }

    showAuthModal() {
        // Auth modal is now handled by AuthSystem in menu2.php
        // This method is no longer used
        console.log('Auth modal handled by AuthSystem');
    }

    fillFieldsFromProfile(userData) {
        // Заполняем поля корзины данными из профиля
        console.log('🔍 fillFieldsFromProfile called with userData:', userData);
        
        const nameField = document.getElementById('customerName');
        const phoneField = document.getElementById('customerPhone');
        
        console.log('🔍 Name field found:', !!nameField);
        console.log('🔍 Phone field found:', !!phoneField);
        console.log('🔍 userData.firstname:', userData.firstname);
        console.log('🔍 userData.lastname:', userData.lastname);
        console.log('🔍 userData.phone:', userData.phone);
        
        if (nameField && userData.firstname && userData.lastname) {
            const fullName = `${userData.firstname} ${userData.lastname}`.trim();
            nameField.value = fullName;
            console.log('✅ Name field filled with:', fullName);
        } else if (nameField && userData.client_name) {
            nameField.value = userData.client_name;
            console.log('✅ Name field filled with client_name:', userData.client_name);
        } else {
            console.log('❌ Name field not filled - missing data');
        }
        
        if (phoneField && userData.phone) {
            phoneField.value = userData.phone;
            console.log('✅ Phone field filled with:', userData.phone);
        } else {
            console.log('❌ Phone field not filled - missing data');
        }
    }

    async loadCurrentPricesFromPoster() {
        // Загружаем актуальные цены товаров из Poster API
        try {
            if (this.items.length === 0) {
                console.log('🛒 Cart is empty, no need to load prices');
                return;
            }
            
            console.log('💰 Loading current prices from Poster API...');
            
            // Убеждаемся, что данные пользователя загружены
            if (window.authSystem && window.authSystem.isAuthenticated && !window.authSystem.userData) {
                console.log('🔄 Loading user data before price calculation...');
                await window.authSystem.loadUserData();
            }
            
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : 'https://veranda.my';
            
            // Получаем все продукты из Poster API
            const productsResponse = await fetch(`${apiUrl}/api/proxy.php?path=menu`);
            
            if (productsResponse.ok) {
                const productsData = await productsResponse.json();
                console.log('📥 Products data from Poster API:', productsData);
                
                // Диагностика: проверяем тип и структуру данных
                console.log('🔍 DEBUG: productsData type:', typeof productsData);
                console.log('🔍 DEBUG: productsData is Array:', Array.isArray(productsData));
                console.log('🔍 DEBUG: productsData keys:', productsData ? Object.keys(productsData) : 'null/undefined');
                console.log('🔍 DEBUG: productsData value:', productsData);

                // Исправление: API возвращает объект с полем products, а не массив продуктов
                const productsArray = productsData.products || productsData;

                console.log('🔍 DEBUG: productsArray type:', typeof productsArray);
                console.log('🔍 DEBUG: productsArray is Array:', Array.isArray(productsArray));

                if (!Array.isArray(productsArray)) {
                    console.error('❌ productsArray is not an array:', productsArray);
                    this.showToast(this.t('price_load_error', 'Ошибка загрузки цен товаров'), 'error');
                    return;
                }

                // Обновляем цены в корзине
                let pricesUpdated = false;
                this.items.forEach(item => {
                    console.log('🔍 DEBUG: Looking for product ID:', item.id, 'in products array');
                    const productFromAPI = productsArray.find(p => p.product_id == item.id);
                    if (productFromAPI) {
                        console.log(`🔍 Product ${item.name} (ID: ${item.id}) - API price:`, productFromAPI.price, 'Type:', typeof productFromAPI.price);
                        
                        // Безопасное преобразование цены из Poster API
                        let priceValue = productFromAPI.price;
                        
                        // Если цена - объект (формат {"1":"7000000"}), извлекаем первое значение
                        if (typeof priceValue === 'object' && priceValue !== null) {
                            const keys = Object.keys(priceValue);
                            if (keys.length > 0) {
                                priceValue = priceValue[keys[0]]; // Берем первое значение (обычно spot ID)
                                console.log(`💰 Extracted price from spot ${keys[0]}: ${priceValue}`);
                            } else {
                                priceValue = 0;
                            }
                        }
                        
                        // Нормализация: деление на 100 (из копеек в донги)
                        const rawPrice = parseFloat(priceValue);
                        const originalPrice = rawPrice / 100;
                        let newPrice = originalPrice;
                        
                        // Применяем скидку клиента, если он авторизован
                        console.log('🔍 Auth system check:', {
                            hasAuthSystem: !!window.authSystem,
                            isAuthenticated: window.authSystem?.isAuthenticated,
                            hasUserData: !!window.authSystem?.userData,
                            userData: window.authSystem?.userData
                        });
                        
                        if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
                            const clientDiscount = window.authSystem.userData.max_discount || 0;
                            console.log(`🎯 Client discount: ${clientDiscount}%`);
                            if (clientDiscount > 0) {
                                const discountAmount = originalPrice * (clientDiscount / 100);
                                newPrice = originalPrice - discountAmount;
                                console.log(`🎯 Applied ${clientDiscount}% discount to ${item.name}: ${originalPrice} -> ${newPrice}`);
                            } else {
                                console.log(`🎯 No discount applied to ${item.name} (discount: ${clientDiscount}%)`);
                            }
                        } else {
                            console.log(`🎯 No discount applied to ${item.name} (user not authenticated or no user data)`);
                        }
                        
                        // Сохраняем оригинальную цену для отображения
                        item.originalPrice = originalPrice;
                        
                        if (!isNaN(newPrice) && newPrice > 0) {
                            const oldPrice = item.price;
                            item.price = newPrice;
                            console.log(`💰 Price updated for ${item.name}: ${oldPrice} -> ${item.price}`);
                            pricesUpdated = true;
                        } else if (isNaN(newPrice) || newPrice <= 0) {
                            console.warn(`⚠️ Invalid price for ${item.name}:`, productFromAPI.price, `(extracted: ${priceValue}, parsed: ${newPrice})`);
                            // Не обновляем цену, если она невалидна
                        } else {
                            console.log(`✅ Price for ${item.name} is already up to date: ${item.price}`);
                        }
                    } else {
                        console.warn(`⚠️ Product not found in API for ID: ${item.id}`);
                    }
                });
                
                if (pricesUpdated) {
                    // Сохраняем обновленные данные корзины
                    this.saveCart();
                    // Обновляем отображение цен и общей суммы
                    this.updateAllCartElements();
                    console.log('✅ Cart prices updated from Poster API');
                } else {
                    console.log('✅ All prices are up to date');
                }
            } else {
                console.error('❌ Failed to fetch products from Poster API:', productsResponse.statusText);
            }
        } catch (error) {
            console.error('❌ Error loading prices from Poster API:', error);
        }
    }

    // Получить максимальную скидку клиента
    getClientDiscount(clientData) {
        if (!clientData) return 0;
        
        const personalDiscount = parseFloat(clientData.discount_per || 0);
        const groupDiscount = parseFloat(clientData.client_groups_discount || 0);
        
        const maxDiscount = Math.max(personalDiscount, groupDiscount);
        console.log(`🎯 Client discounts - Personal: ${personalDiscount}%, Group: ${groupDiscount}%, Max: ${maxDiscount}%`);
        
        return maxDiscount;
    }

    async loadClientDataFromPoster() {
        // Загружаем полные данные клиента из Poster API
        try {
            const phone = window.authSystem.userData.phone;
            if (!phone) {
                console.log('❌ No phone number available for client lookup');
                return;
            }
            
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : '';
            const clientsResponse = await fetch(`${apiUrl}/api/proxy.php?path=poster/clients.getClients&phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
            
            if (clientsResponse.ok) {
                const clientsData = await clientsResponse.json();
                if (clientsData && clientsData.length > 0) {
                    const clientData = clientsData[0];
                    console.log('📥 Full client data from Poster API:', clientData);
                    
                    // Получаем максимальную скидку
                    const maxDiscount = this.getClientDiscount(clientData);
                    
                    // Обновляем данные пользователя
                    window.authSystem.userData = {
                        ...window.authSystem.userData,
                        firstname: clientData.firstname,
                        lastname: clientData.lastname,
                        client_name: clientData.client_name,
                        total_payed_sum: clientData.total_payed_sum || 0,
                        discount_per: clientData.discount_per || 0,
                        client_groups_discount: clientData.client_groups_discount || 0,
                        max_discount: maxDiscount
                    };
                    
                    // Сохраняем в localStorage для кеширования
                    localStorage.setItem('user_client_data', JSON.stringify(clientData));
                    
                    console.log('✅ Client data updated and cached');
                } else {
                    console.log('❌ No client found in Poster API');
                }
            } else {
                console.error('❌ Failed to fetch client data from Poster API:', clientsResponse.statusText);
            }
        } catch (error) {
            console.error('❌ Error loading client data from Poster API:', error);
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
            // Скидка удалена - не применяем автоматическую скидку
            this.showDiscountText(false);
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
                discountInfoElement.style.backgroundColor = '#b88746ff';
            } else {
                discountInfoElement.style.display = 'none';
            }
        }
    }

    async checkOpenTransactions(clientId) {
        // Проверяем незакрытые заказы клиента
        try {
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : 'https://veranda.my';
            // Добавляем date_from и date_to для корректного запроса
            const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            const weekAgoStr = weekAgo.toISOString().split('T')[0]; // YYYY-MM-DD
            
            const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.getTransactions&client_id=${clientId}&date_from=${weekAgoStr}&date_to=${today}&token=${window.API_TOKEN}`, {
                method: 'GET',
                headers: {
                    'X-API-Token': window.API_TOKEN
                }
            });
            
            if (response.ok) {
                const transactions = await response.json();
                console.log('All transactions:', transactions);
                
                // Проверяем, что это массив, а не объект ошибки
                if (Array.isArray(transactions)) {
                    // Ищем незакрытые заказы (date_close пустое или null)
                    const openTransaction = transactions.find(transaction => 
                        !transaction.date_close || transaction.date_close === '' || transaction.date_close === '0000-00-00 00:00:00'
                    );
                    
                    return openTransaction || null;
                } else if (transactions.error) {
                    console.warn('Poster API error:', transactions.message);
                    return null;
                }
            }
        } catch (error) {
            console.warn('Error checking open transactions:', error);
        }
        return null;
    }

    async addToExistingOrder(transactionId) {
        // Добавляем товары к существующему заказу
        try {
            this.showToast(this.t('adding_to_existing_order', 'Добавляем товары к существующему заказу...'), 'info');
            
            const apiUrl = window.location.hostname === 'localhost' ? 'http://localhost:3003' : 'https://veranda.my';
            
            // Проверяем total_payed_sum клиента и применяем скидку если нужно
            if (window.authSystem && window.authSystem.isAuthenticated && window.authSystem.userData) {
                const phone = window.authSystem.userData.phone;
                if (phone) {
                    const clientsResponse = await fetch(`${apiUrl}/api/proxy.php?path=poster/clients.getClients&phone=${encodeURIComponent(phone)}&token=${window.API_TOKEN}`);
                    if (clientsResponse.ok) {
                        const clientsData = await clientsResponse.json();
                        if (clientsData && clientsData.length > 0) {
                            const clientData = clientsData[0];
                            const totalPaidSum = clientData.total_payed_sum || 0;
                            
                            // Если новый клиент (total_payed_sum = 0), применяем скидку
                            if (totalPaidSum === 0) {
                                const discountResponse = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.changeFiscalStatus`, {
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
                    const updateResponse = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.updateTransaction`, {
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
                const response = await fetch(`${apiUrl}/api/proxy.php?path=poster/transactions.addTransactionProduct`, {
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
            
            this.showToast(this.t('added_to_existing_order', 'Товары успешно добавлены к существующему заказу!'), 'success');
            this.clearCart();
            this.hideModal();
            
        } catch (error) {
            console.error('Error adding to existing order:', error);
            this.showToast(this.t('error_adding_to_existing_order', 'Ошибка при добавлении товаров к заказу'), 'error');
        }
    }

    showDiscountInfo() {
        // Показываем информацию о скидке для существующих клиентов
        const cartTotal = document.querySelector('.cart-total');
        if (cartTotal) {
            const discountInfo = document.createElement('div');
            discountInfo.className = 'discount-info';
            // Скидка удалена
            discountInfo.style.display = 'none';
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
            background: ${type === 'success' ? '#b88746ff' : type === 'error' ? '#f44336' : type === 'warning' ? '#ff9800' : '#b88746ff'};
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
