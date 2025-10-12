const express = require('express');
const router = express.Router();
const posterService = require('../services/posterService');

// Middleware для проверки авторизации
const requireAuth = (req, res, next) => {
  const authToken = req.headers['x-api-token'] || req.headers['X-API-Token'] || req.query.token;
  const expectedToken = process.env.API_AUTH_TOKEN;
  
  if (!authToken || !expectedToken || authToken !== expectedToken) {
    return res.status(401).json({
      error: 'Unauthorized',
      message: 'Valid API token required'
    });
  }
  
  next();
};

/**
 * Получить остатки на складе
 * GET /api/storage/:storageId/leftovers
 */
router.get('/:storageId/leftovers', requireAuth, async (req, res) => {
  try {
    const { storageId } = req.params;
    
    console.log(`📦 Getting leftovers for storage ID: ${storageId}`);
    
    const leftovers = await posterService.getStorageLeftovers(storageId);
    
    res.json({
      success: true,
      storage_id: storageId,
      leftovers: leftovers,
      count: leftovers.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Storage Leftovers API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch storage leftovers',
      message: error.message
    });
  }
});

/**
 * Создать перемещение остатков между складами
 * POST /api/storage/moving
 */
router.post('/moving', requireAuth, async (req, res) => {
  try {
    const { 
      storage_id_from, 
      storage_id_to, 
      products, 
      comment 
    } = req.body;
    
    // Валидация обязательных полей
    if (!storage_id_from || !storage_id_to) {
      return res.status(400).json({
        error: 'Missing required fields',
        message: 'storage_id_from and storage_id_to are required'
      });
    }
    
    if (!products || !Array.isArray(products) || products.length === 0) {
      return res.status(400).json({
        error: 'Invalid products data',
        message: 'products array is required and cannot be empty'
      });
    }
    
    console.log(`🔄 Creating moving from storage ${storage_id_from} to ${storage_id_to}`);
    console.log(`📋 Products count: ${products.length}`);
    
    const movingData = {
      storage_id_from: parseInt(storage_id_from),
      storage_id_to: parseInt(storage_id_to),
      comment: comment || 'Автоматическое перемещение остатков',
      products: products
    };
    
    const movingId = await posterService.createMoving(movingData);
    
    res.json({
      success: true,
      moving_id: movingId,
      storage_id_from: storage_id_from,
      storage_id_to: storage_id_to,
      products_count: products.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Storage Moving API Error:', error);
    res.status(500).json({
      error: 'Failed to create storage moving',
      message: error.message
    });
  }
});

/**
 * Получить остатки и создать перемещение (комбинированная операция)
 * POST /api/storage/migrate
 */
router.post('/migrate', requireAuth, async (req, res) => {
  try {
    const { 
      source_storage_id, 
      target_storage_id, 
      comment 
    } = req.body;
    
    if (!source_storage_id || !target_storage_id) {
      return res.status(400).json({
        error: 'Missing required fields',
        message: 'source_storage_id and target_storage_id are required'
      });
    }
    
    console.log(`🚀 Starting migration from storage ${source_storage_id} to ${target_storage_id}`);
    
    // 1. Получаем остатки с исходного склада
    const leftovers = await posterService.getStorageLeftovers(source_storage_id);
    
    if (!leftovers || leftovers.length === 0) {
      return res.status(404).json({
        error: 'No leftovers found',
        message: `No leftovers found on storage ${source_storage_id}`
      });
    }
    
    // 2. Подготавливаем данные для перемещения
    const products = leftovers
      .filter(item => item.product_id && item.count && item.count > 0)
      .map(item => ({
        product_id: item.product_id,
        count: item.count
      }));
    
    if (products.length === 0) {
      return res.status(400).json({
        error: 'No valid products for moving',
        message: 'No products with valid counts found'
      });
    }
    
    // 3. Создаем перемещение
    const movingData = {
      storage_id_from: parseInt(source_storage_id),
      storage_id_to: parseInt(target_storage_id),
      comment: comment || 'Автоматическое перемещение остатков',
      products: products
    };
    
    const movingId = await posterService.createMoving(movingData);
    
    res.json({
      success: true,
      moving_id: movingId,
      source_storage_id: source_storage_id,
      target_storage_id: target_storage_id,
      leftovers_found: leftovers.length,
      products_moved: products.length,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Storage Migration API Error:', error);
    res.status(500).json({
      error: 'Failed to migrate storage',
      message: error.message
    });
  }
});

module.exports = router;
