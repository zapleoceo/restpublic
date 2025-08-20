# RestPublic - Restaurant Menu Application v2.0

Современное веб-приложение и Telegram-бот для отображения меню ресторана с интеграцией Poster POS API v3.

## 🚀 Особенности

- **Современный React Frontend** с роутингом и адаптивным дизайном
- **Node.js Backend** с кэшированием и проксированием API
- **Telegram-бот** на TypeScript с автоматическим переводом
- **Интеграция с Poster API v3** для получения данных меню
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
│   │   │   ├── LoadingSpinner.jsx
│   │   │   └── ErrorBoundary.jsx
│   │   ├── App.jsx
│   │   ├── main.jsx
│   │   └── index.css
│   ├── package.json
│   └── vite.config.js
├── backend/           # Node.js сервер
│   ├── server.js
│   └── package.json
├── bot/               # Telegram-бот
│   ├── src/
│   ├── package.json
│   └── tsconfig.json
├── deploy.sh          # Скрипт автоматического деплоя
├── .env               # Конфигурация (не в репозитории)
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
Создайте файл `.env` в корне проекта:
```env
# Poster API
POSTER_API_TOKEN=<ваш_токен>

# SSH
SSH_HOST=<ваш_ssh_хост>
SSH_USER=<ваш_ssh_пользователь>
SSH_KEY_PATH=<путь_к_ssh_ключу>

# FTP
FTP_HOST=<ваш_ftp_хост>
FTP_USER=<ваш_ftp_пользователь>
FTP_PASS=<ваш_ftp_пароль>

# Telegram Bot
TELEGRAM_BOT_TOKEN=<токен_бота>
```

### 3. Запуск Backend
```bash
cd backend
npm install
npm start
```
Backend будет доступен по адресу: http://localhost:3001

### 4. Запуск Frontend
```bash
cd frontend
npm install
npm run dev
```
Приложение будет доступно по адресу: http://localhost:5173

### 5. Запуск Telegram Bot
```bash
cd bot
npm install
npm run build
npm start
```

## 📱 Использование

### Веб-приложение
- **Главная страница** - Обзор ресторана, статистика, популярные блюда
- **Страница меню** - Полный каталог блюд с поиском и фильтрацией
- **Категории** - Навигация по категориям блюд
- **Поиск** - Поиск блюд по названию и описанию
- **Сортировка** - Сортировка по названию и цене

### Telegram Bot
- Найдите бота в Telegram
- Отправьте `/start` для начала работы
- Используйте кнопки меню для навигации
- Команды: `/menu`, `/help`

## 🔧 Разработка

### Frontend
```bash
cd frontend
npm run dev      # Запуск dev-сервера
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

### Автоматический деплой
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

- Все токены и ключи хранятся в `.env` файле
- `.env` файл добавлен в `.gitignore`
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

Для вопросов и предложений создавайте Issues в репозитории.
