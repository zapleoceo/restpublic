const { MongoClient } = require('mongodb');

const MONGODB_URL = 'mongodb://localhost:27018';
const DB_NAME = 'veranda';

async function createPageContent() {
  let client;
  
  try {
    client = new MongoClient(MONGODB_URL);
    await client.connect();
    console.log('✅ Connected to MongoDB');
    
    const db = client.db(DB_NAME);
    const collection = db.collection('page_content');
    
    // Создаем тестовые данные для главной страницы
    const pageContent = {
      page: 'index',
      language: 'ru',
      content: `
        <section id="hero" class="s-hero">
          <div class="container">
            <div class="row">
              <div class="column xl-6 lg-6 md-12">
                <h1 class="text-display-title">Добро пожаловать в Veranda</h1>
                <p class="text-display-subtitle">Лучшее место для отдыха и вкусной еды</p>
                <a href="/menu.php" class="btn btn--primary">Посмотреть меню</a>
              </div>
            </div>
          </div>
        </section>
      `,
      meta: {
        title: 'Veranda - Главная страница',
        description: 'Добро пожаловать в Veranda - лучшее место для отдыха и вкусной еды',
        keywords: 'veranda, ресторан, еда, отдых',
        menu_title: 'Наше меню',
        menu_subtitle: 'Попробуйте наши лучшие блюда'
      },
      status: 'published',
      updated_at: new Date(),
      updated_by: 'admin'
    };
    
    // Вставляем или обновляем данные
    const result = await collection.replaceOne(
      { page: 'index', language: 'ru' },
      pageContent,
      { upsert: true }
    );
    
    console.log('✅ Page content created/updated:', result);
    
    // Проверяем, что данные сохранились
    const saved = await collection.findOne({ page: 'index', language: 'ru' });
    console.log('✅ Saved data:', saved ? 'Found' : 'Not found');
    
  } catch (error) {
    console.error('❌ Error:', error);
  } finally {
    if (client) {
      await client.close();
      console.log('✅ MongoDB connection closed');
    }
  }
}

createPageContent();

