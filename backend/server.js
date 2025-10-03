const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');
const morgan = require('morgan');
const rateLimit = require('express-rate-limit');
const slowDown = require('express-slow-down');
require('dotenv').config({ path: '../.env' });

const posterRoutes = require('./routes/poster');
const menuRoutes = require('./routes/menu');
const cacheRoutes = require('./routes/cache');
const tablesRoutes = require('./routes/tables');
const authRoutes = require('./routes/auth');
const userRoutes = require('./routes/user');

const app = express();
const PORT = process.env.PORT || 3003;

// Rate limiting configuration
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // limit each IP to 100 requests per windowMs
  message: {
    error: 'Too many requests from this IP, please try again later.',
    retryAfter: '15 minutes'
  },
  standardHeaders: true,
  legacyHeaders: false,
});

// Slow down configuration
const speedLimiter = slowDown({
  windowMs: 15 * 60 * 1000, // 15 minutes
  delayAfter: 50, // allow 50 requests per 15 minutes, then...
  delayMs: () => 500 // begin adding 500ms of delay per request above 50
});

// Trust proxy for rate limiting
app.set('trust proxy', 1);

// Middleware
app.use(helmet());
app.use(compression());
app.use(morgan('combined'));
app.use(limiter);
app.use(speedLimiter);
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// CORS configuration
const corsOptions = {
  origin: process.env.CORS_ORIGIN || 'https://veranda.my',
  credentials: true,
  optionsSuccessStatus: 200
};
app.use(cors(corsOptions));

// Health check endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV
  });
});

// Stricter rate limiting for API endpoints - 5 requests per second
const apiLimiter = rateLimit({
  windowMs: 1000, // 1 second
  max: 5, // limit each IP to 5 requests per second for API
  message: {
    error: 'API rate limit exceeded, please try again later.',
    retryAfter: '1 second'
  },
  standardHeaders: true,
  legacyHeaders: false,
});

// More lenient rate limiting for auth endpoints - 2 requests per second
const authLimiter = rateLimit({
  windowMs: 1000, // 1 second
  max: 2, // limit each IP to 2 requests per second
  message: {
    error: 'Auth API rate limit exceeded, please try again later.',
    retryAfter: '1 second'
  },
  standardHeaders: true,
  legacyHeaders: false,
});

// API Routes with stricter rate limiting
app.use('/api/poster', apiLimiter, posterRoutes);
app.use('/api/menu', apiLimiter, menuRoutes);
app.use('/api/cache', apiLimiter, cacheRoutes);
app.use('/api/tables', apiLimiter, tablesRoutes);
app.use('/api/auth', authLimiter, authRoutes); // Более мягкий лимит для auth
app.use('/api/user', apiLimiter, userRoutes);

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Error:', err);
  res.status(500).json({
    error: 'Internal Server Error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Not Found' });
});

// Start server
app.listen(PORT, () => {
  console.log(`🚀 North Republic Backend running on port ${PORT}`);
  console.log(`📊 Environment: ${process.env.NODE_ENV}`);
  console.log(`🔗 Health check: http://localhost:${PORT}/api/health`);
});

module.exports = app;
