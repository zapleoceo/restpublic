const express = require('express');
const router = express.Router();
const { MongoClient } = require('mongodb');

// MongoDB connection
const MONGODB_URL = process.env.MONGODB_URL || 'mongodb://localhost:27017';
const DB_NAME = process.env.MONGODB_DB_NAME || 'veranda';

let db;
MongoClient.connect(MONGODB_URL)
  .then(client => {
    db = client.db(DB_NAME);
    console.log('✅ MongoDB connected for auth routes');
  })
  .catch(err => {
    console.error('❌ MongoDB connection failed for auth routes:', err);
  });

// Check authentication status (for both /status and /api/auth/status)
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

// Alias for API endpoint
router.get('/', async (req, res) => {
  // Redirect to /status for backward compatibility
  req.url = '/status';
  router.handle(req, res);
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
    
    console.log(`📥 Получены данные от Telegram:`, {
      phone: phone,
      name: name,
      lastName: lastName,
      sessionToken: sessionToken,
      fullBody: req.body
    });

    if (!phone || !sessionToken) {
      console.log(`❌ Отсутствуют обязательные поля:`, { phone: !!phone, sessionToken: !!sessionToken });
      return res.status(400).json({
        success: false,
        error: 'Missing required fields'
      });
    }

    // Check if user exists in Poster API
    const posterService = require('../services/posterService');
    console.log(`🔍 Ищем клиента в Poster API по телефону: ${phone}`);
    let clients = await posterService.getClients(phone);
    console.log(`📋 Найдено клиентов: ${clients ? clients.length : 0}`, clients);
    
    let client_id;
    if (clients && clients.length > 0) {
      // User exists
      client_id = clients[0].client_id;
      console.log(`✅ Клиент найден в Poster API: client_id = ${client_id}`);
    } else {
      // Create new user
      const clientData = {
        firstname: lastName || 'Пользователь',
        lastname: name || '',
        client_groups_id_client: 1, // Default group
        phone: phone
      };
      
      console.log(`🆕 Создаем нового клиента в Poster API:`, clientData);
      const createResult = await posterService.createClient(clientData);
      client_id = createResult.response;
      console.log(`✅ Клиент создан в Poster API: client_id = ${client_id}`);
    }

    // Save user to MongoDB (только client_id)
    const userData = {
      client_id: client_id
    };
    
    console.log(`💾 Сохраняем пользователя в MongoDB:`, userData);
    console.log(`🔍 Проверяем существование client_id ${client_id} в MongoDB...`);
    
    // Проверяем, есть ли уже запись
    const existingUser = await db.collection('users').findOne({ client_id: client_id });
    console.log(`📋 Существующая запись:`, existingUser);
    
    const result = await db.collection('users').updateOne(
      { client_id: client_id },
      { $set: userData },
      { upsert: true }
    );
    
    console.log(`✅ Результат upsert:`, {
      matchedCount: result.matchedCount,
      modifiedCount: result.modifiedCount,
      upsertedCount: result.upsertedCount,
      upsertedId: result.upsertedId
    });

    // Save session to MongoDB
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30); // 30 days
    
    const sessionData = {
      sessionToken: sessionToken,
      client_id: client_id,
      createdAt: new Date(),
      expiresAt: expiresAt
    };
    
    console.log(`🔐 Сохраняем сессию в MongoDB:`, sessionData);
    await db.collection('user_sessions').updateOne(
      { sessionToken: sessionToken },
      { $set: sessionData },
      { upsert: true }
    );

    // Return redirect URL for Telegram bot
    const redirectUrl = `https://veranda.my/menu2.php?auth=success&session=${sessionToken}`;
    
    const responseData = {
      success: true,
      message: 'Authentication successful',
      redirectUrl: redirectUrl,
      client_id: client_id
    };
    
    console.log(`✅ Отправляем успешный ответ:`, responseData);
    
    res.json(responseData);

  } catch (error) {
    console.error('❌ Telegram callback failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

// Telegram widget авторизация
router.post('/telegram-widget', async (req, res) => {
  try {
    const { id, first_name, last_name, username, photo_url, auth_date, hash } = req.body;

    console.log(`📥 Получены данные от Telegram widget:`, {
      id: id,
      first_name: first_name,
      last_name: last_name,
      username: username,
      auth_date: auth_date,
      hash: hash ? 'present' : 'missing',
      fullBody: req.body
    });

    if (!id || !first_name || !hash) {
      console.log(`❌ Отсутствуют обязательные поля для widget:`, {
        id: !!id,
        first_name: !!first_name,
        hash: !!hash
      });
      return res.status(400).json({
        success: false,
        error: 'Missing required fields'
      });
    }

    // Для телеграм виджета мы не проверяем хэш, так как данные приходят напрямую от телеграм
    // Вместо этого создаем пользователя на основе данных из виджета

    // Создаем клиента в Poster API
    const posterService = require('../services/posterService');

    // Ищем клиента по имени и фамилии (простая эвристика)
    let clients = [];
    try {
      // Пробуем найти по имени
      if (first_name) {
        clients = await posterService.getClients(first_name);
      }
    } catch (error) {
      console.log(`⚠️ Ошибка поиска клиента:`, error.message);
    }

    let client_id;
    if (clients && clients.length > 0) {
      // Берем первого найденного клиента
      client_id = clients[0].client_id;
      console.log(`✅ Клиент найден в Poster API по имени: client_id = ${client_id}`);
    } else {
      // Создаем нового клиента
      const clientData = {
        firstname: first_name || 'Пользователь',
        lastname: last_name || '',
        client_groups_id_client: 1, // Default group
        phone: '' // Телеграм виджет не дает телефон
      };

      console.log(`🆕 Создаем нового клиента в Poster API:`, clientData);
      try {
        const createResult = await posterService.createClient(clientData);
        client_id = createResult.response;
        console.log(`✅ Клиент создан в Poster API: client_id = ${client_id}`);
      } catch (error) {
        console.log(`⚠️ Ошибка создания клиента:`, error.message);
        // Используем временный client_id для демо
        client_id = `telegram_${id}`;
      }
    }

    // Сохраняем пользователя в MongoDB
    const userData = {
      client_id: client_id,
      telegram_id: id,
      first_name: first_name,
      last_name: last_name,
      username: username,
      photo_url: photo_url
    };

    console.log(`💾 Сохраняем пользователя в MongoDB:`, userData);

    // Проверяем, есть ли уже запись
    const existingUser = await db.collection('users').findOne({ telegram_id: id });
    console.log(`📋 Существующая запись:`, existingUser);

    const result = await db.collection('users').updateOne(
      { telegram_id: id },
      { $set: userData },
      { upsert: true }
    );

    console.log(`✅ Результат upsert:`, {
      matchedCount: result.matchedCount,
      modifiedCount: result.modifiedCount,
      upsertedCount: result.upsertedCount,
      upsertedId: result.upsertedId
    });

    // Создаем сессию
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 30); // 30 дней

    const sessionToken = 'tg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

    const sessionData = {
      sessionToken: sessionToken,
      client_id: client_id,
      telegram_id: id,
      createdAt: new Date(),
      expiresAt: expiresAt
    };

    console.log(`🔐 Сохраняем сессию в MongoDB:`, sessionData);
    await db.collection('user_sessions').updateOne(
      { sessionToken: sessionToken },
      { $set: sessionData },
      { upsert: true }
    );

    // Возвращаем успешный ответ
    const responseData = {
      success: true,
      message: 'Authentication successful',
      sessionToken: sessionToken,
      client_id: client_id,
      user: userData
    };

    console.log(`✅ Отправляем успешный ответ:`, responseData);

    res.json(responseData);

  } catch (error) {
    console.error('❌ Telegram widget auth failed:', error);
    res.status(500).json({
      success: false,
      error: 'Internal server error'
    });
  }
});

module.exports = router;
