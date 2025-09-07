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
# Определяем текущую ветку
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Текущая ветка: $CURRENT_BRANCH"

# Пытаемся обновить с main, если не получается - с master
if git pull origin main 2>/dev/null; then
    echo "✅ Код обновлен с main"
else
    echo "⚠️ Не удалось обновить с main, пробую master..."
    if git pull origin master 2>/dev/null; then
        echo "✅ Код обновлен с master"
    else
        echo "❌ Ошибка обновления кода"
        exit 1
    fi
fi

# Устанавливаем зависимости backend
echo "📦 Устанавливаю зависимости backend..."
cd backend
npm install
echo "✅ Backend зависимости установлены"

# Устанавливаем зависимости PHP (MongoDB драйвер)
echo "📦 Устанавливаю зависимости PHP..."
cd ..
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
    echo "✅ PHP зависимости установлены"
    
    # Инициализируем кэш меню
    echo "🔄 Инициализирую кэш меню..."
    if [ -f "php/init-cache.php" ]; then
        php php/init-cache.php
        echo "✅ Кэш меню инициализирован"
    else
        echo "⚠️ init-cache.php не найден"
    fi
else
    echo "⚠️ composer.json не найден, пропускаю установку PHP зависимостей"
fi

# Копируем только необходимые файлы в корень (если их нет)
echo "📁 Проверяю структуру файлов..."
cd ..

# Копируем PHP файлы в корень (для совместимости)
if [ ! -f "index.php" ]; then
    cp php/index.php .
    echo "✅ index.php скопирован в корень"
fi

if [ ! -f "menu.php" ]; then
    cp php/menu.php .
    echo "✅ menu.php скопирован в корень"
fi

# Копируем компоненты (если их нет)
if [ ! -d "components" ]; then
    cp -r php/components .
    echo "✅ components скопированы"
fi

# Копируем template файлы (если их нет)
if [ ! -d "css" ]; then
    cp -r template/css .
    echo "✅ CSS скопированы"
fi

if [ ! -d "js" ]; then
    cp -r template/js .
    echo "✅ JS скопированы"
fi

if [ ! -d "images" ]; then
    cp -r template/images .
    echo "✅ Images скопированы"
fi

# Копируем иконки (если их нет)
if [ ! -f "apple-touch-icon.png" ]; then
    cp template/apple-touch-icon.png .
    echo "✅ apple-touch-icon.png скопирован"
fi

if [ ! -f "favicon.ico" ]; then
    cp template/favicon.ico .
    echo "✅ favicon.ico скопирован"
fi

echo "✅ Структура файлов проверена"

# Перезапускаем сервисы
echo "🔄 Перезапускаю сервисы..."
pm2 restart all
echo "✅ Сервисы перезапущены"

# Показываем статус
echo "📊 Статус PM2:"
pm2 list

# Проверяем статус Git (без коммита, согласно правилам)
echo "📊 Статус Git:"
git status --porcelain || echo "✅ Рабочая директория чистая"

# Проверяем доступность API
echo "🔍 Проверяю API..."
if curl -s http://127.0.0.1:3002/api/health > /dev/null; then
    echo "✅ Backend API доступен"
else
    echo "⚠️ Backend API недоступен"
fi

echo ""
echo "🎉 Полный деплой на сервер завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "📝 Не забудьте перезагрузить Nginx: sudo systemctl reload nginx"
echo "🧪 Тестируйте сайт через 30 секунд"
