# GoodZone - Подробная техническая документация

## Обзор проекта

GoodZone - это комплексная система для развлекательного центра "Республика Север" (North Republic), включающая:
- **Frontend**: React приложение с многоязычной поддержкой
- **Backend**: Node.js API сервер с интеграцией Poster CRM
- **Telegram Bot**: Бот для авторизации пользователей
- **SePay Monitor**: Система мониторинга платежей
- **Admin Panel**: Панель управления контентом

---

## Архитектура системы

### Структура проекта
```
GoodZone/
├── frontend/          # React приложение
├── backend/           # Node.js API сервер
├── bot/              # Telegram бот
├── assets/           # Скомпилированные ресурсы
├── lang/             # Файлы переводов
└── .cursor/          # Конфигурация и документация
```

### Технологический стек
- **Frontend**: React 18, Vite, Tailwind CSS, React Router
- **Backend**: Node.js, Express, MongoDB, JWT
- **Bot**: Telegraf, TypeScript
- **API**: Poster CRM API, SePay API, Telegram Bot API
- **Deployment**: PM2, Nginx, SSH

---

## Backend API (server.js)

### Основные модули
- **Express сервер** с CORS и middleware
- **Poster API прокси** для работы с CRM
- **MongoDB интеграция** для конфигураций и переводов
- **SePay мониторинг** для отслеживания платежей
- **JWT авторизация** для админки

### API Endpoints

#### 1. Health Check
```http
GET /api/health
```
**Описание**: Проверка состояния сервера
**Ответ**:
```json
{
  "status": "OK",
  "timestamp": "2024-01-01T00:00:00.000Z",
  "version": "2.1.1",
  "buildDate": "2025-08-19",
  "features": ["price-normalization", "mongodb-configs"],
  "mongodb": "connected"
}
```

#### 2. Poster API Proxy
```http
GET/POST /api/poster/*
```
**Описание**: Прокси для всех запросов к Poster CRM API
**Параметры**: Все параметры Poster API + автоматическое добавление токена
**Заголовки**: 
- `Content-Type: application/json`
- `User-Agent: NorthRepublic-Backend/2.0`

#### 3. Menu API
```http
GET /api/menu
```
**Описание**: Получение меню с кэшированием (5 минут)
**Логика**:
1. Проверка кэша
2. Запрос категорий: `menu.getCategories`
3. Запрос продуктов: `menu.getProducts`
4. Фильтрация видимых товаров
5. Нормализация цен (деление на 100)
6. Обновление кэша

**Ответ**:
```json
{
  "categories": [...],
  "products": [...],
  "timestamp": "2024-01-01T00:00:00.000Z"
}
```

#### 4. Product Popularity
```http
GET /api/products/popularity
```
**Описание**: Получение данных о популярности товаров за последние 7 дней
**Кэширование**: 10 минут
**API**: `dash.getProductsSales`
**Ответ**:
```json
{
  "productPopularity": {
    "product_id": "count"
  }
}
```

#### 5. Product Modificators
```http
GET /api/products/:productId/modificators
```
**Описание**: Получение модификаторов товара
**API**: `menu.getProductModificators`

---

## MongoDB API

### Translations API
```http
GET /api/translations/:language
PUT /api/translations/:language
```
**Описание**: Управление переводами (ru, en, vi)
**Fallback**: Файлы в `frontend/public/lang/`

### Site Configuration API
```http
GET /api/config/site-config
GET /api/config/sections
PUT /api/config/sections
```
**Описание**: Управление конфигурацией сайта и секций

---

## SePay Integration

### SePay Monitor (sepay-monitor.js)
**Класс**: `SePayMonitor`
**Функции**:
- Мониторинг транзакций каждые 10 секунд
- Отправка уведомлений в Telegram
- Graceful shutdown

**Конфигурация**:
- `chatIds`: ['7795513546', '169510539'] (Rest_publica_bar, zapleosoft)
- `checkInterval`: 10000ms

### SePay Service (sepay-service.js)
**Класс**: `SePayService`
**API**: `https://my.sepay.vn/userapi`
**Методы**:
- `getTransactions()`: Получение всех транзакций
- `getNewTransactions()`: Получение новых транзакций
- `formatTransactionMessage()`: Форматирование сообщения

### SePay API Endpoints
```http
GET /api/sepay/status
POST /api/sepay/start
POST /api/sepay/stop
POST /api/sepay/test
```

---

## Order Management

### Order Service (orderService.js)
**Класс**: `OrderService`
**Основные методы**:

#### 1. Client Management
```javascript
checkExistingClient(phone)
findClientByPhone(phone)
createClient(clientData)
```

