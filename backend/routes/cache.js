const express = require('express');
const router = express.Router();
const axios = require('axios');
const { MongoClient } = require('mongodb');

// Подключение к MongoDB
const mongoUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
const dbName = 'northrepublic';
const collectionName = 'menu';

// Endpoint для обновления кэша меню
router.post('/update-menu', async (req, res) => {
    let client;
    try {
        console.log('🔄 Обновление кэша меню...');
        
        // Получаем данные от нашего API (который уже работает)
        const authToken = process.env.API_AUTH_TOKEN;
        const apiResponse = await axios.get('http://127.0.0.1:3002/api/menu', {
            timeout: 30000,
            headers: {
                'X-API-Token': authToken
            }
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
        
        // Загружаем и сохраняем список столов
        console.log('🔄 Загрузка списка столов...');
        try {
            // Получаем столы через наш API
            const tablesResponse = await axios.get('http://127.0.0.1:3002/api/menu/tables', {
                timeout: 15000,
                headers: {
                    'X-API-Token': authToken
                }
            });
            
            if (tablesResponse.status === 200) {
                const tablesData = tablesResponse.data;
                
                // Сохраняем столы в отдельный документ
                const tablesResult = await collection.replaceOne(
                    { _id: 'current_tables' },
                    {
                        _id: 'current_tables',
                        tables: tablesData.tables || [],
                        updated_at: new Date(),
                        count: tablesData.count || 0
                    },
                    { upsert: true }
                );
                
                console.log(`✅ Столы загружены. Количество: ${tablesData.count || 0}`);
            } else {
                throw new Error(`Tables API вернул код: ${tablesResponse.status}`);
            }
        } catch (tablesError) {
            console.error('❌ Ошибка загрузки столов:', tablesError.message);
            // Не прерываем выполнение, если загрузка столов не удалась
        }

        // Обновляем время последнего обновления в настройках
        const settingsCollection = db.collection('settings');
        await settingsCollection.replaceOne(
            { key: 'menu_last_update_time' },
            {
                key: 'menu_last_update_time',
                value: Math.floor(Date.now() / 1000), // Unix timestamp
                updated_at: new Date()
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
