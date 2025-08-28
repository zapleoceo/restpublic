#!/bin/bash

# North Republic Fast Deployment Script v2.2
# Оптимизированный скрипт с кэшированием и параллельными операциями
set -e

echo "🚀 Начинаем быстрый деплой North Republic v2.2..."

cd /var/www/northrepubli_usr/data/www/northrepublic.me
echo "📁 Рабочая директория: $(pwd)"

# Настройки Git
echo "🔧 Настраиваем Git..."
git config --local core.editor /bin/true
export GIT_EDITOR=/bin/true

echo "📥 Обновляем код из репозитория..."
git pull origin main --allow-unrelated-histories --no-edit || {
    echo "⚠️ Принудительный reset..."
    git fetch origin && git reset --hard origin/main
}

# Проверяем изменения в package.json для условной установки
BACKEND_CHANGED=$(git diff HEAD~1 HEAD --name-only | grep -q "backend/package" && echo "true" || echo "false")
FRONTEND_CHANGED=$(git diff HEAD~1 HEAD --name-only | grep -q "frontend/package" && echo "true" || echo "false")
BOT_CHANGED=$(git diff HEAD~1 HEAD --name-only | grep -q "bot/package" && echo "true" || echo "false")

echo "🛑 Останавливаем PM2 процессы..."
pm2 stop northrepublic-backend northrepublic-bot || echo "PM2 процессы не найдены"
pm2 delete northrepublic-backend northrepublic-bot || echo "PM2 процессы не найдены"

# Параллельная сборка backend и bot (в фоне)
echo "🔧 Начинаем параллельную сборку..."

# Backend в фоне
(
  echo "🔧 Собираем Backend..."
  cd backend
  if [ "$BACKEND_CHANGED" = "true" ] || [ ! -d "node_modules" ]; then
    echo "📦 Устанавливаем зависимости backend..."
    npm ci --production --silent
  else
    echo "⚡ Пропускаем установку backend (без изменений)"
  fi
  mkdir -p ../logs
  echo "✅ Backend готов"
) &
BACKEND_PID=$!

# Bot в фоне
(
  echo "🤖 Собираем Bot..."
  cd bot
  if [ "$BOT_CHANGED" = "true" ] || [ ! -d "node_modules" ]; then
    echo "📦 Устанавливаем зависимости bot..."
    npm ci --production --silent
  else
    echo "⚡ Пропускаем установку bot (без изменений)"
  fi
  npm run build --silent
  echo "✅ Bot готов"
) &
BOT_PID=$!

# Frontend (основной поток, так как копируем файлы)
echo "🔨 Собираем Frontend..."
cd frontend
if [ "$FRONTEND_CHANGED" = "true" ] || [ ! -d "node_modules" ]; then
  echo "📦 Устанавливаем зависимости frontend..."
  npm ci --silent
else
  echo "⚡ Пропускаем установку frontend (без изменений)"
fi
npm run build --silent
echo "📋 Копируем файлы frontend..."
cp -r dist/* ../
cd ..

# Ждем завершения фоновых процессов
echo "⏳ Ждем завершения параллельных задач..."
wait $BACKEND_PID
wait $BOT_PID

echo "🔐 Настраиваем права..."
chmod +x bot/dist/bot.js
chown -R northrepubli_usr:northrepubli_usr .

echo "🚀 Запускаем PM2..."
pm2 start ecosystem-prod.config.js --update-env

# Быстрая проверка (без sleep)
echo "✅ Быстрая проверка статуса..."
for i in {1..10}; do
  if pm2 list | grep -q "northrepublic-backend.*online" && pm2 list | grep -q "northrepublic-bot.*online"; then
    echo "✅ Все процессы запущены успешно!"
    break
  fi
  sleep 1
done

echo "🎉 Быстрый деплой завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "📡 API: http://localhost:3002/api/health"
