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
    const dbName = process.env.MONGODB_DB_NAME || 'veranda';
    
    const client = new MongoClient(mongodbUrl);
    await client.connect();
    
    const db = client.db(dbName);
    const menuCollection = db.collection('menu');
    
    // Get tables from the current_tables document
    const tablesDoc = await menuCollection.findOne({ _id: 'current_tables' });
    
    let formattedTables = [];
    let hallsMap = {};
    
    if (tablesDoc && tablesDoc.tables) {
      // Format tables for frontend
      formattedTables = tablesDoc.tables.map(table => {
        // Try to determine hall from various possible fields
        const hallId = table.hall_id || table.zone_id || table.spot_id || null;
        const hallName = table.hall_name || table.zone_name || table.spot_name || table.hall || null;
        
        const formatted = {
          id: table.poster_table_id || table.table_id || table._id?.toString() || Math.random().toString(),
          table_id: table.poster_table_id || table.table_id || table._id?.toString() || Math.random().toString(),
          name: table.name || table.table_title || `Стол ${table.poster_table_id || table.table_num || table.table_id}`,
          capacity: parseInt(table.capacity || table.table_seats || 2),
          status: table.status || (table.is_deleted === 0 ? 'available' : 'unavailable')
        };
        
        if (hallId !== null) {
          formatted.hall_id = String(hallId);
        }
        if (hallName) {
          formatted.hall_name = String(hallName);
        }
        
        // Collect halls
        if (hallId !== null) {
          hallsMap[String(hallId)] = {
            hall_id: String(hallId),
            hall_name: hallName ? String(hallName) : `Зал ${String(hallId)}`
          };
        }
        
        return formatted;
      });
      
      // Sort tables: numeric first, then alphabetical
      formattedTables.sort((a, b) => {
        const nameA = a.name;
        const nameB = b.name;
        
        const isNumericA = !isNaN(nameA) && !isNaN(parseFloat(nameA));
        const isNumericB = !isNaN(nameB) && !isNaN(parseFloat(nameB));
        
        if (isNumericA && isNumericB) {
          return parseInt(nameA) - parseInt(nameB);
        }
        
        if (isNumericA && !isNumericB) return -1;
        if (!isNumericA && isNumericB) return 1;
        
        return nameA.localeCompare(nameB);
      });
    }
    
    // Get halls from MongoDB (comes from Poster API via getSpotTablesHalls)
    let halls = [];
    if (tablesDoc && tablesDoc.halls && Array.isArray(tablesDoc.halls)) {
      halls = tablesDoc.halls.map(hall => ({
        hall_id: String(hall.hall_id),
        hall_name: String(hall.hall_name)
      }));
    } else if (Object.keys(hallsMap).length > 0) {
      // If no halls in MongoDB, use extracted from tables
      halls = Object.values(hallsMap);
      halls.sort((a, b) => a.hall_name.localeCompare(b.hall_name));
    }
    
    await client.close();
    
    console.log(`📋 Found ${formattedTables.length} tables and ${halls.length} halls in MongoDB`);
    
    res.json({
      success: true,
      tables: formattedTables,
      count: formattedTables.length,
      halls: halls,
      updated_at: tablesDoc?.updated_at || null
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
