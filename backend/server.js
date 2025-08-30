const express = require('express');
const cors = require('cors');
const axios = require('axios');
const path = require('path');
const https = require('https');
const FormData = require('form-data');
const SePayMonitor = require('./sepay-monitor');
const adminModule = require('./admin');
const authModule = require('./auth');
const orderService = require('./services/orderService');
const cookieParser = require('cookie-parser');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3002;

// Middleware
app.use(cors({
  origin: ['https://northrepublic.me', 'https://goodzone.zapleo.com', 'http://localhost:3000', 'http://localhost:5173', 'http://localhost:3001'],
  credentials: true
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());

// Логирование запросов
app.use((req, res, next) => {
  console.log(`${new Date().toISOString()} - ${req.method} ${req.path}`);
  next();
});

// Статические файлы
app.use(express.static(path.join(__dirname, '..')));

// HTTPS агент для API запросов
const httpsAgent = new https.Agent({
  rejectUnauthorized: false,
  secureProtocol: 'TLSv1_2_method'
});

// API роуты
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    timestamp: new Date().toISOString(),
    version: process.env.APP_VERSION || '2.1.1',
    buildDate: '2025-08-19',
    features: ['price-normalization']
  });
});



// Poster API прокси
app.use('/api/poster', async (req, res) => {
  try {
    const posterUrl = 'https://joinposter.com/api';
    const token = process.env.POSTER_API_TOKEN;
    
    if (!token) {
      return res.status(500).json({ error: 'POSTER_API_TOKEN not configured' });
    }

    const params = {
      ...req.query,
      token: token
    };

    const response = await axios({
      method: req.method,
      url: `${posterUrl}${req.path}`,
      params: params,
      data: req.body,
      headers: {
        'Content-Type': 'application/json',
        'User-Agent': 'NorthRepublic-Backend/2.0',
        ...req.headers
      },
      httpsAgent: httpsAgent,
      timeout: 15000
    });
    res.json(response.data);
  } catch (error) {
    console.error('❌ Poster API error:', error.message);
    
    if (error.response) {
      console.error('Response status:', error.response.status);
      console.error('Response data:', error.response.data);
      return res.status(error.response.status).json({
        error: error.response.data || error.message
      });
    }
    
    res.status(500).json({
      error: error.message
    });
  }
});

// Кэшированные данные меню
let menuCache = null;
let cacheTimestamp = null;
const CACHE_DURATION = 5 * 60 * 1000; // 5 минут

// Кэш для данных популярности
let popularityCache = {};
let popularityCacheTimestamp = 0;
const POPULARITY_CACHE_DURATION = 10 * 60 * 1000; // 10 минут

app.get('/api/menu', async (req, res) => {
  try {
    // Проверяем кэш
    if (menuCache && cacheTimestamp && (Date.now() - cacheTimestamp) < CACHE_DURATION) {
  
      return res.json(menuCache);
    }

    const token = process.env.POSTER_API_TOKEN;
    if (!token) {
      return res.status(500).json({ error: 'POSTER_API_TOKEN not configured' });
    }



    // Получаем категории
    const categoriesResponse = await axios.get('https://joinposter.com/api/menu.getCategories', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });



    // Получаем продукты
    const productsResponse = await axios.get('https://joinposter.com/api/menu.getProducts', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });



    // Фильтруем только видимые товары (hidden !== "1" и visible !== "0" в spots)
    const rawProducts = productsResponse.data.response || [];
    const visibleProducts = rawProducts.filter(product => {
      // Проверяем основное поле hidden
      if (product.hidden === "1") return false;
      
      // Проверяем видимость в точках продаж
      if (product.spots && Array.isArray(product.spots)) {
        // Если хотя бы одна точка продаж видима, показываем товар
        return product.spots.some(spot => spot.visible !== "0");
      }
      
      // Если нет spots, считаем товар видимым
      return true;
    });

    // Нормализуем цены: делим на 100 везде, чтобы получать итог в донгах
    const normalizePriceValue = (value) => {
      if (value === undefined || value === null) return value;
      const num = Number.parseFloat(String(value));
      if (Number.isNaN(num)) return value;
      return Math.floor(num / 100);
    };

    const productsWithNormalizedPrices = visibleProducts.map((product) => {
      const normalized = { ...product };
      // Массив цен по точкам продаж
      if (Array.isArray(normalized.spots)) {
        normalized.spots = normalized.spots.map((spot) => ({
          ...spot,
          price: String(normalizePriceValue(spot.price)),
          profit: spot.profit !== undefined ? String(normalizePriceValue(spot.profit)) : spot.profit,
          profit_netto: spot.profit_netto !== undefined ? String(normalizePriceValue(spot.profit_netto)) : spot.profit_netto,
        }));
      }
      // Объект цен, где ключи – id прайс-листа
      if (normalized.price && typeof normalized.price === 'object') {
        const newPrice = { ...normalized.price };
        Object.keys(newPrice).forEach((key) => {
          newPrice[key] = String(normalizePriceValue(newPrice[key]));
        });
        normalized.price = newPrice;
      }
      // Дополнительное поле для удобства на фронте
      normalized.product_price_normalized = normalized.price?.['1']
        ? Number.parseInt(String(normalized.price['1']), 10)
        : undefined;
      return normalized;
    });

    const menuData = {
      categories: categoriesResponse.data.response || [],
      products: productsWithNormalizedPrices,
      timestamp: new Date().toISOString()
    };



    // Обновляем кэш
    menuCache = menuData;
    cacheTimestamp = Date.now();


    res.json(menuData);
  } catch (error) {
    console.error('❌ Menu fetch error:', error.message);
    res.status(500).json({ error: error.message });
  }
});

