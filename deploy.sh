#!/bin/bash

# Полноценный скрипт деплоя на сервер
# Использование: ./deploy.sh

set -e  # Остановка при ошибке

echo "🚀 Начинаю полный деплой на сервер..."

# Проверяем, что мы в корне проекта
if [ ! -f "package.json" ] && [ ! -f "frontend/package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из корня проекта"
    exit 1
fi

# Проверяем статус Git локально
if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  Внимание: Есть незакоммиченные изменения"
    echo "📝 Текущий статус Git:"
    git status --short
    echo ""
    read -p "Продолжить деплой? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Деплой отменен"
        exit 1
    fi
fi

# Пушим изменения в Git
echo "📤 Пушим изменения в Git..."
git push origin main

# Деплоим на сервер - ВСЕ ПЕРЕТИРАЕМ!
echo "🌐 Деплою на сервер (ВСЕ ПЕРЕТИРАЮ)..."
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && \
    echo '🗑️  Очищаю все изменения на сервере...' && \
    git reset --hard HEAD && \
    git clean -fd && \
    echo '📥 Обновляю код с Git...' && \
    git pull origin main && \
    echo '📦 Устанавливаю зависимости backend...' && \
    cd backend && npm install && \
    echo '🔨 Собираю frontend...' && \
    cd ../frontend && npm run build && \
    echo '📁 Копирую новые файлы...' && \
    cd .. && rm -rf static && cp -r frontend/build/static . && \
    echo '📄 Копирую index.html...' && \
    cp frontend/build/index.html . && \
    echo '🔄 Перезапускаю сервисы...' && \
    pm2 restart all && \
    echo '✅ Деплой завершен успешно!'"

echo ""
echo "🎉 Полный деплой на сервер завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "📊 PM2 статус: ssh nr 'pm2 status'"
echo ""
echo "🧪 Тестируйте сайт через 30 секунд"
