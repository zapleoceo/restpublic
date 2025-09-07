# North Republic - Restaurant Website v2.0

Современный веб-сайт ресторана North Republic с интеграцией Poster POS API v3, адаптивным дизайном и системой кэширования меню.

## 🌍 Окружения

### Development (Разработка)
- **Домен**: https://goodzone.zapleo.com
- **Сервер**: 159.253.23.113
- **Пользователь**: goodzone_zap_usr
- **Путь**: /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com
- **База данных**: goodzone (MongoDB)

### Production (Продакшен)
- **Домен**: https://northrepublic.me
- **Сервер**: (указан в .cursor/env.txt)
- **Пользователь**: northrepubli_usr
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

- **PHP Frontend** с адаптивным дизайном и мобильным меню
- **Node.js Backend** с кэшированием и проксированием Poster API
- **Интеграция с Poster API v3** для получения данных меню и статистики продаж
- **MongoDB** для кэширования данных меню
- **Локальные шрифты** Serati для уникального дизайна
- **Автоматический деплой** с обновлением кода и перезапуском сервисов
- **Система сортировки** по популярности, цене и алфавиту
- **Hover эффекты** и анимации для улучшения UX

## 📁 Структура проекта

```
NRsite/
├── backend/           # Node.js API сервер
│   ├── server.js
│   ├── routes/
│   │   ├── poster.js
│   │   ├── menu.js
│   │   └── cache.js
│   ├── services/
│   │   └── posterService.js
│   └── package.json
├── classes/           # PHP классы
│   └── MenuCache.php
├── components/        # PHP компоненты
│   ├── header.php
│   ├── footer.php
│   └── cart.php
├── css/              # Стили
│   ├── styles.css
│   ├── vendor.css
│   └── custom.css
├── fonts/            # Локальные шрифты
│   ├── Serati.ttf
│   ├── SeratiItalic.ttf
│   └── License.txt
├── images/           # Основные изображения
│   ├── logo.png
│   ├── shawa.png
│   └── ...
├── js/               # JavaScript
│   ├── main.js
│   └── plugins.js
├── template/         # Шаблоны и ресурсы
│   └── images/
├── index.php         # Главная страница
├── menu.php          # Страница меню
├── deploy.sh         # Скрипт деплоя
└── README.md
```

## 🛠️ Технологии

### Frontend
- **PHP 8.x** - Основной язык серверной части
- **HTML5/CSS3** - Разметка и стили
- **JavaScript (Vanilla)** - Клиентская логика
- **MongoDB PHP Driver** - Работа с базой данных
- **Composer** - Управление зависимостями

### Backend
- **Node.js** - Серверная среда
- **Express.js** - Веб-фреймворк
- **MongoDB** - База данных для кэширования
- **Axios** - HTTP-клиент для API
- **CORS** - Обработка CORS
- **PM2** - Менеджер процессов

### Шрифты и дизайн
- **Roboto Flex** - Основной шрифт (Google Fonts)
- **Serati** - Декоративный шрифт (локальный)
- **CSS Grid/Flexbox** - Современная верстка
- **Адаптивный дизайн** - Mobile-first подход

## 🚀 Быстрый старт

### 1. Клонирование репозитория
```bash
git clone https://github.com/zapleoceo/restpublic.git
cd restpublic
```

### 2. Настройка переменных окружения
Все конфигурационные данные находятся в файле `.cursor/env.txt`:
- Poster API токены
- SSH доступы для development и production
- FTP доступы
- Telegram Bot токены
- SePay API токены

### 3. Разработка
```bash
# Разработка ведется только на локалке
# Все изменения через Git
# Автодеплой на development при push в main
# Ручной деплой на production при необходимости
```

## 📱 Использование

### Development (goodzone.zapleo.com)
- **Главная страница** - Обзор ресторана, статистика, популярные блюда
- **Страница меню** - Полный каталог блюд с поиском и фильтрацией
- **Категории** - Навигация по категориям блюд
- **Поиск** - Поиск блюд по названию и описанию
- **Сортировка** - Сортировка по названию и цене

### Production (northrepublic.me)
- Продакшен версия для клиентов
- Стабильная версия приложения

### Telegram Bot
- Найдите бота в Telegram
- Отправьте `/start` для начала работы
- Используйте кнопки меню для навигации
- Команды: `/menu`, `/help`

## 🔧 Разработка

### Frontend
```bash
cd frontend
npm run dev      # Запуск dev-сервера (только для тестирования)
npm run build    # Сборка для продакшена
npm run preview  # Предпросмотр сборки
npm run lint     # Проверка кода
```

### Backend
```bash
cd backend
npm start        # Запуск продакшен версии
npm run dev      # Запуск в режиме разработки
```

### Bot
```bash
cd bot
npm run dev      # Запуск в режиме разработки
npm run build    # Компиляция TypeScript
npm start        # Запуск продакшен версии
```

## 📦 Деплой

### Development (Автоматический)
```bash
# На сервере
cd /var/www/restbublic_z_usr/data/www/goodzone.zapleo.com
./deploy.sh
```

Скрипт автоматически:
1. Обновляет код из репозитория
2. Останавливает старые процессы
3. Собирает и запускает backend
4. Собирает frontend
5. Собирает и запускает бота
6. Проверяет статус всех сервисов

### Ручной деплой
```bash
# Обновление кода
git pull origin master

# Backend
cd backend && npm install && npm start

# Frontend
cd frontend && npm install && npm run build
cp -r dist/* ../

# Bot
cd bot && npm install && npm run build && npm start
```

## 🔒 Безопасность

- Все токены и ключи хранятся в `.cursor/env.txt`
- `.cursor/env.txt` добавлен в `.gitignore`
- API запросы проксируются через backend
- CORS настроен для безопасности
- SSH подключение по ключу

## 📊 API Endpoints

### Backend API
- `GET /api/health` - Проверка состояния сервера
- `GET /api/menu` - Получение кэшированных данных меню
- `GET /api/poster/*` - Прокси к Poster API

### Poster API (через прокси)
- `GET /api/poster/menu.getCategories` - Категории блюд
- `GET /api/poster/menu.getProducts` - Список блюд

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
 #   T e s t   d e p l o y m e n t   0 9 / 0 7 / 2 0 2 5   1 2 : 4 7 : 3 6 
 
 