#### 2. Order Operations
```javascript
createOrder(orderData)
createGuestOrder(orderData)
getUserOrders(userId)
getUserPastOrders(userId, limit, offset)
getOrderDetails(transactionId)
```

### Order API Endpoints

#### 1. Phone Check
```http
POST /api/orders/check-phone
Body: { "phone": "+84..." }
```

#### 2. Client Registration
```http
POST /api/orders/register
Body: {
  "name": "string",
  "lastName": "string", 
  "phone": "string",
  "birthday": "string",
  "gender": "string"
}
```

#### 3. First Order Check
```http
POST /api/orders/check-first
Body: { "clientId": "string" }
```

#### 4. Create Order
```http
POST /api/orders/create
Body: {
  "items": [...],
  "total": "number",
  "tableId": "string",
  "comment": "string",
  "customerData": {...},
  "withRegistration": "boolean"
}
```

#### 5. Guest Order
```http
POST /api/orders/create-guest
Body: {
  "items": [...],
  "total": "number", 
  "tableId": "string",
  "comment": "string",
  "customerData": {...}
}
```

#### 6. User Orders
```http
GET /api/orders/user/:userId
GET /api/orders/user/:userId/past?limit=10&offset=0
GET /api/orders/details/:transactionId
```

---

## Authentication System

### JWT Authentication (auth.js)
**Модуль**: `authModule`
**Функции**:
- `authenticateUser(username, password)`
- `requireAuth` middleware
- `requireAdmin` middleware

### Auth API Endpoints
```http
POST /api/auth/login
POST /api/auth/logout  
GET /api/auth/status
POST /api/auth/register
POST /api/auth/telegram-callback
GET /api/auth/session/:token
```

### Session Management
```http
POST /api/session/create
POST /api/session/update
```

---

## Telegram Bot Integration

### Bot Configuration (bot.ts)
**Framework**: Telegraf
**TypeScript**: Да
**Основные функции**:
- Авторизация через контакты
- Интеграция с backend API
- Сессии для пользователей

### Bot Flow
1. **Start Command**: `/start auth_<returnUrl>`
2. **Contact Request**: Пользователь делится контактом
3. **Backend Call**: Отправка данных на `/api/auth/telegram-callback`
4. **Return**: Создание сессии и возврат в приложение

### Bot API Calls
```typescript
// Отправка данных на backend
POST /api/auth/telegram-callback
{
  "phone": "string",
  "name": "string", 
  "lastName": "string",
  "birthday": "string",
  "sessionToken": "string"
}
```

---

## Frontend Services

### API Service (apiService.js)
**Класс**: `ApiService`
**Методы**:
- `request(endpoint, options)`
- `get(endpoint, params)`
- `post(endpoint, data)`

### Menu Service (menuService.js)
**Методы**:
- `getMenuData()`: Получение полного меню
- `getCategories()`: Получение категорий
- `getProducts(categoryId)`: Получение продуктов
- `getPopularityData()`: Получение популярности
- `checkHealth()`: Проверка API

### Poster API Service (posterApi.js)
**Методы**:
- `getCategories()`: Категории через прокси
- `getProducts(categoryId)`: Продукты категории
- `getProductImage(imageId, size)`: Изображения товаров

### Events Service (eventsService.js)
**Методы**:
- `getEvents()`: Получение событий
- `getEventDetails(id)`: Детали события

---

## Admin Panel

### Admin Routes (admin.js)
**Endpoints**:
```http
GET /api/admin/translations
PUT /api/admin/translations/:language
GET /api/admin/configs
PUT /api/admin/configs/:type
```

### Admin Module (adminModule.js)
**Функции**:
- Управление секциями сайта
- Управление страницами
- Конфигурация админки

### Admin API
```http
GET /api/admin/config
POST /api/admin/section/:key
POST /api/admin/page/:path
GET /api/sections
GET /api/admin/page/:path/status
```

---

## Sections Management

### Sections Routes (sections.js)
**Endpoints**:
```http
GET /api/sections
PUT /api/sections/:key
```

### Events Routes (events.js)
**Endpoints**:
```http
GET /api/events
GET /api/events/:id
POST /api/events
PUT /api/events/:id
DELETE /api/events/:id
```

---

## MongoDB Service

### MongoService (mongoService.js)
**Методы**:
- `connect()`: Подключение к MongoDB
- `getTranslations(language)`: Получение переводов
- `setTranslations(language, data)`: Сохранение переводов
- `getConfig(type)`: Получение конфигурации
- `setConfig(type, data)`: Сохранение конфигурации
- `getAllTranslations()`: Все переводы
- `getAllConfigs()`: Все конфигурации

---

## Frontend Components

