const express = require('express');
const router = express.Router();
const posterService = require('../../services/posterService');

// GET /api/webzakaz/menu/dishes
router.get('/dishes', async (req, res) => {
  try {
    const products = await posterService.getProducts();
    
    // Transform to match TZ format
    const transformed = products.map(product => ({
      id: product.product_id?.toString() || product.id?.toString(),
      name: product.product_name || product.name,
      price: parseFloat(product.product_price || product.price || 0),
      categoryId: product.category_id?.toString() || product.categoryId?.toString(),
      active: product.hidden !== "1" && product.hidden !== 1
    }));

    res.json({
      success: true,
      data: transformed,
      timestamp: Date.now()
    });
  } catch (error) {
    console.error('WebZakaz getDishes error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Ошибка загрузки блюд'
    });
  }
});

// GET /api/webzakaz/menu/categories
router.get('/categories', async (req, res) => {
  try {
    const categories = await posterService.getCategories();
    
    // Transform to match TZ format
    const transformed = categories.map(cat => ({
      id: cat.category_id?.toString() || cat.id?.toString(),
      title: cat.category_name || cat.name || cat.title,
      sortOrder: parseInt(cat.sort_order || cat.sortOrder || 0),
      active: cat.category_hidden !== "1" && cat.category_hidden !== 1
    }));

    res.json({
      success: true,
      data: transformed,
      timestamp: Date.now()
    });
  } catch (error) {
    console.error('WebZakaz getCategories error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Ошибка загрузки категорий'
    });
  }
});

module.exports = router;

