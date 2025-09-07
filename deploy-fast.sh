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

# Проверяем структуру файлов (файлы уже в корне после очистки)
echo "📁 Проверяю структуру файлов..."

# Проверяем наличие основных файлов
if [ ! -f "index.php" ] || [ ! -f "menu.php" ] || [ ! -d "components" ] || [ ! -d "classes" ] || [ ! -d "css" ] || [ ! -d "js" ]; then
    echo "❌ Ошибка: файлы не найдены в корне проекта"
    exit 1
fi

echo "✅ Структура файлов корректна (файлы уже в корне)"

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
