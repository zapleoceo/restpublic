const mongoService = require('./backend/services/mongoService');

async function addTestData() {
  try {
    await mongoService.connect();
    const db = mongoService.getDatabase();
    
    console.log('🔍 Добавляем тестовые данные...');
    
    // Добавляем тестовые секции
    const sections = [
      {
        sectionId: 'intro',
        data: {
          title: 'Республика Север',
          subtitle: 'Развлекательный комплекс',
          description: 'Добро пожаловать в Республику Север - место, где каждый найдет что-то для себя!'
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        sectionId: 'about',
        data: {
          title: 'О нас',
          description: 'Республика Север - это современный развлекательный комплекс, где вы можете насладиться отличной кухней, активными играми и приятным отдыхом.',
          image: '/img/about-main.jpg'
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        sectionId: 'menu',
        data: {
          title: 'Меню ресторана',
          description: 'Попробуйте наши лучшие блюда и напитки',
          categories: ['Популярное', 'Напитки', 'Закуски', 'Основные блюда']
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        sectionId: 'services',
        data: {
          title: 'Услуги',
          description: 'Широкий спектр развлечений для всех возрастов',
          items: [
            { id: 'lasertag', name: 'Лазертаг', icon: '/img/lazertag/icon.png' },
            { id: 'archery', name: 'Стрельба из лука', icon: '/img/archery/icon.png' },
            { id: 'cinema', name: 'Кинотеатр', icon: '/img/cinema/icon.png' },
            { id: 'bbq', name: 'BBQ зона', icon: '/img/bbq/icon.png' },
            { id: 'quests', name: 'Квесты', icon: '/img/quests/icon.png' },
            { id: 'guitar', name: 'Гитара', icon: '/img/guitar/icon.png' },
            { id: 'boardgames', name: 'Настольные игры', icon: '/img/boardgames/icon.png' },
            { id: 'yoga', name: 'Йога', icon: '/img/yoga/icon.png' },
            { id: 'bathhouse', name: 'Банный комплекс', icon: '/img/bathhouse/icon.png' }
          ]
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        sectionId: 'events',
        data: {
          title: 'Афиша',
          description: 'События и мероприятия',
          items: []
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        sectionId: 'testimonials',
        data: {
          title: 'Что говорят наши клиенты',
          items: [
            {
              id: 1,
              author: 'Anna',
              photo: '/img/avatar-placeholder.jpg',
              text: 'На сегодня это лучший кинотеатр под открытым небом💛 надеюсь мы вместе посмотрим и обсудим ещё много фильмов.) Шикарный звук, большой хороший экран, свобода , где хочешь там и лежишь смотришь.) Спасибо огромное организаторам.) шаурма от Олега тоже была вкусна.) 🙃🌊👍',
              rating: 5,
              active: true,
              order: 1
            }
          ]
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      },
      {
        sectionId: 'contact',
        data: {
          title: 'Контакты',
          description: 'Свяжитесь с нами',
          phone: '+7 (XXX) XXX-XX-XX',
          email: 'info@northrepublic.me',
          address: 'Республика Север'
        },
        enabled: true,
        createdAt: new Date(),
        updatedAt: new Date()
      }
    ];
    
    // Очищаем коллекцию и добавляем новые данные
    await db.collection('sections').deleteMany({});
    const result = await db.collection('sections').insertMany(sections);
    
    console.log(`✅ Добавлено ${result.insertedCount} секций`);
    
    // Проверяем результат
    const allSections = await db.collection('sections').find({}).toArray();
    console.log('📄 Всего секций в базе:', allSections.length);
    
    process.exit(0);
  } catch (error) {
    console.error('❌ Ошибка:', error);
    process.exit(1);
  }
}

addTestData();
