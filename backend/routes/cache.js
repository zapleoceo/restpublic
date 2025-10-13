const express = require('express');
const router = express.Router();
const axios = require('axios');
const { MongoClient } = require('mongodb');

// Подключение к MongoDB
const mongoUrl = process.env.MONGODB_URL || 'mongodb://localhost:27017';
const dbName = process.env.MONGODB_DB_NAME || 'veranda';
const collectionName = 'menu';
const API_PORT = process.env.PORT || 3002;

// Endpoint для обновления кэша меню
router.post('/update-menu', async (req, res) => {
    let client;
    let tablesData = null;
    try {
        console.log('🔄 Обновление кэша меню...');
        
        // Получаем данные от нашего API (который уже работает)
        const authToken = process.env.API_AUTH_TOKEN;
        const apiResponse = await axios.get(`http://127.0.0.1:${API_PORT}/api/menu`, {
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
            const tablesResponse = await axios.get(`http://127.0.0.1:${API_PORT}/api/menu/tables`, {
                timeout: 15000,
                headers: {
                    'X-API-Token': authToken
                }
            });
            
            if (tablesResponse.status === 200) {
                tablesData = tablesResponse.data;
                
                // Сохраняем столы и залы в отдельный документ
                console.log('🔍 Before save - Залы:', JSON.stringify(tablesData.halls, null, 2));
                
                const docToSave = {
                    _id: 'current_tables',
                    tables: tablesData.tables || [],
                    halls: tablesData.halls || [], // Добавляем залы
                    updated_at: new Date(),
                    count: tablesData.count || 0
                };
                
                const tablesResult = await collection.replaceOne(
                    { _id: 'current_tables' },
                    docToSave,
                    { upsert: true }
                );
                
                console.log(`✅ Столы загружены. Количество: ${tablesData.count || 0}`);
                console.log(`✅ Залы загружены. Количество: ${tablesData.halls ? tablesData.halls.length : 0}`);
                console.log('🔍 After save - Залы:', JSON.stringify(tablesData.halls, null, 2));
                
                // Проверяем, что сохранилось
                const saved = await collection.findOne({ _id: 'current_tables' });
                console.log('🔍 Verified in DB - Залы:', JSON.stringify(saved.halls, null, 2));
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
        
        // Очистка устаревших данных: удаляем старые записи логов обновлений (старше 30 дней)
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        const cacheLogsCollection = db.collection('cache_update_logs');
        const deletedLogs = await cacheLogsCollection.deleteMany({
            timestamp: { $lt: thirtyDaysAgo }
        });
        
        if (deletedLogs.deletedCount > 0) {
            console.log(`🗑️ Удалено ${deletedLogs.deletedCount} устаревших записей логов обновления кэша`);
        }
        
        // Логируем успешное обновление
        await cacheLogsCollection.insertOne({
            timestamp: new Date(),
            status: 'success',
            message: 'Cache updated successfully',
            categoriesCount: menuData.categories?.length || 0,
            productsCount: menuData.products?.length || 0,
            tablesCount: (tablesData && tablesData.count) || 0,
            hallsCount: (tablesData && tablesData.halls) ? tablesData.halls.length : 0
        });
        
        console.log(`✅ Кэш обновлен. Модифицировано записей: ${result.modifiedCount}`);
        
        res.json({
            success: true,
            message: 'Кэш обновлен успешно',
            modifiedCount: result.modifiedCount,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error('❌ Ошибка обновления кэша:', error.message);
        
        // Логируем ошибку в MongoDB
        try {
            if (client) {
                const db = client.db(dbName);
                const cacheLogsCollection = db.collection('cache_update_logs');
                await cacheLogsCollection.insertOne({
                    timestamp: new Date(),
                    status: 'error',
                    message: 'Cache update failed',
                    error: error.message,
                    stack: error.stack
                });
            }
        } catch (logError) {
            console.error('❌ Не удалось записать ошибку в лог:', logError.message);
        }
        
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
