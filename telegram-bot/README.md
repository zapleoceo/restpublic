# North Republic Telegram Bot

Telegram бот для ресторана North Republic, перенесенный с сервера goodzone на NR.

## Функциональность

- **Авторизация пользователей** через Telegram контакты
- **Уведомления** в реальном времени
- **Интеграция с основным приложением** через API

## Установка

1. Установите зависимости:
```bash
npm install
```

2. Соберите проект:
```bash
npm run build
```

3. Запустите через PM2:
```bash
npm run pm2:start
```

## Переменные окружения

Создайте файл `.env` в корне проекта:

```env
TELEGRAM_BOT_TOKEN=your_bot_token
BACKEND_URL=https://northrepublic.me
NOTIFICATION_CHAT_IDS=chat_id_1,chat_id_2
NOTIFICATION_CHECK_INTERVAL=30
```

## Управление

- **Запуск**: `npm run pm2:start`
- **Остановка**: `npm run pm2:stop`
- **Перезапуск**: `npm run pm2:restart`
- **Логи**: `npm run pm2:logs`

## Архитектура

- **bot.ts** - Основной файл бота с обработчиками команд
- **TelegramService.php** - PHP сервис для отправки сообщений

## Безопасность

- Все токены хранятся в переменных окружения
- SSL/TLS для всех API запросов
- Валидация входящих данных
