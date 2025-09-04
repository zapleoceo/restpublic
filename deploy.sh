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

echo ""
echo "🎉 Деплой завершен!"
echo "🌐 GitHub Actions автоматически задеплоит на сервер"
echo "⏰ Ожидайте 2-3 минуты для автодеплоя"
echo ""
echo "💡 Для ручного деплоя выполните команды на сервере:"
echo "   git reset --hard HEAD && git clean -fd"
echo "   git pull origin main"
echo "   cd backend && npm install"
echo "   cd ../frontend && npm run build"
echo "   cd .. && rm -rf static && cp -r frontend/build/static ."
echo "   cp frontend/build/index.html ."
echo "   pm2 restart all"
