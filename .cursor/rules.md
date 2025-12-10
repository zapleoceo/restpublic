# Правила работы с проектом Veranda

**Дата последнего обновления:** 03.10.2025  
**Статус:** После миграции на veranda.my

---

## 🎯 Основные принципы

1. **Чистота кода** - минимум кода, максимум функциональности
2. **Безопасность** - все секреты только в `.env`
3. **Не создавать лишнее** - только необходимые компоненты
4. **Следовать структуре** - не нарушать архитектуру

---

## 🚀 Git Workflow

### Работа с кодом:
```bash
# Локально - редактируем и тестируем
git add .
git commit -m "Описание изменений"
git push origin main

# На сервере - только pull
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my && git pull origin main"
```

### Важно:
- **НЕ коммитить** тестовые файлы
- Тестовые и временные файлы создавтаь только в папке TEMP 
- **НЕ коммитить** `.env` файлы
- **НЕ делать** изменений напрямую на сервере

---

## 📂 Структура проекта

```
veranda.my/
├── index.php           # Главная страница
├── menu.php            # Страница меню (v1)
├── menu2.php           # Страница меню (v2, основная)
├── events.php          # Календарь событий
├── webhook-handler.php # Webhook для SePay платежей
│
├── backend/            # Node.js Backend API
│   ├── server.js       # Express сервер (порт 3003)
│   ├── routes/         # API endpoints
│   ├── services/       # PosterService
│   └── config.env      # Конфигурация backend
│
├── classes/            # PHP классы
│   ├── MenuCache.php   # Кэш меню из MongoDB
│   ├── EventsService.php
│   ├── TranslationService.php
│   ├── UserAuth.php
│   └── ...
│
├── components/         # PHP компоненты
│   ├── header.php
│   ├── footer.php
│   ├── cart.php
│   └── events-widget.php
│
├── admin/              # Админ панель
│   ├── index.php       # Главная админки
│   ├── auth/           # Авторизация
│   ├── events/         # Управление событиями
│   ├── pages/          # Управление текстами
│   └── settings/       # Настройки
│
├── api/                # PHP API endpoints
│   ├── menu.php
│   ├── events.php
│   ├── check-phone.php
│   └── ...
│
├── telegram-bot/       # Telegram бот
│   ├── src/bot.ts      # TypeScript код
│   ├── dist/bot.js     # Скомпилированный JS
│   └── ecosystem.config.cjs
│
├── css/                # Стили
├── js/                 # JavaScript
├── images/             # Изображения
└── .env                # Переменные окружения (НЕ в Git!)
```

---

## 🔧 Технологии

- **Frontend:** PHP 8.x, Vanilla JavaScript, CSS3
- **Backend:** Node.js 18+, Express.js
- **База данных:** MongoDB 7.0
- **Кэширование:** MongoDB + in-memory
- **Внешние API:** Poster POS API v3
- **Платежи:** SePay API
- **Уведомления:** Telegram Bot API
- **Деплой:** PM2, Git, SSH

---

## ⚙️ Переменные окружения

**Файл:** `.env` (корень проекта)

```env
# Backend
BACKEND_URL=http://localhost:3003

# MongoDB (основная БД: veranda2026:27026, резервная: veranda:27017)
MONGODB_URL=mongodb://localhost:27026
MONGODB_DB_NAME=veranda2026

# Poster API
POSTER_API_TOKEN=<токен>

# SePay
SEPAY_API_TOKEN=<токен>
SEPAY_INCOMING_API_TOKEN=<токен>

# Telegram
TELEGRAM_BOT_TOKEN=<токен>
TELEGRAM_GROUP_ID=<id>

# API Auth
API_AUTH_TOKEN=<токен>
```

**Важно:** Эти значения уже настроены на сервере!

---

## 🚀 Деплой

### Быстрый деплой (обычный):
```bash
# Локально
git push origin main

# На сервере
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my && git pull origin main"
```

### Полный деплой (с перезапуском):
```bash
ssh veranda "cd /var/www/veranda_my_usr/data/www/veranda.my && ./deploy.sh"
```

### Перезапуск сервисов:
```bash
ssh veranda "pm2 restart all"
```

---

## 📊 Логи

### PM2 логи:
```bash
# Все логи
ssh veranda "pm2 logs"

# Только backend
ssh veranda "pm2 logs veranda-backend --lines 100"

# Только telegram bot  
ssh veranda "pm2 logs veranda-telegram-bot --lines 100"
```

### PHP логи:
```bash
# Admin логи (в MongoDB)
ssh veranda "mongosh veranda --eval 'db.admin_logs.find().sort({timestamp:-1}).limit(10)'"

# SePay webhook логи
ssh veranda "tail -f /var/www/veranda_my_usr/data/www/veranda.my/logs/sepay_webhook.log"

# Backend и Apache логи
ssh veranda "tail -f /var/www/veranda_my_usr/data/logs/*"
```

### Статус сервисов:
```bash
ssh veranda "pm2 status"
```

---

## 🔐 SSH Подключение

```bash
# Veranda (frontend + backend)
ssh veranda

# North Republic (только редирект)
ssh nr
```

**Конфиг:** `~/.ssh/config`

---

## 📝 Коммиты

### Хороший коммит:
```
Fix menu cache update endpoint port to use env variable
```

### Плохой коммит:
```
fix
```

**Правило:** Описывайте ЧТО и ЗАЧЕМ изменили

---

## ⚠️ Важные замечания

1. **Тестовые файлы:** Создавайте локально, не коммитьте
2. **База данных:** Основная БД `veranda2026:27026`, резервная `veranda:27017` (автоматический fallback)
3. **Порт backend:** 3003 (не 3002!)
4. **Webhook:** https://veranda.my/webhook-handler.php
5. **Cron задачи:** На сервере, обновляют кэш каждые 30 минут

---

## 🆘 Проблемы и решения

### Сайт не работает:
```bash
ssh veranda "pm2 status"
ssh veranda "pm2 logs --lines 50"
```

### Меню не обновляется:
```bash
curl -X POST http://localhost:3003/api/cache/update-menu
```

### Backend упал:
```bash
ssh veranda "pm2 restart veranda-backend"
```

### Telegram bot не отвечает:
```bash
ssh veranda "pm2 restart veranda-telegram-bot"
```

---

## 📚 Полная документация

См. `.cursor/PROJECT_DOCUMENTATION.md`

