const express = require('express');
const router = express.Router();
const posterService = require('../services/posterService');

// Get all menu data (categories + products)
router.get('/', async (req, res) => {
  try {
    console.log('🔄 Starting menu data fetch...');
    
    let categories = [];
    let products = [];
    
    try {
      categories = await posterService.getCategories();
      console.log(`✅ Categories fetched: ${categories.length}`);
    } catch (error) {
      console.error('❌ Categories fetch failed:', error.message);
      categories = [];
    }
    
    try {
      products = await posterService.getProducts();
      console.log(`✅ Products fetched: ${products.length}`);
    } catch (error) {
      console.error('❌ Products fetch failed:', error.message);
      products = [];
    }

    // Process products to normalize prices and add images
    const processedProducts = products.map(product => ({
      ...product,
      price_normalized: posterService.normalizePrice(product.price),
      price_formatted: posterService.formatPrice(product.price),
      image_url: posterService.getProductImage(product.photo)
    }));

    res.json({
      categories,
      products: processedProducts,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Menu API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch menu data',
      message: error.message
    });
  }
});

// Get categories only
router.get('/categories', async (req, res) => {
  try {
    const categories = await posterService.getCategories();
    res.json({
      categories,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Categories API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch categories',
      message: error.message
    });
  }
});

// Get products by category
router.get('/categories/:categoryId/products', async (req, res) => {
  try {
    const { categoryId } = req.params;
    const products = await posterService.getProductsByCategory(categoryId);

    // Process products to normalize prices and add images
    const processedProducts = products.map(product => ({
      ...product,
      price_normalized: posterService.normalizePrice(product.price),
      price_formatted: posterService.formatPrice(product.price),
      image_url: posterService.getProductImage(product.photo)
    }));

    res.json({
      category_id: categoryId,
      products: processedProducts,
      count: processedProducts.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Category Products API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch category products',
      message: error.message
    });
  }
});

// Get popular products by category
router.get('/categories/:categoryId/popular', async (req, res) => {
  try {
    const { categoryId } = req.params;
    const limit = parseInt(req.query.limit) || 5;
    
    const popularProducts = await posterService.getPopularProductsByCategory(categoryId, limit);

    // Process products to normalize prices and add images
    const processedProducts = popularProducts.map(product => ({
      ...product,
      price_normalized: posterService.normalizePrice(product.price),
      price_formatted: posterService.formatPrice(product.price),
      image_url: posterService.getProductImage(product.photo)
    }));

    res.json({
      category_id: categoryId,
      popular_products: processedProducts,
      count: processedProducts.length,
      limit,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Popular Products API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch popular products',
      message: error.message
    });
  }
});

// Get specific product
router.get('/products/:productId', async (req, res) => {
  try {
    const { productId } = req.params;
    const products = await posterService.getProducts();
    const product = products.find(p => p.product_id === productId);
    
    if (!product) {
      return res.status(404).json({
        error: 'Product not found',
        message: `Product with ID ${productId} not found`
      });
    }

    // Process product to normalize prices and add images
    const processedProduct = {
      ...product,
      price_normalized: posterService.normalizePrice(product.price),
      price_formatted: posterService.formatPrice(product.price),
      image_url: posterService.getProductImage(product.photo)
    };

    res.json({
      product: processedProduct,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Product API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch product',
      message: error.message
    });
  }
});

// Get tables list
router.get('/tables', async (req, res) => {
  try {
    console.log('🔄 Starting tables fetch...');
    const tables = await posterService.getTables();
    console.log(`✅ Tables fetched: ${tables.length}`);
    
    // Фильтруем только активные столы и преобразуем в нужный формат
    const activeTables = tables
      .filter(table => table.is_deleted === 0)
      .map(table => ({
        name: table.table_num,
        poster_table_id: table.table_id,
        // Добавляем информацию о зале
        hall_id: table.hall_id || table.zone_id || table.spot_id || null,
        hall_name: table.hall_name || table.zone_name || table.spot_name || null,
        capacity: table.capacity || 2,
        status: 'available'
      }));
    
    // Создаем список уникальных залов
    const hallsMap = new Map();
    activeTables.forEach(table => {
      if (table.hall_id) {
        hallsMap.set(table.hall_id, {
          hall_id: table.hall_id,
          hall_name: table.hall_name || `Зал ${table.hall_id}`
        });
      }
    });
    
    let halls = Array.from(hallsMap.values());
    
    // Если залов нет, создаем дефолтные залы
    if (halls.length === 0) {
      console.log('⚠️ No halls found in Poster API, creating default halls');
      halls = [
        { hall_id: '1', hall_name: 'Основной зал' },
        { hall_id: '2', hall_name: 'VIP зал' }
      ];
    } else {
      // Маппинг реальных названий залов (настраивается вручную)
      const hallNamesMapping = {
        '1': 'Основной зал',  // Замените на реальное название
        '2': 'VIP зал'        // Замените на реальное название
      };
      
      // Обновляем названия залов согласно маппингу
      halls = halls.map(hall => ({
        ...hall,
        hall_name: hallNamesMapping[hall.hall_id] || hall.hall_name || `Зал ${hall.hall_id}`
      }));
    }
    
    console.log(`✅ Active tables processed: ${activeTables.length}`);
    console.log(`✅ Halls found: ${halls.length}`);
    
    res.json({
      tables: activeTables,
      halls: halls,
      count: activeTables.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Tables API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch tables',
      message: error.message
    });
  }
});

module.exports = router;
