const express = require('express');
const router = express.Router();
const posterService = require('../services/posterService');

// Proxy endpoint for any Poster API method
router.get('/:method', async (req, res) => {
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
router.post('/:method', async (req, res) => {
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

module.exports = router;
