# Полная документация проекта North Republic

## Архитектура проекта

### Общая структура
```
NRsite/
├── backend/          # Node.js API сервер
├── frontend/         # React приложение
├── template/         # HTML шаблон
├── index.html        # Гибридный HTML файл
└── deploy.sh         # Скрипт автоматического деплоя
```

### Технологический стек
- **Frontend**: React 18, CSS3, HTML5
- **Backend**: Node.js, Express.js, Axios
- **API**: Poster POS API v3
- **Deploy**: PM2, Nginx, SSH
- **Version Control**: Git

## Frontend (React приложение)

### Инициализация React
**Файл**: `frontend/src/main.jsx`

React приложение инициализируется с задержкой для совместимости с HTML шаблоном:

1. **Ожидание загрузки**: Ждет события `window.load`
2. **Дополнительная задержка**: 100ms для гарантии выполнения template JS
3. **Создание root**: Использует `createRoot` API React 18
4. **Рендеринг**: Рендерит `<App />` в контейнер `#root`

```javascript
// Ключевая логика инициализации
if (document.readyState === 'complete') {
  initReactApp();
} else {
  window.addEventListener('load', function() {
    setTimeout(initReactApp, 100);
  });
}
```

### Структура компонентов

#### App.jsx
**Файл**: `frontend/src/App.jsx`
- Основной компонент приложения
- Использует React Router для навигации
- Включает компонент `VersionInfo` для отображения версии

#### HomePage.jsx
**Файл**: `frontend/src/pages/HomePage.jsx`
- Главная страница сайта
- Импортирует все секции: Intro, About, DynamicMenu, Services, Events, Testimonials
- Обрабатывает ошибки рендеринга

#### DynamicMenu.jsx
**Файл**: `frontend/src/components/DynamicMenu.jsx`

**Ключевая логика**:
1. **Состояние**: Управляет категориями, продуктами, активной категорией, загрузкой
2. **Загрузка данных**: Использует `menuService` для получения данных
3. **Кэширование**: Данные кэшируются на 5 минут
4. **Интеграция с шаблоном**: Создает DOM элементы с ID `tab-${categoryId}` для совместимости с template JS
5. **Ограничение продуктов**: Показывает только первые 5 популярных продуктов
6. **Skeleton loader**: Отображает анимированный загрузчик во время загрузки

**Ключевые методы**:
- `loadMenuData()`: Загружает категории и продукты
- `handleCategoryClick()`: Обрабатывает клик по категории, интегрируется с template JS

### Сервисы

#### menuService.js
**Файл**: `frontend/src/services/menuService.js`

**Функциональность**:
- **Кэширование**: 5-минутный кэш для оптимизации
- **API вызовы**: Интеграция с backend API
- **Форматирование цен**: Конвертация цен из копеек в доллары
- **Обработка изображений**: Генерация URL изображений продуктов

**Основные методы**:
- `getMenuData()`: Получение полного меню с кэшированием
- `getCategories()`: Получение категорий
- `getProductsByCategory()`: Продукты по категории
- `getPopularProductsByCategory()`: Популярные продукты
- `formatPrice()`: Форматирование цены

#### apiService.js
**Файл**: `frontend/src/services/apiService.js`
- Базовый HTTP клиент на основе Axios
- Настроен для работы с backend API
- Обработка ошибок и интерцепторы

### Константы и конфигурация

#### apiEndpoints.js
**Файл**: `frontend/src/constants/apiEndpoints.js`
- Определяет все API endpoints
- Автоматическое определение базового URL (production/development)
- Production: `https://northrepublic.me`
- Development: `http://localhost:3002`

## Backend (Node.js API)

### Структура сервера

#### server.js
**Файл**: `backend/server.js`
- Express.js сервер
- Middleware: CORS, Helmet, Morgan
- Роуты: `/api/menu`, `/api/poster`, `/api/health`
- Порт: 3002 (production)

#### Роуты

##### menu.js
**Файл**: `backend/routes/menu.js`
- `GET /api/menu`: Полное меню (категории + продукты)
- `GET /api/menu/categories`: Только категории
- Обработка ошибок и логирование
- Нормализация цен и изображений

##### poster.js
**Файл**: `backend/routes/poster.js`
- `GET /api/poster/:method`: Прокси для Poster API
- `POST /api/poster/:method`: POST запросы к Poster API
- Безопасность: токен не передается клиенту

### Сервисы

#### posterService.js
**Файл**: `backend/services/posterService.js`

**Ключевая функциональность**:
1. **Интеграция с Poster API**: Прямые вызовы к `https://joinposter.com/api`
2. **Аутентификация**: Использует токен из переменных окружения
3. **Кэширование**: Встроенное кэширование для оптимизации
4. **Обработка данных**: Нормализация цен, форматирование изображений

**Основные методы**:
- `makeRequest()`: Базовый метод для API запросов
- `getCategories()`: Получение категорий из Poster
- `getProducts()`: Получение всех продуктов
- `getProductsByCategory()`: Продукты по категории
- `getPopularProducts()`: Популярные продукты (по статистике продаж)
- `normalizePrice()`: Конвертация цены из копеек в доллары
- `formatPrice()`: Форматирование цены для отображения
- `getProductImage()`: Генерация URL изображений

**Кэширование**:
- Категории: 5 минут
- Продукты: 5 минут
- Популярные продукты: 30 минут

