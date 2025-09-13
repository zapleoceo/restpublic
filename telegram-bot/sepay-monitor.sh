#!/bin/bash

# Скрипт для мониторинга Sepay транзакций каждую секунду
# Запускать в фоне: nohup ./sepay-monitor.sh &

SCRIPT_DIR="/var/www/northrepubli_usr/data/www/northrepublic.me"
LOG_FILE="/var/www/northrepubli_usr/data/.pm2/logs/sepay-monitor.log"

echo "$(date): Запуск мониторинга Sepay транзакций каждую секунду" >> "$LOG_FILE"

while true; do
    echo "$(date): Проверка новых транзакций..." >> "$LOG_FILE"
    cd "$SCRIPT_DIR" && php admin/telegram/cron.php >> "$LOG_FILE" 2>&1
    sleep 1
done
