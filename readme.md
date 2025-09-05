# North Republic - Restaurant Menu Application v3.0

Современное веб-приложение для отображения меню ресторана с интеграцией Poster POS API v3.
**Архитектура**: PHP Frontend + Node.js Backend

## 🌍 Окружения

### Development (Разработка)
- **Домен**: https://goodzone.zapleo.com
- **Сервер**: 159.253.23.113
- **Пользователь**: goodzone_zap_usr
- **Путь**: /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com
- **База данных**: goodzone (MongoDB)

### Production (Продакшен)
- **Домен**: https://northrepublic.me
- **Сервер**: 159.253.23.113
- **Пользователь**: northrepubli_usr
- **Путь**: /var/www/northrepubli_usr/data/www/northrepublic.me
- **База данных**: northrepublic (MongoDB)

## 🗄️ База данных MongoDB

### Конфигурация
- **Версия**: MongoDB 8.0.4
- **Порт**: 27017
- **Хост**: 127.0.0.1
- **Авторизация**: Отключена (только локальный доступ)

### Переменные окружения
```bash
# Development (goodzone.zapleo.com)
MONGODB_URL=mongodb://127.0.0.1:27017
MONGODB_DB_NAME=goodzone

# Production (northrepublic.me)
MONGODB_URL=mongodb://127.0.0.1:27017
MONGODB_DB_NAME=northrepublic
```

### Структура данных
MongoDB используется для хранения конфигурационных данных:
- **Переводы** (`translations`) - Многоязычные тексты
- **Настройки сайта** (`site_config`) - Параметры сайта
- **Секции** (`sections`) - Конфигурация разделов
- **Версии конфигураций** (`versions`) - История изменений

### Админ панель
- **URL**: `https://northrepublic.me/admin`
- **Функции**: Управление переводами, настройками, версионирование
- **Доступ**: Требует авторизации (TODO)

## 🚀 Особенности

- **PHP Frontend** на основе оригинального HTML шаблона с сохранением всех стилей и анимаций
- **Node.js Backend** с кэшированием и проксированием API
- **Интеграция с Poster API v3** для получения данных меню
- **Русская локализация** всех текстов
- **Автоматический деплой** через единый скрипт deploy.sh
- **Nginx + Apache** архитектура (Nginx как reverse proxy, Apache для PHP)

## 📁 Структура проекта

```
NRsite/
├── php/               # PHP Frontend
│   ├── index.php      # Главная страница
│   └── menu.php       # Страница меню
├── template/          # Оригинальный HTML шаблон
│   ├── css/           # Стили (vendor.css, styles.css, custom.css)
│   ├── js/            # JavaScript файлы
│   ├── images/        # Изображения
│   └── index.html     # Базовый шаблон
├── backend/           # Node.js сервер
│   ├── server.js
│   ├── routes/
│   │   ├── menu.js
│   │   └── poster.js
│   ├── services/
│   │   └── posterService.js
│   └── package.json
├── deploy.sh          # Единый скрипт деплоя
└── README.md
```

## 🛠️ Технологии

### Frontend
- **PHP** - Серверный язык для динамического контента
- **HTML5** - Разметка на основе оригинального шаблона
- **CSS3** - Стили из template/css/ (vendor.css, styles.css, custom.css)
- **JavaScript** - Интерактивность из template/js/
- **cURL** - HTTP-клиент для API запросов

### Backend
- **Node.js** - Серверная среда
- **Express.js** - Веб-фреймворк
- **MongoDB** - База данных для конфигураций и переводов
- **Poster POS API v3** - Интеграция с системой ресторана
- **Axios** - HTTP-клиент для API
- **CORS** - Обработка CORS
- **Кэширование** - Встроенное кэширование данных

### PHP-MongoDB интеграция
- **MongoDB PHP Driver** - Подключение к MongoDB из PHP
- **Конфигурации** - Хранение настроек сайта в MongoDB
- **Переводы** - Многоязычные тексты в MongoDB
- **Версионирование** - История изменений конфигураций

### Серверная архитектура
- **Nginx** - Reverse proxy и статические файлы
- **Apache** - Обработка PHP файлов (порт 81)
- **PM2** - Управление Node.js процессами

