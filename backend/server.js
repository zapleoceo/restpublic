const express = require('express');
const cors = require('cors');
const axios = require('axios');
const path = require('path');
const https = require('https');
const FormData = require('form-data');
const SePayMonitor = require('./sepay-monitor');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors({
  origin: ['https://goodzone.zapleo.com', 'http://localhost:3000', 'http://localhost:5173', 'http://localhost:3001'],
  credentials: true
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Логирование запросов
app.use((req, res, next) => {
  console.log(`${new Date().toISOString()} - ${req.method} ${req.path}`);
  next();
});

// Статические файлы
app.use(express.static(path.join(__dirname, '../dist')));

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

// Тестовый endpoint для проверки API Poster
app.get('/api/test-poster', async (req, res) => {
  try {
    const token = process.env.POSTER_API_TOKEN;
    if (!token) {
      return res.status(500).json({ error: 'POSTER_API_TOKEN not configured' });
    }

    console.log('🧪 Testing Poster API...');

    // Тестируем категории
    const categoriesResponse = await axios.get('https://joinposter.com/api/menu.getCategories', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });

    // Тестируем продукты
    const productsResponse = await axios.get('https://joinposter.com/api/menu.getProducts', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });

    res.json({
      success: true,
      categories: {
        count: categoriesResponse.data.response?.length || 0,
        sample: categoriesResponse.data.response?.slice(0, 2) || [],
        fullResponse: categoriesResponse.data
      },
      products: {
        count: productsResponse.data.response?.length || 0,
        sample: productsResponse.data.response?.slice(0, 2) || [],
        fullResponse: productsResponse.data
      }
    });
  } catch (error) {
    console.error('❌ Poster API test error:', error.message);
    res.status(500).json({ 
      error: error.message,
      details: error.response?.data || 'No response data'
    });
  }
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

    console.log(`📡 Poster API request: ${req.method} ${req.path}`);

    const response = await axios({
      method: req.method,
      url: `${posterUrl}${req.path}`,
      params: params,
      data: req.body,
      headers: {
        'Content-Type': 'application/json',
        'User-Agent': 'RestPublic-Backend/2.0',
        ...req.headers
      },
      httpsAgent: httpsAgent,
      timeout: 15000
    });

    console.log(`✅ Poster API response: ${response.status}`);
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
      console.log('📋 Serving menu from cache');
      return res.json(menuCache);
    }

    const token = process.env.POSTER_API_TOKEN;
    if (!token) {
      return res.status(500).json({ error: 'POSTER_API_TOKEN not configured' });
    }

    console.log('🔄 Fetching fresh menu data');

    // Получаем категории
    const categoriesResponse = await axios.get('https://joinposter.com/api/menu.getCategories', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });

    console.log('📋 Categories response:', JSON.stringify(categoriesResponse.data, null, 2));

    // Получаем продукты
    const productsResponse = await axios.get('https://joinposter.com/api/menu.getProducts', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });

    console.log('🍽️ Products response sample:', JSON.stringify(productsResponse.data.response?.slice(0, 2), null, 2));

    // Фильтруем только видимые товары (hidden !== "1")
    const rawProducts = productsResponse.data.response || [];
    const visibleProducts = rawProducts.filter(product => product.hidden !== "1");

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

    // Логируем пример цены для отладки
    if (menuData.products.length > 0) {
      const sampleProduct = menuData.products[0];
      console.log('🔍 Sample product price debug:');
      console.log('Product:', sampleProduct.product_name);
      console.log('Price object:', JSON.stringify(sampleProduct.price));
      console.log('Price["1"]:', sampleProduct.price?.['1']);
      console.log('Price type:', typeof sampleProduct.price?.['1']);
    }

    // Обновляем кэш
    menuCache = menuData;
    cacheTimestamp = Date.now();

    console.log(`✅ Menu data cached: ${menuData.categories.length} categories, ${menuData.products.length} products`);
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
      console.log('📊 Возвращаем кэшированные данные популярности');
      return res.json({ productPopularity: popularityCache });
    }

    console.log('📊 Запрашиваем данные популярности товаров...');
    
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

    console.log(`📅 Период: ${dateFrom} - ${dateTo} (7 дней)`);

    // Используем правильный API метод для получения продаж по товарам
    const response = await axios.get('https://joinposter.com/api/dash.getProductsSales', {
      params: {
        token: process.env.POSTER_API_TOKEN,
        date_from: dateFrom,
        date_to: dateTo
      }
    });

    console.log('📊 Получен ответ от Poster API:', response.status);

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

      console.log('📊 Обработано товаров:', Object.keys(productPopularity).length);
      console.log('📊 Данные популярности:', productPopularity);

      // Обновляем кэш
      popularityCache = productPopularity;
      popularityCacheTimestamp = now;

      res.json({ productPopularity });
    } else {
      console.log('❌ Неверный формат ответа от Poster API');
      res.json({ productPopularity: {} });
    }
  } catch (error) {
    console.error('❌ Ошибка при получении данных популярности:', error.message);
    res.json({ productPopularity: {} });
  }
});

