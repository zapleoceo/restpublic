# 🔍 ПОЛНЫЙ АУДИТ ПРОЕКТА VERANDA.MY

**Дата**: 14.10.2025  
**MongoDB порт**: 27018  
**База данных**: `veranda`

---

## 📊 ТЕКУЩЕЕ СОСТОЯНИЕ ИНФРАСТРУКТУРЫ

### 1. MongoDB (порт 27018)

**Существующие коллекции:**
- `sepay_transactions` - транзакции SePay
- `cache_update_logs` - логи обновления кэша
- `event_images.chunks` / `event_images.files` - GridFS для изображений событий
- `settings` - настройки системы
- `events` - события ресторана
- `admin_logs` - логи администратора
- `admin_users` - пользователи админки
- `menu` - кэш меню из Poster API (2 документа: categories + products)

**ОТСУТСТВУЮТ коллекции:**
- ❌ `admin_texts` - переводы интерфейса
- ❌ `page_content` - контент страниц
- ❌ `users` - пользователи сайта
- ❌ `orders` - заказы/чеки
- ❌ `clients` - клиенты ресторана
- ❌ `translations` - мультиязычность

---

## 🏗️ АРХИТЕКТУРА ЗАПРОСОВ

### A. Frontend → Backend (Node.js)

**Через `api/proxy.php`:**
```
Frontend (JS) 
  ↓ fetch()
  ↓ api/proxy.php (PHP прокси)
  ↓ http://localhost:3003/api/*
  ↓ Backend Node.js (Express)
  ↓ Poster API / MongoDB
```

**Используемые эндпоинты:**
1. `/api/poster/*` - запросы к Poster API
   - `poster/orders/create` - создание заказов
   - `poster/orders/create-check` - создание чеков
   - `poster/clients.getClients` - получение клиентов
   - `poster/transactions.getTransactions` - получение транзакций
   - `poster/transactions.addTransactionProduct` - добавление товаров

2. `/api/menu/*` - работа с меню
   - `menu/update` - обновление кэша меню

3. `/api/cache/*` - управление кэшем
   - `cache/update` - обновление кэша

4. `/api/auth/*` - авторизация
   - `auth/telegram` - авторизация через Telegram

5. `/api/user/*` - работа с пользователями
   - `user/profile` - профиль пользователя

---

### B. Backend → Внешние API

**1. Poster API (напрямую из Node.js)**
```javascript
// backend/services/posterService.js
Base URL: https://joinposter.com/api
Token: 922371:489411264005b482039f38b8ee21f6fb

Методы:
- menu.getCategories
- menu.getProducts
- clients.getClients
- clients.createClient
- orders (POST) - создание чеков
- incomingOrders.createIncomingOrder
- transactions.getTransactions
- transactions.addTransactionProduct
- dash.getProductsSales
- storage.getLeftovers
```

**2. SePay API (напрямую из PHP)**
```php
// classes/SePayApiService.php
Base URL: https://my.sepay.vn/userapi
Token: ATUV13DSBM72D6JQXOZIGGE0OH8ULFBOBFNZ9XXEIWFQEY4NWYHCGCSKLVMYPWEJ

Методы:
- /transactions/list - получение транзакций
```

---

### C. Прямые запросы к MongoDB (PHP)

**43 файла напрямую используют MongoDB\Client:**

**Classes:**
- `MenuCache.php` - кэш меню
- `EventsService.php` - события
- `TranslationService.php` - переводы
- `PageContentService.php` - контент страниц
- `UserAuth.php` - авторизация пользователей
- `SePayTransactionService.php` - транзакции SePay
- `ImageService.php` - изображения
- `SettingsService.php` - настройки
- `TablesCache.php` - столики
- `Logger.php` - логирование
- `RateLimiter.php` - ограничение запросов
- `TextManager.php` - тексты

**Pages:**
- `index.php`, `menu.php`, `menu2.php`
- `admin/events/index.php`
- `admin/database/*.php`
- `api/events.php`, `api/tables.php`

---

## 🚨 ПРОБЛЕМЫ ТЕКУЩЕЙ АРХИТЕКТУРЫ

### 1. **Дублирование подключений к MongoDB**
- ❌ 43+ файла создают собственные подключения
- ❌ Нет единого сервиса для работы с MongoDB
- ❌ Каждое подключение = новый TCP connection

