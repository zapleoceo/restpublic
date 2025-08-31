const mongoService = require('./backend/services/mongoService');

async function checkDatabase() {
  try {
    await mongoService.connect();
    const db = mongoService.getDatabase();
    
    console.log('🔍 Проверяем базу данных...');
    
    // Список коллекций
    const collections = await db.listCollections().toArray();
    console.log('📚 Коллекции:', collections.map(c => c.name));
    
    // Проверяем секции
    const sections = await db.collection('sections').find({}).toArray();
    console.log('📄 Секций найдено:', sections.length);
    
    if (sections.length > 0) {
      console.log('📄 Данные секций:', JSON.stringify(sections, null, 2));
    }
    
    // Проверяем переводы
    const translations = await db.collection('translations').find({}).toArray();
    console.log('🌐 Переводов найдено:', translations.length);
    
    // Проверяем конфигурации
    const configs = await db.collection('configs').find({}).toArray();
    console.log('⚙️ Конфигураций найдено:', configs.length);
    
    process.exit(0);
  } catch (error) {
    console.error('❌ Ошибка:', error);
    process.exit(1);
  }
}

checkDatabase();
