#!/bin/bash

# RestPublic Deployment Script v2.1
# Этот скрипт автоматически обновляет код, собирает приложения и перезапускает сервисы
set -e  # Остановить выполнение при ошибке

echo "🚀 Начинаем деплой RestPublic v2.1..."

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

echo "🛑 Останавливаем PM2 процессы..."
pm2 stop restpublic-backend restpublic-bot || echo "PM2 процессы не найдены или уже остановлены"
pm2 delete restpublic-backend restpublic-bot || echo "PM2 процессы не найдены для удаления"

echo "🔧 Собираем Backend..."
cd backend
npm install
mkdir -p ../logs
cd ..

echo "🔨 Собираем Frontend..."
cd frontend
npm install
npm run build
echo "📋 Копируем собранные файлы frontend..."
cp -r dist/* ../
cd ..

echo "🤖 Собираем Telegram Bot..."
cd bot
npm install
npm run build
cd ..
echo "✅ Бот собран и готов к запуску через PM2"

echo "🔐 Настраиваем права доступа..."
chmod +x bot/dist/bot.js
chown -R goodzone_zap_usr:goodzone_zap_usr .

echo "🚀 Запускаем процессы через PM2..."
pm2 start ecosystem.config.js --update-env
echo "✅ Процессы запущены через PM2"

echo "✅ Проверяем статус деплоя..."
sleep 5

# Проверяем статус PM2 процессов
if pm2 list | grep -q "restpublic-backend.*online"; then
    echo "✅ Backend успешно запущен через PM2"
else
    echo "❌ Ошибка: backend не запущен"
    echo "📋 Последние логи backend:"
    tail -n 20 logs/backend.log
    exit 1
fi

if pm2 list | grep -q "restpublic-bot.*online"; then
    echo "✅ Бот успешно запущен через PM2"
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
echo "📋 Логи backend: pm2 logs restpublic-backend"
echo "📋 Логи бота: pm2 logs restpublic-bot"
echo "🔍 Проверить процессы: pm2 list"
