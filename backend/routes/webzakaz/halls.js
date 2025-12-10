const express = require('express');
const router = express.Router();
const posterService = require('../../services/posterService');

// GET /api/webzakaz/halls
router.get('/', async (req, res) => {
  try {
    // Get tables and halls from Poster API
    const [tables, hallsList] = await Promise.all([
      posterService.getTables(),
      posterService.getHalls()
    ]);
    
    // Create halls map
    const halls = {};
    
    // First, create halls from hallsList
    if (Array.isArray(hallsList)) {
      hallsList.forEach(hall => {
        if (hall.delete === '0' || hall.delete === 0) {
          const hallId = hall.hall_id?.toString();
          if (hallId) {
            halls[hallId] = {
              hallName: hall.hall_name || `Зал ${hallId}`,
              tables: []
            };
          }
        }
      });
    }
    
    // Then, add tables to their halls
    if (Array.isArray(tables)) {
      tables.forEach(table => {
        if (table.is_deleted === 0 || table.is_deleted === '0') {
          const hallId = table.hall_id?.toString() || 'default';
          
          if (!halls[hallId]) {
            halls[hallId] = {
              hallName: `Зал ${hallId}`,
              tables: []
            };
          }
          
          halls[hallId].tables.push({
            id: table.table_id?.toString() || table.id?.toString(),
            number: table.table_num?.toString() || table.number?.toString() || '0',
            available: table.is_deleted === 0 || table.is_deleted === '0'
          });
        }
      });
    }

    res.json({
      success: true,
      data: halls,
      timestamp: Date.now()
    });
  } catch (error) {
    console.error('WebZakaz getHalls error:', error);
    res.status(500).json({
      success: false,
      message: error.message || 'Ошибка загрузки залов'
    });
  }
});

module.exports = router;

