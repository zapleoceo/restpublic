#!/bin/bash

# Быстрое исправление меню
echo "🔧 Быстрое исправление меню..."

# 1. Обновляем кэш
echo "1. Обновление кэша..."
if [ -f "update-cache-debug.php" ]; then
    php update-cache-debug.php
else
    echo "⚠️ Скрипт обновления кэша не найден"
fi

# 2. Проверяем статус сервисов
echo "2. Проверка сервисов..."
echo "MongoDB:"
sudo systemctl status mongodb --no-pager -l

echo "PM2:"
pm2 status

# 3. Перезапускаем backend если нужно
echo "3. Перезапуск backend..."
pm2 restart northrepublic-backend

echo "✅ Исправление завершено!"
echo "Проверьте:"
echo "- https://northrepublic.me/"
echo "- https://northrepublic.me/menu.php"
echo "- https://northrepublic.me/debug-menu.php"
