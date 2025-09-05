const express = require('express');
const router = express.Router();
const axios = require('axios');
const { MongoClient } = require('mongodb');

// Подключение к MongoDB
const mongoUrl = 'mongodb://localhost:27018';
const dbName = 'northrepublic';
const collectionName = 'menu';

// Endpoint для обновления кэша меню
router.post('/update-menu', async (req, res) => {
    let client;
    try {
        console.log('🔄 Обновление кэша меню...');
        
        // Получаем данные от нашего API (который уже работает)
        const apiResponse = await axios.get('http://127.0.0.1:3002/api/menu', {
            timeout: 30000
        });
        
        if (apiResponse.status !== 200) {
            throw new Error(`API вернул код: ${apiResponse.status}`);
        }
        
        const menuData = apiResponse.data;
        
        // Сохраняем в MongoDB
        client = new MongoClient(mongoUrl);
        await client.connect();
        const db = client.db(dbName);
        const collection = db.collection(collectionName);
        
        const result = await collection.replaceOne(
            { _id: 'current_menu' },
            {
                _id: 'current_menu',
                data: menuData,
                updated_at: new Date(),
                categories: menuData.categories || [],
                products: menuData.products || []
            },
            { upsert: true }
        );
        
        console.log(`✅ Кэш обновлен. Модифицировано записей: ${result.modifiedCount}`);
        
        res.json({
            success: true,
            message: 'Кэш обновлен успешно',
            modifiedCount: result.modifiedCount,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error('❌ Ошибка обновления кэша:', error.message);
        res.status(500).json({
            success: false,
            message: 'Ошибка обновления кэша',
            error: error.message
        });
    } finally {
        if (client) {
            await client.close();
        }
    }
});

module.exports = router;
