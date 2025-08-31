#!/bin/bash

# North Republic Deployment Script v5.1
# Этот скрипт автоматически обновляет код, собирает приложения и перезапускает сервисы
set -e  # Остановить выполнение при ошибке

# Функция для логирования с временными метками
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Функция для выполнения команды с таймаутом
run_with_timeout() {
    local timeout=$1
    local command="$2"
    local description="$3"
    
    log "⏱️ Выполняем: $description (таймаут: ${timeout}s)"
    log "🔧 Команда: $command"
    
    if timeout $timeout bash -c "$command"; then
        log "✅ $description завершено успешно"
    else
        log "❌ $description не завершено за $timeout секунд или завершилось с ошибкой"
        return 1
    fi
}

# Функция для проверки доступности порта
wait_for_port() {
    local port=$1
    local service=$2
    local max_attempts=30
    local attempt=1
    
    log "🔍 Ожидаем запуска $service на порту $port..."
    
    while [ $attempt -le $max_attempts ]; do
        if curl -s http://localhost:$port/api/health > /dev/null 2>&1; then
            log "✅ $service доступен на порту $port"
            return 0
        fi
        
        log "⏳ Попытка $attempt/$max_attempts - $service еще не готов..."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    log "❌ $service не стал доступен за $((max_attempts * 2)) секунд"
    return 1
}

log "🚀 Начинаем деплой North Republic v5.1 (Production)..."
log "📅 Время начала: $(date)"
log "💻 Система: $(uname -a)"
log "💾 Память: $(free -h | grep Mem | awk '{print $2}')"
log "💽 Диск: $(df -h . | tail -1 | awk '{print $4}') свободно"

cd /var/www/northrepubli_usr/data/www/northrepublic.me
log "📁 Рабочая директория: $(pwd)"
log "📊 Размер директории: $(du -sh . | cut -f1)"

# Настройки Git для предотвращения открытия редактора
log "🔧 Настраиваем Git..."
git config --local core.editor /bin/true
git config --local merge.tool /bin/true
export GIT_EDITOR=/bin/true
export EDITOR=/bin/true

log "📥 Обновляем код из репозитория..."
log "📋 Текущий статус Git:"
git status --porcelain || true

# Используем --allow-unrelated-histories для решения проблемы с очищенной историей
if ! run_with_timeout 60 "git pull origin main --allow-unrelated-histories --no-edit" "Git pull"; then
    log "⚠️ Обычный pull не удался, выполняем принудительный reset..."
    run_with_timeout 30 "git fetch origin" "Git fetch"
    run_with_timeout 30 "git reset --hard origin/main" "Git reset"
fi

log "📋 Статус после обновления:"
git log --oneline -3 || true

log "🛑 Останавливаем PM2 процессы..."
pm2 stop all || log "⚠️ PM2 процессы не найдены или уже остановлены"
pm2 delete all || log "⚠️ PM2 процессы не найдены для удаления"

log "🔧 Собираем Backend..."
cd backend
log "📦 Устанавливаем зависимости backend..."
if ! run_with_timeout 120 "npm install" "Backend npm install"; then
    log "❌ Ошибка установки зависимостей backend"
    exit 1
fi

mkdir -p ../logs
log "🔗 Инициализируем MongoDB..."
if ! run_with_timeout 60 "node scripts/migrate-to-mongodb.js" "MongoDB migration"; then
    log "❌ Ошибка миграции MongoDB"
    exit 1
fi
cd ..

log "🔨 Собираем Frontend..."
cd frontend
log "📦 Устанавливаем зависимости frontend..."
if ! run_with_timeout 180 "npm install" "Frontend npm install"; then
    log "❌ Ошибка установки зависимостей frontend"
    exit 1
fi

log "🏗️ Собираем frontend..."
if ! run_with_timeout 300 "npm run build" "Frontend build"; then
    log "❌ Ошибка сборки frontend"
    log "📋 Проверяем логи сборки..."
    npm run build 2>&1 | tail -20 || true
    exit 1
fi

log "📋 Копируем собранные файлы frontend..."
if ! run_with_timeout 30 "cp -r dist/* ../" "Copy frontend files"; then
    log "❌ Ошибка копирования файлов frontend"
    exit 1
fi
cd ..

log "🤖 Собираем Telegram Bot..."
cd bot
log "📦 Устанавливаем зависимости bot..."
if ! run_with_timeout 120 "npm install" "Bot npm install"; then
    log "❌ Ошибка установки зависимостей bot"
    exit 1
fi

log "🏗️ Собираем bot..."
if ! run_with_timeout 60 "npm run build" "Bot build"; then
    log "❌ Ошибка сборки bot"
    exit 1
fi
cd ..

log "🔐 Настраиваем права доступа..."
chmod +x bot/dist/bot.js
chown -R northrepubli_usr:northrepubli_usr .

log "🚀 Запускаем процессы через PM2..."
if ! run_with_timeout 60 "pm2 start ecosystem.config.js --update-env" "PM2 start"; then
    log "❌ Ошибка запуска PM2 процессов"
    exit 1
fi

log "📊 Статус PM2 процессов:"
pm2 list

log "✅ Проверяем статус деплоя..."
sleep 10

# Проверяем статус PM2 процессов
log "🔍 Проверяем backend..."
if pm2 list | grep -q "northrepublic-backend.*online"; then
    log "✅ Backend успешно запущен через PM2"
else
    log "❌ Ошибка: backend не запущен"
    log "📋 Последние логи backend:"
    pm2 logs northrepublic-backend --lines 10 || log "❌ PM2 логи недоступны"
    exit 1
fi

log "🔍 Проверяем bot..."
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

# Ждем запуска backend
if wait_for_port 3002 "Backend"; then
    log "✅ Backend API доступен"
else
    log "❌ Backend API недоступен"
    log "📋 Логи backend:"
    pm2 logs northrepublic-backend --lines 20 || log "❌ PM2 логи недоступны"
    exit 1
fi

log "🎉 Деплой North Republic v5.1 завершен успешно!"
log "📅 Время завершения: $(date)"
log "🌐 Сайт доступен по адресу: https://northrepublic.me"
log "📡 Backend API: http://localhost:3002/api/health"
log "🔧 Админ панель: https://northrepublic.me/admin"
log "📋 Логи backend: pm2 logs northrepublic-backend"
log "📋 Логи bot: pm2 logs northrepublic-bot"
log "🔍 Проверить процессы: pm2 list"
log "💾 Использование памяти: $(free -h | grep Mem | awk '{print $3"/"$2}')"
log "💽 Использование диска: $(df -h . | tail -1 | awk '{print $3"/"$2}')"