### 2. **Смешанная логика**
- ❌ Часть запросов через Backend (Node.js)
- ❌ Часть запросов напрямую из PHP
- ❌ Нет единой точки входа

### 3. **Отсутствие коллекций для критичных данных**
- ❌ Нет хранения заказов/чеков в MongoDB
- ❌ Нет хранения клиентов (только в Poster)
- ❌ Нет истории транзакций
- ❌ Нет переводов интерфейса

### 4. **Прямые вызовы внешних API**
- ❌ SePay API вызывается напрямую из PHP
- ❌ Нет централизованного логирования
- ❌ Нет обработки rate limits

### 5. **Отсутствие кэширования**
- ❌ Poster API вызывается при каждом запросе
- ❌ Нет кэша для клиентов
- ❌ Нет кэша для транзакций

---

## ✅ ПЛАН РЕОРГАНИЗАЦИИ

### ЭТАП 1: Централизация MongoDB (Приоритет: ВЫСОКИЙ)

**Цель:** Все данные в одной базе `veranda` на порту 27018

#### 1.1 Создать недостающие коллекции:

```javascript
// Коллекции для хранения
veranda:
  ├── menu (✅ существует)
  ├── events (✅ существует)
  ├── admin_users (✅ существует)
  ├── admin_logs (✅ существует)
  ├── settings (✅ существует)
  ├── sepay_transactions (✅ существует)
  │
  ├── admin_texts (❌ создать) - переводы интерфейса
  ├── page_content (❌ создать) - контент страниц
  ├── users (❌ создать) - пользователи сайта
  ├── clients (❌ создать) - клиенты ресторана (кэш из Poster)
  ├── orders (❌ создать) - заказы/чеки
  ├── transactions (❌ создать) - история транзакций
  └── cache_poster (❌ создать) - кэш Poster API
```

#### 1.2 Структура коллекций:

**`admin_texts` (переводы):**
```json
{
  "_id": ObjectId,
  "key": "menu.title_v2",
  "category": "menu",
  "translations": {
    "ru": "Наше меню",
    "en": "Our Menu",
    "vi": "Thực đơn của chúng tôi"
  },
  "published": true,
  "created_at": ISODate,
  "updated_at": ISODate
}
```

**`page_content` (контент страниц):**
```json
{
  "_id": ObjectId,
  "page": "home",
  "language": "ru",
  "content": "HTML контент",
  "meta": {
    "title": "...",
    "description": "...",
    "keywords": "..."
  },
  "published": true,
  "updated_at": ISODate
}
```

**`users` (пользователи):**
```json
{
  "_id": ObjectId,
  "telegram_id": 123456789,
  "first_name": "...",
  "last_name": "...",
  "username": "...",
  "phone": "+84...",
  "poster_client_id": 71,
  "created_at": ISODate,
  "last_login": ISODate
}
```

**`clients` (клиенты - кэш Poster):**
```json
{
  "_id": ObjectId,
  "poster_id": 71,
  "phone": "+84...",
  "first_name": "...",
  "last_name": "...",
  "email": "...",
  "discount": 10,
  "cached_at": ISODate,
  "expires_at": ISODate
}
```

**`orders` (заказы/чеки):**
```json
{
  "_id": ObjectId,
  "poster_order_id": 12345,
  "poster_transaction_id": 67890,
  "user_id": ObjectId,
  "client_id": 71,
  "status": "open|closed|cancelled",
  "type": "table|takeaway|delivery",
  "products": [
    {
      "product_id": 126,
      "name": "Шаурма",
      "quantity": 2,
      "price": 80000
    }
  ],
  "total": 160000,
  "comment": "...",
  "created_at": ISODate,
  "closed_at": ISODate
}
```

**`transactions` (история транзакций):**
```json
{
  "_id": ObjectId,
  "poster_transaction_id": 67890,
  "order_id": ObjectId,
  "client_id": 71,
  "type": "sale|refund",
  "amount": 160000,
  "payment_method": "cash|card|sepay",
  "sepay_transaction_id": "...",
  "created_at": ISODate
}
```

---

### ЭТАП 2: Унификация API запросов (Приоритет: ВЫСОКИЙ)

**Цель:** Все запросы через Backend Node.js