## 🚀 Быстрый старт

### 1. Клонирование репозитория
```bash
git clone [repository-url]
cd NRsite
```

### 2. Настройка переменных окружения
Backend конфигурация в `backend/config.env`:
- Poster API токены
- MongoDB настройки (mongodb://127.0.0.1:27017)
- Порт сервера (3002)

PHP конфигурация для MongoDB:
- MongoDB PHP Driver должен быть установлен на сервере
- Подключение к той же MongoDB базе данных

### 3. Разработка
```bash
# Backend (Node.js)
cd backend
npm install
npm start

# Frontend (PHP) - локальное тестирование
cd php
php -S localhost:8080
```

### 4. Деплой
```bash
# Единый скрипт деплоя
./deploy.sh
```

## 📱 Использование

### Production (northrepublic.me)
- **Главная страница** (`/`) - Обзор ресторана, контакты, социальные сети
- **Страница меню** (`/menu.php`) - Полный каталог блюд с категориями
- **Динамическое меню** - Данные загружаются из Node.js API
- **Русская локализация** - Все тексты на русском языке
- **Адаптивный дизайн** - Работает на всех устройствах

## 🔧 Разработка

### Frontend (PHP)
```bash
cd php
php -S localhost:8080    # Локальный PHP сервер для тестирования
# Редактирование: index.php, menu.php
# MongoDB подключение через PHP MongoDB Driver
```

### Backend (Node.js)
```bash
cd backend
npm install              # Установка зависимостей
npm start               # Запуск продакшен версии (порт 3002)
```

### Тестирование
```bash
# Локальное тестирование PHP
cd php && php -S localhost:8080

# Локальное тестирование API
cd backend && npm start
# API доступно на http://localhost:3002/api/
```

## 📦 Деплой

### Автоматический деплой

#### Полный деплой (рекомендуется)
```bash
# Запуск на сервере
ssh northrepubli_usr@159.253.23.113
cd /var/www/northrepubli_usr/data/www/northrepublic.me
./deploy.sh
```

#### Быстрый деплой backend
```bash
# С локальной машины (если настроен SSH алиас 'nr')
./deploy-backend.sh
```

#### Деплой frontend
```bash
# С локальной машины (если настроен SSH алиас 'nr')
./deploy-frontend.sh
```

### Правила деплоя
- **SSH подключение**: `ssh northrepubli_usr@159.253.23.113`
- **SSH алиас**: `nr` (если настроен в ~/.ssh/config)
- **Полный деплой**: запускать `deploy.sh` на сервере
- **Быстрый деплой**: использовать `deploy-backend.sh` и `deploy-frontend.sh` с локальной машины
- **Архитектура**: Nginx (reverse proxy) + Apache (PHP) + Node.js (API)

## 🔒 Безопасность

- Все токены и ключи хранятся в `backend/config.env`
- `config.env` добавлен в `.gitignore`
- API запросы проксируются через Node.js backend
- CORS настроен для безопасности
- SSH подключение по паролю
- Nginx как reverse proxy для дополнительной безопасности

## 📊 API Endpoints

### Node.js Backend API (порт 3002)
- `GET /api/health` - Проверка состояния сервера
- `GET /api/menu` - Получение кэшированных данных меню
- `GET /api/poster/*` - Прокси к Poster API

### Poster API (через прокси)
- `GET /api/poster/menu.getCategories` - Категории блюд
- `GET /api/poster/menu.getProducts` - Список блюд

### PHP Frontend
- `GET /` - Главная страница (index.php)
- `GET /menu.php` - Страница меню

## 🎨 Дизайн

- **Цветовая схема**: Оранжево-красные градиенты
- **Типографика**: Inter font family
- **Адаптивность**: Mobile-first подход
- **Анимации**: Плавные переходы и hover-эффекты
- **Иконки**: Lucide React

## 📄 Лицензия

MIT License

## 🤝 Поддержка

Для вопросов и предложений создавайте Issues в репозитории.#   T e s t   d e p l o y m e n t 
 
 #   T e s t   c o m m i t   f o r   a u t o - d e p l o y 
 
 