## HTML шаблон и интеграция

### index.html
**Файл**: `index.html` (гибридный файл)

**Структура**:
1. **HTML шаблон**: Базовый HTML с мета-тегами и favicon
2. **CSS**: Подключение template CSS и React CSS
3. **Preloader**: Анимированный загрузчик сайта
4. **React контейнер**: `<div id="root"></div>`
5. **Template JS**: `plugins.js` и `main.js`
6. **React JS**: Динамически обновляемый JS файл

**Ключевые особенности**:
- Гибридная структура: HTML шаблон + React приложение
- Автоматическое обновление ссылок на JS файлы
- Совместимость с template JavaScript
- UTF-8 кодировка (автоматически исправляется при деплое)

### Интеграция React с шаблоном

**Проблема**: Template JS и React конфликтуют при одновременной инициализации

**Решение**:
1. **Отложенная инициализация**: React ждет полной загрузки страницы
2. **DOM интеграция**: React создает элементы с ID для совместимости
3. **События**: Обработка кликов интегрируется с template JS
4. **Стили**: CSS правила для устранения конфликтов

## Деплой и инфраструктура

### deploy.sh
**Файл**: `deploy.sh` (автоматический скрипт деплоя)

**Последовательность операций**:
1. **Очистка сервера**: `git clean -fd`
2. **Обновление кода**: `git pull origin main`
3. **Установка зависимостей**: `npm install` для backend
4. **Сборка frontend**: `npm run build`
5. **Копирование файлов**: Static файлы, CSS, изображения, иконки
6. **Восстановление index.html**: Из копии в репозитории
7. **Исправление кодировки**: UTF-16 → UTF-8 (автоматически)
8. **Обновление JS ссылок**: Автоматическое обновление ссылки на новый JS файл
9. **Валидация**: Проверка корректности обновления
10. **Перезапуск сервисов**: PM2 restart
11. **Синхронизация**: Обновление локальной копии index.html

**Автоматизация**:
- Полностью автоматический процесс
- Не требует ручных правок
- Автоматическое исправление проблем с кодировкой
- Валидация результатов

### Серверная инфраструктура

**Структура на сервере**:
```
/var/www/northrepubli_usr/data/www/northrepublic.me/
├── backend/          # Node.js API
├── frontend/         # React приложение
├── static/           # Собранные React файлы
├── template/         # HTML шаблон
├── index.html        # Гибридный HTML файл
└── deploy.sh         # Скрипт деплоя
```

**PM2 сервисы**:
- `northrepublic-backend`: Backend API на порту 3002
- Автоматический перезапуск при сбоях
- Логирование и мониторинг

**Nginx**:
- Проксирование запросов на backend
- Обслуживание статических файлов
- SSL сертификаты

## Конфигурация и переменные окружения

### backend/config.env
**Файл**: `backend/config.env`

**Переменные**:
- `PORT=3002`: Порт backend сервера
- `NODE_ENV=production`: Окружение
- `POSTER_API_TOKEN`: Токен для Poster API
- `POSTER_API_BASE_URL=https://joinposter.com/api`: URL Poster API
- `CACHE_TTL=300000`: Время жизни кэша (5 минут)
- `CORS_ORIGIN=https://northrepublic.me`: Разрешенный origin

### frontend/src/constants/apiEndpoints.js
**Автоматическое определение окружения**:
- Production: `https://northrepublic.me`
- Development: `http://localhost:3002`

## Безопасность

### Backend
- **CORS**: Настроен только для домена northrepublic.me
- **Helmet.js**: Защита HTTP заголовков
- **Токены**: Poster API токен хранится только на сервере
- **Прокси**: Все запросы к Poster API проходят через backend

### Frontend
- **HTTPS**: Принудительное использование HTTPS в production
- **Валидация**: Проверка данных от API
- **Обработка ошибок**: Безопасные сообщения об ошибках

## Производительность

### Кэширование
- **Frontend**: 5-минутный кэш в menuService
- **Backend**: Кэширование запросов к Poster API
- **Статические файлы**: Кэширование через Nginx

### Оптимизация
- **React**: Оптимизированная сборка для production
- **Изображения**: Оптимизированные размеры для Poster API
- **CSS**: Минификация и сжатие
- **JS**: Минификация и tree shaking

## Мониторинг и логирование

### Backend
- **Morgan**: HTTP логирование
- **Console**: Детальное логирование операций
- **PM2**: Мониторинг процессов

### Frontend
- **Console**: Логирование для отладки
- **Error boundaries**: Обработка ошибок React

## Восстановление проекта

### Для полного восстановления проекта необходимо:

1. **Клонировать репозиторий**
2. **Настроить переменные окружения** в `backend/config.env`
3. **Установить зависимости**: `npm install` в backend и frontend
4. **Настроить сервер**: PM2, Nginx, SSL
5. **Запустить деплой**: `./deploy.sh`

### Критически важные файлы:
- `index.html`: Гибридный HTML файл (НЕ перезаписывать!)
- `deploy.sh`: Автоматический скрипт деплоя
- `backend/config.env`: Конфигурация и токены
- `frontend/src/services/menuService.js`: Логика работы с меню
- `backend/services/posterService.js`: Интеграция с Poster API

### Зависимости:
- Node.js 16+
- PM2 для управления процессами
- Nginx для веб-сервера
- Git для версионного контроля
- SSH доступ к серверу