#### 2.1 Маршрутизация:

```
Frontend (JS)
  ↓
api/proxy.php (PHP)
  ↓
Backend Node.js (localhost:3003)
  ↓
├─→ MongoDB (localhost:27018)
├─→ Poster API (joinposter.com)
└─→ SePay API (my.sepay.vn)
```

#### 2.2 Новые роуты в Backend:

**`backend/routes/orders.js`** (новый файл)
```javascript
// Заказы/чеки в MongoDB
router.get('/orders', getOrders);
router.post('/orders', createOrder);
router.get('/orders/:id', getOrder);
router.patch('/orders/:id', updateOrder);
router.delete('/orders/:id', closeOrder);
```

**`backend/routes/clients.js`** (новый файл)
```javascript
// Клиенты с кэшированием
router.get('/clients', getClients);
router.get('/clients/:id', getClient);
router.post('/clients', createClient);
router.get('/clients/search', searchClients);
```

**`backend/routes/transactions.js`** (новый файл)
```javascript
// Транзакции
router.get('/transactions', getTransactions);
router.get('/transactions/:id', getTransaction);
```

**`backend/routes/sepay.js`** (новый файл)
```javascript
// SePay интеграция
router.get('/sepay/transactions', getSePayTransactions);
router.post('/sepay/webhook', handleSePayWebhook);
```

#### 2.3 Создать сервисы:

**`backend/services/mongoService.js`** (новый)
```javascript
class MongoService {
  constructor() {
    this.client = new MongoClient('mongodb://localhost:27018');
    this.db = this.client.db('veranda');
  }
  
  // Единая точка доступа к MongoDB
  getCollection(name) {
    return this.db.collection(name);
  }
}
```

**`backend/services/sepayService.js`** (новый)
```javascript
// Перенести логику из classes/SePayApiService.php
class SePayService {
  async getTransactions() { ... }
  async handleWebhook() { ... }
}
```

**`backend/services/cacheService.js`** (новый)
```javascript
class CacheService {
  async get(key, fetchFn, ttl) { ... }
  async set(key, value, ttl) { ... }
  async invalidate(key) { ... }
}
```

---

### ЭТАП 3: Миграция PHP логики в Backend (Приоритет: СРЕДНИЙ)

#### 3.1 Переписать на Node.js:

**Из PHP:**
- `classes/SePayApiService.php` → `backend/services/sepayService.js`
- `classes/MenuCache.php` → `backend/services/menuService.js`
- `classes/UserAuth.php` → `backend/services/authService.js`
- `classes/EventsService.php` → `backend/services/eventsService.js`

**Оставить в PHP (админка):**
- `classes/TranslationService.php` - для админки
- `classes/PageContentService.php` - для админки
- `classes/ImageService.php` - загрузка файлов
- `admin/*` - вся админка остается на PHP

#### 3.2 Новая структура:

```
api/
├── proxy.php (единая точка входа)
└── admin/ (админка на PHP)
    ├── texts/
    ├── pages/
    └── ...

backend/
├── server.js
├── routes/
│   ├── poster.js (✅)
│   ├── menu.js (✅)
│   ├── cache.js (✅)
│   ├── auth.js (✅)
│   ├── orders.js (❌ новый)
│   ├── clients.js (❌ новый)
│   ├── transactions.js (❌ новый)
│   └── sepay.js (❌ новый)
└── services/
    ├── posterService.js (✅)
    ├── mongoService.js (❌ новый)
    ├── cacheService.js (❌ новый)
    ├── sepayService.js (❌ новый)
    ├── menuService.js (❌ новый)
    ├── authService.js (❌ новый)
    └── eventsService.js (❌ новый)
```

---

### ЭТАП 4: Оптимизация (Приоритет: НИЗКИЙ)

#### 4.1 Кэширование:

**Уровни кэша:**
1. **In-Memory (Node.js)** - 5 минут
2. **MongoDB (cache_poster)** - 30 минут
3. **Poster API** - источник истины

**Что кэшировать:**
- Меню (categories + products)
- Клиенты (по phone, по ID)
- Транзакции (за последний день)
- Настройки

#### 4.2 Rate Limiting:

**Poster API:**
- Max 5 запросов/сек (уже реализовано в backend)
- Retry с exponential backoff

