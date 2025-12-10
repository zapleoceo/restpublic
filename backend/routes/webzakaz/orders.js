const express = require('express');
const router = express.Router();
const posterService = require('../../services/posterService');

// Middleware для проверки авторизации
const checkAuth = (req, res, next) => {
  if (!req.session || !req.session.cashierId) {
    return res.status(401).json({
      success: false,
      message: 'Требуется авторизация'
    });
  }
  next();
};

// GET /api/webzakaz/orders?spotId=spot_001
router.get('/', checkAuth, async (req, res) => {
  try {
    const { spotId } = req.query;
    
    if (!spotId) {
      return res.status(400).json({
        success: false,
        message: 'spotId обязателен'
      });
    }

    // Get open transactions from Poster API
    // Note: Poster API v3 might use different endpoint
    const transactions = await posterService.makeRequest('incomingOrders.getIncomingOrders', {
      spot_id: spotId,
      status: 'open'
    });

    // Transform response
    const transformed = Array.isArray(transactions) ? transactions.map(trans => ({
      id: trans.transaction_id?.toString() || trans.id?.toString(),
      number: trans.transaction_number || trans.number,
      spotId: trans.spot_id?.toString() || trans.spotId?.toString(),
      status: trans.status || 'open',
      items: (trans.products || trans.items || []).map(item => ({
        dishId: item.product_id?.toString() || item.dishId?.toString(),
        dishTitle: item.product_name || item.dishTitle,
        quantity: parseFloat(item.count || item.quantity || 1),
        price: parseFloat(item.price || 0),
        comment: item.comment || ''
      })),
      comment: trans.comment || '',
      total: parseFloat(trans.total || 0),
      createdAt: trans.created_at || trans.createdAt
    })) : [];

    res.json({
      success: true,
      data: transformed,
      timestamp: Date.now()
    });
  } catch (error) {
    console.error('WebZakaz getOrders error:', error);
    // Return empty array if API fails
    res.json({
      success: true,
      data: [],
      timestamp: Date.now()
    });
  }
});

// POST /api/webzakaz/orders/create
router.post('/create', checkAuth, async (req, res) => {
  try {
    const { hallId, spotId, items, orderComment } = req.body;

    // Validation
    if (!spotId || !items || items.length === 0) {
      return res.status(400).json({
        success: false,
        message: 'Заполните все поля'
      });
    }

    // Generate transaction number (timestamp-based)
    const transactionNumber = Math.floor(Date.now() / 1000);

    // Prepare order payload for Poster API
    // Note: createIncomingOrder requires phone or client_id, so we use a dummy phone for web orders
    const orderPayload = {
      spot_id: parseInt(spotId),
      phone: '0000000000', // Dummy phone for web orders
      service_mode: 1, // 1 - в заведении
      comment: orderComment || '',
      products: items.map(item => ({
        product_id: parseInt(item.dishId),
        count: parseInt(item.quantity),
        price: Math.round(parseFloat(item.price) * 100), // Convert to minor units (cents)
        comment: item.comment || ''
      }))
    };

    // Create order in Poster API using existing service method
    const result = await posterService.createIncomingOrder(orderPayload);

    // Handle different response formats from Poster API
    // createIncomingOrder returns response.data directly
    const responseData = result.response || result;
    
    if (responseData) {
      // Check for incoming_order_id in response (Poster API format)
      const transactionId = responseData.incoming_order_id || responseData.transaction_id || responseData.id;
      const transactionNumber = responseData.incoming_order_number || responseData.transaction_number || responseData.number || transactionNumber;
      
      if (transactionId) {
        return res.json({
          success: true,
          transactionId: transactionId.toString(),
          transactionNumber: transactionNumber.toString(),
          message: 'Заказ успешно создан'
        });
      }

      // If response has error
      if (responseData.error) {
        throw new Error(responseData.error.message || 'Ошибка создания заказа');
      }
    }

    // If no transaction ID found, still return success with generated number
    return res.json({
      success: true,
      transactionId: transactionNumber.toString(),
      transactionNumber: transactionNumber.toString(),
      message: 'Заказ успешно создан'
    });
  } catch (error) {
    console.error('WebZakaz createOrder error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Ошибка создания заказа'
    });
  }
});

module.exports = router;

