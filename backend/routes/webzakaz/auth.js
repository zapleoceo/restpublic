const express = require('express');
const router = express.Router();

// POST /api/webzakaz/auth/login
router.post('/login', async (req, res) => {
  try {
    const { pin } = req.body;
    const CORRECT_PIN = process.env.CASHIER_PIN || '9078';

    if (pin === CORRECT_PIN) {
      // Create session
      req.session.cashierId = 1;
      req.session.cashierName = 'Главный кассир';

      return res.json({
        success: true,
        cashier_id: 1,
        cashier_name: 'Главный кассир',
        message: 'Вход выполнен'
      });
    }

    res.status(401).json({
      success: false,
      message: 'Неверный пинкод'
    });
  } catch (error) {
    console.error('WebZakaz login error:', error);
    res.status(500).json({
      success: false,
      message: 'Ошибка входа'
    });
  }
});

// POST /api/webzakaz/auth/logout
router.post('/logout', (req, res) => {
  req.session.destroy((err) => {
    if (err) {
      return res.status(500).json({
        success: false,
        message: 'Ошибка выхода'
      });
    }
    res.json({ success: true });
  });
});

// GET /api/webzakaz/auth/check
router.get('/check', (req, res) => {
  if (req.session && req.session.cashierId) {
    return res.json({
      success: true,
      cashier_id: req.session.cashierId,
      cashier_name: req.session.cashierName
    });
  }
  res.status(401).json({
    success: false,
    message: 'Не авторизован'
  });
});

module.exports = router;

