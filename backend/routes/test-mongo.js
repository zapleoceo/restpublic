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

// Test MongoDB connection
router.get('/test-connection', requireAuth, async (req, res) => {
  try {
    console.log('🔍 Testing MongoDB connection...');
    
    const mongodbUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
    const dbName = process.env.MONGODB_DB_NAME || 'northrepublic';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    
    // Test connection
    await db.admin().ping();
    
    // Get collections list
    const collections = await db.listCollections().toArray();
    
    await client.close();
    
    res.json({
      success: true,
      message: 'MongoDB connection successful',
      database: dbName,
      collections: collections.map(c => c.name)
    });
  } catch (error) {
    console.error('MongoDB connection test failed:', error);
    res.status(500).json({
      error: 'MongoDB connection failed',
      message: error.message
    });
  }
});

// Get all tables
router.get('/tables', requireAuth, async (req, res) => {
  try {
    console.log('📋 Getting all tables from MongoDB...');
    
    const mongodbUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
    const dbName = process.env.MONGODB_DB_NAME || 'northrepublic';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    const tablesCollection = db.collection('tables');
    
    const tables = await tablesCollection.find({}).toArray();
    
    await client.close();
    
    res.json({
      success: true,
      tables: tables,
      count: tables.length
    });
  } catch (error) {
    console.error('Error getting tables:', error);
    res.status(500).json({
      error: 'Failed to get tables',
      message: error.message
    });
  }
});

// Add tables
router.post('/tables/init', requireAuth, async (req, res) => {
  try {
    console.log('➕ Initializing tables in MongoDB...');
    
    const mongodbUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
    const dbName = process.env.MONGODB_DB_NAME || 'northrepublic';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    const tablesCollection = db.collection('tables');
    
    // Check if tables already exist
    const existingCount = await tablesCollection.countDocuments();
    
    if (existingCount > 0) {
      await client.close();
      return res.json({
        success: true,
        message: `Tables already exist (${existingCount} tables)`,
        count: existingCount
      });
    }
    
    // Add tables
    const tables = [
      { table_id: '1', name: 'Стол у окна', capacity: 2, status: 'available' },
      { table_id: '2', name: 'Стол в центре', capacity: 4, status: 'available' },
      { table_id: '3', name: 'Стол у входа', capacity: 2, status: 'available' },
      { table_id: '4', name: 'Стол VIP', capacity: 6, status: 'available' },
      { table_id: '5', name: 'Стол на террасе', capacity: 4, status: 'available' },
      { table_id: '6', name: 'Стол для двоих', capacity: 2, status: 'available' },
      { table_id: '7', name: 'Стол семейный', capacity: 8, status: 'available' },
      { table_id: '8', name: 'Стол у бара', capacity: 2, status: 'available' },
      { table_id: '9', name: 'Стол романтический', capacity: 2, status: 'available' },
      { table_id: '10', name: 'Стол бизнес', capacity: 4, status: 'available' }
    ];
    
    const result = await tablesCollection.insertMany(tables);
    
    await client.close();
    
    res.json({
      success: true,
      message: `Added ${result.insertedCount} tables`,
      insertedCount: result.insertedCount,
      tables: tables
    });
  } catch (error) {
    console.error('Error initializing tables:', error);
    res.status(500).json({
      error: 'Failed to initialize tables',
      message: error.message
    });
  }
});

// Clear all tables
router.delete('/tables', requireAuth, async (req, res) => {
  try {
    console.log('🗑️ Clearing all tables from MongoDB...');
    
    const mongodbUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
    const dbName = process.env.MONGODB_DB_NAME || 'northrepublic';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    const tablesCollection = db.collection('tables');
    
    const result = await tablesCollection.deleteMany({});
    
    await client.close();
    
    res.json({
      success: true,
      message: `Deleted ${result.deletedCount} tables`,
      deletedCount: result.deletedCount
    });
  } catch (error) {
    console.error('Error clearing tables:', error);
    res.status(500).json({
      error: 'Failed to clear tables',
      message: error.message
    });
  }
});

// Add single table
router.post('/tables', requireAuth, async (req, res) => {
  try {
    const { table_id, name, capacity, status } = req.body;
    
    if (!table_id || !name) {
      return res.status(400).json({
        error: 'Missing required fields',
        message: 'table_id and name are required'
      });
    }
    
    console.log(`➕ Adding table: ${name} (ID: ${table_id})`);
    
    const mongodbUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
    const dbName = process.env.MONGODB_DB_NAME || 'northrepublic';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    const tablesCollection = db.collection('tables');
    
    const table = {
      table_id,
      name,
      capacity: capacity || 2,
      status: status || 'available'
    };
    
    const result = await tablesCollection.insertOne(table);
    
    await client.close();
    
    res.json({
      success: true,
      message: 'Table added successfully',
      table: { ...table, _id: result.insertedId }
    });
  } catch (error) {
    console.error('Error adding table:', error);
    res.status(500).json({
      error: 'Failed to add table',
      message: error.message
    });
  }
});

module.exports = router;
