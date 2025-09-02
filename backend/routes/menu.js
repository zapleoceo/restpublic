const express = require('express');
const router = express.Router();
const posterService = require('../services/posterService');

// Get all menu data (categories + products)
router.get('/', async (req, res) => {
  try {
    const [categories, products] = await Promise.all([
      posterService.getCategories(),
      posterService.getProducts()
    ]);

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

// Get popular products
router.get('/popular', async (req, res) => {
  try {
    const limit = parseInt(req.query.limit) || 5;
    const popularProducts = await posterService.getPopularProducts(limit);

    // Process products to normalize prices and add images
    const processedProducts = popularProducts.map(product => ({
      ...product,
      price_normalized: posterService.normalizePrice(product.price),
      price_formatted: posterService.formatPrice(product.price),
      image_url: posterService.getProductImage(product.photo)
    }));

    res.json({
      products: processedProducts,
      limit,
      count: processedProducts.length,
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
      products: processedProducts,
      limit,
      count: processedProducts.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Category Popular Products API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch category popular products',
      message: error.message
    });
  }
});

// Get product by ID
router.get('/products/:productId', async (req, res) => {
  try {
    const { productId } = req.params;
    const products = await posterService.getProducts();
    const product = products.find(p => p.product_id === productId);

    if (!product) {
      return res.status(404).json({
        error: 'Product not found',
        product_id: productId
      });
    }

    // Process product to normalize price and add image
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



module.exports = router;
