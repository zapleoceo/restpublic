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
  origin: ['https://goodzone.zapleo.com', 'http://localhost:3000', 'http://localhost:5173'],
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
    version: '2.1.0',
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

    // Получаем продукты
    const productsResponse = await axios.get('https://joinposter.com/api/menu.getProducts', {
      params: { token },
      httpsAgent: httpsAgent,
      timeout: 10000
    });

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
  console.log(`🚀 RestPublic Backend v2.1.0 running on port ${PORT}`);
  console.log(`📡 Poster API proxy: /api/poster/*`);
  console.log(`📋 Menu cache: /api/menu (with price normalization)`);
  console.log(`🌐 Frontend: /dist/*`);
  console.log(`🔗 Health check: http://localhost:${PORT}/api/health`);
});