// Endpoint для управления мониторингом SePay
app.get('/api/sepay/status', (req, res) => {
  if (!sepayMonitor) {
    return res.json({ 
      status: 'not_initialized', 
      message: 'SePay мониторинг не инициализирован' 
    });
  }
  
  res.json({ 
    status: sepayMonitor.isRunning ? 'running' : 'stopped',
    chatIds: sepayMonitor.chatIds,
    checkInterval: sepayMonitor.checkInterval / 1000,
    lastTransactionId: sepayMonitor.sepayService.lastTransactionId
  });
});

app.post('/api/sepay/start', (req, res) => {
  if (!sepayMonitor) {
    return res.status(400).json({ error: 'SePay мониторинг не инициализирован' });
  }
  
  sepayMonitor.start();
  res.json({ status: 'started', message: 'Мониторинг SePay запущен' });
});

app.post('/api/sepay/stop', (req, res) => {
  if (!sepayMonitor) {
    return res.status(400).json({ error: 'SePay мониторинг не инициализирован' });
  }
  
  sepayMonitor.stop();
  res.json({ status: 'stopped', message: 'Мониторинг SePay остановлен' });
});

app.post('/api/sepay/test', async (req, res) => {
  if (!sepayMonitor) {
    return res.status(400).json({ error: 'SePay мониторинг не инициализирован' });
  }
  
  try {
    const result = await sepayMonitor.testConnection();
    res.json({ 
      success: result, 
      message: result ? 'Подключение к SePay API успешно' : 'Ошибка подключения к SePay API' 
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Endpoint для получения популярности товаров
app.get('/api/products/popularity', async (req, res) => {
  try {
    const now = Date.now();
    
    // Проверяем кэш
    if (popularityCache && (now - popularityCacheTimestamp) < POPULARITY_CACHE_DURATION) {
  
      return res.json({ productPopularity: popularityCache });
    }

    // Вычисляем даты для последних 7 дней
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 7);
    
    const dateFrom = startDate.getFullYear().toString() + 
                    (startDate.getMonth() + 1).toString().padStart(2, '0') + 
                    startDate.getDate().toString().padStart(2, '0');
    const dateTo = endDate.getFullYear().toString() + 
                  (endDate.getMonth() + 1).toString().padStart(2, '0') + 
                  endDate.getDate().toString().padStart(2, '0');

    // Используем правильный API метод для получения продаж по товарам
    const response = await axios.get('https://joinposter.com/api/dash.getProductsSales', {
      params: {
        token: process.env.POSTER_API_TOKEN,
        date_from: dateFrom,
        date_to: dateTo
      }
    });

    if (response.data && response.data.response) {
      const productPopularity = {};
      
      // Обрабатываем данные о продажах товаров
      response.data.response.forEach(item => {
        const productId = item.product_id;
        const count = parseFloat(item.count) || 0;
        
        if (productId && count > 0) {
          productPopularity[productId] = count;
        }
      });

      // Обновляем кэш
      popularityCache = productPopularity;
      popularityCacheTimestamp = now;

      res.json({ productPopularity });
    } else {
  
      res.json({ productPopularity: {} });
    }
  } catch (error) {
    console.error('❌ Ошибка при получении данных популярности:', error.message);
    res.json({ productPopularity: {} });
  }
});

// ===== АВТОРИЗАЦИЯ API =====

// Вход в админку
app.post('/api/auth/login', (req, res) => {
  try {
    const { username, password } = req.body;
    
    if (!username || !password) {
      return res.status(400).json({ error: 'Логин и пароль обязательны' });
    }
    
    const result = authModule.authenticateUser(username, password);
    
    if (result.success) {
      // Устанавливаем JWT токен в httpOnly cookie
      res.cookie('adminToken', result.token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 24 * 60 * 60 * 1000 // 24 часа
      });
      
      res.json({
        success: true,
        user: result.user,
        message: 'Успешная авторизация'
      });
    } else {
      res.status(401).json({ error: result.error });
    }
  } catch (error) {
    console.error('❌ Ошибка авторизации:', error.message);
    res.status(500).json({ error: error.message });
  }
});

// Выход из админки
app.post('/api/auth/logout', (req, res) => {
  res.clearCookie('adminToken');
  res.json({ success: true, message: 'Выход выполнен' });
});

// Проверка статуса авторизации
app.get('/api/auth/status', authModule.requireAuth, (req, res) => {
  res.json({
    authenticated: true,
    user: req.user
  });
});

// ===== АДМИНКА API =====

// Получить конфигурацию админки (защищено)
app.get('/api/admin/config', authModule.requireAuth, authModule.requireAdmin, (req, res) => {
  try {
    const config = adminModule.getAdminConfig();
    res.json(config);
  } catch (error) {
    console.error('❌ Ошибка получения конфигурации админки:', error.message);
    res.status(500).json({ error: error.message });
  }
});

// Обновить секцию (защищено)
app.post('/api/admin/section/:key', authModule.requireAuth, authModule.requireAdmin, (req, res) => {
  try {
    const { key } = req.params;
    const { enabled } = req.body;
    
    if (typeof enabled !== 'boolean') {
      return res.status(400).json({ error: 'Поле enabled должно быть boolean' });
    }
    
    const success = adminModule.updateSection(key, enabled);
    if (success) {
      res.json({ success: true, message: `Секция ${key} ${enabled ? 'активирована' : 'деактивирована'}` });
    } else {
      res.status(404).json({ error: 'Секция не найдена' });
    }
  } catch (error) {
    console.error('❌ Ошибка обновления секции:', error.message);
    res.status(500).json({ error: error.message });
  }
});

// Обновить страницу (защищено)
app.post('/api/admin/page/:path(*)', authModule.requireAuth, authModule.requireAdmin, (req, res) => {
  try {
    const pagePath = '/' + req.params.path;
    const { enabled } = req.body;
    
    if (typeof enabled !== 'boolean') {
      return res.status(400).json({ error: 'Поле enabled должно быть boolean' });
    }
    
    const success = adminModule.updatePage(pagePath, enabled);
    if (success) {
      res.json({ success: true, message: `Страница ${pagePath} ${enabled ? 'активирована' : 'деактивирована'}` });
    } else {
      res.status(404).json({ error: 'Страница не найдена' });
    }
  } catch (error) {
    console.error('❌ Ошибка обновления страницы:', error.message);
    res.status(500).json({ error: error.message });
  }
});

// Получить доступные секции для фронтенда
app.get('/api/sections', (req, res) => {
  try {
    const enabledSections = adminModule.getEnabledSections();
    res.json({ sections: enabledSections });
  } catch (error) {
    console.error('❌ Ошибка получения секций:', error.message);
    res.status(500).json({ error: error.message });
  }
});

// Проверить доступность страницы
app.get('/api/admin/page/:path(*)/status', (req, res) => {
  try {
    const pagePath = '/' + req.params.path;
    const isEnabled = adminModule.isPageEnabled(pagePath);
    res.json({ enabled: isEnabled, path: pagePath });
  } catch (error) {
    console.error('❌ Ошибка проверки статуса страницы:', error.message);
    res.status(500).json({ error: error.message });
  }
});



// Error handler
app.use((error, req, res, next) => {
  console.error('❌ Server error:', error);
  res.status(500).json({ error: 'Internal server error' });
});

// Инициализация мониторинга SePay
let sepayMonitor = null;

if (process.env.SEPAY_API_TOKEN) {
  try {
    sepayMonitor = new SePayMonitor();
  } catch (error) {
    console.error('❌ Ошибка инициализации SePay мониторинга:', error.message);
  }
}

// Функция генерации QR кода через SePay
// API endpoints для заказов
app.post('/api/orders/check-phone', async (req, res) => {
  try {
    const { phone } = req.body;
    
    if (!phone) {
      return res.status(400).json({ error: 'Номер телефона обязателен' });
    }

    const result = await orderService.checkExistingClient(phone);
    res.json(result);
  } catch (error) {
    console.error('Error in check-phone endpoint:', error);
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/orders/register', async (req, res) => {
  try {
    const { name, lastName, phone, birthday, gender } = req.body;
    
    if (!name || !phone) {
      return res.status(400).json({ error: 'Имя и телефон обязательны' });
    }

    const client = await orderService.createClient({
      name,
      lastName,
      phone,
      birthday,
      gender
    });

    res.json({ success: true, client });
  } catch (error) {
    console.error('Error in register endpoint:', error);
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/orders/check-first', async (req, res) => {
  try {
    const { clientId } = req.body;
    
    if (!clientId) {
      return res.status(400).json({ error: 'ID клиента обязателен' });
    }

    const isFirstOrder = await orderService.checkFirstOrder(clientId);
    res.json({ isFirstOrder });
  } catch (error) {
    console.error('Error in check-first endpoint:', error);
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/orders/create', async (req, res) => {
  try {
    const { items, total, tableId, comment, customerData, withRegistration } = req.body;
    
    if (!items || !items.length) {
      return res.status(400).json({ error: 'Товары в заказе обязательны' });
    }

    if (!customerData || !customerData.name) {
      return res.status(400).json({ error: 'Имя обязательно' });
    }

    if (!customerData.phone) {
      return res.status(400).json({ error: 'Номер телефона обязателен' });
    }

    let clientId;

    // Проверяем существование клиента по телефону
    if (customerData.phone) {
      const existingClient = await orderService.findClientByPhone(customerData.phone);
      
      if (existingClient) {
        clientId = existingClient.client.client_id;
        console.log(`✅ Найден существующий клиент с ID: ${clientId}`);
      } else {
        // Если клиента нет, создаем нового
        const newClient = await orderService.createClient(customerData);
        clientId = newClient; // API возвращает только ID
        console.log(`✅ Создан новый клиент с ID: ${clientId}`);
      }
    } else {
      return res.status(400).json({ error: 'Номер телефона обязателен для привязки заказа к пользователю' });
    }

    // Создаем заказ
    const order = await orderService.createOrder({
      items,
      total,
      tableId,
      comment,
      clientId,
      customerData,
      withRegistration: withRegistration || false
    });

    // Создаем сессию для пользователя
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30);
    const session = {
      userId: clientId,
      userData: customerData,
      expiresAt: expiresAt.toISOString()
    };

    res.json({ 
      success: true, 
      order, 
      session,
      discount: withRegistration ? total * 0.2 : 0
    });
  } catch (error) {
    console.error('Error in create order endpoint:', error);
    res.status(500).json({ error: error.message });
  }
});

// Получить заказы пользователя (неоплаченные)
app.get('/api/orders/user/:userId', async (req, res) => {
  try {
    const { userId } = req.params;
    const orders = await orderService.getUserOrders(userId);
    res.json({ success: true, orders });
  } catch (error) {
    console.error('Error fetching user orders:', error);
    res.status(500).json({ error: error.message });
  }
});

// Получить прошлые заказы пользователя с пагинацией
app.get('/api/orders/user/:userId/past', async (req, res) => {
  try {
    const { userId } = req.params;
    const { limit = 10, offset = 0 } = req.query;
    const orders = await orderService.getUserPastOrders(userId, parseInt(limit), parseInt(offset));
    res.json({ success: true, orders });
  } catch (error) {
    console.error('Error fetching user past orders:', error);
    res.status(500).json({ error: error.message });
  }
});

// Получить детали заказа с товарами
app.get('/api/orders/details/:transactionId', async (req, res) => {
  try {
    const { transactionId } = req.params;
    
    const orderDetails = await orderService.getOrderDetails(transactionId);
    
    res.json({ 
      success: true, 
      orderDetails 
    });
  } catch (error) {
    console.error('Error fetching order details:', error);
    res.status(500).json({ error: error.message });
  }
});

// Создать сессию пользователя
app.post('/api/session/create', async (req, res) => {
  try {
    const { userId, userData } = req.body;
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30); // 30 дней
    
    const session = {
      userId,
      userData,
      expiresAt: expiresAt.toISOString()
    };
    
    res.json({ success: true, session });
  } catch (error) {
    console.error('Error creating session:', error);
    res.status(500).json({ error: error.message });
  }
});

// Обновить сессию пользователя
app.post('/api/session/update', async (req, res) => {
  try {
    const { userId, userData } = req.body;
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30); // 30 дней
    
    const session = {
      userId,
      userData,
      expiresAt: expiresAt.toISOString()
    };
    
    res.json({ success: true, session });
  } catch (error) {
    console.error('Error updating session:', error);
    res.status(500).json({ error: error.message });
  }
});

// ===== АВТОРИЗАЦИЯ ЧЕРЕЗ TELEGRAM =====

// Регистрация пользователя
app.post('/api/auth/register', async (req, res) => {
  try {
    const { name, lastName, phone, birthday, gender } = req.body;
    
    if (!name || !phone) {
      return res.status(400).json({ error: 'Имя и телефон обязательны' });
    }

    // Проверяем существование клиента по телефону
    let clientId;
    const existingClient = await orderService.findClientByPhone(phone);
    
    if (existingClient) {
      clientId = existingClient.client.client_id;
      console.log(`✅ Найден существующий клиент с ID: ${clientId}`);
    } else {
      // Создаем нового клиента
      const clientData = {
        name,
        lastName,
        phone,
        birthday,
        gender
      };
      clientId = await orderService.createClient(clientData);
      console.log(`✅ Создан новый клиент с ID: ${clientId}`);
    }

    // Создаем сессию
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30);
    const session = {
      userId: clientId,
      userData: { name, lastName, phone, birthday, gender },
      expiresAt: expiresAt.toISOString()
    };

    res.json({ 
      success: true, 
      session,
      message: 'Регистрация успешно завершена'
    });
  } catch (error) {
    console.error('Error in register endpoint:', error);
    res.status(500).json({ error: error.message });
  }
});

// Обработка данных от Telegram бота
app.post('/api/auth/telegram-callback', async (req, res) => {
  try {
    const { phone, name, lastName, birthday, sessionToken } = req.body;
    
    if (!phone) {
      return res.status(400).json({ error: 'Номер телефона обязателен' });
    }

    // Проверяем существование клиента по телефону
    let clientId;
    const existingClient = await orderService.findClientByPhone(phone);
    
    if (existingClient) {
      clientId = existingClient.client.client_id;
      console.log(`✅ Найден существующий клиент с ID: ${clientId}`);
    } else {
      // Создаем нового клиента с данными из Telegram
      const clientData = {
        name: name || 'Пользователь',
        lastName: lastName || '',
        phone,
        birthday: birthday || '',
        gender: ''
      };
      clientId = await orderService.createClient(clientData);
      console.log(`✅ Создан новый клиент с ID: ${clientId} из Telegram`);
    }

    // Создаем сессию
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30);
    const session = {
      userId: clientId,
      userData: { name, lastName, phone, birthday },
      expiresAt: expiresAt.toISOString()
    };

    // Формируем redirect URL для возврата в приложение
    const frontendUrl = process.env.FRONTEND_URL || 'https://northrepublic.me';
    const redirectUrl = `${frontendUrl}/?session=${encodeURIComponent(JSON.stringify(session))}`;

    res.json({ 
      success: true, 
      session,
      redirectUrl
    });
  } catch (error) {
    console.error('Error in telegram callback:', error);
    res.status(500).json({ error: error.message });
  }
});

// Получить данные сессии по токену
app.get('/api/auth/session/:token', async (req, res) => {
  try {
    const { token } = req.params;
    
    // Декодируем токен сессии
    const sessionData = JSON.parse(decodeURIComponent(token));
    
    // Проверяем валидность сессии
    if (!sessionData.userId || !sessionData.expiresAt) {
      return res.status(400).json({ error: 'Неверный токен сессии' });
    }

    const expiresAt = new Date(sessionData.expiresAt);
    if (expiresAt <= new Date()) {
      return res.status(400).json({ error: 'Сессия истекла' });
    }

    res.json({ 
      success: true, 
      session: sessionData
    });
  } catch (error) {
    console.error('Error getting session:', error);
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/orders/create-guest', async (req, res) => {
  try {
    const { items, total, tableId, comment, customerData } = req.body;
    
    if (!items || !items.length) {
      return res.status(400).json({ error: 'Товары в заказе обязательны' });
    }

    if (!customerData || !customerData.name || !customerData.phone) {
      return res.status(400).json({ error: 'Имя и телефон обязательны для гостевого заказа' });
    }

    const result = await orderService.createGuestOrder({
      items,
      total,
      tableId,
      comment,
      customerData
    });

    // Создаем сессию для пользователя
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30);
    const session = {
      userId: result.client.client_id,
      userData: customerData,
      expiresAt: expiresAt.toISOString()
    };

    res.json({ 
      success: true, 
      order: result.order,
      client: result.client,
      session
    });
  } catch (error) {
    console.error('Error in create guest order endpoint:', error);
    res.status(500).json({ error: error.message });
  }
});

function generateQRCode(amount, comment) {
    const bidvAccount = process.env.BIDV_ACCOUNT_NUMBER || '8845500293'; // Используем номер счета из SePay
    const bankCode = 'BIDV';
    const encodedComment = encodeURIComponent(comment);
    
    return `https://qr.sepay.vn/img?acc=${bidvAccount}&bank=${bankCode}&amount=${amount}&des=${encodedComment}&template=compact`;
}

// Функция отправки QR кода в Telegram
async function sendQRToTelegram(chatId, amount, comment, qrUrl) {
    try {
        const message = `💳 **QR код для оплаты**

💵 Сумма: ${amount} VND
📝 Комментарий: ${comment}
🏦 Банк: BIDV
💳 Счет: ${process.env.BIDV_ACCOUNT_NUMBER || '8845500293'}

📱 **Сканируйте QR код для оплаты**`;

        // Сначала скачиваем QR код
        const qrResponse = await axios.get(qrUrl, { responseType: 'arraybuffer' });
        const qrBuffer = Buffer.from(qrResponse.data);
        
        // Отправляем QR код как файл
        const formData = new FormData();
        formData.append('chat_id', chatId);
        formData.append('photo', qrBuffer, { filename: 'qr_code.png', contentType: 'image/png' });
        formData.append('caption', message);
        formData.append('parse_mode', 'Markdown');

        const response = await axios.post(`https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/sendPhoto`, formData, {
            headers: {
                ...formData.getHeaders()
            }
        });


        return response.data;
    } catch (error) {
        console.error(`❌ Ошибка отправки QR кода в Telegram:`, error.message);
        throw error;
    }
}

// SPA fallback - должен быть последним
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, '../index.html'));
});

app.listen(PORT, async () => {
  console.log(`🚀 RestPublic Backend v${process.env.APP_VERSION || '2.1.1'} running on port ${PORT}`);
  console.log(`📡 Poster API proxy: /api/poster/*`);
  console.log(`📋 Menu cache: /api/menu (with price normalization)`);
  console.log(`🌐 Frontend: /*`);
  console.log(`🔗 Health check: http://localhost:${PORT}/api/health`);
  
  // Запускаем мониторинг SePay после запуска сервера
  if (sepayMonitor) {
    try {
      const connectionOk = await sepayMonitor.testConnection();
      
      if (connectionOk) {
        console.log('🚀 SePay мониторинг запущен');
        sepayMonitor.start();
      } else {
        console.log('⚠️ SePay мониторинг не запущен из-за ошибки подключения');
      }
    } catch (error) {
      console.error('❌ Ошибка запуска SePay мониторинга:', error.message);
    }
  }
});
