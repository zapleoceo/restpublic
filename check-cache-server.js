const { MongoClient } = require('mongodb');

async function checkCache() {
  const client = new MongoClient('mongodb://localhost:27017');
  try {
    await client.connect();
    const db = client.db('northrepublic');
    const menuCollection = db.collection('menu');
    const settingsCollection = db.collection('settings');
    
    console.log('🔍 Проверяем текущий кеш меню...');
    const menuCache = await menuCollection.findOne({_id: 'current_menu'});
    
    if (menuCache) {
      console.log('📅 Время последнего обновления кеша:', menuCache.updated_at);
      console.log('📋 Количество категорий в кеше:', menuCache.categories ? menuCache.categories.length : 0);
      
      if (menuCache.categories && menuCache.categories.length > 0) {
        console.log('\n📝 Текущие названия категорий:');
        menuCache.categories.forEach((cat, index) => {
          console.log(`  ${index + 1}. ID: ${cat.category_id} | Название: ${cat.category_name || cat.name}`);
        });
      }
    } else {
      console.log('❌ Кеш меню не найден');
    }
    
    console.log('\n⏰ Проверяем настройки времени обновления...');
    const lastUpdate = await settingsCollection.findOne({key: 'menu_last_update_time'});
    const lastCheck = await settingsCollection.findOne({key: 'menu_last_check_time'});
    
    if (lastUpdate) {
      const updateTime = new Date(lastUpdate.value * 1000);
      console.log('🕐 Последнее обновление меню:', updateTime.toLocaleString('ru-RU', {timeZone: 'Asia/Ho_Chi_Minh'}));
    }
    
    if (lastCheck) {
      const checkTime = new Date(lastCheck.value * 1000);
      console.log('🕐 Последняя проверка обновления:', checkTime.toLocaleString('ru-RU', {timeZone: 'Asia/Ho_Chi_Minh'}));
    }
    
  } catch (error) {
    console.error('❌ Ошибка:', error.message);
  } finally {
    await client.close();
  }
}

checkCache();