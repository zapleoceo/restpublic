const express = require('express');
const router = express.Router();
const { MongoClient, ObjectId } = require('mongodb');

// Подключение к MongoDB
const uri = process.env.MONGODB_URI || 'mongodb://localhost:27017';
const client = new MongoClient(uri);

// Получить все события
router.get('/', async (req, res) => {
  try {
    const { category, status, date } = req.query;
    
    await client.connect();
    const database = client.db('northrepublic');
    const events = database.collection('events');
    
    // Строим фильтр на основе параметров
    const filter = {};
    
    if (category && category !== 'all') {
      filter.category = category;
    }
    
    if (status && status !== 'all') {
      filter.status = status;
    }
    
    if (date) {
      const startDate = new Date(date);
      const endDate = new Date(date);
      endDate.setDate(endDate.getDate() + 1);
      
      filter.date = {
        $gte: startDate,
        $lt: endDate
      };
    }
    
    const eventsData = await events.find(filter).sort({ date: 1 }).toArray();
    res.json(eventsData);
  } catch (error) {
    console.error('Ошибка получения событий:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  } finally {
    await client.close();
  }
});

// Получить событие по ID
router.get('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    await client.connect();
    const database = client.db('northrepublic');
    const events = database.collection('events');
    
    const event = await events.findOne({ _id: new ObjectId(id) });
    
    if (!event) {
      return res.status(404).json({ error: 'Событие не найдено' });
    }
    
    res.json(event);
  } catch (error) {
    console.error('Ошибка получения события:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  } finally {
    await client.close();
  }
});

// Создать новое событие
router.post('/', async (req, res) => {
  try {
    const eventData = req.body;
    
    // Валидация обязательных полей
    if (!eventData.title || !eventData.date || !eventData.location) {
      return res.status(400).json({ 
        error: 'Отсутствуют обязательные поля: title, date, location' 
      });
    }
    
    await client.connect();
    const database = client.db('northrepublic');
    const events = database.collection('events');
    
    // Добавляем метаданные
    const eventDocument = {
      ...eventData,
      createdAt: new Date(),
      updatedAt: new Date()
    };
    
    const result = await events.insertOne(eventDocument);
    
    res.status(201).json({
      success: true,
      message: 'Событие успешно создано',
      eventId: result.insertedId
    });
  } catch (error) {
    console.error('Ошибка создания события:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  } finally {
    await client.close();
  }
});

// Обновить событие
router.put('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const eventData = req.body;
    
    await client.connect();
    const database = client.db('northrepublic');
    const events = database.collection('events');
    
    // Добавляем метаданные обновления
    const updateData = {
      ...eventData,
      updatedAt: new Date()
    };
    
    const result = await events.updateOne(
      { _id: new ObjectId(id) },
      { $set: updateData }
    );
    
    if (result.matchedCount === 0) {
      return res.status(404).json({ error: 'Событие не найдено' });
    }
    
    res.json({
      success: true,
      message: 'Событие успешно обновлено'
    });
  } catch (error) {
    console.error('Ошибка обновления события:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  } finally {
    await client.close();
  }
});

// Удалить событие
router.delete('/:id', async (req, res) => {
  try {
    const { id } = req.params;
    
    await client.connect();
    const database = client.db('northrepublic');
    const events = database.collection('events');
    
    const result = await events.deleteOne({ _id: new ObjectId(id) });
    
    if (result.deletedCount === 0) {
      return res.status(404).json({ error: 'Событие не найдено' });
    }
    
    res.json({
      success: true,
      message: 'Событие успешно удалено'
    });
  } catch (error) {
    console.error('Ошибка удаления события:', error);
    res.status(500).json({ error: 'Ошибка сервера' });
  } finally {
    await client.close();
  }
});

module.exports = router;
