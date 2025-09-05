# Документация проекта North Republic

## Архитектура проекта

### Общая структура
```
NRsite/
├── php/               # PHP Frontend
│   ├── index.php      # Главная страница
│   └── menu.php       # Страница меню
├── backend/           # Node.js API сервер
├── template/          # HTML шаблон и ресурсы
└── deploy.sh          # Скрипт автоматического деплоя
```

### Технологический стек
- **Frontend**: PHP, HTML5, CSS3, JavaScript
- **Backend**: Node.js, Express.js, MongoDB
- **API**: Poster POS API v3
- **Deploy**: PM2, Nginx, Apache, SSH
- **Version Control**: Git

## Frontend (PHP)

### index.php
**Файл**: `php/index.php`
- Главная страница сайта
- Интеграция с оригинальным HTML шаблоном
- Динамическое меню через Node.js API
- Русская локализация

### menu.php
**Файл**: `php/menu.php`
- Полная страница меню
- Отображение всех категорий и продуктов
- Интеграция с template стилями

### Интеграция с шаблоном
- **CSS**: vendor.css, styles.css, custom.css из template/
- **JavaScript**: main.js, plugins.js из template/
- **Изображения**: все ресурсы из template/images/
- **Сохранение**: всех оригинальных стилей и анимаций

## Backend (Node.js API)

### Структура сервера

#### server.js
**Файл**: `backend/server.js`
- Express.js сервер на порту 3002
- Middleware: CORS, Helmet, Morgan
- Роуты: `/api/menu`, `/api/poster`, `/api/health`

#### Роуты

##### menu.js
**Файл**: `backend/routes/menu.js`
- `GET /api/menu`: Полное меню (категории + продукты)
- `GET /api/menu/categories`: Только категории
- Обработка ошибок и логирование

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
- `normalizePrice()`: Конвертация цены из копеек в доллары
- `formatPrice()`: Форматирование цены для отображения
- `getProductImage()`: Генерация URL изображений

**Кэширование**:
- Категории: 5 минут
- Продукты: 5 минут

## MongoDB интеграция

### Конфигурация
- **Версия**: MongoDB 8.0.4
- **Порт**: 27017
- **Хост**: 127.0.0.1
- **База данных**: northrepublic

### Структура данных
- **Переводы** (`translations`) - Многоязычные тексты
- **Настройки сайта** (`site_config`) - Параметры сайта
- **Секции** (`sections`) - Конфигурация разделов
- **Версии конфигураций** (`versions`) - История изменений

### PHP-MongoDB интеграция
- **MongoDB PHP Driver** - Подключение к MongoDB из PHP
- **Конфигурации** - Хранение настроек сайта в MongoDB
- **Переводы** - Многоязычные тексты в MongoDB
- **Версионирование** - История изменений конфигураций

## Деплой и инфраструктура

### deploy.sh
**Файл**: `deploy.sh` (автоматический скрипт деплоя)

**Последовательность операций**:
1. **Очистка сервера**: `git clean -fd`
2. **Обновление кода**: `git pull origin main`
3. **Установка зависимостей**: `npm install` для backend
4. **Копирование PHP файлов**: index.php, menu.php
5. **Копирование template файлов**: CSS, JS, images, иконки
6. **Перезапуск сервисов**: PM2 restart
7. **Коммит изменений**: автоматический коммит в репозиторий

### Серверная инфраструктура

**Структура на сервере**:
```
/var/www/northrepubli_usr/data/www/northrepublic.me/
├── backend/          # Node.js API
├── php/              # PHP Frontend
├── template/         # HTML шаблон
├── index.php         # Главная страница
├── menu.php          # Страница меню
└── deploy.sh         # Скрипт деплоя
```

**PM2 сервисы**:
- `northrepublic-backend`: Backend API на порту 3002
- Автоматический перезапуск при сбоях
- Логирование и мониторинг

**Nginx + Apache**:
- **Nginx**: Reverse proxy, статические файлы, SSL
- **Apache**: Обработка PHP файлов (порт 81)
- **Проксирование**: API запросы на Node.js, PHP на Apache

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
- **Backend**: Кэширование запросов к Poster API
- **Статические файлы**: Кэширование через Nginx
- **MongoDB**: Кэширование конфигураций

### Оптимизация
- **PHP**: Оптимизированная обработка
- **Изображения**: Оптимизированные размеры для Poster API
- **CSS**: Минификация и сжатие
- **JS**: Минификация

## Мониторинг и логирование

### Backend
- **Morgan**: HTTP логирование
- **Console**: Детальное логирование операций
- **PM2**: Мониторинг процессов

### Frontend
- **PHP логи**: Логирование ошибок PHP
- **Nginx логи**: Доступ и ошибки

## Восстановление проекта

### Для полного восстановления проекта необходимо:

1. **Клонировать репозиторий**
2. **Настроить переменные окружения** в `backend/config.env`
3. **Установить зависимости**: `npm install` в backend
4. **Настроить сервер**: PM2, Nginx, Apache, SSL
5. **Запустить деплой**: `./deploy.sh`

### Критически важные файлы:
- `php/index.php`: Главная страница
- `php/menu.php`: Страница меню
- `deploy.sh`: Автоматический скрипт деплоя
- `backend/config.env`: Конфигурация и токены
- `backend/services/posterService.js`: Интеграция с Poster API

### Зависимости:
- Node.js 16+
- PHP 7.4+
- MongoDB 8.0+
- PM2 для управления процессами
- Nginx + Apache для веб-сервера
- Git для версионного контроля
- SSH доступ к серверу