### Основные компоненты
- **App.jsx**: Главный компонент с роутингом
- **Header.jsx**: Шапка с логотипом и навигацией
- **LanguageSwitcher.jsx**: Переключатель языков
- **CartButton.jsx**: Кнопка корзины
- **LoadingSpinner.jsx**: Индикатор загрузки

### Страницы
- **HomePage.jsx**: Главная страница
- **MenuPage.jsx**: Страница меню
- **EventsPage.jsx**: Страница событий
- **EventDetailPage.jsx**: Детали события

### Секции
- **IntroSection.jsx**: Секция "Welcome to"
- **ServicesSection.jsx**: Секция услуг
- **MenuPreviewSection.jsx**: Предварительный просмотр меню
- **EventsSection.jsx**: Секция событий

### Контексты
- **CartContext.jsx**: Контекст корзины
- **TableContext.jsx**: Контекст столиков

### Хуки
- **useTranslation.js**: Интернационализация
- **useMenuData.js**: Данные меню
- **useEvents.js**: Данные событий
- **useSiteConfig.js**: Конфигурация сайта
- **useSiteContent.js**: Контент сайта
- **useSiteSections.js**: Секции сайта

---

## Internationalization (i18n)

### Конфигурация (i18n.js)
**Поддерживаемые языки**: ru, en, vi
**Файлы переводов**: `frontend/public/lang/`
**Backend интеграция**: MongoDB

### Translation Hook (useTranslation.js)
**Функции**:
- `t(key)`: Перевод по ключу
- `changeLanguage(lang)`: Смена языка
- `i18n`: Объект i18next

---

## Styling System

### CSS Architecture
- **Tailwind CSS**: Утилитарные классы
- **Custom CSS Variables**: В `template.css`
- **Component Styles**: В отдельных файлах

### Design Tokens (designTokens.js)
**Переменные**:
- Цвета (`--color-primary`, `--color-bg`, etc.)
- Размеры (`--logo-width`, `--content-padding`, etc.)
- Шрифты (`--font-family`, `--font-size`, etc.)

---

## Deployment

### Deploy Script (deploy.sh)
**Функции**:
- Сборка фронтенда
- Копирование файлов на сервер
- Перезапуск PM2 процессов
- Очистка кэша

### PM2 Configuration (ecosystem.config.js)
**Приложения**:
- `frontend`: React приложение
- `backend`: Node.js API
- `bot`: Telegram бот

### Environment Variables
```bash
# Poster API
POSTER_API_TOKEN=

# SePay API  
SEPAY_API_TOKEN=

# Telegram Bot
TELEGRAM_BOT_TOKEN=

# MongoDB
MONGODB_URI=

# JWT
JWT_SECRET=

# BIDV Account
BIDV_ACCOUNT_NUMBER=
```

---

## API Integration Details

### Poster CRM API
**Base URL**: `https://joinposter.com/api`
**Authentication**: Token в параметрах
**Основные методы**:
- `menu.getCategories`: Категории меню
- `menu.getProducts`: Товары меню
- `menu.getProductModificators`: Модификаторы
- `clients.getClients`: Клиенты
- `clients.createClient`: Создание клиента
- `dash.getProductsSales`: Продажи товаров
- `transactions.createTransaction`: Создание заказа

### SePay API
**Base URL**: `https://my.sepay.vn/userapi`
**Authentication**: Bearer token
**Методы**:
- `transactions/list`: Список транзакций

### Telegram Bot API
**Base URL**: `https://api.telegram.org/bot<TOKEN>`
**Методы**:
- `sendMessage`: Отправка сообщения
- `sendPhoto`: Отправка фото
- `deleteMyCommands`: Удаление команд

---

## Error Handling

### Backend Error Handling
- **Try-catch блоки** во всех async функциях
- **HTTP статус коды** для разных типов ошибок
- **Логирование** всех ошибок в консоль
- **Graceful fallbacks** для критических ошибок

### Frontend Error Handling
- **Error boundaries** для React компонентов
- **Try-catch** в API вызовах
- **Fallback UI** для ошибок загрузки
- **Retry механизмы** для сетевых ошибок

---

## Performance Optimizations

### Backend
- **Кэширование меню** (5 минут)
- **Кэширование популярности** (10 минут)
- **Connection pooling** для MongoDB
- **Compression** для статических файлов

### Frontend
- **Code splitting** по роутам
- **Lazy loading** компонентов
- **Image optimization** через OptimizedImage
- **Memoization** для дорогих вычислений

---

## Security

### Authentication
- **JWT токены** для админки
- **HttpOnly cookies** для безопасности
- **Session management** для пользователей
- **Rate limiting** (планируется)

### API Security
- **CORS** настройки для разрешенных доменов
- **Input validation** для всех запросов
- **HTTPS** для всех внешних API
- **Token rotation** (планируется)

