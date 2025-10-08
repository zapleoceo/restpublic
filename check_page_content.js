// Скрипт для проверки контента страниц

print('=== ПРОВЕРКА КОНТЕНТА СТРАНИЦ ===');

// Подключаемся к базе данных
const verandaDB = db.getSiblingDB('veranda');

print('\n--- Коллекция page_content ---');
const pageContent = verandaDB.getCollection('page_content');
print('Количество документов:', pageContent.countDocuments());

// Показываем все страницы
print('\nВсе страницы в базе:');
pageContent.find({}, {page: 1, language: 1, status: 1, updated_at: 1}).forEach(function(doc) {
    print('- Страница: ' + doc.page + ', Язык: ' + doc.language + ', Статус: ' + doc.status + ', Обновлено: ' + (doc.updated_at || 'не указано'));
});

// Ищем контент для главной страницы
print('\n--- Контент для главной страницы (index) ---');
const indexContent = pageContent.find({page: 'index'}).toArray();
print('Найдено документов для index:', indexContent.length);

indexContent.forEach(function(doc) {
    print('\nДокумент:');
    print('  Язык: ' + doc.language);
    print('  Статус: ' + doc.status);
    print('  Есть контент: ' + (doc.content ? 'да' : 'нет'));
    print('  Есть мета: ' + (doc.meta ? 'да' : 'нет'));
    if (doc.content) {
        print('  Длина контента: ' + doc.content.length + ' символов');
        print('  Начало контента: ' + doc.content.substring(0, 100) + '...');
    }
});

print('\n=== ПРОВЕРКА ЗАВЕРШЕНА ===');
