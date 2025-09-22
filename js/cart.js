// Cart functionality
class Cart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('cart') || '[]');
        if (!Array.isArray(this.items)) {
            this.items = [];
        }
        this.init();
    }
    
    // Функция для форматирования чисел с пробелами
    formatNumber(num) {
        return num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
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

    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartDisplay();
    }

    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
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
                <div class="cart-item">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${this.formatNumber(item.price)} ₫</div>
                    <div class="cart-item-quantity">
                        <a href="#" class="quantity-btn" onclick="cart.updateQuantity('${item.id}', ${item.quantity - 1}); return false;">-</a>
                        <span>${item.quantity}</span>
                        <a href="#" class="quantity-btn" onclick="cart.updateQuantity('${item.id}', ${item.quantity + 1}); return false;">+</a>
                    </div>
                </div>
            `).join('');

            cartTotalAmount.textContent = `${this.formatNumber(this.getTotal())} ₫`;
        }
    }

    toggleCart() {
        if (this.items.length === 0) {
            this.showToast('Корзина пуста', 'info');
            return;
        }
        this.showCartModal();
    }

    showCartModal() {
        this.populateCartModal();
        this.showModal();
        this.showGuestFields();
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
        
        if (this.items.length === 0) {
            cartItemsList.innerHTML = '<p class="cart-empty-message">Корзина пуста</p>';
            cartTotalAmount.textContent = '0 ₫';
            return;
        }

        cartItemsList.innerHTML = this.items.map(item => `
            <div class="cart-item">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">${this.formatNumber(item.price)} ₫</div>
                <div class="cart-item-quantity">
                    <a href="#" class="quantity-btn" onclick="cart.updateQuantity('${item.id}', ${item.quantity - 1}); return false;">-</a>
                    <span>${item.quantity}</span>
                    <a href="#" class="quantity-btn" onclick="cart.updateQuantity('${item.id}', ${item.quantity + 1}); return false;">+</a>
                </div>
            </div>
        `).join('');

        cartTotalAmount.textContent = `${this.formatNumber(this.getTotal())} ₫`;
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
        select.innerHTML = '<option value="">Выберите стол</option>';
        
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
                this.showToast('Выберите стол', 'error');
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
        // TODO: Implement auth modal
        this.showToast('Модалка авторизации будет реализована', 'info');
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