**SePay API:**
- Max 1 запрос/сек
- Кэш на 5 минут

#### 4.3 Логирование:

**Централизованное логирование в MongoDB:**
```json
{
  "collection": "api_logs",
  "fields": {
    "timestamp": ISODate,
    "service": "poster|sepay|frontend",
    "endpoint": "/api/orders/create",
    "method": "POST",
    "status": 200,
    "duration_ms": 125,
    "user_id": ObjectId,
    "request": {...},
    "response": {...},
    "error": null
  }
}
```

---

## 📋 ПЛАН ВНЕДРЕНИЯ (ПОШАГОВЫЙ)

### ШАГ 1: Подготовка MongoDB (1-2 часа)

1. ✅ Создать скрипт инициализации коллекций
2. ✅ Создать индексы для производительности
3. ✅ Добавить тестовые данные

```bash
# Создать файл: admin/database/init-collections.php
php admin/database/init-collections.php
```

### ШАГ 2: Backend расширение (2-3 часа)

1. ✅ Создать `mongoService.js`
2. ✅ Создать `cacheService.js`
3. ✅ Создать роуты `orders.js`, `clients.js`, `transactions.js`
4. ✅ Добавить middleware для кэширования

### ШАГ 3: Миграция SePay (1-2 часа)

1. ✅ Создать `sepayService.js` в backend
2. ✅ Перенести логику из PHP
3. ✅ Обновить webhook handler
4. ✅ Тестирование

### ШАГ 4: Обновление Frontend (1 час)

1. ✅ Обновить `js/cart-menu2.js` для новых API
2. ✅ Добавить обработку ошибок
3. ✅ Тестирование

### ШАГ 5: Тестирование и деплой (2 часа)

1. ✅ Локальное тестирование
2. ✅ Git commit + push
3. ✅ Деплой на сервер
4. ✅ Мониторинг логов

---

## 🎯 ИТОГОВАЯ АРХИТЕКТУРА

```
┌─────────────┐
│  Frontend   │
│  (JS/HTML)  │
└──────┬──────┘
       │ fetch()
       ▼
┌─────────────┐
│ api/proxy.php│
│  (PHP Proxy)│
└──────┬──────┘
       │ HTTP
       ▼
┌─────────────────────────────────┐
│  Backend Node.js (Express)      │
│  localhost:3003                 │
├─────────────────────────────────┤
│  Routes:                        │
│  - /api/poster/*                │
│  - /api/menu/*                  │
│  - /api/orders/*  ← новый       │
│  - /api/clients/* ← новый       │
│  - /api/sepay/*   ← новый       │
├─────────────────────────────────┤
│  Services:                      │
│  - posterService                │
│  - mongoService   ← новый       │
│  - cacheService   ← новый       │
│  - sepayService   ← новый       │
└────┬─────────┬──────────┬───────┘
     │         │          │
     ▼         ▼          ▼
┌─────────┐ ┌────────┐ ┌────────┐
│ MongoDB │ │ Poster │ │ SePay  │
│  :27018 │ │  API   │ │  API   │
└─────────┘ └────────┘ └────────┘
```

---

## 📊 ПРЕИМУЩЕСТВА НОВОЙ АРХИТЕКТУРЫ

### ✅ Централизация
- Единая база данных `veranda`
- Единая точка входа через Backend
- Централизованное логирование

### ✅ Производительность
- Кэширование на 3 уровнях
- Пул соединений к MongoDB
- Rate limiting

### ✅ Масштабируемость
- Легко добавлять новые эндпоинты
- Горизонтальное масштабирование Backend
- Разделение ответственности

### ✅ Безопасность
- API токены в backend
- Валидация на уровне сервиса
- Централизованная авторизация

### ✅ Поддержка
- Единообразный код
- Логи в одном месте
- Проще отладка

---

## 🚀 СЛЕДУЮЩИЕ ШАГИ

1. **СРОЧНО:** Создать коллекцию `admin_texts` для переводов
2. **СРОЧНО:** Создать коллекцию `orders` для чеков
3. **ВАЖНО:** Реализовать `mongoService.js`
4. **ВАЖНО:** Миграция SePay в backend
5. **ПОТОМ:** Оптимизация и кэширование

---

**Готов приступать к реализации!**

