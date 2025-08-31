const express = require('express');
const router = express.Router();
const mongoService = require('../services/mongoService');

// Middleware для проверки авторизации админа
const requireAuth = (req, res, next) => {
  // TODO: Добавить проверку JWT токена админа
  // Пока пропускаем все запросы
  next();
};

// Получить все переводы
router.get('/translations', requireAuth, async (req, res) => {
  try {
    const translations = await mongoService.getAllTranslations();
    res.json({
      success: true,
      data: translations
    });
  } catch (error) {
    console.error('Ошибка получения переводов:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка получения переводов'
    });
  }
});

// Обновить переводы для языка
router.put('/translations/:language', requireAuth, async (req, res) => {
  try {
    const { language } = req.params;
    const { data } = req.body;
    
    if (!data) {
      return res.status(400).json({
        success: false,
        error: 'Данные переводов обязательны'
      });
    }
    
    await mongoService.setTranslations(language, data);
    
    res.json({
      success: true,
      message: `Переводы для языка ${language} обновлены`
    });
  } catch (error) {
    console.error('Ошибка обновления переводов:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка обновления переводов'
    });
  }
});

// Получить все конфигурации
router.get('/configs', requireAuth, async (req, res) => {
  try {
    const configs = await mongoService.getAllConfigs();
    res.json({
      success: true,
      data: configs
    });
  } catch (error) {
    console.error('Ошибка получения конфигураций:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка получения конфигураций'
    });
  }
});

// Обновить конфигурацию
router.put('/configs/:type', requireAuth, async (req, res) => {
  try {
    const { type } = req.params;
    const { data } = req.body;
    
    if (!data) {
      return res.status(400).json({
        success: false,
        error: 'Данные конфигурации обязательны'
      });
    }
    
    await mongoService.setConfig(type, data);
    
    res.json({
      success: true,
      message: `Конфигурация ${type} обновлена`
    });
  } catch (error) {
    console.error('Ошибка обновления конфигурации:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка обновления конфигурации'
    });
  }
});

// Получить статистику MongoDB
router.get('/stats', requireAuth, async (req, res) => {
  try {
    const db = mongoService.getDatabase();
    
    const translationsCount = await db.collection('translations').countDocuments();
    const configsCount = await db.collection('configs').countDocuments();
    
    res.json({
      success: true,
      data: {
        translations: translationsCount,
        configs: configsCount,
        total: translationsCount + configsCount
      }
    });
  } catch (error) {
    console.error('Ошибка получения статистики:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка получения статистики'
    });
  }
});

module.exports = router;
