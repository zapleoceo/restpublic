const express = require('express');
const router = express.Router();
const { MongoClient } = require('mongodb');

// MongoDB connection
const MONGODB_URL = process.env.MONGODB_URL || 'mongodb://localhost:27018';
const DB_NAME = process.env.MONGODB_DB_NAME || 'veranda';

let client;
let db;

// Initialize MongoDB connection
async function initMongoDB() {
  try {
    if (!client) {
      client = new MongoClient(MONGODB_URL);
      await client.connect();
      db = client.db(DB_NAME);
      console.log('✅ MongoDB connected for page content');
    }
  } catch (error) {
    console.error('❌ MongoDB connection failed:', error.message);
  }
}

// Get page content by page and language
router.get('/:page/:language', async (req, res) => {
  try {
    await initMongoDB();
    
    const { page, language } = req.params;
    const collection = db.collection('page_content');
    
    // Try to find published content first
    let document = await collection.findOne({
      page: page,
      language: language,
      status: 'published'
    });
    
    // If no published content, try draft
    if (!document) {
      document = await collection.findOne({
        page: page,
        language: language,
        status: 'draft'
      });
    }
    
    if (document) {
      res.json({
        content: document.content || '',
        meta: document.meta || {},
        updated_at: document.updated_at,
        status: document.status
      });
    } else {
      res.status(404).json({
        error: 'Page content not found',
        page: page,
        language: language
      });
    }
  } catch (error) {
    console.error('Page content API error:', error);
    res.status(500).json({
      error: 'Failed to fetch page content',
      message: error.message
    });
  }
});

// Get all pages
router.get('/', async (req, res) => {
  try {
    await initMongoDB();
    
    const collection = db.collection('page_content');
    const pages = await collection.distinct('page');
    
    res.json({
      pages: pages,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Pages list API error:', error);
    res.status(500).json({
      error: 'Failed to fetch pages list',
      message: error.message
    });
  }
});

// Get page stats
router.get('/stats', async (req, res) => {
  try {
    await initMongoDB();
    
    const collection = db.collection('page_content');
    const pipeline = [
      {
        $group: {
          _id: { page: '$page', language: '$language' },
          status: { $first: '$status' },
          updated_at: { $first: '$updated_at' },
          updated_by: { $first: '$updated_by' }
        }
      },
      {
        $group: {
          _id: '$_id.page',
          languages: {
            $push: {
              language: '$_id.language',
              status: '$status',
              updated_at: '$updated_at',
              updated_by: '$updated_by'
            }
          }
        }
      }
    ];
    
    const result = await collection.aggregate(pipeline).toArray();
    
    res.json({
      stats: result,
      timestamp: new Date().toISOString()
    });
  } catch (error) {
    console.error('Page stats API error:', error);
    res.status(500).json({
      error: 'Failed to fetch page stats',
      message: error.message
    });
  }
});

module.exports = router;