---

## Monitoring & Logging

### Application Logs
- **Console logging** для всех операций
- **Error tracking** с детальной информацией
- **Performance metrics** (время ответа API)
- **User activity** логирование

### External Monitoring
- **SePay transaction monitoring** каждые 10 секунд
- **Telegram notifications** для платежей
- **Health check endpoints** для uptime мониторинга
- **PM2 process monitoring**

---

## Development Workflow

### Local Development
1. **Backend**: `npm run dev` в папке backend
2. **Frontend**: `npm run dev` в папке frontend  
3. **Bot**: `npm run dev` в папке bot
4. **MongoDB**: Локальная или облачная база

### Testing
- **API testing** через Postman/Insomnia
- **Frontend testing** через браузер
- **Bot testing** через Telegram
- **Integration testing** (планируется)

### Deployment Process
1. **Code review** и merge в main
2. **Automatic deployment** через GitHub Actions
3. **Manual deployment** через SSH при необходимости
4. **Health checks** после деплоя

---

## Future Enhancements

### Planned Features
- **Real-time notifications** через WebSocket
- **Advanced analytics** для продаж
- **Multi-language admin panel**
- **Mobile app** для iOS/Android
- **Payment gateway integration**
- **Loyalty program system**

### Technical Improvements
- **GraphQL API** для оптимизации запросов
- **Redis caching** для улучшения производительности
- **Microservices architecture** для масштабирования
- **Docker containerization** для упрощения деплоя
- **Automated testing** с Jest и Cypress

---

## Troubleshooting

### Common Issues

#### Backend Issues
- **MongoDB connection**: Проверить MONGODB_URI
- **Poster API errors**: Проверить POSTER_API_TOKEN
- **SePay monitoring**: Проверить SEPAY_API_TOKEN
- **JWT errors**: Проверить JWT_SECRET

#### Frontend Issues
- **Build errors**: Проверить CSS синтаксис
- **API errors**: Проверить CORS настройки
- **Translation issues**: Проверить файлы переводов
- **Styling issues**: Проверить Tailwind конфигурацию

#### Bot Issues
- **Telegram API errors**: Проверить TELEGRAM_BOT_TOKEN
- **Backend connection**: Проверить BACKEND_URL
- **Session issues**: Проверить логи авторизации

### Debug Commands
```bash
# Backend logs
pm2 logs backend

# Frontend logs  
pm2 logs frontend

# Bot logs
pm2 logs bot

# MongoDB connection
mongo $MONGODB_URI

# API health check
curl https://northrepublic.me/api/health
```

---

## API Reference Summary

### Public Endpoints
- `GET /api/health` - Health check
- `GET /api/menu` - Menu data
- `GET /api/products/popularity` - Product popularity
- `GET /api/products/:id/modificators` - Product modificators
- `GET /api/translations/:lang` - Translations
- `GET /api/config/site-config` - Site configuration
- `GET /api/config/sections` - Sections configuration
- `GET /api/sections` - Available sections
- `GET /api/events` - Events list
- `GET /api/events/:id` - Event details

### Protected Endpoints (Admin)
- `POST /api/auth/login` - Admin login
- `POST /api/auth/logout` - Admin logout
- `GET /api/auth/status` - Auth status
- `GET /api/admin/config` - Admin configuration
- `POST /api/admin/section/:key` - Update section
- `POST /api/admin/page/:path` - Update page
- `GET /api/admin/translations` - All translations
- `PUT /api/admin/translations/:lang` - Update translations
- `GET /api/admin/configs` - All configs
- `PUT /api/admin/configs/:type` - Update config

### Order Management
- `POST /api/orders/check-phone` - Check existing client
- `POST /api/orders/register` - Register client
- `POST /api/orders/check-first` - Check first order
- `POST /api/orders/create` - Create order
- `POST /api/orders/create-guest` - Create guest order
- `GET /api/orders/user/:id` - User orders
- `GET /api/orders/user/:id/past` - User past orders
- `GET /api/orders/details/:id` - Order details

### Authentication & Sessions
- `POST /api/auth/register` - User registration
- `POST /api/auth/telegram-callback` - Telegram auth
- `GET /api/auth/session/:token` - Get session
- `POST /api/session/create` - Create session
- `POST /api/session/update` - Update session

### SePay Monitoring
- `GET /api/sepay/status` - Monitor status
- `POST /api/sepay/start` - Start monitoring
- `POST /api/sepay/stop` - Stop monitoring
- `POST /api/sepay/test` - Test connection

### Poster API Proxy
- `GET/POST /api/poster/*` - All Poster API endpoints

---

*Документация обновлена: 2024-01-01*
*Версия проекта: 3.2.2*
