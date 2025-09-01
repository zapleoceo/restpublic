# RestPublic - Restaurant Menu Application v2.0

Современное веб-приложение и Telegram-бот для отображения меню ресторана с интеграцией Poster POS API v3.

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

- **Современный React Frontend** с роутингом и адаптивным дизайном
- **Node.js Backend** с кэшированием и проксированием API
- **Telegram-бот** на TypeScript с автоматическим переводом
- **Интеграция с Poster API v3** для получения данных меню
- **MongoDB** для хранения конфигураций и переводов
- **Автоматический деплой** с обновлением кода и перезапуском сервисов
- **Кэширование данных** для улучшения производительности

## 📁 Структура проекта

```
restpublic/
├── frontend/          # React веб-приложение
│   ├── src/
│   │   ├── components/
│   │   │   ├── Header.jsx
│   │   │   ├── HomePage.jsx
│   │   │   ├── MenuPage.jsx
│   │   │   ├── AdminPanel.jsx
│   │   │   ├── LoadingSpinner.jsx
│   │   │   └── ErrorBoundary.jsx
│   │   ├── App.jsx
│   │   ├── main.jsx
│   │   └── index.css
│   ├── package.json
│   └── vite.config.js
├── backend/           # Node.js сервер
│   ├── server.js
│   ├── routes/
│   │   └── admin.js
│   ├── services/
│   │   └── mongoService.js
│   ├── scripts/
│   │   └── migrate-to-mongodb.js
│   └── package.json
├── bot/               # Telegram-бот
│   ├── src/
│   ├── package.json
│   └── tsconfig.json
├── deploy.sh          # Скрипт автоматического деплоя
├── .cursor/env.txt    # Конфигурация копия (не в репозитории)
├── .cursor/.env    # Конфигурация (не в репозитории)
└── README.md
```

## 🛠️ Технологии

### Frontend
- **React 18** - Основной фреймворк
- **React Router** - Клиентская маршрутизация
- **Vite** - Сборщик и dev-сервер
- **Tailwind CSS** - Стилизация
- **Lucide React** - Иконки
- **Axios** - HTTP-клиент

### Backend
- **Node.js** - Серверная среда
- **Express.js** - Веб-фреймворк
- **MongoDB** - База данных для конфигураций
- **Axios** - HTTP-клиент для API
- **CORS** - Обработка CORS
- **Кэширование** - Встроенное кэширование данных

### Telegram Bot
- **Telegraf** - Telegram Bot API
- **TypeScript** - Типизация
- **Axios** - HTTP-клиент

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
 #   T e s t   c o m m i t   f o r   a u t o - d e p l o y  
 