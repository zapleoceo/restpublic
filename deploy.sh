#!/bin/bash

# RestPublic Deployment Script v2.0
# Этот скрипт автоматически обновляет код, собирает приложения и перезапускает сервисы
set -e  # Остановить выполнение при ошибке

echo "🚀 Начинаем деплой RestPublic v2.0..."

cd /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com
echo "📁 Рабочая директория: $(pwd)"

# Настройки Git для предотвращения открытия редактора
echo "🔧 Настраиваем Git..."
git config --local core.editor /bin/true
git config --local merge.tool /bin/true
export GIT_EDITOR=/bin/true
export EDITOR=/bin/true

echo "📥 Обновляем код из репозитория..."
# Используем --allow-unrelated-histories для решения проблемы с очищенной историей
git pull origin main --allow-unrelated-histories --no-edit || {
    echo "⚠️ Обычный pull не удался, выполняем принудительный reset..."
    git fetch origin
    git reset --hard origin/main
}

echo "🛑 Останавливаем старые процессы..."
pkill -f "node dist/bot.js" || echo "Процессы бота не найдены"
pkill -f "node server.js" || echo "Backend процессы не найдены"

echo "🔧 Собираем и запускаем Backend..."
cd backend
npm install
mkdir -p ../logs
echo "🚀 Запускаем backend..."
nohup node server.js > ../logs/backend.log 2>&1 &
BACKEND_PID=$!
echo "Backend запущен с PID: $BACKEND_PID"
cd ..

echo "🔨 Собираем Frontend..."
cd frontend
npm install
npm run build
echo "📋 Копируем собранные файлы frontend..."
cp -r dist/* ../
cd ..

echo "🤖 Собираем и запускаем Telegram Bot..."
cd bot
npm install
npm run build
echo "🚀 Запускаем бота..."
nohup node dist/bot.js > ../logs/bot.log 2>&1 &
BOT_PID=$!
echo "Бот запущен с PID: $BOT_PID"
cd ..

echo "🔐 Настраиваем права доступа..."
chmod +x bot/dist/bot.js
chown -R goodzone_zap_usr:goodzone_zap_usr .

echo "✅ Проверяем статус деплоя..."
sleep 3

if ps -p $BACKEND_PID > /dev/null; then
    echo "✅ Backend успешно запущен (PID: $BACKEND_PID)"
else
    echo "❌ Ошибка: backend не запущен"
    echo "📋 Последние логи backend:"
    tail -n 20 logs/backend.log
    exit 1
fi

if ps -p $BOT_PID > /dev/null; then
    echo "✅ Бот успешно запущен (PID: $BOT_PID)"
else
    echo "❌ Ошибка: бот не запущен"
    echo "📋 Последние логи бота:"
    tail -n 20 logs/bot.log
    exit 1
fi

if [ -f "index.html" ]; then
    echo "✅ Frontend файлы успешно развернуты"
else
    echo "❌ Ошибка: frontend файлы не найдены"
    exit 1
fi

echo "🎉 Деплой завершен успешно!"
echo "🌐 Сайт доступен по адресу: https://goodzone.zapleo.com"
echo "📡 Backend API: http://localhost:3001/api/health"
echo "📋 Логи backend: tail -f logs/backend.log"
echo "📋 Логи бота: tail -f logs/bot.log"
echo "🔍 Проверить процессы: ps aux | grep node"
