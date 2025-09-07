#!/bin/bash

# Быстрый деплой North Republic
# Использование: bash deploy-fast.sh (на сервере)
# Автор: AI Assistant
# Версия: 1.0

set -e

echo "⚡ Запускаю быстрый деплой North Republic..."

# Засекаем время начала
START_TIME=$(date +%s)

# Переходим в рабочую директорию
cd /var/www/northrepubli_usr/data/www/northrepublic.me

# Очищаем изменения и обновляем код
echo "📥 Обновляю код..."
git reset --hard HEAD
git clean -fd
git pull origin main

# Быстрая установка зависимостей (только если нужно)
echo "📦 Проверяю зависимости..."

# Backend зависимости
if [ -d "backend" ] && [ -f "backend/package.json" ]; then
    cd backend
    if [ ! -d "node_modules" ] || [ "package.json" -nt "node_modules" ]; then
        echo "📦 Обновляю backend зависимости..."
        npm ci --only=production --prefer-offline --silent
    fi
    cd ..
fi

# PHP зависимости
if [ -f "composer.json" ]; then
    if [ ! -d "vendor" ] || [ "composer.json" -nt "vendor" ]; then
        echo "📦 Обновляю PHP зависимости..."
        composer install --no-dev --optimize-autoloader --no-scripts --quiet
    fi
fi

# Копируем только измененные файлы
echo "📁 Синхронизирую файлы..."

# PHP файлы
[ -f "php/index.php" ] && [ (! -f "index.php" || "php/index.php" -nt "index.php") ] && cp php/index.php .
[ -f "php/menu.php" ] && [ (! -f "menu.php" || "php/menu.php" -nt "menu.php") ] && cp php/menu.php .

# Компоненты
[ -d "php/components" ] && [ (! -d "components" || "php/components" -nt "components") ] && cp -r php/components .

# Template файлы
[ -d "template/css" ] && [ (! -d "css" || "template/css" -nt "css") ] && cp -r template/css .
[ -d "template/js" ] && [ (! -d "js" || "template/js" -nt "js") ] && cp -r template/js .
[ -d "template/images" ] && [ (! -d "images" || "template/images" -nt "images") ] && cp -r template/images .

# Иконки
[ -f "template/apple-touch-icon.png" ] && [ (! -f "apple-touch-icon.png" || "template/apple-touch-icon.png" -nt "apple-touch-icon.png") ] && cp template/apple-touch-icon.png .
[ -f "template/favicon.ico" ] && [ (! -f "favicon.ico" || "template/favicon.ico" -nt "favicon.ico") ] && cp template/favicon.ico .

# Перезапускаем сервисы
echo "🔄 Перезапускаю сервисы..."
pm2 restart all > /dev/null 2>&1 || true

# Вычисляем время выполнения
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
MINUTES=$((DURATION / 60))
SECONDS=$((DURATION % 60))

echo ""
echo "✅ Быстрый деплой завершен за ${MINUTES}m ${SECONDS}s"
echo "🌐 Сайт: https://northrepublic.me"
echo "🧪 Тестируйте сайт через 10 секунд"
