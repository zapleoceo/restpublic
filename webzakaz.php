<?php
require_once __DIR__ . '/vendor/autoload.php';
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}


session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['webzakaz_cashier_id']);
$cashierName = $_SESSION['webzakaz_cashier_name'] ?? 'Гость';

// If not logged in, redirect to login
if (!$isLoggedIn) {
    header('Location: webzakaz-login.php');
    exit();
}

// Backend API URL
$backendUrl = $_ENV['BACKEND_URL'] ?? 'http://localhost:3003';
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['webzakaz_cashier_id']);
$cashierName = $_SESSION['webzakaz_cashier_name'] ?? 'Гость';

// If not logged in, redirect to login
if (!$isLoggedIn) {
    header('Location: webzakaz-login.php');
    exit();
}

// Backend API URL
$backendUrl = 'http://localhost:3003';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebZakaz | Veranda</title>
    <style>
        /* Основные стили (Veranda-стиль: светлый, чистый, с зелеными акцентами) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        :root {
            --primary-color: #5cb85c;
            --primary-dark: #4cae4c;
            --secondary-bg: #ffffff;
            --border-color: #ddd;
            --header-bg: #e9ecef;
        }

        /* HEADER */
        .header {
            background-color: var(--header-bg);
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .header-left, .header-right {
            display: flex;
            align-items: center;
        }

        .header-logo {
            font-weight: bold;
            font-size: 18px;
            color: var(--primary-dark);
            margin-right: 30px;
        }

        .header-info span {
            margin-left: 15px;
        }

        .logout-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-weight: bold;
            margin-left: 20px;
            padding: 5px 10px;
        }

        .logout-btn:hover {
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        /* CONTROL PANEL */
        .control-panel {
            background-color: var(--secondary-bg);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .spot-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .spot-selector select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .action-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ccc;
        }

        .btn-order-add {
            background-color: #ffc107;
            color: #333;
        }

        /* MAIN LAYOUT */
        .main-container {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
            padding: 10px;
            gap: 10px;
        }

        .menu-panel, .order-panel {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
        }

        .menu-panel {
            flex: 3;
            min-width: 350px;
        }

        .order-panel {
            flex: 2;
            min-width: 300px;
            display: flex;
            flex-direction: column;
        }

        /* MENU STYLES */
        .menu-search input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .category-item {
            cursor: pointer;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }

        .category-title {
            font-weight: bold;
            display: flex;
            align-items: center;
            color: var(--primary-dark);
            font-size: 15px;
        }

        .expand-icon {
            margin-right: 10px;
            font-size: 1.2em;
            user-select: none;
        }

        .dishes-list {
            padding-left: 15px;
            list-style: none;
            margin: 5px 0 0 0;
        }

        .dish-item {
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            cursor: pointer;
            transition: background-color 0.1s;
            border-radius: 4px;
            padding-left: 5px;
            padding-right: 5px;
        }

        .dish-item:hover {
            background-color: #f0f0f0;
        }

        .dish-price {
            font-weight: 600;
            color: #666;
        }

        /* ORDER STYLES */
        .order-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .order-items {
            flex-grow: 1;
            margin-bottom: 15px;
            padding-right: 5px;
            overflow-y: auto;
        }

        .order-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .item-name {
            font-weight: bold;
            flex-grow: 1;
        }

        .item-quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-btn {
            background-color: #eee;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .item-comment input {
            width: 100%;
            border: 1px dashed #ccc;
            padding: 5px;
            margin-top: 5px;
            font-size: 12px;
        }

        .order-summary {
            border-top: 2px solid var(--primary-color);
            padding-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .order-comment-area textarea {
            width: 100%;
            min-height: 50px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            resize: vertical;
        }

        .submit-area button {
            width: 100%;
            margin-bottom: 10px;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
        }

        .modal h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
        }

        .modal-order-number {
            font-size: 2.5em;
            color: #dc3545;
            margin: 10px 0;
            font-weight: bold;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 2000;
            display: none;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <span class="header-logo">🍽️ WebZakaz</span>
            <span class="header-info">Кассир: <strong><?= htmlspecialchars($cashierName) ?></strong></span>
        </div>
        <div class="header-right">
            <span class="header-info" id="cache-status">⏰ Кеш актуален</span>
            <button class="logout-btn" id="logout-btn">🚪 Выход</button>
        </div>
    </header>

    <div class="control-panel">
        <div class="spot-selector">
            <label for="hall-select">Зал:</label>
            <select id="hall-select">
                <option value="">Загрузка...</option>
            </select>

            <label for="spot-select">Стол:</label>
            <select id="spot-select">
                <option value="">Выберите зал</option>
            </select>
        </div>

        <div class="order-actions">
            <button class="action-btn btn-secondary" id="refresh-cache-btn">🔄 Обновить Кеш</button>
            <button class="action-btn btn-primary" id="new-order-btn">🟢 НОВЫЙ ЗАКАЗ</button>
            <button class="action-btn btn-order-add" id="add-to-existing-btn" style="display:none;">
                🟠 ДОПОЛНИТЬ К ЗАКАЗУ №<span id="existing-order-number"></span>
            </button>
        </div>
    </div>

    <div class="main-container">
        <div class="menu-panel">
            <div class="menu-search">
                <input type="text" id="dish-search" placeholder="🔍 Поиск блюд..." autocomplete="off">
            </div>
            
            <div class="menu-tree" id="menu-tree">
                <div class="loading">Загрузка меню...</div>
            </div>
        </div>

        <div class="order-panel">
            <div class="order-title">ЗАКАЗ № <span id="current-order-number">Новый</span></div>
            <div class="order-info">Стол: <span id="current-spot-display">-</span></div>
            
            <hr style="margin: 10px 0; border-top: 1px solid #eee;">
            
            <div class="order-items" id="order-items-list">
                <div class="loading">Заказ пуст</div>
            </div>
            
            <div class="order-summary">
                <div class="total-row">
                    <span>ИТОГО:</span>
                    <span id="order-total">0 ₫</span>
                </div>
                <div class="order-comment-area">
                    <label for="order-comment">Комментарий к заказу:</label>
                    <textarea id="order-comment" placeholder="Общий комментарий к заказу (например, Клиент спешит)"></textarea>
                </div>
                <div class="submit-area">
                    <button class="action-btn btn-primary" id="submit-order-btn">🚀 ОТПРАВИТЬ ЗАКАЗ</button>
                    <button class="action-btn btn-secondary" id="clear-order-btn">❌ ОЧИСТИТЬ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal для номера заказа -->
    <div id="order-modal" class="modal">
        <div class="modal-content">
            <h3>✅ Заказ успешно создан</h3>
            <p style="font-size: 1.1em;">НОМЕР ДЛЯ ТИКЕТА:</p>
            <h1 id="modal-order-number" class="modal-order-number">№0</h1>
            <p style="color: #6c757d; margin: 15px 0;">⚠️ ОБЯЗАТЕЛЬНО отправьте этот номер на кухню вручную через главный POS.</p>
            
            <button class="action-btn btn-primary" id="copy-order-number-btn" style="margin-top: 15px;">
                📋 КОПИРОВАТЬ НОМЕР
            </button>
            <button class="action-btn btn-secondary" onclick="closeOrderModal()">OK</button>
        </div>
    </div>

    <!-- Toast уведомления -->
    <div id="toast" class="toast"></div>

    <script>
        const BACKEND_URL = '<?= $backendUrl ?>';
        let currentOrder = {
            hallId: null,
            spotId: null,
            items: [],
            orderComment: '',
            total: 0
        };

        let categories = [];
        let dishes = [];
        let halls = {};

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            checkAuth();
            loadHalls();
            loadMenuData();
            setupEventListeners();
        });

        // Проверка авторизации
        async function checkAuth() {
            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/auth/check`, {
                    credentials: 'include'
                });
                const data = await response.json();
                if (!data.success) {
                    window.location.href = 'webzakaz-login.php';
                }
            } catch (error) {
                console.error('Auth check error:', error);
                window.location.href = 'webzakaz-login.php';
            }
        }

        // Загрузка залов и столов
        async function loadHalls() {
            const cached = localStorage.getItem('poster_halls');
            const cacheTime = localStorage.getItem('poster_halls_time');
            
            if (cached && (Date.now() - parseInt(cacheTime)) < 60 * 60 * 1000) {
                halls = JSON.parse(cached);
                populateHallsSelect();
                return;
            }

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/halls`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    halls = data.data;
                    localStorage.setItem('poster_halls', JSON.stringify(halls));
                    localStorage.setItem('poster_halls_time', Date.now().toString());
                    populateHallsSelect();
                }
            } catch (error) {
                console.error('Load halls error:', error);
                showToast('Ошибка загрузки залов');
            }
        }

        function populateHallsSelect() {
            const hallSelect = document.getElementById('hall-select');
            hallSelect.innerHTML = '<option value="">Выберите зал</option>';
            
            Object.keys(halls).forEach(hallId => {
                const option = document.createElement('option');
                option.value = hallId;
                option.textContent = halls[hallId].hallName;
                hallSelect.appendChild(option);
            });

            hallSelect.addEventListener('change', (e) => {
                const hallId = e.target.value;
                populateSpotsSelect(hallId);
                currentOrder.hallId = hallId;
            });
        }

        function populateSpotsSelect(hallId) {
            const spotSelect = document.getElementById('spot-select');
            spotSelect.innerHTML = '<option value="">Выберите стол</option>';
            
            if (hallId && halls[hallId]) {
                halls[hallId].tables.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table.id;
                    option.textContent = `№${table.number} ${table.available ? '(Свободен)' : '(Занят)'}`;
                    spotSelect.appendChild(option);
                });
            }

            spotSelect.addEventListener('change', (e) => {
                const spotId = e.target.value;
                currentOrder.spotId = spotId;
                document.getElementById('current-spot-display').textContent = 
                    spotSelect.options[spotSelect.selectedIndex].textContent;
                loadExistingOrders(spotId);
            });
        }

        // Загрузка меню
        async function loadMenuData() {
            await Promise.all([loadCategories(), loadDishes()]);
            renderMenuTree();
        }

        async function loadCategories() {
            const cached = localStorage.getItem('poster_categories');
            const cacheTime = localStorage.getItem('poster_categories_time');
            
            if (cached && (Date.now() - parseInt(cacheTime)) < 5 * 60 * 1000) {
                categories = JSON.parse(cached);
                return;
            }

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/menu/categories`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    categories = data.data;
                    localStorage.setItem('poster_categories', JSON.stringify(categories));
                    localStorage.setItem('poster_categories_time', Date.now().toString());
                }
            } catch (error) {
                console.error('Load categories error:', error);
            }
        }

        async function loadDishes() {
            const cached = localStorage.getItem('poster_menu');
            const cacheTime = localStorage.getItem('poster_menu_time');
            
            if (cached && (Date.now() - parseInt(cacheTime)) < 5 * 60 * 1000) {
                dishes = JSON.parse(cached);
                return;
            }

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/menu/dishes`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    dishes = data.data;
                    localStorage.setItem('poster_menu', JSON.stringify(dishes));
                    localStorage.setItem('poster_menu_time', Date.now().toString());
                }
            } catch (error) {
                console.error('Load dishes error:', error);
            }
        }

        function renderMenuTree() {
            const menuTree = document.getElementById('menu-tree');
            menuTree.innerHTML = '';

            categories.sort((a, b) => a.sortOrder - b.sortOrder).forEach(category => {
                const categoryDishes = dishes.filter(d => d.categoryId === category.id && d.active);
                
                if (categoryDishes.length === 0) return;

                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'category-item';
                categoryDiv.dataset.category = category.id;

                const categoryTitle = document.createElement('div');
                categoryTitle.className = 'category-title';
                categoryTitle.innerHTML = `
                    <span class="expand-icon">⊕</span>
                    <span class="category-name">${category.title}</span>
                `;
                categoryTitle.onclick = () => toggleCategory(category.id);

                const dishesList = document.createElement('ul');
                dishesList.className = 'dishes-list';
                dishesList.id = `dishes-${category.id}`;
                dishesList.style.display = 'none';

                categoryDishes.forEach(dish => {
                    const dishItem = document.createElement('li');
                    dishItem.className = 'dish-item';
                    dishItem.innerHTML = `
                        <span>${dish.name}</span>
                        <span class="dish-price">${formatPrice(dish.price)} ₫</span>
                    `;
                    dishItem.onclick = () => addItemToOrder(dish);
                    dishesList.appendChild(dishItem);
                });

                categoryDiv.appendChild(categoryTitle);
                categoryDiv.appendChild(dishesList);
                menuTree.appendChild(categoryDiv);
            });
        }

        function toggleCategory(categoryId) {
            const list = document.getElementById(`dishes-${categoryId}`);
            const icon = document.querySelector(`[data-category="${categoryId}"] .expand-icon`);
            
            if (list.style.display === 'none') {
                list.style.display = 'block';
                icon.textContent = '⊖';
            } else {
                list.style.display = 'none';
                icon.textContent = '⊕';
            }
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price);
        }

        function addItemToOrder(dish) {
            const existingItem = currentOrder.items.find(item => item.dishId === dish.id);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                currentOrder.items.push({
                    dishId: dish.id,
                    dishName: dish.name,
                    quantity: 1,
                    price: dish.price,
                    comment: ''
                });
            }
            
            updateOrderTotal();
            renderOrderItems();
        }

        function updateItemQuantity(dishId, delta) {
            const item = currentOrder.items.find(item => item.dishId === dishId);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    currentOrder.items = currentOrder.items.filter(item => item.dishId !== dishId);
                }
                updateOrderTotal();
                renderOrderItems();
            }
        }

        function updateOrderTotal() {
            currentOrder.total = currentOrder.items.reduce((sum, item) => {
                return sum + (item.price * item.quantity);
            }, 0);
            document.getElementById('order-total').textContent = formatPrice(currentOrder.total) + ' ₫';
        }

        function renderOrderItems() {
            const orderItemsList = document.getElementById('order-items-list');
            
            if (currentOrder.items.length === 0) {
                orderItemsList.innerHTML = '<div class="loading">Заказ пуст</div>';
                return;
            }

            orderItemsList.innerHTML = currentOrder.items.map(item => `
                <div class="order-item" data-item-id="${item.dishId}">
                    <div class="item-details">
                        <span class="item-name">${item.dishName}</span>
                        <div class="item-quantity-control">
                            <button class="quantity-btn" onclick="updateItemQuantity('${item.dishId}', -1)">–</button>
                            <span>${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateItemQuantity('${item.dishId}', 1)">+</button>
                        </div>
                    </div>
                    <div class="item-price">Цена: ${formatPrice(item.price * item.quantity)} ₫</div>
                    <div class="item-comment">
                        <input type="text" placeholder="Комментарий к позиции" 
                               value="${item.comment}" 
                               onchange="updateItemComment('${item.dishId}', this.value)">
                    </div>
                </div>
            `).join('');
        }

        function updateItemComment(dishId, comment) {
            const item = currentOrder.items.find(item => item.dishId === dishId);
            if (item) {
                item.comment = comment;
            }
        }

        async function loadExistingOrders(spotId) {
            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/orders?spotId=${spotId}`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const order = data.data[0];
                    document.getElementById('existing-order-number').textContent = order.number;
                    document.getElementById('add-to-existing-btn').style.display = 'block';
                    // TODO: Load existing order items
                } else {
                    document.getElementById('add-to-existing-btn').style.display = 'none';
                }
            } catch (error) {
                console.error('Load existing orders error:', error);
            }
        }

        async function submitOrder() {
            if (!currentOrder.spotId || currentOrder.items.length === 0) {
                showToast('Выберите стол и добавьте блюда');
                return;
            }

            currentOrder.orderComment = document.getElementById('order-comment').value;

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/orders/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(currentOrder)
                });

                const data = await response.json();
                
                if (data.success) {
                    showOrderCreatedModal(data.transactionNumber);
                    clearOrder();
                } else {
                    showToast('Ошибка: ' + data.message);
                }
            } catch (error) {
                console.error('Submit order error:', error);
                showToast('Ошибка отправки заказа');
            }
        }

        function showOrderCreatedModal(orderNumber) {
            document.getElementById('modal-order-number').textContent = '№' + orderNumber;
            document.getElementById('order-modal').style.display = 'flex';
            document.getElementById('copy-order-number-btn').onclick = () => copyOrderNumber(orderNumber);
        }

        function closeOrderModal() {
            document.getElementById('order-modal').style.display = 'none';
        }

        function copyOrderNumber(number) {
            navigator.clipboard.writeText(number).then(() => {
                showToast('Номер скопирован в буфер обмена');
            });
        }

        function clearOrder() {
            currentOrder = {
                hallId: null,
                spotId: null,
                items: [],
                orderComment: '',
                total: 0
            };
            document.getElementById('order-comment').value = '';
            document.getElementById('current-order-number').textContent = 'Новый';
            document.getElementById('current-spot-display').textContent = '-';
            updateOrderTotal();
            renderOrderItems();
        }

        function refreshCache() {
            localStorage.removeItem('poster_menu');
            localStorage.removeItem('poster_categories');
            localStorage.removeItem('poster_halls');
            localStorage.removeItem('poster_menu_time');
            localStorage.removeItem('poster_categories_time');
            localStorage.removeItem('poster_halls_time');
            
            loadHalls();
            loadMenuData();
            showToast('Кеш обновлен');
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function setupEventListeners() {
            document.getElementById('logout-btn').onclick = async () => {
                await fetch(`${BACKEND_URL}/api/webzakaz/auth/logout`, {
                    method: 'POST',
                    credentials: 'include'
                });
                window.location.href = 'webzakaz-login.php';
            };

            document.getElementById('refresh-cache-btn').onclick = refreshCache;
            document.getElementById('submit-order-btn').onclick = submitOrder;
            document.getElementById('clear-order-btn').onclick = clearOrder;
            document.getElementById('new-order-btn').onclick = clearOrder;

            // Search functionality
            document.getElementById('dish-search').addEventListener('input', (e) => {
                searchDishes(e.target.value);
            });
        }

        function searchDishes(query) {
            if (!query) {
                renderMenuTree();
                return;
            }

            const filtered = dishes.filter(dish => 
                dish.name.toLowerCase().includes(query.toLowerCase()) && dish.active
            );

            const menuTree = document.getElementById('menu-tree');
            menuTree.innerHTML = '';

            if (filtered.length === 0) {
                menuTree.innerHTML = '<div class="loading">Блюда не найдены</div>';
                return;
            }

            filtered.forEach(dish => {
                const dishItem = document.createElement('div');
                dishItem.className = 'dish-item';
                dishItem.innerHTML = `
                    <span>${dish.name}</span>
                    <span class="dish-price">${formatPrice(dish.price)} ₫</span>
                `;
                dishItem.onclick = () => addItemToOrder(dish);
                menuTree.appendChild(dishItem);
            });
        }
    </script>
</body>
</html>

ENV['BACKEND_URL'] ?? 'http://localhost:3003';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebZakaz | Veranda</title>
    <style>
        /* Основные стили (Veranda-стиль: светлый, чистый, с зелеными акцентами) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        :root {
            --primary-color: #5cb85c;
            --primary-dark: #4cae4c;
            --secondary-bg: #ffffff;
            --border-color: #ddd;
            --header-bg: #e9ecef;
        }

        /* HEADER */
        .header {
            background-color: var(--header-bg);
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .header-left, .header-right {
            display: flex;
            align-items: center;
        }

        .header-logo {
            font-weight: bold;
            font-size: 18px;
            color: var(--primary-dark);
            margin-right: 30px;
        }

        .header-info span {
            margin-left: 15px;
        }

        .logout-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-weight: bold;
            margin-left: 20px;
            padding: 5px 10px;
        }

        .logout-btn:hover {
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        /* CONTROL PANEL */
        .control-panel {
            background-color: var(--secondary-bg);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .spot-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .spot-selector select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .action-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.2s;
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ccc;
        }

        .btn-order-add {
            background-color: #ffc107;
            color: #333;
        }

        /* MAIN LAYOUT */
        .main-container {
            flex-grow: 1;
            display: flex;
            overflow: hidden;
            padding: 10px;
            gap: 10px;
        }

        .menu-panel, .order-panel {
            background-color: var(--secondary-bg);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            overflow-y: auto;
        }

        .menu-panel {
            flex: 3;
            min-width: 350px;
        }

        .order-panel {
            flex: 2;
            min-width: 300px;
            display: flex;
            flex-direction: column;
        }

        /* MENU STYLES */
        .menu-search input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .category-item {
            cursor: pointer;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
        }

        .category-title {
            font-weight: bold;
            display: flex;
            align-items: center;
            color: var(--primary-dark);
            font-size: 15px;
        }

        .expand-icon {
            margin-right: 10px;
            font-size: 1.2em;
            user-select: none;
        }

        .dishes-list {
            padding-left: 15px;
            list-style: none;
            margin: 5px 0 0 0;
        }

        .dish-item {
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            cursor: pointer;
            transition: background-color 0.1s;
            border-radius: 4px;
            padding-left: 5px;
            padding-right: 5px;
        }

        .dish-item:hover {
            background-color: #f0f0f0;
        }

        .dish-price {
            font-weight: 600;
            color: #666;
        }

        /* ORDER STYLES */
        .order-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .order-items {
            flex-grow: 1;
            margin-bottom: 15px;
            padding-right: 5px;
            overflow-y: auto;
        }

        .order-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .item-name {
            font-weight: bold;
            flex-grow: 1;
        }

        .item-quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-btn {
            background-color: #eee;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .item-comment input {
            width: 100%;
            border: 1px dashed #ccc;
            padding: 5px;
            margin-top: 5px;
            font-size: 12px;
        }

        .order-summary {
            border-top: 2px solid var(--primary-color);
            padding-top: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .order-comment-area textarea {
            width: 100%;
            min-height: 50px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
            resize: vertical;
        }

        .submit-area button {
            width: 100%;
            margin-bottom: 10px;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
        }

        .modal h3 {
            color: var(--primary-dark);
            margin-bottom: 15px;
        }

        .modal-order-number {
            font-size: 2.5em;
            color: #dc3545;
            margin: 10px 0;
            font-weight: bold;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 2000;
            display: none;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-left">
            <span class="header-logo">🍽️ WebZakaz</span>
            <span class="header-info">Кассир: <strong><?= htmlspecialchars($cashierName) ?></strong></span>
        </div>
        <div class="header-right">
            <span class="header-info" id="cache-status">⏰ Кеш актуален</span>
            <button class="logout-btn" id="logout-btn">🚪 Выход</button>
        </div>
    </header>

    <div class="control-panel">
        <div class="spot-selector">
            <label for="hall-select">Зал:</label>
            <select id="hall-select">
                <option value="">Загрузка...</option>
            </select>

            <label for="spot-select">Стол:</label>
            <select id="spot-select">
                <option value="">Выберите зал</option>
            </select>
        </div>

        <div class="order-actions">
            <button class="action-btn btn-secondary" id="refresh-cache-btn">🔄 Обновить Кеш</button>
            <button class="action-btn btn-primary" id="new-order-btn">🟢 НОВЫЙ ЗАКАЗ</button>
            <button class="action-btn btn-order-add" id="add-to-existing-btn" style="display:none;">
                🟠 ДОПОЛНИТЬ К ЗАКАЗУ №<span id="existing-order-number"></span>
            </button>
        </div>
    </div>

    <div class="main-container">
        <div class="menu-panel">
            <div class="menu-search">
                <input type="text" id="dish-search" placeholder="🔍 Поиск блюд..." autocomplete="off">
            </div>
            
            <div class="menu-tree" id="menu-tree">
                <div class="loading">Загрузка меню...</div>
            </div>
        </div>

        <div class="order-panel">
            <div class="order-title">ЗАКАЗ № <span id="current-order-number">Новый</span></div>
            <div class="order-info">Стол: <span id="current-spot-display">-</span></div>
            
            <hr style="margin: 10px 0; border-top: 1px solid #eee;">
            
            <div class="order-items" id="order-items-list">
                <div class="loading">Заказ пуст</div>
            </div>
            
            <div class="order-summary">
                <div class="total-row">
                    <span>ИТОГО:</span>
                    <span id="order-total">0 ₫</span>
                </div>
                <div class="order-comment-area">
                    <label for="order-comment">Комментарий к заказу:</label>
                    <textarea id="order-comment" placeholder="Общий комментарий к заказу (например, Клиент спешит)"></textarea>
                </div>
                <div class="submit-area">
                    <button class="action-btn btn-primary" id="submit-order-btn">🚀 ОТПРАВИТЬ ЗАКАЗ</button>
                    <button class="action-btn btn-secondary" id="clear-order-btn">❌ ОЧИСТИТЬ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal для номера заказа -->
    <div id="order-modal" class="modal">
        <div class="modal-content">
            <h3>✅ Заказ успешно создан</h3>
            <p style="font-size: 1.1em;">НОМЕР ДЛЯ ТИКЕТА:</p>
            <h1 id="modal-order-number" class="modal-order-number">№0</h1>
            <p style="color: #6c757d; margin: 15px 0;">⚠️ ОБЯЗАТЕЛЬНО отправьте этот номер на кухню вручную через главный POS.</p>
            
            <button class="action-btn btn-primary" id="copy-order-number-btn" style="margin-top: 15px;">
                📋 КОПИРОВАТЬ НОМЕР
            </button>
            <button class="action-btn btn-secondary" onclick="closeOrderModal()">OK</button>
        </div>
    </div>

    <!-- Toast уведомления -->
    <div id="toast" class="toast"></div>

    <script>
        const BACKEND_URL = '<?= $backendUrl ?>';
        let currentOrder = {
            hallId: null,
            spotId: null,
            items: [],
            orderComment: '',
            total: 0
        };

        let categories = [];
        let dishes = [];
        let halls = {};

        // Инициализация при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            checkAuth();
            loadHalls();
            loadMenuData();
            setupEventListeners();
        });

        // Проверка авторизации
        async function checkAuth() {
            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/auth/check`, {
                    credentials: 'include'
                });
                const data = await response.json();
                if (!data.success) {
                    window.location.href = 'webzakaz-login.php';
                }
            } catch (error) {
                console.error('Auth check error:', error);
                window.location.href = 'webzakaz-login.php';
            }
        }

        // Загрузка залов и столов
        async function loadHalls() {
            const cached = localStorage.getItem('poster_halls');
            const cacheTime = localStorage.getItem('poster_halls_time');
            
            if (cached && (Date.now() - parseInt(cacheTime)) < 60 * 60 * 1000) {
                halls = JSON.parse(cached);
                populateHallsSelect();
                return;
            }

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/halls`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    halls = data.data;
                    localStorage.setItem('poster_halls', JSON.stringify(halls));
                    localStorage.setItem('poster_halls_time', Date.now().toString());
                    populateHallsSelect();
                }
            } catch (error) {
                console.error('Load halls error:', error);
                showToast('Ошибка загрузки залов');
            }
        }

        function populateHallsSelect() {
            const hallSelect = document.getElementById('hall-select');
            hallSelect.innerHTML = '<option value="">Выберите зал</option>';
            
            Object.keys(halls).forEach(hallId => {
                const option = document.createElement('option');
                option.value = hallId;
                option.textContent = halls[hallId].hallName;
                hallSelect.appendChild(option);
            });

            hallSelect.addEventListener('change', (e) => {
                const hallId = e.target.value;
                populateSpotsSelect(hallId);
                currentOrder.hallId = hallId;
            });
        }

        function populateSpotsSelect(hallId) {
            const spotSelect = document.getElementById('spot-select');
            spotSelect.innerHTML = '<option value="">Выберите стол</option>';
            
            if (hallId && halls[hallId]) {
                halls[hallId].tables.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table.id;
                    option.textContent = `№${table.number} ${table.available ? '(Свободен)' : '(Занят)'}`;
                    spotSelect.appendChild(option);
                });
            }

            spotSelect.addEventListener('change', (e) => {
                const spotId = e.target.value;
                currentOrder.spotId = spotId;
                document.getElementById('current-spot-display').textContent = 
                    spotSelect.options[spotSelect.selectedIndex].textContent;
                loadExistingOrders(spotId);
            });
        }

        // Загрузка меню
        async function loadMenuData() {
            await Promise.all([loadCategories(), loadDishes()]);
            renderMenuTree();
        }

        async function loadCategories() {
            const cached = localStorage.getItem('poster_categories');
            const cacheTime = localStorage.getItem('poster_categories_time');
            
            if (cached && (Date.now() - parseInt(cacheTime)) < 5 * 60 * 1000) {
                categories = JSON.parse(cached);
                return;
            }

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/menu/categories`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    categories = data.data;
                    localStorage.setItem('poster_categories', JSON.stringify(categories));
                    localStorage.setItem('poster_categories_time', Date.now().toString());
                }
            } catch (error) {
                console.error('Load categories error:', error);
            }
        }

        async function loadDishes() {
            const cached = localStorage.getItem('poster_menu');
            const cacheTime = localStorage.getItem('poster_menu_time');
            
            if (cached && (Date.now() - parseInt(cacheTime)) < 5 * 60 * 1000) {
                dishes = JSON.parse(cached);
                return;
            }

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/menu/dishes`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    dishes = data.data;
                    localStorage.setItem('poster_menu', JSON.stringify(dishes));
                    localStorage.setItem('poster_menu_time', Date.now().toString());
                }
            } catch (error) {
                console.error('Load dishes error:', error);
            }
        }

        function renderMenuTree() {
            const menuTree = document.getElementById('menu-tree');
            menuTree.innerHTML = '';

            categories.sort((a, b) => a.sortOrder - b.sortOrder).forEach(category => {
                const categoryDishes = dishes.filter(d => d.categoryId === category.id && d.active);
                
                if (categoryDishes.length === 0) return;

                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'category-item';
                categoryDiv.dataset.category = category.id;

                const categoryTitle = document.createElement('div');
                categoryTitle.className = 'category-title';
                categoryTitle.innerHTML = `
                    <span class="expand-icon">⊕</span>
                    <span class="category-name">${category.title}</span>
                `;
                categoryTitle.onclick = () => toggleCategory(category.id);

                const dishesList = document.createElement('ul');
                dishesList.className = 'dishes-list';
                dishesList.id = `dishes-${category.id}`;
                dishesList.style.display = 'none';

                categoryDishes.forEach(dish => {
                    const dishItem = document.createElement('li');
                    dishItem.className = 'dish-item';
                    dishItem.innerHTML = `
                        <span>${dish.name}</span>
                        <span class="dish-price">${formatPrice(dish.price)} ₫</span>
                    `;
                    dishItem.onclick = () => addItemToOrder(dish);
                    dishesList.appendChild(dishItem);
                });

                categoryDiv.appendChild(categoryTitle);
                categoryDiv.appendChild(dishesList);
                menuTree.appendChild(categoryDiv);
            });
        }

        function toggleCategory(categoryId) {
            const list = document.getElementById(`dishes-${categoryId}`);
            const icon = document.querySelector(`[data-category="${categoryId}"] .expand-icon`);
            
            if (list.style.display === 'none') {
                list.style.display = 'block';
                icon.textContent = '⊖';
            } else {
                list.style.display = 'none';
                icon.textContent = '⊕';
            }
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price);
        }

        function addItemToOrder(dish) {
            const existingItem = currentOrder.items.find(item => item.dishId === dish.id);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                currentOrder.items.push({
                    dishId: dish.id,
                    dishName: dish.name,
                    quantity: 1,
                    price: dish.price,
                    comment: ''
                });
            }
            
            updateOrderTotal();
            renderOrderItems();
        }

        function updateItemQuantity(dishId, delta) {
            const item = currentOrder.items.find(item => item.dishId === dishId);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    currentOrder.items = currentOrder.items.filter(item => item.dishId !== dishId);
                }
                updateOrderTotal();
                renderOrderItems();
            }
        }

        function updateOrderTotal() {
            currentOrder.total = currentOrder.items.reduce((sum, item) => {
                return sum + (item.price * item.quantity);
            }, 0);
            document.getElementById('order-total').textContent = formatPrice(currentOrder.total) + ' ₫';
        }

        function renderOrderItems() {
            const orderItemsList = document.getElementById('order-items-list');
            
            if (currentOrder.items.length === 0) {
                orderItemsList.innerHTML = '<div class="loading">Заказ пуст</div>';
                return;
            }

            orderItemsList.innerHTML = currentOrder.items.map(item => `
                <div class="order-item" data-item-id="${item.dishId}">
                    <div class="item-details">
                        <span class="item-name">${item.dishName}</span>
                        <div class="item-quantity-control">
                            <button class="quantity-btn" onclick="updateItemQuantity('${item.dishId}', -1)">–</button>
                            <span>${item.quantity}</span>
                            <button class="quantity-btn" onclick="updateItemQuantity('${item.dishId}', 1)">+</button>
                        </div>
                    </div>
                    <div class="item-price">Цена: ${formatPrice(item.price * item.quantity)} ₫</div>
                    <div class="item-comment">
                        <input type="text" placeholder="Комментарий к позиции" 
                               value="${item.comment}" 
                               onchange="updateItemComment('${item.dishId}', this.value)">
                    </div>
                </div>
            `).join('');
        }

        function updateItemComment(dishId, comment) {
            const item = currentOrder.items.find(item => item.dishId === dishId);
            if (item) {
                item.comment = comment;
            }
        }

        async function loadExistingOrders(spotId) {
            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/orders?spotId=${spotId}`, {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const order = data.data[0];
                    document.getElementById('existing-order-number').textContent = order.number;
                    document.getElementById('add-to-existing-btn').style.display = 'block';
                    // TODO: Load existing order items
                } else {
                    document.getElementById('add-to-existing-btn').style.display = 'none';
                }
            } catch (error) {
                console.error('Load existing orders error:', error);
            }
        }

        async function submitOrder() {
            if (!currentOrder.spotId || currentOrder.items.length === 0) {
                showToast('Выберите стол и добавьте блюда');
                return;
            }

            currentOrder.orderComment = document.getElementById('order-comment').value;

            try {
                const response = await fetch(`${BACKEND_URL}/api/webzakaz/orders/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(currentOrder)
                });

                const data = await response.json();
                
                if (data.success) {
                    showOrderCreatedModal(data.transactionNumber);
                    clearOrder();
                } else {
                    showToast('Ошибка: ' + data.message);
                }
            } catch (error) {
                console.error('Submit order error:', error);
                showToast('Ошибка отправки заказа');
            }
        }

        function showOrderCreatedModal(orderNumber) {
            document.getElementById('modal-order-number').textContent = '№' + orderNumber;
            document.getElementById('order-modal').style.display = 'flex';
            document.getElementById('copy-order-number-btn').onclick = () => copyOrderNumber(orderNumber);
        }

        function closeOrderModal() {
            document.getElementById('order-modal').style.display = 'none';
        }

        function copyOrderNumber(number) {
            navigator.clipboard.writeText(number).then(() => {
                showToast('Номер скопирован в буфер обмена');
            });
        }

        function clearOrder() {
            currentOrder = {
                hallId: null,
                spotId: null,
                items: [],
                orderComment: '',
                total: 0
            };
            document.getElementById('order-comment').value = '';
            document.getElementById('current-order-number').textContent = 'Новый';
            document.getElementById('current-spot-display').textContent = '-';
            updateOrderTotal();
            renderOrderItems();
        }

        function refreshCache() {
            localStorage.removeItem('poster_menu');
            localStorage.removeItem('poster_categories');
            localStorage.removeItem('poster_halls');
            localStorage.removeItem('poster_menu_time');
            localStorage.removeItem('poster_categories_time');
            localStorage.removeItem('poster_halls_time');
            
            loadHalls();
            loadMenuData();
            showToast('Кеш обновлен');
        }

        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        function setupEventListeners() {
            document.getElementById('logout-btn').onclick = async () => {
                await fetch(`${BACKEND_URL}/api/webzakaz/auth/logout`, {
                    method: 'POST',
                    credentials: 'include'
                });
                window.location.href = 'webzakaz-login.php';
            };

            document.getElementById('refresh-cache-btn').onclick = refreshCache;
            document.getElementById('submit-order-btn').onclick = submitOrder;
            document.getElementById('clear-order-btn').onclick = clearOrder;
            document.getElementById('new-order-btn').onclick = clearOrder;

            // Search functionality
            document.getElementById('dish-search').addEventListener('input', (e) => {
                searchDishes(e.target.value);
            });
        }

        function searchDishes(query) {
            if (!query) {
                renderMenuTree();
                return;
            }

            const filtered = dishes.filter(dish => 
                dish.name.toLowerCase().includes(query.toLowerCase()) && dish.active
            );

            const menuTree = document.getElementById('menu-tree');
            menuTree.innerHTML = '';

            if (filtered.length === 0) {
                menuTree.innerHTML = '<div class="loading">Блюда не найдены</div>';
                return;
            }

            filtered.forEach(dish => {
                const dishItem = document.createElement('div');
                dishItem.className = 'dish-item';
                dishItem.innerHTML = `
                    <span>${dish.name}</span>
                    <span class="dish-price">${formatPrice(dish.price)} ₫</span>
                `;
                dishItem.onclick = () => addItemToOrder(dish);
                menuTree.appendChild(dishItem);
            });
        }
    </script>
</body>
</html>


