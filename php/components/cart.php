<?php
// Cart component for North Republic website
// Usage: include 'components/cart.php';
?>
<!-- Cart Sidebar -->
<div id="cart-sidebar" class="cart-sidebar">
    <div class="cart-sidebar__header">
        <h3>Корзина</h3>
        <button class="cart-close" id="cart-close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
        </button>
    </div>
    
    <div class="cart-sidebar__content">
        <div class="cart-items" id="cart-items">
            <!-- Cart items will be populated here -->
        </div>
        
        <div class="cart-empty" id="cart-empty">
            <p>Ваша корзина пуста</p>
            <a href="/menu.php" class="btn btn--primary">Посмотреть меню</a>
        </div>
    </div>
    
    <div class="cart-sidebar__footer">
        <div class="cart-total">
            <div class="cart-total__row">
                <span>Итого:</span>
                <span class="cart-total__amount" id="cart-total">0 ₽</span>
            </div>
        </div>
        
        <div class="cart-actions">
            <button class="btn btn--outline" id="clear-cart">Очистить</button>
            <button class="btn btn--primary" id="checkout-btn">Оформить заказ</button>
        </div>
    </div>
</div>

<!-- Cart Overlay -->
<div id="cart-overlay" class="cart-overlay"></div>

<style>
/* Cart Sidebar Styles */
.cart-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}

.cart-sidebar.open {
    right: 0;
}

.cart-sidebar__header {
    padding: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-sidebar__header h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c2c2c;
}

.cart-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    color: #666;
    transition: color 0.3s ease;
}

.cart-close:hover {
    color: #d4af37;
}

.cart-sidebar__content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item__image {
    width: 60px;
    height: 60px;
    background: #f5f5f5;
    border-radius: 8px;
    margin-right: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.cart-item__details {
    flex: 1;
}

.cart-item__name {
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #2c2c2c;
}

.cart-item__price {
    color: #d4af37;
    font-weight: 600;
}

.cart-item__controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-item__quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-item__quantity button {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.cart-item__quantity button:hover {
    background: #d4af37;
    color: #fff;
    border-color: #d4af37;
}

.cart-item__quantity span {
    min-width: 30px;
    text-align: center;
    font-weight: 600;
}

.cart-item__remove {
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 0.5rem;
    transition: color 0.3s ease;
}

.cart-item__remove:hover {
    color: #e74c3c;
}

.cart-empty {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.cart-sidebar__footer {
    padding: 1.5rem;
    border-top: 1px solid #e0e0e0;
    background: #f9f9f9;
}

.cart-total__row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.cart-total__amount {
    color: #d4af37;
}

.cart-actions {
    display: flex;
    gap: 1rem;
}

.cart-actions .btn {
    flex: 1;
}

.cart-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.cart-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .cart-sidebar {
        width: 100%;
        right: -100%;
    }
    
    .cart-actions {
        flex-direction: column;
    }
}

/* Header Cart Button Styles */
.header-cart {
    position: relative;
}

.header-cart .btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: #fff;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 20px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-auth {
    display: flex;
    align-items: center;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-name {
    font-weight: 600;
    color: #2c2c2c;
}

@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .header-auth,
    .header-cart {
        width: 100%;
    }
    
    .header-auth .btn,
    .header-cart .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Cart functionality
class Cart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('cart') || '[]');
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateCartDisplay();
    }

    bindEvents() {
        // Cart toggle
        document.getElementById('cart-toggle')?.addEventListener('click', () => {
            this.toggleCart();
        });

        // Cart close
        document.getElementById('cart-close')?.addEventListener('click', () => {
            this.closeCart();
        });

        // Overlay click
        document.getElementById('cart-overlay')?.addEventListener('click', () => {
            this.closeCart();
        });

        // Clear cart
        document.getElementById('clear-cart')?.addEventListener('click', () => {
            this.clearCart();
        });

        // Checkout
        document.getElementById('checkout-btn')?.addEventListener('click', () => {
            this.checkout();
        });
    }

    addItem(product) {
        const existingItem = this.items.find(item => item.id === product.product_id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.items.push({
                id: product.product_id,
                name: product.name,
                price: product.price_normalized || product.price,
                quantity: 1,
                image: product.image_url
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
        const cartCount = document.getElementById('cart-count');
        const cartItems = document.getElementById('cart-items');
        const cartEmpty = document.getElementById('cart-empty');
        const cartTotal = document.getElementById('cart-total');

        // Update count
        const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
        if (cartCount) {
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
        }

        // Update items display
        if (this.items.length === 0) {
            if (cartItems) cartItems.style.display = 'none';
            if (cartEmpty) cartEmpty.style.display = 'block';
        } else {
            if (cartItems) cartItems.style.display = 'block';
            if (cartEmpty) cartEmpty.style.display = 'none';
            
            if (cartItems) {
                cartItems.innerHTML = this.items.map(item => `
                    <div class="cart-item">
                        <div class="cart-item__image">
                            ${item.image ? `<img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">` : '🍽️'}
                        </div>
                        <div class="cart-item__details">
                            <div class="cart-item__name">${item.name}</div>
                            <div class="cart-item__price">${item.price.toFixed(0)} ₽</div>
                        </div>
                        <div class="cart-item__controls">
                            <div class="cart-item__quantity">
                                <button onclick="cart.updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                                <span>${item.quantity}</span>
                                <button onclick="cart.updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                            </div>
                            <button class="cart-item__remove" onclick="cart.removeItem('${item.id}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }

        // Update total
        if (cartTotal) {
            cartTotal.textContent = `${this.getTotal().toFixed(0)} ₽`;
        }
    }

    toggleCart() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
    }

    closeCart() {
        const sidebar = document.getElementById('cart-sidebar');
        const overlay = document.getElementById('cart-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
    }

    checkout() {
        if (this.items.length === 0) {
            alert('Корзина пуста');
            return;
        }

        // TODO: Implement checkout logic
        alert('Функция оформления заказа будет реализована позже');
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.cart = new Cart();
});

// Add to cart function for menu items
function addToCart(product) {
    if (window.cart) {
        window.cart.addItem(product);
        window.cart.toggleCart();
    }
}
</script>
