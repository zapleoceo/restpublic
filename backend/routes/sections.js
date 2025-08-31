const express = require('express');
const router = express.Router();
const mongoService = require('../services/mongoService');

// Получить все секции
router.get('/', async (req, res) => {
  try {
    const db = mongoService.getDatabase();
    const sections = db.collection('sections');
    
    const sectionsData = await sections.find({}).toArray();
    
    // Преобразуем массив в объект для удобства фронтенда
    const sectionsObject = {};
    sectionsData.forEach(section => {
      sectionsObject[section.sectionId] = section.data;
    });
    
    res.json(sectionsObject);
  } catch (error) {
    console.error('Ошибка получения секций:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  }
});

// Получить конкретную секцию
router.get('/:sectionId', async (req, res) => {
  try {
    const { sectionId } = req.params;
    
    const db = mongoService.getDatabase();
    const sections = db.collection('sections');
    
    const section = await sections.findOne({ sectionId });
    
    if (!section) {
      return res.status(404).json({ error: 'Секция не найдена' });
    }
    
    res.json(section.data);
  } catch (error) {
    console.error('Ошибка получения секции:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  }
});

// Обновить секцию
router.put('/:sectionId', async (req, res) => {
  try {
    const { sectionId } = req.params;
    const sectionData = req.body;
    
    const db = mongoService.getDatabase();
    const sections = db.collection('sections');
    
    // Добавляем метаданные
    const sectionDocument = {
      sectionId,
      data: sectionData,
      updatedAt: new Date(),
      createdAt: new Date()
    };
    
    // Используем upsert для создания или обновления
    const result = await sections.updateOne(
      { sectionId },
      { $set: sectionDocument },
      { upsert: true }
    );
    
    res.json({ 
      success: true, 
      message: 'Секция успешно сохранена',
      sectionId 
    });
  } catch (error) {
    console.error('Ошибка сохранения секции:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  }
});

// Удалить секцию
router.delete('/:sectionId', async (req, res) => {
  try {
    const { sectionId } = req.params;
    
    const db = mongoService.getDatabase();
    const sections = db.collection('sections');
    
    const result = await sections.deleteOne({ sectionId });
    
    if (result.deletedCount === 0) {
      return res.status(404).json({ error: 'Секция не найдена' });
    }
    
    res.json({ 
      success: true, 
      message: 'Секция успешно удалена' 
    });
  } catch (error) {
    console.error('Ошибка удаления секции:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  }
});

module.exports = router;
