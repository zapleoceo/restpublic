#!/bin/bash

# Полноценный скрипт деплоя на сервер
# Использование: bash deploy.sh (на сервере)

set -e  # Остановка при ошибке

echo "🚀 Начинаю полный деплой на сервер..."

# Проверяем, что мы на сервере
if [ ! -d "/var/www/northrepubli_usr/data/www/northrepublic.me" ]; then
    echo "❌ Ошибка: Запустите скрипт на сервере"
    exit 1
fi

cd /var/www/northrepubli_usr/data/www/northrepublic.me

echo "📍 Рабочая директория: $(pwd)"

# Очищаем все изменения на сервере
echo "🗑️  Очищаю все изменения на сервере..."
git reset --hard HEAD
git clean -fd
echo "✅ Сервер очищен"

# Обновляем код с Git
echo "📥 Обновляю код с Git..."
git pull origin main
echo "✅ Код обновлен с Git"

# Устанавливаем зависимости backend
echo "📦 Устанавливаю зависимости backend..."
cd backend
npm install
echo "✅ Backend зависимости установлены"

# Собираем frontend
echo "🔨 Собираю frontend..."
cd ../frontend
npm run build
echo "✅ Frontend собран"

# Копируем новые файлы
echo "📁 Копирую новые файлы..."
cd ..
rm -rf static
cp -r frontend/build/static .
echo "✅ Static файлы скопированы"

# Копируем index.html
echo "📄 Копирую index.html..."
cp frontend/build/index.html .
echo "✅ index.html скопирован"

# Перезапускаем сервисы
echo "🔄 Перезапускаю сервисы..."
pm2 restart all
echo "✅ Сервисы перезапущены"

# Показываем статус
echo "📊 Статус PM2:"
pm2 list

echo ""
echo "🎉 Полный деплой на сервер завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "🧪 Тестируйте сайт через 30 секунд"
