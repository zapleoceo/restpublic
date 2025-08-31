#!/bin/bash

# North Republic Deployment Script v5.2
# Этот скрипт автоматически обновляет код, собирает приложения и перезапускает сервисы
set -e  # Остановить выполнение при ошибке

# Функция для логирования с временными метками
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Функция для выполнения команды с подробным логированием
run_command() {
    local description="$1"
    local command="$2"
    
    log "🚀 НАЧИНАЕМ: $description"
    log "📋 Команда: $command"
    log "⏰ Время начала: $(date)"
    
    # Выполняем команду и сохраняем exit code
    if eval "$command"; then
        log "✅ УСПЕШНО: $description"
        log "⏰ Время завершения: $(date)"
    else
        local exit_code=$?
        log "❌ ОШИБКА: $description (exit code: $exit_code)"
        log "⏰ Время ошибки: $(date)"
        return $exit_code
    fi
}

# Функция для проверки процесса
check_process() {
    local process_name="$1"
    log "🔍 Проверяем процесс: $process_name"
    if pgrep -f "$process_name" > /dev/null; then
        log "✅ Процесс $process_name запущен"
        pgrep -f "$process_name" | xargs ps -p
    else
        log "❌ Процесс $process_name не найден"
    fi
}

log "🚀 ========================================="
log "🚀 НАЧИНАЕМ ДЕПЛОЙ North Republic v5.2"
log "🚀 ========================================="
log "📅 Время начала: $(date)"
log "💻 Система: $(uname -a)"
log "💾 Память: $(free -h | grep Mem | awk '{print $2}')"
log "💽 Диск: $(df -h . | tail -1 | awk '{print $4}') свободно"

# Переходим в рабочую директорию
run_command "Переход в рабочую директорию" "cd /var/www/northrepubli_usr/data/www/northrepublic.me"
log "📁 Рабочая директория: $(pwd)"
log "📊 Размер директории: $(du -sh . | cut -f1)"

# Настройки Git
run_command "Настройка Git" "git config --local core.editor /bin/true && git config --local merge.tool /bin/true"
export GIT_EDITOR=/bin/true
export EDITOR=/bin/true

# Проверяем Git статус
run_command "Проверка Git статуса" "git status --porcelain"
log "📋 Последние коммиты:"
git log --oneline -3 || log "⚠️ Не удалось получить историю коммитов"

# Обновляем код
run_command "Обновление кода из репозитория" "git pull origin main --allow-unrelated-histories --no-edit"

# Останавливаем PM2 процессы
log "🛑 ========================================="
log "🛑 ОСТАНОВКА PM2 ПРОЦЕССОВ"
log "🛑 ========================================="

run_command "Остановка всех PM2 процессов" "pm2 stop all"
run_command "Удаление всех PM2 процессов" "pm2 delete all"

log "📊 Статус PM2 после остановки:"
pm2 list

# Сборка Backend
log "🔧 ========================================="
log "🔧 СБОРКА BACKEND"
log "🔧 ========================================="

run_command "Переход в директорию backend" "cd backend"
log "📁 Директория backend: $(pwd)"

run_command "Установка зависимостей backend" "npm install"
log "📦 Зависимости backend установлены"

run_command "Создание директории logs" "mkdir -p ../logs"
log "📁 Директория logs создана"

# MongoDB миграция
log "🔗 ========================================="
log "🔗 MONGODB МИГРАЦИЯ"
log "🔗 ========================================="

log "🔍 Проверяем доступность MongoDB..."
if ! timeout 10 bash -c 'until nc -z 127.0.0.1 27017; do sleep 1; done'; then
    log "❌ MongoDB недоступна на порту 27017"
    exit 1
fi
log "✅ MongoDB доступна на порту 27017"

log "🚀 Запускаем миграцию MongoDB..."
run_command "MongoDB миграция" "node scripts/migrate-to-mongodb.js"
log "✅ Миграция MongoDB завершена"

run_command "Возврат в корневую директорию" "cd .."
log "📁 Вернулись в: $(pwd)"

# Сборка Frontend
log "🔨 ========================================="
log "🔨 СБОРКА FRONTEND"
log "🔨 ========================================="

run_command "Переход в директорию frontend" "cd frontend"
log "📁 Директория frontend: $(pwd)"
log "📊 Размер node_modules: $(du -sh node_modules 2>/dev/null || echo 'не существует')"

run_command "Установка зависимостей frontend" "npm install"
log "📦 Зависимости frontend установлены"

log "🏗️ Запускаем сборку frontend..."
run_command "Сборка frontend" "npm run build"
log "✅ Сборка frontend завершена"

