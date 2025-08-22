const express = require('express');
const cors = require('cors');
const axios = require('axios');
const path = require('path');
const https = require('https');
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

// Кэш для данных о популярности
let popularityCache = null;
let popularityCacheTimestamp = null;
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

// Endpoint для получения данных о популярности продуктов
app.get('/api/products/popularity', async (req, res) => {
  try {
    // Проверяем кэш
    if (popularityCache && popularityCacheTimestamp && (Date.now() - popularityCacheTimestamp) < POPULARITY_CACHE_DURATION) {
      console.log('📊 Serving popularity data from cache');
      return res.json(popularityCache);
    }

    const token = process.env.POSTER_API_TOKEN;
    if (!token) {
      return res.status(500).json({ error: 'POSTER_API_TOKEN not configured' });
    }

    console.log('🔄 Fetching fresh popularity data');

    // Вычисляем дату 3 дня назад
    const threeDaysAgo = new Date();
    threeDaysAgo.setDate(threeDaysAgo.getDate() - 3);
    const dateFrom = threeDaysAgo.toISOString().split('T')[0]; // YYYY-MM-DD формат

    // Получаем транзакции за последние 3 дня
    const transactionsResponse = await axios.get('https://joinposter.com/api/dash.getTransactions', {
      params: { 
        token,
        date_from: dateFrom,
        type: 'incoming_order',
        include_products: 1
      },
      httpsAgent: httpsAgent,
      timeout: 15000
    });

    console.log('📊 Transactions response:', JSON.stringify(transactionsResponse.data, null, 2));

    const transactions = transactionsResponse.data.response || [];
    console.log(`📊 Found ${transactions.length} transactions`);
    
    // Подсчитываем количество заказов для каждого продукта
    const productPopularity = {};
    
    transactions.forEach((transaction, index) => {
      console.log(`🔍 Transaction ${index + 1}:`, {
        transaction_id: transaction.transaction_id,
        has_products: !!transaction.products,
        products_count: transaction.products ? transaction.products.length : 0,
        products_sample: transaction.products ? transaction.products.slice(0, 2) : null
      });
      
      if (transaction.products && Array.isArray(transaction.products)) {
        transaction.products.forEach(product => {
          const productId = product.product_id;
          if (productId) {
            productPopularity[productId] = (productPopularity[productId] || 0) + (product.count || 1);
            console.log(`📦 Product ${productId}: count ${product.count || 1}, total popularity: ${productPopularity[productId]}`);
          }
        });
      }
    });

    const popularityData = {
      productPopularity,
      dateFrom,
      totalTransactions: transactions.length,
      timestamp: new Date().toISOString()
    };

    // Обновляем кэш
    popularityCache = popularityData;
    popularityCacheTimestamp = Date.now();

    console.log(`✅ Popularity data cached: ${Object.keys(productPopularity).length} products with orders`);
    res.json(popularityData);
  } catch (error) {
    console.error('❌ Popularity fetch error:', error.message);
    res.status(500).json({ error: error.message });
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

app.listen(PORT, () => {
  console.log(`🚀 RestPublic Backend v${process.env.APP_VERSION || '2.1.1'} running on port ${PORT}`);
  console.log(`📡 Poster API proxy: /api/poster/*`);
  console.log(`📋 Menu cache: /api/menu (with price normalization)`);
  console.log(`🌐 Frontend: /dist/*`);
  console.log(`🔗 Health check: http://localhost:${PORT}/api/health`);
});
