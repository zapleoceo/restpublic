# North Republic Telegram Bot

Telegram бот для ресторана North Republic, перенесенный с сервера goodzone на NR.

## Функциональность

- **Авторизация пользователей** через Telegram контакты
- **Уведомления о платежах** Sepay в реальном времени
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
SEPAY_API_TOKEN=your_sepay_token
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
- **SepayNotificationService.php** - Сервис уведомлений о транзакциях

## Интеграция с Sepay

Бот автоматически отправляет уведомления о новых входящих платежах в настроенные чаты:
- Rest_publica_bar (7795513546)
- zapleosoft (169510539)

## Безопасность

- Все токены хранятся в переменных окружения
- SSL/TLS для всех API запросов
- Валидация входящих данных
