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

// Proxy endpoint for any Poster API method
router.get('/:method', requireAuth, async (req, res) => {
  try {
    const { method } = req.params;
    const params = req.query;
    
    // Remove token from params if present (we'll add it in the service)
    delete params.token;
    
    console.log(`📡 Poster API Proxy GET: ${method}`);
    console.log(`📋 Params:`, params);
    
    const result = await posterService.makeRequest(method, params);
    res.json(result);
  } catch (error) {
    console.error('Poster Proxy Error:', error);
    res.status(500).json({
      error: 'Poster API request failed',
      message: error.message
    });
  }
});

// POST endpoint for Poster API methods that require POST
router.post('/:method', requireAuth, async (req, res) => {
  try {
    const { method } = req.params;
    const params = req.query;
    const body = req.body;
    
    // Remove token from params if present
    delete params.token;
    
    console.log(`📡 Poster API Proxy POST: ${method}`);
    console.log(`📋 Params:`, params);
    console.log(`📦 Body:`, body);
    
    // For POST requests, we need to handle the body differently
    // This is a simplified version - you might need to adjust based on specific Poster API requirements
    const result = await posterService.makeRequest(method, { ...params, ...body });
    res.json(result);
  } catch (error) {
    console.error('Poster Proxy POST Error:', error);
    res.status(500).json({
      error: 'Poster API POST request failed',
      message: error.message
    });
  }
});

// Get tables list
router.get('/tables/list', requireAuth, async (req, res) => {
  try {
    console.log('📡 Getting tables list...');
    const tables = await posterService.getTables();
    res.json({
      success: true,
      tables: tables,
      count: tables.length
    });
  } catch (error) {
    console.error('Tables API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch tables',
      message: error.message
    });
  }
});

// Create order
router.post('/orders/create', requireAuth, async (req, res) => {
  try {
    console.log('📡 Creating order...');
    const orderData = req.body;
    
    // Validate required fields
    if (!orderData.products || !Array.isArray(orderData.products) || orderData.products.length === 0) {
      return res.status(400).json({
        error: 'Invalid order data',
        message: 'Products array is required and cannot be empty'
      });
    }
    
    const result = await posterService.createIncomingOrder(orderData);
    res.json({
      success: true,
      order: result
    });
  } catch (error) {
    console.error('Order creation error:', error);
    res.status(500).json({
      error: 'Failed to create order',
      message: error.message
    });
  }
});

// Create order (check) using orders.createOrder
router.post('/orders/create-check', requireAuth, async (req, res) => {
  try {
    console.log('📡 Creating order (check)...');
    const orderData = req.body;
    
    // Validate required fields
    if (!orderData.spotId) {
      return res.status(400).json({
        error: 'Invalid order data',
        message: 'spotId is required'
      });
    }
    if (!orderData.client || !orderData.client.phone) {
      return res.status(400).json({
        error: 'Invalid order data',
        message: 'client.phone is required'
      });
    }
    if (!orderData.products || !Array.isArray(orderData.products) || orderData.products.length === 0) {
      return res.status(400).json({
        error: 'Invalid order data',
        message: 'Products array is required and cannot be empty'
      });
    }
    
    const result = await posterService.createOrder(orderData);
    res.json({
      success: true,
      order: result
    });
  } catch (error) {
    console.error('Order (check) creation error:', error);
    res.status(500).json({
      error: 'Failed to create order (check)',
      message: error.message
    });
  }
});

// Remove client
router.post('/clients.removeClient', requireAuth, async (req, res) => {
  try {
    console.log('📡 Removing client...');
    const { client_id } = req.body;
    
    if (!client_id) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'client_id is required'
      });
    }
    
    const result = await posterService.removeClient(client_id);
    res.json({
      success: true,
      result: result
    });
  } catch (error) {
    console.error('Client removal error:', error);
    res.status(500).json({
      error: 'Failed to remove client',
      message: error.message
    });
  }
});

