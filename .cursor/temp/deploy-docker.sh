#!/bin/bash

# North Republic Docker Deployment Script v1.0
# Этот скрипт автоматически обновляет код и запускает Docker контейнер
set -e  # Остановить выполнение при ошибке

echo "🚀 Начинаем Docker деплой North Republic v1.0 (Production)..."

cd /var/www/northrepubli_usr/data/www/northrepublic.me
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
pm2 stop all || echo "PM2 процессы не найдены или уже остановлены"
pm2 delete all || echo "PM2 процессы не найдены для удаления"

echo "🐳 Проверяем Docker..."
if ! command -v docker &> /dev/null; then
    echo "❌ Docker не установлен"
    exit 1
fi

echo "🔧 Собираем и запускаем Docker контейнер..."
# Используем sudo для Docker команд
sudo docker compose down || echo "Контейнеры не запущены"
sudo docker compose build --no-cache
sudo docker compose up -d

echo "✅ Проверяем статус деплоя..."
sleep 10

# Проверяем статус контейнеров
if sudo docker compose ps | grep -q "Up"; then
    echo "✅ Docker контейнеры успешно запущены"
else
    echo "❌ Ошибка: контейнеры не запущены"
    echo "📋 Логи контейнеров:"
    sudo docker compose logs
    exit 1
fi

# Проверяем health endpoint
echo "🔍 Проверяем health endpoint..."
if curl -f http://localhost:3002/api/health; then
    echo "✅ Backend API работает"
else
    echo "❌ Backend API не отвечает"
    echo "📋 Логи backend контейнера:"
    sudo docker compose logs northrepublic
    exit 1
fi

echo "🎉 Docker деплой North Republic завершен успешно!"
echo "🌐 Сайт доступен по адресу: https://northrepublic.me"
echo "📡 Backend API: http://localhost:3002/api/health"
echo "📋 Логи контейнеров: sudo docker compose logs"
echo "🔍 Проверить контейнеры: sudo docker compose ps"
