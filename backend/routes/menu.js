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
    console.log('🔄 Starting tables and halls fetch...');
    
    // Получаем столы и залы параллельно
    const [tables, halls] = await Promise.all([
      posterService.getTables(),
      posterService.getHalls()
    ]);
    
    console.log(`✅ Tables fetched: ${tables.length}`);
    console.log(`✅ Halls fetched: ${halls.length}`);
    
    // Фильтруем только активные столы и преобразуем в нужный формат
    const activeTables = tables
      .filter(table => table.is_deleted === 0)
      .map(table => ({
        name: table.table_num,
        poster_table_id: table.table_id,
        hall_id: table.hall_id || null,
        capacity: table.capacity || 2,
        status: 'available'
      }));
    
    // Создаем маппинг залов из API
    const hallsMap = new Map();
    halls.forEach(hall => {
      if (hall.delete === '0') { // Только активные залы
        hallsMap.set(hall.hall_id, {
          hall_id: hall.hall_id,
          hall_name: hall.hall_name
        });
      }
    });
    
    // Преобразуем в массив
    const hallsList = Array.from(hallsMap.values());
    
    console.log(`✅ Active tables processed: ${activeTables.length}`);
    console.log(`✅ Active halls processed: ${hallsList.length}`);
    
    res.json({
      tables: activeTables,
      halls: hallsList,
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