// Get all clients
router.get('/clients.getClients', requireAuth, async (req, res) => {
  try {
    console.log('📡 Getting all clients...');
    const clients = await posterService.getAllClients();
    res.json(clients);
  } catch (error) {
    console.error('All clients get error:', error);
    res.status(500).json({
      error: 'Failed to get all clients',
      message: error.message
    });
  }
});

// Get client by ID
router.get('/clients.getClient', requireAuth, async (req, res) => {
  try {
    console.log('📡 Getting client...');
    const { client_id } = req.query;
    
    if (!client_id) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'client_id is required'
      });
    }
    
    const result = await posterService.getClientById(client_id);
    res.json(result);
  } catch (error) {
    console.error('Client get error:', error);
    res.status(500).json({
      error: 'Failed to get client',
      message: error.message
    });
  }
});

// Get transactions
router.get('/transactions.getTransactions', requireAuth, async (req, res) => {
  try {
    console.log('📡 Getting transactions...');
    const { client_id } = req.query;
    
    if (!client_id) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'client_id is required'
      });
    }
    
    const result = await posterService.getTransactions(client_id);
    res.json(result);
  } catch (error) {
    console.error('Transactions get error:', error);
    res.status(500).json({
      error: 'Failed to get transactions',
      message: error.message
    });
  }
});

// Add product to transaction
router.post('/transactions.addTransactionProduct', requireAuth, async (req, res) => {
  try {
    console.log('📡 Adding product to transaction...');
    const { transaction_id, product_id, count, price, spot_id, spot_tablet_id } = req.body;
    
    if (!transaction_id || !product_id || !count || !price) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'transaction_id, product_id, count, and price are required'
      });
    }
    
    const result = await posterService.addTransactionProduct(
      transaction_id, 
      product_id, 
      count, 
      price, 
      spot_id || 1, 
      spot_tablet_id || 1
    );
    res.json(result);
  } catch (error) {
    console.error('Add transaction product error:', error);
    res.status(500).json({
      error: 'Failed to add product to transaction',
      message: error.message
    });
  }
});

// Update transaction
router.post('/transactions.updateTransaction', requireAuth, async (req, res) => {
  try {
    console.log('📡 Updating transaction...');
    const { transaction_id, comment } = req.body;
    
    if (!transaction_id) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'transaction_id is required'
      });
    }
    
    const result = await posterService.updateTransaction(transaction_id, comment);
    res.json(result);
  } catch (error) {
    console.error('Update transaction error:', error);
    res.status(500).json({
      error: 'Failed to update transaction',
      message: error.message
    });
  }
});

// Change transaction product count
router.post('/transactions.changeTransactionProductCount', requireAuth, async (req, res) => {
  try {
    console.log('📡 Changing transaction product count...');
    const { transaction_id, product_id, count } = req.body;
    
    if (!transaction_id || !product_id || count === undefined) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'transaction_id, product_id, and count are required'
      });
    }
    
    const result = await posterService.changeTransactionProductCount(transaction_id, product_id, count);
    res.json(result);
  } catch (error) {
    console.error('Change transaction product count error:', error);
    res.status(500).json({
      error: 'Failed to change product count',
      message: error.message
    });
  }
});

router.post('/transactions.changeFiscalStatus', requireAuth, async (req, res) => {
  try {
    console.log('📡 Changing fiscal status...');
    const { transaction_id, fiscal_status } = req.body;
    
    if (!transaction_id || fiscal_status === undefined) {
      return res.status(400).json({
        error: 'Invalid request',
        message: 'transaction_id and fiscal_status are required'
      });
    }
    
    const result = await posterService.changeFiscalStatus(transaction_id, fiscal_status);
    res.json(result);
  } catch (error) {
    console.error('Change fiscal status error:', error);
    res.status(500).json({
      error: 'Failed to change fiscal status',
      message: error.message
    });
  }
});

module.exports = router;