log "📋 Копируем собранные файлы..."
run_command "Копирование файлов frontend" "cp -r dist/* ../"
log "✅ Файлы frontend скопированы"

run_command "Возврат в корневую директорию" "cd .."
log "📁 Вернулись в: $(pwd)"

# Сборка Bot
log "🤖 ========================================="
log "🤖 СБОРКА TELEGRAM BOT"
log "🤖 ========================================="

run_command "Переход в директорию bot" "cd bot"
log "📁 Директория bot: $(pwd)"

run_command "Установка зависимостей bot" "npm install"
log "📦 Зависимости bot установлены"

run_command "Сборка bot" "npm run build"
log "✅ Сборка bot завершена"

run_command "Возврат в корневую директорию" "cd .."
log "📁 Вернулись в: $(pwd)"

# Настройка прав доступа
log "🔐 ========================================="
log "🔐 НАСТРОЙКА ПРАВ ДОСТУПА"
log "🔐 ========================================="

run_command "Установка прав на bot.js" "chmod +x bot/dist/bot.js"
run_command "Изменение владельца файлов" "chown -R northrepubli_usr:northrepubli_usr ."
log "✅ Права доступа настроены"

# Запуск PM2 процессов
log "🚀 ========================================="
log "🚀 ЗАПУСК PM2 ПРОЦЕССОВ"
log "🚀 ========================================="

run_command "Запуск PM2 процессов" "pm2 start ecosystem.config.js --update-env"
log "✅ PM2 процессы запущены"

log "📊 Статус PM2 процессов:"
pm2 list

# Проверка процессов
log "🔍 ========================================="
log "🔍 ПРОВЕРКА ПРОЦЕССОВ"
log "🔍 ========================================="

log "⏳ Ожидаем запуска процессов..."
sleep 10

log "🔍 Проверяем backend процесс..."
if pm2 list | grep -q "northrepublic-backend.*online"; then
    log "✅ Backend успешно запущен через PM2"
else
    log "❌ Ошибка: backend не запущен"
    log "📋 Последние логи backend:"
    pm2 logs northrepublic-backend --lines 10 || log "❌ PM2 логи недоступны"
    exit 1
fi

log "🔍 Проверяем bot процесс..."
if pm2 list | grep -q "northrepublic-bot.*online"; then
    log "✅ Bot успешно запущен через PM2"
else
    log "❌ Ошибка: bot не запущен"
    log "📋 Последние логи bot:"
    pm2 logs northrepublic-bot --lines 10 || log "❌ PM2 логи недоступны"
    exit 1
fi

log "🔍 Проверяем frontend файлы..."
if [ -f "index.html" ]; then
    log "✅ Frontend файлы успешно развернуты"
    log "📊 Размер index.html: $(ls -lh index.html | awk '{print $5}')"
else
    log "❌ Ошибка: frontend файлы не найдены"
    exit 1
fi

# Проверка доступности backend
log "🔍 ========================================="
log "🔍 ПРОВЕРКА ДОСТУПНОСТИ BACKEND"
log "🔍 ========================================="

log "⏳ Ожидаем запуска backend API..."
for i in {1..30}; do
    log "🔍 Попытка $i/30 - проверка backend API..."
    if curl -s http://localhost:3002/api/health > /dev/null 2>&1; then
        log "✅ Backend API доступен на попытке $i"
        break
    fi
    
    if [ $i -eq 30 ]; then
        log "❌ Backend API не стал доступен за 30 попыток"
        log "📋 Логи backend:"
        pm2 logs northrepublic-backend --lines 20 || log "❌ PM2 логи недоступны"
        exit 1
    fi
    
    sleep 2
done

# Финальная проверка
log "🎉 ========================================="
log "🎉 ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!"
log "🎉 ========================================="

log "📅 Время завершения: $(date)"
log "🌐 Сайт доступен по адресу: https://northrepublic.me"
log "📡 Backend API: http://localhost:3002/api/health"
log "🔧 Админ панель: https://northrepublic.me/admin"

log "📊 Финальная статистика:"
log "💾 Использование памяти: $(free -h | grep Mem | awk '{print $3"/"$2}')"
log "💽 Использование диска: $(df -h . | tail -1 | awk '{print $3"/"$2}')"

log "📋 Команды для мониторинга:"
log "🔍 Проверить процессы: pm2 list"
log "📋 Логи backend: pm2 logs northrepublic-backend"
log "📋 Логи bot: pm2 logs northrepublic-bot"

log "🚀 ========================================="
log "🚀 ДЕПЛОЙ North Republic v5.2 ЗАВЕРШЕН!"
log "🚀 ========================================="
