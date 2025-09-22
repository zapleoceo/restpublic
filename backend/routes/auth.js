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
    console.log('✅ MongoDB connected for auth routes');
  })
  .catch(err => {
    console.error('❌ MongoDB connection failed for auth routes:', err);
  });

// Check authentication status
router.get('/status', async (req, res) => {
  try {
    const sessionToken = req.headers['x-session-token'] || req.query.sessionToken;
    
    if (!sessionToken) {
      return res.json({
        success: true,
        authenticated: false,
        user: null
      });
    }

    // Check session in MongoDB
    const session = await db.collection('user_sessions').findOne({
      sessionToken: sessionToken,
      expiresAt: { $gt: new Date() }
    });

    if (!session) {
      return res.json({
        success: true,
        authenticated: false,
        user: null
      });
    }

    // Get user data
    const user = await db.collection('users').findOne({
      client_id: session.client_id
    });

    res.json({
      success: true,
      authenticated: true,
      user: user || { client_id: session.client_id }
    });

  } catch (error) {
    console.error('❌ Auth status check failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

// Logout
router.post('/logout', async (req, res) => {
  try {
    const sessionToken = req.headers['x-session-token'] || req.body.sessionToken;
    
    if (sessionToken) {
      // Delete session from MongoDB
      await db.collection('user_sessions').deleteOne({
        sessionToken: sessionToken
      });
    }

    res.json({
      success: true,
      message: 'Logged out successfully'
    });

  } catch (error) {
    console.error('❌ Logout failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

// Telegram callback
router.post('/telegram-callback', async (req, res) => {
  try {
    const { phone, name, lastName, sessionToken } = req.body;

    if (!phone || !sessionToken) {
      return res.status(400).json({
        success: false,
        error: 'Missing required fields'
      });
    }

    // Check if user exists in Poster API
    const posterService = require('../services/posterService');
    let clients = await posterService.getClients(phone);
    
    let client_id;
    if (clients && clients.length > 0) {
      // User exists
      client_id = clients[0].client_id;
    } else {
      // Create new user
      const clientData = {
        client_name: `${name || ''} ${lastName || ''}`.trim() || 'Пользователь',
        client_groups_id_client: 1, // Default group
        phone: phone
      };
      
      const createResult = await posterService.createClient(clientData);
      client_id = createResult.client_id;
    }

    // Save user to MongoDB
    await db.collection('users').updateOne(
      { client_id: client_id },
      {
        $set: {
          client_id: client_id,
          phone: phone,
          name: name,
          lastName: lastName,
          updatedAt: new Date()
        }
      },
      { upsert: true }
    );

    // Save session to MongoDB
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30); // 30 days

    await db.collection('user_sessions').updateOne(
      { sessionToken: sessionToken },
      {
        $set: {
          sessionToken: sessionToken,
          client_id: client_id,
          createdAt: new Date(),
          expiresAt: expiresAt
        }
      },
      { upsert: true }
    );

    // Return redirect URL for Telegram bot
    const redirectUrl = `https://northrepublic.me/menu2.php?auth=success&session=${sessionToken}`;
    
    res.json({
      success: true,
      message: 'Authentication successful',
      redirectUrl: redirectUrl,
      client_id: client_id
    });

  } catch (error) {
    console.error('❌ Telegram callback failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

module.exports = router;
