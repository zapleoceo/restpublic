const express = require('express');
const router = express.Router();
const { MongoClient } = require('mongodb');

// MongoDB connection
const MONGODB_URL = process.env.MONGODB_URL || 'mongodb://localhost:27018';
const DB_NAME = process.env.MONGODB_DB_NAME || 'northrepublic';

let db;
MongoClient.connect(MONGODB_URL)
  .then(client => {
    db = client.db(DB_NAME);
    console.log('✅ MongoDB connected for user routes');
  })
  .catch(err => {
    console.error('❌ MongoDB connection failed for user routes:', err);
  });

// Middleware to check authentication
const requireAuth = async (req, res, next) => {
  try {
    const sessionToken = req.headers['x-session-token'] || req.query.sessionToken;
    
    if (!sessionToken) {
      return res.status(401).json({
        success: false,
        error: 'Authentication required'
      });
    }

    // Check session in MongoDB
    const session = await db.collection('user_sessions').findOne({
      sessionToken: sessionToken,
      expiresAt: { $gt: new Date() }
    });

    if (!session) {
      return res.status(401).json({
        success: false,
        error: 'Session expired'
      });
    }

    req.user = { client_id: session.client_id };
    next();

  } catch (error) {
    console.error('❌ Auth middleware failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
};

// Get user profile
router.get('/profile', requireAuth, async (req, res) => {
  try {
    const posterService = require('../services/posterService');
    const user = await posterService.getClientById(req.user.client_id);

    if (!user) {
      return res.status(404).json({
        success: false,
        error: 'User not found'
      });
    }

    // Вычисляем максимальную скидку
    const personalDiscount = parseFloat(user.discount_per || 0);
    const groupDiscount = parseFloat(user.client_groups_discount || 0);
    const maxDiscount = Math.max(personalDiscount, groupDiscount);
    
    // Добавляем максимальную скидку к данным пользователя
    user.max_discount = maxDiscount;

    res.json({
      success: true,
      user: user
    });

  } catch (error) {
    console.error('❌ Get profile failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

// Get user orders
router.get('/orders', requireAuth, async (req, res) => {
  try {
    const posterService = require('../services/posterService');
    
    // Получаем заказы клиента за последние 30 дней
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    
    const dateFrom = thirtyDaysAgo.toISOString().split('T')[0]; // YYYY-MM-DD
    const dateTo = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
    
    const orders = await posterService.getClientTransactions(req.user.client_id, dateFrom, dateTo);
    
    res.json({
      success: true,
      orders: orders || []
    });

  } catch (error) {
    console.error('❌ Get orders failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

module.exports = router;
