# North Republic - Развлекательный комплекс

Современный веб-сайт для развлекательного комплекса "Республика Север" с рестораном, кинотеатром под открытым небом и множеством активностей.

## 🚀 Технологии

### Frontend
- **React 18** - Основной фреймворк
- **Vite** - Сборщик и dev сервер
- **Tailwind CSS** - Стилизация
- **React Router** - Клиентская маршрутизация
- **i18next** - Мультиязычность (RU/EN/VI)
- **Lucide React** - Иконки
- **Axios** - HTTP клиент

### Backend
- **Node.js** - Серверная платформа
- **Express.js** - Веб-фреймворк
- **MongoDB** - База данных
- **Poster API v3** - Интеграция с системой заказов
- **Telegram Bot API** - Авторизация через бота
- **PM2** - Управление процессами

## 📁 Структура проекта

```
NRsite/
├── frontend/                 # React приложение
│   ├── src/
│   │   ├── components/       # React компоненты
│   │   ├── pages/           # Страницы приложения
│   │   ├── hooks/           # Кастомные хуки
│   │   ├── services/        # API сервисы
│   │   ├── utils/           # Утилиты
│   │   ├── constants/       # Константы
│   │   └── styles/          # Стили
│   ├── public/              # Статические файлы
│   └── template/            # Шаблон Lounge
├── backend/                 # Node.js сервер (будет создан)
├── bot/                     # Telegram бот (будет создан)
└── .cursor/                 # Конфигурация и секреты
```

## 🌍 Окружения

### Production
- **Домен**: https://northrepublic.me
- **Сервер**: 159.253.23.113
- **Автодеплой**: GitHub Actions при push в main

### Development
- **Локальный сервер**: http://localhost:5173
- **API**: http://localhost:3002

## 🚀 Быстрый старт

### Frontend
```bash
cd frontend
npm install
npm run dev
```

### Backend (будет создан)
```bash
cd backend
npm install
npm run dev
```

## 📱 Функциональность

### Основные возможности
- ✅ **Мультиязычность** - Поддержка русского, английского и вьетнамского языков
- ✅ **Адаптивный дизайн** - Оптимизация для всех устройств
- ✅ **Секции сайта** - Intro, About, Services, Events, Testimonials
- ✅ **Переключение языков** - Компонент с флагами стран
- ✅ **Плавная прокрутка** - Навигация по секциям
- ✅ **Preloader** - Анимация загрузки

### Планируемые функции
- 🔄 **Админ панель** - Управление контентом
- 🔄 **WYSIWYG редактор** - Редактирование секций
- 🔄 **Календарь событий** - Страница с событиями
- 🔄 **Интеграция с API** - Подключение к бэкенду
- 🔄 **Telegram авторизация** - Вход через бота

## 🎨 Дизайн

Основан на шаблоне **Lounge - Restaurant Website Template** с адаптацией под развлекательный комплекс:

### Цветовая схема
- **Primary**: Зеленые тона (#468672)
- **Secondary**: Бежевые тона (#b1885e)
- **Neutral**: Серые тона (#5f6362)

### Типографика
- **Заголовки**: Playfair Display
- **Основной текст**: Roboto Flex

## 🔧 Разработка

### Правила
- Вся разработка ведется в ветке `main`
- Автодеплой на production при push в main
- Секреты хранятся в `.cursor/env.txt`
- Версии повышаются перед каждым push

### Команды
```bash
# Разработка
npm run dev

# Сборка
npm run build

# Предпросмотр сборки
npm run preview

# Линтинг
npm run lint
```

## 📊 API Endpoints

### Основные
- `GET /api/health` - Проверка состояния
- `GET /api/sections` - Получение секций сайта
- `GET /api/events` - Получение событий
- `GET /api/menu` - Получение меню

### Админ (планируется)
- `PUT /api/admin/sections/:name` - Обновление секции
- `POST /api/admin/upload` - Загрузка изображений
- `GET /api/admin/translations` - Получение переводов

## 🌐 Деплой

### Автоматический деплой
1. Push в ветку `main`
2. GitHub Actions автоматически деплоит на сервер
3. Ожидание 2 минуты для завершения деплоя
4. Проверка на https://northrepublic.me

### Ручной деплой
```bash
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && ./deploy.sh"
```

## 🔍 Отладка

### Логи
```bash
# Backend логи
ssh nr "pm2 logs northrepublic-backend --lines 50"

# Bot логи
ssh nr "pm2 logs northrepublic-bot --lines 50"

# Файловые логи
ssh nr "tail -f /var/www/northrepubli_usr/data/www/northrepublic.me/logs/backend.log"
```

### Перезапуск сервисов
```bash
# Backend
ssh nr "pm2 restart northrepublic-backend"

# Bot
ssh nr "pm2 restart northrepublic-bot"

# Все сервисы
ssh nr "pm2 restart all"
```

## 📝 Лицензия

Проект разработан для развлекательного комплекса "Республика Север".

---

**Версия**: 1.0.0  
**Дата**: 2025-01-27  
**Статус**: В разработке