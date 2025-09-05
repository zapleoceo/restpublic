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

# Копируем PHP файлы
echo "📁 Копирую PHP файлы..."
cd ..
cp php/index.php .
cp php/menu.php .
cp -r php/components .
echo "✅ PHP файлы скопированы"

# Копируем template файлы
echo "📁 Копирую template файлы..."
cp -r template/css .
cp -r template/js .
cp -r template/images .
echo "✅ Template файлы скопированы"

# Копируем иконки и favicon
echo "🔗 Копирую иконки и favicon..."
cp template/apple-touch-icon.png .
cp template/favicon-16x16.png .
cp template/favicon-32x32.png .
cp template/favicon.ico .
echo "✅ Иконки скопированы"

# Перезапускаем сервисы
echo "🔄 Перезапускаю сервисы..."
pm2 restart all
echo "✅ Сервисы перезапущены"

# Показываем статус
echo "📊 Статус PM2:"
pm2 list

# Коммитим изменения
echo "🔄 Коммичу изменения..."
git add .
git commit -m "Deploy: PHP frontend with template styles" || echo "⚠️ Нет изменений для коммита"
git push origin main
echo "✅ Изменения отправлены в репозиторий"

echo ""
echo "🎉 Полный деплой на сервер завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "🧪 Тестируйте сайт через 30 секунд"
