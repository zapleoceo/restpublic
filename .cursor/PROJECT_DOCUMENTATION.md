# 📚 ПОЛНАЯ ДОКУМЕНТАЦИЯ ПРОЕКТА VERANDA

**Дата создания:** 03.10.2025  
**Версия:** 2.0 (После миграции)  
**Домен:** veranda.my  
**Статус:** Production

---

## 📋 СОДЕРЖАНИЕ

1. [Обзор проекта](#обзор-проекта)
2. [Архитектура](#архитектура)
3. [Технологии](#технологии)
4. [Установка и настройка](#установка-и-настройка)
5. [Структура файлов](#структура-файлов)
6. [Backend API](#backend-api)
7. [Frontend](#frontend)
8. [База данных MongoDB](#база-данных-mongodb)
9. [Интеграции](#интеграции)
10. [Безопасность](#безопасность)
11. [Деплой](#деплой)
12. [Мониторинг и логи](#мониторинг-и-логи)
13. [Решение проблем](#решение-проблем)

---

## 🎯 ОБЗОР ПРОЕКТА

### Назначение
**Veranda** - веб-платформа для ресторана и бара в Нячанге (Вьетнам), включающая:
- Многоязычный сайт (RU, EN, VI)
- Интерактивное меню с корзиной и заказами
- Систему событий и календарь
- Админ-панель для управления контентом
- Интеграцию с Poster POS системой
- Платежную систему через SePay
- Telegram бота для авторизации и уведомлений

### Целевая аудитория
- Посетители ресторана (просмотр меню, заказ)
- Администраторы (управление контентом, мониторинг)
- Менеджеры (получение уведомлений о заказах/платежах)

---

## 🏗️ АРХИТЕКТУРА

### Общая схема

```
┌─────────────────────────────────────────┐
│         veranda.my (Production)         │
├─────────────────────────────────────────┤
│                                         │
│  ┌──────────────┐    ┌──────────────┐  │
│  │   Frontend   │───>│   Backend    │  │
│  │   (PHP 8.x)  │<───│  (Node.js)   │  │
│  │   Port 80    │    │   Port 3003  │  │
│  └──────────────┘    └──────────────┘  │
│         │                    │          │
│         └──────┬─────────────┘          │
│                │                        │
│       ┌────────▼────────┐               │
│       │    MongoDB      │               │
│       │  Port 27017     │               │
│       │   DB: veranda   │               │
│       └─────────────────┘               │
│                                         │
└─────────────────────────────────────────┘
              │
              ▼
    ┌──────────────────────┐
    │  Внешние сервисы:    │
    ├──────────────────────┤
    │  • Poster POS API    │
    │  • SePay Payments    │
    │  • Telegram Bot API  │
    └──────────────────────┘
```

### Компоненты

1. **Frontend (PHP)**
   - Рендеринг страниц
   - Обработка запросов
   - Работа с сессиями
   - Мультиязычность

2. **Backend (Node.js)**
   - REST API
   - Прокси для Poster API
   - Кэширование данных
   - Rate limiting

3. **MongoDB**
   - Кэш меню
   - События
   - Пользователи
   - Транзакции
   - Тексты и переводы
   - Админ-логи

4. **Telegram Bot (Node.js)**
   - Авторизация пользователей
   - Уведомления о платежах
   - Уведомления о заказах

---

## 💻 ТЕХНОЛОГИИ

### Backend
- **Node.js** 18.x
- **Express.js** 4.x - веб-фреймворк
- **Axios** - HTTP клиент для Poster API
- **MongoDB Driver** - работа с БД
- **Helmet** - security headers
- **CORS** - кросс-доменные запросы
- **express-rate-limit** - защита от флуда
- **compression** - сжатие ответов
- **morgan** - логирование запросов

### Frontend
- **PHP** 8.x
- **MongoDB PHP Library** - работа с БД
- **Dotenv** - переменные окружения
- **Vanilla JavaScript** - без фреймворков
- **CSS3** - стилизация

### База данных
- **MongoDB** 7.0
- **19 коллекций**
- **Индексация** по ключевым полям

### DevOps
- **PM2** - process manager для Node.js
- **Git** - версионирование
- **Nginx** - веб-сервер
- **Apache** - PHP handler
- **SSH** - удаленное управление

---

## 🔧 УСТАНОВКА И НАСТРОЙКА

### Требования

```bash
# Минимальные требования сервера:
- CPU: 2 ядра
- RAM: 2 GB
- Disk: 10 GB
- OS: Ubuntu 20.04+ / Debian 11+

# Программное обеспечение:
- Node.js 18.x
- PHP 8.x
- MongoDB 7.x
- Nginx 1.x
- Composer 2.x
- PM2 5.x
```

### Установка на новом сервере

#### 1. Клонирование репозитория
```bash
cd /var/www/veranda_my_usr/data/www/
git clone git@github.com:zapleoceo/restpublic.git veranda.my
cd veranda.my
```

#### 2. Создание .env файла
```bash
cp .cursor/.env.veranda .env
nano .env  # Настроить токены и ключи
```

**Важные переменные:**
```env
# Backend Configuration
BACKEND_URL=http://localhost:3003
PORT=3003

# MongoDB Configuration
MONGODB_URL=mongodb://localhost:27017
MONGODB_DB_NAME=veranda

# Poster POS API
POSTER_API_TOKEN=<ваш_токен>
POSTER_API_BASE_URL=https://joinposter.com/api

# SePay Payment System
SEPAY_API_TOKEN=<ваш_токен>
SEPAY_INCOMING_API_TOKEN=<ваш_токен_для_webhook>

# Telegram Bot
TELEGRAM_BOT_TOKEN=<ваш_токен>
TELEGRAM_GROUP_ID=<id_группы>

# API Authorization
API_AUTH_TOKEN=<случайная_строка_64_символа>
```

#### 3. Установка зависимостей

**Backend (Node.js):**
```bash
cd backend
npm install --production
cd ..
```

**Frontend (PHP):**
```bash
composer install --no-dev --optimize-autoloader
```

**Telegram Bot:**
```bash
cd telegram-bot
npm install --production
npm run build
cd ..
```

#### 4. Настройка MongoDB

```bash
# Импорт базы данных (если есть backup)
mongorestore --db veranda /path/to/backup/veranda/

# Или создать новую базу
mongosh
> use veranda
> db.createCollection("menu")
> db.createCollection("events")
> db.createCollection("users")
> db.createCollection("admin_texts")
> exit
```

#### 5. Запуск сервисов

**Backend:**
```bash
cd backend
pm2 start ecosystem.config.js
cd ..
```

**Telegram Bot:**
```bash
cd telegram-bot  
pm2 start ecosystem.config.cjs
cd ..
```

**Сохранить конфигурацию PM2:**
```bash
pm2 save
pm2 startup  # Следовать инструкциям
```

#### 6. Настройка Cron задач

```bash
crontab -e
```

Добавить:
```cron
# Обновление кэша меню каждые 30 минут
*/30 * * * * curl -X POST http://localhost:3003/api/cache/update-menu >/dev/null 2>&1

# Отправка событий в GameZone (утро)
1 3 * * * /usr/bin/php /var/www/veranda_my_usr/data/www/veranda.my/send_tomorrow_to_gamezone.php >/dev/null 2>&1

# Отправка событий в GameZone (вечер)
12 14 * * * /usr/bin/php /var/www/veranda_my_usr/data/www/veranda.my/send_tomorrow_evening_to_gamezone.php >/dev/null 2>&1

# Отправка недельного расписания в Sila
0 15 * * 0 /usr/bin/php /var/www/veranda_my_usr/data/www/veranda.my/send_weekly_to_sila.php >/dev/null 2>&1

# Telegram cron
* * * * * /usr/bin/php /var/www/veranda_my_usr/data/www/veranda.my/admin/telegram/cron.php >/dev/null 2>&1
```

---

## 📁 СТРУКТУРА ФАЙЛОВ

### Корневые файлы

| Файл | Назначение |
|------|------------|
| `index.php` | Главная страница с мини-меню и виджетом событий |
| `menu.php` | Страница полного меню (версия 1) |
| `menu2.php` | Страница полного меню (версия 2, основная) |
| `events.php` | Календарь событий на 2 недели |
| `webhook-handler.php` | Webhook для приема платежей от SePay |
| `composer.json` | PHP зависимости |
| `.env` | Переменные окружения (НЕ в Git!) |
| `deploy.sh` | Скрипт автоматического деплоя |

### Backend (Node.js)

```
backend/
├── server.js                 # Express сервер
├── config.env                # Конфигурация backend
├── ecosystem.config.js       # PM2 конфигурация
├── package.json              # Node.js зависимости
│
├── routes/                   # API endpoints
│   ├── menu.js              # /api/menu - меню и продукты
│   ├── poster.js            # /api/poster - прокси для Poster API
│   ├── cache.js             # /api/cache - обновление кэша
│   ├── tables.js            # /api/tables - список столов
│   ├── auth.js              # /api/auth - авторизация
│   └── user.js              # /api/user - данные пользователя
│
└── services/
    └── posterService.js     # Сервис для работы с Poster API
```

### PHP Classes

```
classes/
├── MenuCache.php                # Кэш меню из MongoDB + автоперевод блюд
├── EventsService.php            # Управление событиями
├── TranslationService.php       # Мультиязычность (MongoDB)
├── PageContentService.php       # Контент страниц
├── UserAuth.php                 # Авторизация пользователей
├── TelegramService.php          # Работа с Telegram API
├── SePayTransactionService.php  # Обработка платежей
├── SePayApiService.php          # SePay API клиент
├── ImageService.php             # Работа с изображениями
├── SettingsService.php          # Настройки системы
├── TablesCache.php              # Кэш столов
├── Logger.php                   # Логирование
├── RateLimiter.php              # Rate limiting
└── SecurityValidator.php        # Валидация данных
```

### Админ панель

```
admin/
├── index.php              # Дашборд
├── auth/                  # Авторизация админов
│   ├── login.php
│   ├── logout.php
│   └── telegram.php
│
├── events/                # Управление событиями
│   ├── index.php
│   └── api.php
│
├── pages/                 # Управление текстами страниц
│   └── index.php
│
├── settings/              # Настройки системы
│   └── index.php
│
├── logs/                  # Просмотр логов
│   └── index.php
│
├── database/              # Управление БД
│   ├── index.php
│   ├── mongodb-viewer.php
│   └── init-*.php         # Инициализация коллекций
│
└── includes/              # Общие компоненты
    ├── header.php
    ├── footer.php
    ├── sidebar.php
    └── auth-check.php     # Проверка авторизации
```

---

## 🔌 BACKEND API

### Базовая информация

**URL:** `http://localhost:3003` (внутренний)  
**CORS:** `https://veranda.my`  
**Авторизация:** `X-API-Token` header  
**Rate Limit:** 5 запросов/секунду

### Endpoints

#### Health Check
```http
GET /api/health
```

**Ответ:**
```json
{
  "status": "ok",
  "timestamp": "2025-10-03T18:00:00.000Z",
  "uptime": 123456,
  "environment": "production"
}
```

---

#### Меню (полное)
```http
GET /api/menu
Headers: X-API-Token: <token>
```

**Ответ:**
```json
{
  "categories": [
    {
      "category_id": "2",
      "category_name": "Еда",
      "category_photo": "/upload/...",
      "sort_order": "1"
    }
  ],
  "products": [
    {
      "product_id": "36",
      "product_name": "Шаурма",
      "price": "6000000",
      "price_normalized": 60000,
      "price_formatted": "60 000",
      "image_url": "https://joinposter.com/upload/..."
    }
  ],
  "timestamp": "2025-10-03T18:00:00.000Z"
}
```

---

#### Продукты по категории
```http
GET /api/menu/categories/:categoryId/products
Headers: X-API-Token: <token>
```

---

#### Популярные продукты категории
```http
GET /api/menu/categories/:categoryId/popular?limit=5
Headers: X-API-Token: <token>
```

---

#### Обновление кэша меню
```http
POST /api/cache/update-menu
Headers: X-API-Token: <token>
```

**Описание:** Загружает свежие данные из Poster API и сохраняет в MongoDB

**Ответ:**
```json
{
  "success": true,
  "message": "Кэш обновлен успешно",
  "modifiedCount": 1,
  "timestamp": "2025-10-03T18:00:00.000Z"
}
```

---

#### Авторизация через Telegram
```http
POST /api/auth/telegram
Content-Type: application/json

{
  "telegram_id": "123456789",
  "phone": "+84349338758",
  "first_name": "John",
  "last_name": "Doe"
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Authentication successful",
  "redirectUrl": "https://veranda.my/menu2.php?auth=success&session=<token>"
}
```

---

## 🌐 FRONTEND

### Главная страница (index.php)

**Функции:**
- Hero секция с описанием ресторана
- Мини-меню (5 популярных блюд на категорию)
- Виджет предстоящих событий
- Мультиязычность
- Автообновление кэша меню

**Технологии:**
- PHP рендеринг
- MongoDB для контента (`PageContentService`)
- MongoDB для меню (`MenuCache`)
- Автоматический перевод блюд

**Кэширование:**
```php
// Автообновление кэша при заходе (фоновый запрос)
updateMenuCacheAsync(); // → POST localhost:3003/api/cache/update-menu
```

---

### Страница меню (menu2.php)

**Функции:**
- Полное меню всех категорий
- Фильтрация по категориям
- Сортировка (популярные, по цене, А-Я)
- Добавление в корзину
- Авторизация
- Оформление заказа

**Источник данных:**
1. **MongoDB кэш** (primary)
2. **Backend API** (fallback)

Примечание (MongoDB PHP driver): массивы из MongoDB могут приходить как `BSONArray`. Для преобразования используйте итерацию через `foreach` и формируйте обычный PHP-массив; не полагайтесь на `is_array()` для таких полей.

**JavaScript функции:**
- Управление корзиной (`cart.js`)
- Авторизация через Telegram/Google/FB
- AJAX заказы через Poster API

---

### Календарь событий (events.php)

**Функции:**
- Отображение событий на 2 недели
- Недельный вид (Пн-Вс)
- Модальное окно с деталями события
- Мультиязычность
- Lazy loading изображений

**Источник данных:**
```javascript
fetch(`/api/events.php?start_date=${startDate}&days=14&language=${language}`)
```

**Особенности:**
- Загружает события с **начала недели** (не с сегодня!)
- Автоматический перевод названий дней
- Пустые дни показывают приглашение связаться

---

## 💾 БАЗА ДАННЫХ MONGODB

### Структура БД: `veranda`

#### Коллекция: `menu`
**Назначение:** Кэш меню от Poster API

**Документы:**
- `current_menu` - текущее меню (категории + продукты)
- `current_tables` - список столов и залы

**Структура:**
```json
{
  "_id": "current_menu",
  "data": { /* полные данные */ },
  "categories": [ /* массив категорий */ ],
  "products": [ /* массив продуктов */ ],
  "updated_at": ISODate("2025-10-03...")
}
```

```json
{
  "_id": "current_tables",
  "tables": [ /* массив столов */ ],
  "halls": [
    { "hall_id": "1", "hall_name": "Сцена" },
    { "hall_id": "2", "hall_name": "Veranda" }
  ],
  "updated_at": ISODate("2025-10-09...")
}
```

Примечание: названия залов поступают из Poster API `spots.getSpotTablesHalls` (Node.js backend) и сохраняются в `menu.current_tables.halls`.

---

#### Коллекция: `events`
**Назначение:** События ресторана

**Поля:**
```json
{
  "_id": ObjectId("..."),
  "title_ru": "Йога",
  "title_en": "Yoga",
  "title_vi": "Yoga",
  "description_ru": "...",
  "description_en": "...",
  "description_vi": "...",
  "date": "2025-10-03",
  "time": "08:00",
  "conditions_ru": "250.000 VND",
  "is_active": true,
  "category": "Музыкальное",
  "created_at": ISODate("...")
}
```

**Индексы:**
- `date` (ascending)
- `is_active` (ascending)

---

#### Коллекция: `users`
**Назначение:** Зарегистрированные пользователи

**Поля:**
```json
{
  "_id": ObjectId("..."),
  "phone": "+84349338758",
  "name": "John",
  "email": "user@example.com",
  "telegram_id": "123456789",
  "poster_client_id": "12345",
  "created_at": ISODate("..."),
  "last_login": ISODate("...")
}
```

---

#### Коллекция: `sepay_transactions`
**Назначение:** Платежи от SePay

**Поля:**
```json
{
  "_id": ObjectId("..."),
  "transaction_id": "ABC123",
  "amount": 100000,
  "content": "Payment description",
  "gateway": "MB BANK",
  "transaction_date": "2025-10-03 18:00:00",
  "webhook_received_at": ISODate("..."),
  "telegram_sent": true,
  "telegram_sent_at": ISODate("...")
}
```

---

#### Коллекция: `admin_texts`
**Назначение:** Переводы интерфейса

**Поля:**
```json
{
  "_id": ObjectId("..."),
  "key": "menu.title_v2",
  "translations": {
    "ru": "Наше меню v2",
    "en": "Our Menu v2",
    "vi": "Thực đơn của chúng tôi v2"
  },
  "category": "menu",
  "published": true,
  "created_at": ISODate("..."),
  "updated_at": ISODate("...")
}
```

---

#### Коллекция: `page_content`
**Назначение:** Контент страниц (About, Hero и т.д.)

**Поля:**
```json
{
  "_id": ObjectId("..."),
  "page": "home",
  "section": "hero",
  "content_ru": "...",
  "content_en": "...",
  "content_vi": "...",
  "updated_at": ISODate("...")
}
```

---

#### Коллекция: `admin_logs`
**Назначение:** Логи действий администраторов

---

#### Коллекция: `settings`
**Назначение:** Настройки системы

---

#### Коллекция: `user_sessions`
**Назначение:** Сессии пользователей

---

#### Полный список коллекций (18):
1. `admin_settings`
2. `admin_logs`
3. `admin_texts`
4. `admin_texts_backup`
5. `admin_users`
6. `configs`
7. `event_images.files`
8. `event_images.chunks`
9. `events`
10. `menu`
11. `page_content`
12. `rate_limits`
13. `sepay_transactions`
14. `settings`
15. `transactions`
16. `translations`
17. `users`
18. `user_sessions`

---

## 🔗 ИНТЕГРАЦИИ

### 1. Poster POS API

**Документация:** https://dev.joinposter.com/docs/v3

**Использование:**
- Получение меню (категории и продукты)
- Получение списка столов
- Создание заказов
- Получение статистики продаж

**Авторизация:** Token в query параметре
```
https://joinposter.com/api/menu.getProducts?token=<POSTER_API_TOKEN>
```

**Кэширование:**
- В памяти (PosterService): 5 минут
- В MongoDB (MenuCache): 30 минут
- Автообновление через cron каждые 30 минут

---

### 2. SePay Payment System

**Документация:** https://my.sepay.vn/

**Webhook URL:** `https://veranda.my/webhook-handler.php`

**Формат webhook:**
```json
{
  "id": "transaction_id",
  "transferAmount": 100000,
  "content": "Payment description",
  "gateway": "MB BANK",
  "accountNumber": "1234567890",
  "transactionDate": "2025-10-03 18:00:00"
}
```

**Авторизация:** `Authorization: Apikey <SEPAY_INCOMING_API_TOKEN>`

**Обработка:**
1. Получает webhook от SePay
2. Сохраняет в MongoDB (`sepay_transactions`)
3. Отправляет уведомление в Telegram
4. Помечает как отправленное

---

### 3. Telegram Bot

**Bot:** @RestPublic_bot

**Функции:**
- Авторизация пользователей (отправка номера телефона)
- Уведомления о новых платежах
- Уведомления о новых заказах

**Авторизация пользователей:**
1. Пользователь нажимает "Войти" на сайте
2. Открывается Telegram бот
3. Пользователь отправляет контакт (номер телефона)
4. Бот отправляет данные на `/api/auth/telegram`
5. Backend создает сессию
6. Бот возвращает ссылку с токеном сессии
7. Пользователь переходит на сайт авторизованным

---

## 🔐 БЕЗОПАСНОСТЬ

### Принципы

1. **Все секреты в .env** - НЕТ хардкода
2. **Rate limiting** - защита от флуда
3. **CSRF защита** - в формах админки
4. **XSS защита** - валидация всех входов
5. **SQL Injection** - нет, используем MongoDB
6. **Session hijacking** - защита сессий

### Rate Limiting

**Backend API:**
- General: 100 запросов / 15 минут
- API endpoints: 5 запросов / секунду
- Auth endpoints: 2 запроса / секунду

**PHP API:**
- Используется `RateLimiter` class
- Данные хранятся в MongoDB

### Валидация

**SecurityValidator class:**
```php
SecurityValidator::validatePhone($phone);
SecurityValidator::validateEmail($email);
SecurityValidator::sanitizeInput($input);
SecurityValidator::validateCSRF($token);
```

---

## 🚀 ДЕПЛОЙ

### Процесс деплоя

```bash
# 1. Локально - внести изменения
git add .
git commit -m "Description"
git push origin main

# 2. На сервере - обновить код
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my && git pull origin main"

# 3. При изменении зависимостей
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my/backend && npm install"
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my && composer install"

# 4. Перезапустить сервисы
ssh veranda "pm2 restart all"

# 5. Проверить статус
ssh veranda "pm2 status"
ssh veranda "pm2 logs --lines 20"
```

### Автоматический деплой

```bash
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my && ./deploy.sh"
```

**Что делает deploy.sh:**
1. Сбрасывает локальные изменения (`git reset --hard`)
2. Обновляет код (`git pull origin main`)
3. Устанавливает зависимости (npm + composer)
4. Перезапускает PM2 сервисы
5. Проверяет работоспособность

---

## 📊 МОНИТОРИНГ И ЛОГИ

### PM2 Monitoring

```bash
# Статус сервисов
pm2 status

# Логи (все)
pm2 logs

# Логи конкретного сервиса
pm2 logs veranda-backend --lines 100

# Метрики
pm2 monit
```

### Логи приложения

**Логи виртуального хоста (Frontend/Backend):**
- Путь: `/var/www/veranda_my_usr/data/logs/`
- Файлы:
  - `veranda.my-frontend.access.log`
  - `veranda.my-frontend.error.log`
  - `veranda.my-backend.access.log`
  - `veranda.my-backend.error.log`

**PM2 логи Node.js:**
- Путь: `/var/www/veranda_my_usr/data/.pm2/logs/`
- Файлы:
  - `veranda-backend-out.log` - стандартный вывод
  - `veranda-backend-error.log` - ошибки
  - `veranda-backend-combined.log` - все вместе

**SePay webhook логи:**
- Путь: `/var/www/veranda_my_usr/data/www/veranda.my/logs/sepay_webhook.log`
- Формат: `YYYY-MM-DD HH:MM:SS - MESSAGE`

**Admin логи (MongoDB):**
```bash
mongosh veranda --eval 'db.admin_logs.find().sort({timestamp:-1}).limit(10).pretty()'
```

### Проверка здоровья системы

```bash
# Backend API
curl http://localhost:3003/api/health

# Frontend
curl -I https://veranda.my/

# MongoDB
mongosh veranda --eval 'db.adminCommand({ping: 1})'

# PM2 сервисы
pm2 status
```

---

## 🔧 РЕШЕНИЕ ПРОБЛЕМ

### Меню не загружается

**Причины:**
1. Backend не работает
2. MongoDB недоступна
3. Кэш устарел

**Решение:**
```bash
# Проверить backend
curl http://localhost:3003/api/menu

# Обновить кэш
curl -X POST http://localhost:3003/api/cache/update-menu

# Перезапустить backend
pm2 restart veranda-backend
```

Для PHP-ошибок смотреть:
```bash
tail -f /var/www/veranda_my_usr/data/logs/veranda.my-backend.error.log
```

---

### События не отображаются

**Причины:**
1. EventsService не может подключиться к MongoDB
2. Нет активных событий
3. JavaScript ошибка на странице

**Решение:**
```bash
# Проверить события в БД
mongosh veranda --eval 'db.events.countDocuments({is_active: true})'

# Проверить API
curl "https://veranda.my/api/events.php?start_date=2025-10-03&days=14"

# Проверить логи
tail -f /var/log/php_errors.log
```

---

### Webhook не работает

**Причины:**
1. Неправильный API ключ
2. MongoDB недоступна
3. Telegram бот не запущен

**Решение:**
```bash
# Проверить webhook вручную
curl -X POST https://veranda.my/webhook-handler.php \
  -H "Authorization: Apikey <SEPAY_INCOMING_API_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"id":"test123","transferAmount":100000,"content":"Test"}'

# Проверить логи
tail -f logs/sepay_webhook.log

# Проверить Telegram bot
pm2 logs veranda-telegram-bot
```

---

### Backend постоянно перезапускается

**Причины:**
1. Ошибка в коде
2. Порт уже занят
3. Недостаточно памяти

**Решение:**
```bash
# Проверить логи ошибок
pm2 logs veranda-backend --err --lines 50

# Проверить порт
ss -tulpn | grep 3003

# Проверить память
pm2 monit
```

---

## 🎨 МУЛЬТИЯЗЫЧНОСТЬ

### Поддерживаемые языки

- **ru** - Русский (основной)
- **en** - English
- **vi** - Tiếng Việt

### Где хранятся переводы

**1. MongoDB (`admin_texts` коллекция):**
- Переводы интерфейса (кнопки, заголовки, меню навигации)
- Управляется через админ-панель
- Автоматический fallback на русский

**2. PHP код (`MenuCache.php`):**
- Переводы названий блюд
- Хардкод-словарь для быстрого перевода
- Только основные блюда

**3. MongoDB (`events` коллекция):**
- Переводы событий (title, description, conditions)
- Отдельные поля для каждого языка

### Как работает

**TranslationService:**
```php
$translationService = new TranslationService();
echo $translationService->get('menu.title_v2', 'Наше меню v2');
// На русском: "Наше меню v2"
// На английском: "Our Menu v2"  
// На вьетнамском: "Thực đơn của chúng tôi v2"
```

**Смена языка:**
1. Пользователь кликает на языковой переключатель
2. AJAX запрос на `/api/language/change.php`
3. Устанавливается сессия и cookie
4. Страница перезагружается
5. Все тексты отображаются на выбранном языке

---

## 🔄 АВТОМАТИЧЕСКИЕ ЗАДАЧИ (CRON)

### Список задач

```cron
# Обновление кэша меню (каждые 30 минут)
*/30 * * * * curl -X POST http://localhost:3003/api/cache/update-menu

# Отправка событий в GameZone - завтра утром (ежедневно в 03:01)
1 3 * * * /usr/bin/php .../send_tomorrow_to_gamezone.php

# Отправка событий в GameZone - завтра вечером (ежедневно в 14:12)
12 14 * * * /usr/bin/php .../send_tomorrow_evening_to_gamezone.php

# Отправка недельного расписания в Sila (воскресенье в 15:00)
0 15 * * 0 /usr/bin/php .../send_weekly_to_sila.php

# Telegram cron (каждую минуту)
* * * * * /usr/bin/php .../admin/telegram/cron.php
```

---

## 📝 КАК СОЗДАТЬ ПОДОБНЫЙ ПРОЕКТ

### Шаг 1: Подготовка сервера

```bash
# Установка Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Установка PHP 8.x
sudo apt-get install -y php8.2 php8.2-fpm php8.2-mongodb php8.2-curl php8.2-mbstring

# Установка MongoDB
wget -qO- https://www.mongodb.org/static/pgp/server-7.0.asc | sudo apt-key add -
echo "deb http://repo.mongodb.org/apt/ubuntu focal/mongodb-org/7.0 multiverse" | sudo tee /etc/apt/sources.list.d/mongodb-org-7.0.list
sudo apt-get update
sudo apt-get install -y mongodb-org

# Установка Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer

# Установка PM2
sudo npm install -g pm2
```

### Шаг 2: Создание структуры проекта

```bash
mkdir -p veranda.my/{backend,classes,components,admin,api,css,js,images,logs}
cd veranda.my
```

### Шаг 3: Backend (Express + Poster API)

**server.js:**
```javascript
const express = require('express');
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(cors());
app.use(express.json());

// Health check
app.get('/api/health', (req, res) => {
  res.json({ status: 'ok' });
});

// Poster API proxy
const posterService = require('./services/posterService');
app.get('/api/menu', async (req, res) => {
  const categories = await posterService.getCategories();
  const products = await posterService.getProducts();
  res.json({ categories, products });
});

app.listen(3003, () => console.log('Server running on 3003'));
```

### Шаг 4: MongoDB кэширование

**MenuCache.php:**
```php
class MenuCache {
    private $client;
    private $menuCollection;
    
    public function __construct() {
        $mongoUrl = $_ENV['MONGODB_URL'];
        $dbName = $_ENV['MONGODB_DB_NAME'];
        
        $this->client = new MongoDB\Client($mongoUrl);
        $this->menuCollection = $this->client->$dbName->menu;
    }
    
    public function getMenu() {
        return $this->menuCollection->findOne(['_id' => 'current_menu']);
    }
}
```

### Шаг 5: Мультиязычность

**TranslationService.php:**
```php
class TranslationService {
    private $textsCollection;
    private $currentLanguage;
    
    public function get($key, $default = null) {
        $text = $this->textsCollection->findOne(['key' => $key]);
        return $text['translations'][$this->currentLanguage] ?? $default;
    }
}
```

### Шаг 6: Frontend страницы

**index.php:**
```php
<?php
require 'classes/MenuCache.php';
require 'classes/TranslationService.php';

$menuCache = new MenuCache();
$translation = new TranslationService();
$menu = $menuCache->getMenu();
?>

<html>
<head>
    <title><?php echo $translation->get('site.title'); ?></title>
</head>
<body>
    <h1><?php echo $translation->get('menu.title'); ?></h1>
    <?php foreach ($menu['products'] as $product): ?>
        <div><?php echo $product['product_name']; ?></div>
    <?php endforeach; ?>
</body>
</html>
```

### Шаг 7: Webhook для платежей

**webhook-handler.php:**
```php
<?php
$apiKey = $_ENV['SEPAY_INCOMING_API_TOKEN'];
$receivedKey = str_replace('Apikey ', '', $_SERVER['HTTP_AUTHORIZATION']);

if ($receivedKey !== $apiKey) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Сохранить в MongoDB
$client = new MongoDB\Client($_ENV['MONGODB_URL']);
$db = $client->{$_ENV['MONGODB_DB_NAME']};
$db->sepay_transactions->insertOne($data);

// Отправить в Telegram
$telegram = new TelegramService();
$telegram->sendPaymentNotification($data);

echo json_encode(['success' => true]);
```

### Шаг 8: PM2 конфигурация

**backend/ecosystem.config.js:**
```javascript
module.exports = {
  apps: [{
    name: 'veranda-backend',
    script: 'server.js',
    cwd: '/path/to/backend',
    env: {
      NODE_ENV: 'production',
      PORT: 3003
    },
    instances: 1,
    exec_mode: 'fork',
    autorestart: true
  }]
};
```

### Шаг 9: Настройка веб-сервера (Nginx)

```nginx
server {
    listen 80;
    server_name veranda.my;
    root /var/www/veranda_my_usr/data/www/veranda.my;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

## 🎓 КЛЮЧЕВЫЕ КОНЦЕПЦИИ

### 1. Кэширование меню

**Многоуровневое кэширование:**

```
Poster API → Backend (5 мин) → MongoDB (30 мин) → PHP (request)
```

**Почему:**
- Poster API - ограничение запросов
- Быстрая загрузка страниц
- Отказоустойчивость

### 2. Автоматический перевод блюд

**Хардкод-словарь** в `MenuCache.php`:
```php
private function autoTranslateProductName($name, $language) {
    $translations = [
        'en' => ['Шаурма' => 'Shawarma', ...],
        'vi' => ['Шаурма' => 'Shawarma', ...]
    ];
    return $translations[$language][$name] ?? $name;
}
```

**Преимущества:**
- Быстро (без запросов к API)
- Контролируемо (точные переводы)
- Расширяемо (легко добавить новые)

### 3. Безопасная обработка платежей

**Процесс:**
1. SePay → Webhook (проверка API ключа)
2. Валидация данных
3. Сохранение в MongoDB
4. Отправка в Telegram
5. Пометка как отправленное
6. Повторная отправка неотправленных

**Защита:**
- API ключ в заголовке
- Логирование всех запросов
- Проверка формата данных
- Обработка ошибок

### 4. Мультиязычность

**3-уровневая система:**
1. **MongoDB** - переводы UI (гибко)
2. **Hardcode** - переводы блюд (быстро)
3. **Fallback** - русский язык (безопасно)

---

## 📖 ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ

### Зависимости проекта

**Backend (package.json):**
```json
{
  "dependencies": {
    "express": "^4.18.2",
    "cors": "^2.8.5",
    "axios": "^1.6.0",
    "mongodb": "^6.3.0",
    "dotenv": "^16.3.1",
    "helmet": "^7.1.0",
    "compression": "^1.7.4",
    "morgan": "^1.10.0",
    "express-rate-limit": "^7.1.5",
    "express-slow-down": "^2.0.1"
  }
}
```

**Frontend (composer.json):**
```json
{
  "require": {
    "mongodb/mongodb": "^1.17",
    "vlucas/phpdotenv": "^5.5"
  }
}
```

### Внешние API и документация

- **Poster API:** https://dev.joinposter.com/docs/v3
- **SePay API:** https://my.sepay.vn/
- **Telegram Bot API:** https://core.telegram.org/bots/api
- **MongoDB PHP:** https://www.mongodb.com/docs/drivers/php/

---

## ✅ ЧЕКЛИСТ ДЛЯ НОВОГО ПРОЕКТА

### Инфраструктура
- [ ] Установлен Node.js 18+
- [ ] Установлен PHP 8.x с расширениями
- [ ] Установлена MongoDB 7.x
- [ ] Установлен Nginx
- [ ] Установлен PM2
- [ ] Настроен SSH доступ

### Конфигурация
- [ ] Создан .env файл с все токенами
- [ ] Настроен CORS для правильного домена
- [ ] Порт backend указан правильно (3003)
- [ ] MongoDB DB_NAME настроен
- [ ] Webhook URL обновлен в SePay
- [ ] Telegram bot токен настроен

### База данных
- [ ] MongoDB запущена
- [ ] База данных создана
- [ ] Коллекции инициализированы
- [ ] Индексы созданы
- [ ] Тестовые данные добавлены

### Сервисы
- [ ] Backend запущен через PM2
- [ ] Telegram bot запущен через PM2
- [ ] PM2 сохранен (pm2 save)
- [ ] PM2 startup настроен
- [ ] Сервисы автостартуют

### Интеграции
- [ ] Poster API токен работает
- [ ] SePay webhook настроен
- [ ] Telegram bot отвечает
- [ ] Уведомления приходят

### Деплой
- [ ] Git репозиторий настроен
- [ ] SSH ключи настроены
- [ ] deploy.sh работает
- [ ] Cron задачи установлены

### Тестирование
- [ ] Главная страница загружается
- [ ] Меню отображается
- [ ] События показываются
- [ ] Корзина работает
- [ ] Заказы создаются
- [ ] Webhook обрабатывает платежи
- [ ] Переключение языков работает

---

## 🚨 КРИТИЧЕСКИ ВАЖНО

1. **НИКОГДА не коммитить .env** в Git
2. **ВСЕГДА делать backup** перед изменениями БД
3. **ВСЕГДА тестировать** локально перед деплоем
4. **НИКОГДА не останавливать** MongoDB без backup
5. **ВСЕГДА проверять** логи после деплоя

---

## 📞 КОНТАКТЫ И РЕСУРСЫ

- **Репозиторий:** github.com:zapleoceo/restpublic.git
- **Сервер:** 159.253.23.113
- **Домен:** veranda.my
- **SSH Alias:** veranda
- **Разработчик:** zapleo.com

---

**Конец документации**  
Версия 2.0 - После миграции на veranda.my  
Дата: 03.10.2025

