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

// Получить историю версий конфигурации
router.get('/configs/:type/versions', requireAuth, async (req, res) => {
  try {
    const { type } = req.params;
    const db = mongoService.getDatabase();
    
    const versions = await db.collection('configs').findOne(
      { type },
      { projection: { versions: 1, currentVersion: 1 } }
    );
    
    res.json({
      success: true,
      data: {
        versions: versions?.versions || [],
        currentVersion: versions?.currentVersion || 1
      }
    });
  } catch (error) {
    console.error('Ошибка получения версий:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка получения версий'
    });
  }
});

// Восстановить версию конфигурации
router.post('/configs/:type/restore/:version', requireAuth, async (req, res) => {
  try {
    const { type, version } = req.params;
    const versionNum = parseInt(version);
    
    if (isNaN(versionNum)) {
      return res.status(400).json({
        success: false,
        error: 'Неверный номер версии'
      });
    }
    
    const db = mongoService.getDatabase();
    const config = await db.collection('configs').findOne({ type });
    
    if (!config || !config.versions) {
      return res.status(404).json({
        success: false,
        error: 'Конфигурация или версии не найдены'
      });
    }
    
    const targetVersion = config.versions.find(v => v.version === versionNum);
    if (!targetVersion) {
      return res.status(404).json({
        success: false,
        error: 'Версия не найдена'
      });
    }
    
    // Восстанавливаем данные из версии
    await mongoService.setConfig(type, targetVersion.data);
    
    res.json({
      success: true,
      message: `Конфигурация ${type} восстановлена до версии ${versionNum}`
    });
  } catch (error) {
    console.error('Ошибка восстановления версии:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка восстановления версии'
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

// Экспорт всех данных
router.get('/export', requireAuth, async (req, res) => {
  try {
    const translations = await mongoService.getAllTranslations();
    const configs = await mongoService.getAllConfigs();
    
    const exportData = {
      timestamp: new Date().toISOString(),
      version: '1.0',
      translations,
      configs
    };
    
    res.setHeader('Content-Type', 'application/json');
    res.setHeader('Content-Disposition', `attachment; filename="mongodb-export-${new Date().toISOString().split('T')[0]}.json"`);
    res.json(exportData);
  } catch (error) {
    console.error('Ошибка экспорта:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка экспорта данных'
    });
  }
});

// Импорт данных
router.post('/import', requireAuth, async (req, res) => {
  try {
    const { translations, configs, overwrite = false } = req.body;
    
    if (!translations && !configs) {
      return res.status(400).json({
        success: false,
        error: 'Данные для импорта обязательны'
      });
    }
    
    let importedCount = 0;
    
    // Импортируем переводы
    if (translations) {
      for (const [language, data] of Object.entries(translations)) {
        await mongoService.setTranslations(language, data);
        importedCount++;
      }
    }
    
    // Импортируем конфигурации
    if (configs) {
      for (const [type, data] of Object.entries(configs)) {
        await mongoService.setConfig(type, data);
        importedCount++;
      }
    }
    
    res.json({
      success: true,
      message: `Импортировано ${importedCount} записей`,
      importedCount
    });
  } catch (error) {
    console.error('Ошибка импорта:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка импорта данных'
    });
  }
});

// Очистить все данные (опасная операция)
router.delete('/clear', requireAuth, async (req, res) => {
  try {
    const db = mongoService.getDatabase();
    
    await db.collection('translations').deleteMany({});
    await db.collection('configs').deleteMany({});
    
    res.json({
      success: true,
      message: 'Все данные очищены'
    });
  } catch (error) {
    console.error('Ошибка очистки:', error);
    res.status(500).json({
      success: false,
      error: 'Ошибка очистки данных'
    });
  }
});

module.exports = router;
