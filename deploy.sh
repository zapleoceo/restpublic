#!/bin/bash

# Скрипт деплоя на сервер
# Использование: ./deploy.sh

set -e  # Остановка при ошибке

echo "🚀 Начинаю деплой на сервер..."

# Проверяем, что мы в корне проекта
if [ ! -f "package.json" ] && [ ! -f "frontend/package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из корня проекта"
    exit 1
fi

# Проверяем статус Git
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