// Временный тестовый endpoint для проверки Telegram API
app.get('/api/test-telegram', async (req, res) => {
    try {
        const testMessage = '🧪 Тестовое сообщение от SePay мониторинга';
        
        // Тестируем разные варианты chat_id
        const testChatIds = [
            'Rest_publica_bar',
            '@Rest_publica_bar', 
            'zapleosoft',
            '@zapleosoft'
        ];
        
        const results = [];
        
        for (const chatId of testChatIds) {
            try {
                const response = await axios.post(`https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/sendMessage`, {
                    chat_id: chatId,
                    text: testMessage
                });
                
                results.push({
                    chatId,
                    success: true,
                    response: response.data
                });
            } catch (error) {
                results.push({
                    chatId,
                    success: false,
                    error: error.response?.data || error.message
                });
            }
        }
        
        res.json({
            message: 'Тест Telegram API завершен',
            results
        });
    } catch (error) {
        res.status(500).json({
            error: 'Ошибка тестирования Telegram API',
            details: error.message
        });
    }
});

// Endpoint для получения chat_id пользователя
app.get('/api/get-chat-id', async (req, res) => {
    try {
        const updates = await axios.get(`https://api.telegram.org/bot${process.env.TELEGRAM_BOT_TOKEN}/getUpdates`);
        
        if (updates.data.ok && updates.data.result.length > 0) {
            const chatIds = updates.data.result.map(update => {
                if (update.message) {
                    return {
                        chat_id: update.message.chat.id,
                        username: update.message.chat.username,
                        first_name: update.message.chat.first_name,
                        last_name: update.message.chat.last_name,
                        type: update.message.chat.type
                    };
                }
                return null;
            }).filter(Boolean);
            
            res.json({
                message: 'Найденные chat_id',
                chatIds: chatIds
            });
        } else {
            res.json({
                message: 'Обновлений не найдено. Попросите пользователей написать боту /start',
                chatIds: []
            });
        }
    } catch (error) {
        res.status(500).json({
            error: 'Ошибка получения chat_id',
            details: error.message
        });
    }
});

// SPA fallback
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, '../dist/index.html'));
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
    console.log('💰 SePay мониторинг инициализирован');
  } catch (error) {
    console.error('❌ Ошибка инициализации SePay мониторинга:', error.message);
  }
}

// Функция генерации QR кода через SePay
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

        console.log(`✅ QR код отправлен в Telegram чат ${chatId}`);
        return response.data;
    } catch (error) {
        console.error(`❌ Ошибка отправки QR кода в Telegram:`, error.message);
        throw error;
    }
}

// Endpoint для тестирования QR кода
app.post('/api/test-qr', async (req, res) => {
    try {
        const { amount = 1, comment = 'Test payment', chatId = '169510539' } = req.body;
        
        console.log(`🧪 Генерация тестового QR кода: ${amount} VND, комментарий: ${comment}`);
        
        const qrUrl = generateQRCode(amount, comment);
        
        await sendQRToTelegram(chatId, amount, comment, qrUrl);
        
        res.json({
            success: true,
            message: 'QR код отправлен в Telegram',
            qr_url: qrUrl,
            amount: amount,
            comment: comment
        });
    } catch (error) {
        console.error('❌ Ошибка генерации QR кода:', error.message);
        res.status(500).json({
            error: 'Ошибка генерации QR кода',
            details: error.message
        });
    }
});

app.listen(PORT, async () => {
  console.log(`🚀 RestPublic Backend v${process.env.APP_VERSION || '2.1.1'} running on port ${PORT}`);
  console.log(`📡 Poster API proxy: /api/poster/*`);
  console.log(`📋 Menu cache: /api/menu (with price normalization)`);
  console.log(`🌐 Frontend: /dist/*`);
  console.log(`🔗 Health check: http://localhost:${PORT}/api/health`);
  
  // Запускаем мониторинг SePay после запуска сервера
  if (sepayMonitor) {
    try {
      console.log('🧪 Тестирование подключения к SePay API...');
      const connectionOk = await sepayMonitor.testConnection();
      
      if (connectionOk) {
        console.log('🚀 Запуск мониторинга транзакций SePay...');
        sepayMonitor.start();
      } else {
        console.log('⚠️ SePay мониторинг не запущен из-за ошибки подключения');
      }
    } catch (error) {
      console.error('❌ Ошибка запуска SePay мониторинга:', error.message);
    }
  } else {
    console.log('⚠️ SePay мониторинг не инициализирован (отсутствует SEPAY_API_TOKEN)');
  }
});
