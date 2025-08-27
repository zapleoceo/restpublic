# Инструкции по деплою North Republic

## 🌍 Окружения

### Development (Разработка)
- **Домен**: https://goodzone.zapleo.com
- **Сервер**: 159.253.23.113
- **Пользователь**: goodzone_zap_usr
- **Путь**: /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com
- **Деплой**: Автоматический через GitHub Actions

### Production (Продакшен)
- **Домен**: https://northrepublic.me
- **Пользователь**: northrepubli_usr
- **База данных**: northrepubli
- **Деплой**: Ручной при необходимости

## 🚀 Деплой на Development (goodzone.zapleo.com)

### Автоматический деплой (рекомендуется)
```bash
# При push в main ветку GitHub Actions автоматически деплоит
git push origin main
```

### Ручной деплой (если нужно)

#### 1. Подключение к серверу
```bash
ssh -i ~/.ssh/goodzone goodzone_zap_usr@159.253.23.113
```

#### 2. Переход в рабочую директорию
```bash
cd /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com
```

#### 3. Клонирование/обновление репозитория
```bash
# Если репозиторий еще не клонирован
git clone https://github.com/zapleoceo/restpublic.git .

# Если репозиторий уже существует
git pull origin main --allow-unrelated-histories --no-edit
```

#### 4. Настройка переменных окружения
```bash
# Создать .env файл из .cursor/env.txt
cp .cursor/env.txt .env
# Отредактировать .env файл с реальными токенами
nano .env
```

#### 5. Сборка и запуск Backend
```bash
cd backend
npm install
mkdir -p ../logs
nohup node server.js > ../logs/backend.log 2>&1 &
cd ..
```

#### 6. Сборка и запуск Frontend
```bash
cd frontend
npm install
npm run build

# Копировать собранные файлы в корень сайта
cp -r dist/* ../
cd ..
```

#### 7. Сборка и запуск Telegram Bot
```bash
cd bot
npm install
npm run build

# Запуск бота в фоне
nohup node dist/bot.js > ../logs/bot.log 2>&1 &
cd ..
```

#### 8. Настройка прав доступа
```bash
chmod +x bot/dist/bot.js
chown -R goodzone_zap_usr:goodzone_zap_usr .
```

## 🚀 Деплой на Production (northrepublic.me)

### Доступы (из .cursor/env.txt)
```bash
# SSH пользователь
northrepubli_usr
# Пароль: Qcgn_d8nlF+I%@b7

# База данных
northrepubli
# Логин: northrepubli
# Пароль: L9T:n2S7<|o:~d=S

# FTP аккаунт
northrepubli
# Пароль: a|[SP_H:r1!V7V!o
```

### Процесс деплоя
```bash
# 1. Подключение к серверу
ssh northrepubli_usr@[SERVER_IP]

# 2. Переход в рабочую директорию
cd /path/to/northrepublic.me

# 3. Обновление кода
git pull origin main

# 4. Настройка переменных окружения
cp .cursor/env.txt .env

# 5. Сборка и запуск
./deploy.sh
```

## 📝 Логи

### Development
Логи проекта находятся в:
```
/var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com/logs/
```

- `bot.log` - логи Telegram бота
- `backend.log` - логи backend сервера
- `nginx.log` - логи веб-сервера

### Production
Логи указаны в конфигурации сервера northrepublic.me

## 🔄 Автоматический деплой для Development

Для автоматического деплоя можно создать скрипт:

```bash
#!/bin/bash
cd /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com

# Обновление кода
git pull origin main --allow-unrelated-histories --no-edit

# Остановка старых процессов
pkill -f "node dist/bot.js" || echo "Процессы бота не найдены"
pkill -f "node server.js" || echo "Backend процессы не найдены"

# Сборка и запуск backend
cd backend
npm install
mkdir -p ../logs
nohup node server.js > ../logs/backend.log 2>&1 &
cd ..

# Сборка frontend
cd frontend
npm install
npm run build
cp -r dist/* ../
cd ..

# Сборка и перезапуск бота
cd bot
npm install
npm run build
nohup node dist/bot.js > ../logs/bot.log 2>&1 &
cd ..

# Настройка прав доступа
chmod +x bot/dist/bot.js
chown -R goodzone_zap_usr:goodzone_zap_usr .

echo "Deployment completed!"
```

## 🌐 Проверка работы

### Development
- Веб-сайт: https://goodzone.zapleo.com
- Проверить логи: `tail -f /var/www/goodzone_zap_usr/data/logs/bot.log`

### Production
- Веб-сайт: https://northrepublic.me
- Проверить логи: согласно конфигурации сервера

## 🔧 Устранение неполадок

### Если backend не запускается:
```bash
cd backend
node server.js
# Проверить ошибки в консоли
```

### Если бот не запускается:
```bash
cd bot
node dist/bot.js
# Проверить ошибки в консоли
```

### Если frontend не собирается:
```bash
cd frontend
npm install
npm run build
# Проверить ошибки сборки
```

### Проверка статуса процессов:
```bash
ps aux | grep node
ps aux | grep nginx
```

### Проверка логов:
```bash
tail -f logs/backend.log
tail -f logs/bot.log
```

## 🔐 Безопасность

- Все токены и ключи хранятся в `.cursor/env.txt`
- `.cursor/env.txt` добавлен в `.gitignore`
- SSH подключение по ключу для Development
- Парольное подключение для Production
