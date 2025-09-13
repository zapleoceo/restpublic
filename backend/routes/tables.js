const express = require('express');
const router = express.Router();
const { MongoClient } = require('mongodb');

// Middleware для проверки авторизации
const requireAuth = (req, res, next) => {
  const authToken = req.headers['x-api-token'] || req.query.token;
  const expectedToken = process.env.API_AUTH_TOKEN;
  
  if (!authToken || !expectedToken || authToken !== expectedToken) {
    return res.status(401).json({
      error: 'Unauthorized',
      message: 'Valid API token required'
    });
  }
  
  next();
};

// Get tables from MongoDB
router.get('/list', requireAuth, async (req, res) => {
  try {
    console.log('📡 Getting tables from MongoDB...');
    
    const mongodbUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
    const dbName = process.env.MONGODB_DB_NAME || 'northrepublic';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    const tablesCollection = db.collection('tables');
    
    // Get all tables sorted by name
    const tables = await tablesCollection.find({}, {
      sort: { name: 1 }
    }).toArray();
    
    // Format tables for frontend
    const formattedTables = tables.map(table => ({
      id: table._id.toString(),
      table_id: table.table_id || table._id.toString(),
      name: table.name || table.table_name || `Стол ${table.table_id || table._id}`,
      capacity: table.capacity || null,
      status: table.status || 'available'
    }));
    
    await client.close();
    
    console.log(`📋 Found ${formattedTables.length} tables in MongoDB`);
    
    res.json({
      success: true,
      tables: formattedTables,
      count: formattedTables.length
    });
  } catch (error) {
    console.error('MongoDB Tables API Error:', error);
    res.status(500).json({
      error: 'Failed to fetch tables from MongoDB',
      message: error.message
    });
  }
});

module.exports = router;
