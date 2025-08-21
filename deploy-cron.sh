#!/bin/bash

# Скрипт для автоматического деплоя по расписанию
# Добавить в crontab: */5 * * * * /path/to/deploy-cron.sh

cd /var/www/goodzone_zap_usr/data/www/goodzone.zapleo.com

# Проверяем, есть ли новые коммиты
git fetch origin
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/main)

if [ "$LOCAL" != "$REMOTE" ]; then
    echo "$(date): 🚀 Обнаружены новые изменения, запускаем деплой..."
    ./deploy.sh >> logs/cron-deploy.log 2>&1
    echo "$(date): ✅ Деплой завершен" >> logs/cron-deploy.log
else
    echo "$(date): 📋 Изменений нет" >> logs/cron-deploy.log
fi
