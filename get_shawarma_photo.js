const axios = require('axios');

// Конфигурация Poster API
const POSTER_API_TOKEN = '922371:489411264005b482039f38b8ee21f6fb';
const POSTER_API_BASE_URL = 'https://joinposter.com/api';

async function getShawarmaPhoto() {
    try {
        console.log('🔍 Поиск товара "Шаурма" в Poster API...');
        
        // Получаем все продукты
        const productsUrl = `${POSTER_API_BASE_URL}/menu.getProducts?token=${POSTER_API_TOKEN}`;
        console.log(`📡 Запрос к API: ${productsUrl}`);
        
        const response = await axios.get(productsUrl);
        const products = response.data.response;
        
        console.log(`📋 Получено ${products.length} товаров`);
        
        // Ищем товар "Шаурма"
        const shawarma = products.find(product => {
            const productName = (product.product_name || '').toLowerCase();
            return productName.includes('шаурма') || productName.includes('shawarma');
        });
        
        if (!shawarma) {
            console.log('❌ Товар "Шаурма" не найден');
            console.log('📋 Доступные товары:');
            products.slice(0, 10).forEach(product => {
                console.log(`  - ${product.product_name} (ID: ${product.product_id})`);
            });
            return;
        }
        
        console.log('✅ Найден товар "Шаурма":');
        console.log(`  ID: ${shawarma.product_id}`);
        console.log(`  Название: ${shawarma.product_name}`);
        console.log(`  Цена: ${shawarma.price}`);
        console.log(`  Категория: ${shawarma.menu_category_id}`);
        console.log(`  Скрыт: ${shawarma.hidden}`);
        
        // Проверяем наличие фото
        if (shawarma.photo) {
            console.log('📸 Фото товара найдено!');
            console.log(`  Photo ID: ${shawarma.photo}`);
            
            // Формируем URL для получения фото
            const photoUrl = `https://joinposter.com/api/image?image_id=${shawarma.photo}&size=600x600`;
            console.log(`🖼️ URL фото: ${photoUrl}`);
            
            // Показываем информацию о фото
            console.log('\n📸 ИНФОРМАЦИЯ О ФОТО:');
            console.log(`  Полный URL: ${photoUrl}`);
            console.log(`  Размер: 600x600 пикселей`);
            console.log(`  Формат: JPG/PNG`);
            
            // Пробуем получить фото
            try {
                const photoResponse = await axios.get(photoUrl, { responseType: 'arraybuffer' });
                console.log(`✅ Фото успешно загружено!`);
                console.log(`  Размер файла: ${photoResponse.data.length} байт`);
                console.log(`  Content-Type: ${photoResponse.headers['content-type']}`);
                
                // Сохраняем фото локально
                const fs = require('fs');
                const path = require('path');
                const filename = `shawarma_${shawarma.product_id}.jpg`;
                fs.writeFileSync(filename, photoResponse.data);
                console.log(`💾 Фото сохранено как: ${filename}`);
                
            } catch (photoError) {
                console.log(`❌ Ошибка загрузки фото: ${photoError.message}`);
            }
            
        } else {
            console.log('❌ У товара "Шаурма" нет фото');
        }
        
        // Показываем полную информацию о товаре
        console.log('\n📋 ПОЛНАЯ ИНФОРМАЦИЯ О ТОВАРЕ:');
        console.log(JSON.stringify(shawarma, null, 2));
        
    } catch (error) {
        console.error('❌ Ошибка:', error.message);
        if (error.response) {
            console.error('📥 Ответ API:', error.response.data);
        }
    }
}

// Запускаем поиск
getShawarmaPhoto();

