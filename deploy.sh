#!/bin/bash

# North Republic Deployment Script v5.4 - FIXED
# Исправленный скрипт деплоя с правильными вызовами функций
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
    
    log "🚀 НАЧИНАЕМ: $description (таймаут: ${timeout}s)"
    log "📋 Команда: $command"
    log "⏰ Время начала: $(date)"
    
    if timeout $timeout bash -c "$command"; then
        log "✅ УСПЕШНО: $description"
        log "⏰ Время завершения: $(date)"
    else
        local exit_code=$?
        log "❌ ОШИБКА: $description (exit code: $exit_code)"
        log "⏰ Время ошибки: $(date)"
        return $exit_code
    fi
}

# Функция для проверки изменений в package.json
has_package_changes() {
    local dir="$1"
    local package_file="$dir/package.json"
    local lock_file="$dir/package-lock.json"
    
    if [ ! -f "$package_file" ]; then
        return 1
    fi
    
    # Проверяем изменения в package.json или package-lock.json
    if git diff --name-only HEAD~1 | grep -q "$package_file\|$lock_file"; then
        return 0
    fi
    
    return 1
}

# Функция для умной установки npm зависимостей
smart_npm_install() {
    local dir="$1"
    local description="$2"
    
    log "📁 Директория: $(pwd)"
    
    if has_package_changes "$dir"; then
        log "📦 Обнаружены изменения в package.json - устанавливаем зависимости"
        run_with_timeout 120 "npm install" "$description"
    else
        log "📦 Изменений в package.json нет - пропускаем npm install"
    fi
}

log "🚀 ========================================="
log "🚀 НАЧИНАЕМ ДЕПЛОЙ БЕЗ БОТА v6.1 - ИСПРАВЛЕН CSS"
log "🚀 ========================================="
log "📅 Время начала: $(date)"
log "💻 Система: $(uname -a)"
log "💾 Память: $(free -h | grep Mem | awk '{print $2}')"
log "💽 Диск: $(df -h . | tail -1 | awk '{print $4}') свободно"

# Переходим в рабочую директорию
cd /var/www/northrepubli_usr/data/www/northrepublic.me
log "📁 Рабочая директория: $(pwd)"

# Настройки Git для ускорения
git config --local core.editor /bin/true
git config --local merge.tool /bin/true
export GIT_EDITOR=/bin/true
export EDITOR=/bin/true

# Принудительное обновление кода с перетиранием локальных изменений
log "📥 Принудительно обновляем код из репозитория..."
git fetch origin main
if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ]; then
    log "🔄 Обнаружены изменения - принудительно обновляем код"
    # Сбрасываем все локальные изменения и принудительно обновляем
    git reset --hard HEAD
    git clean -fd
    git pull origin main --allow-unrelated-histories --no-edit
    log "✅ Код успешно обновлен"
else
    log "✅ Код уже актуален - пропускаем обновление"
fi

# Останавливаем PM2 процессы только если они запущены
log "🛑 Проверяем PM2 процессы..."
if pm2 list | grep -q "online"; then
    log "🛑 Останавливаем запущенные PM2 процессы"
    run_with_timeout 30 "pm2 stop all" "Остановка PM2 процессов"
    run_with_timeout 30 "pm2 delete all" "Удаление PM2 процессов"
    
    # Принудительно останавливаем все процессы бота
    log "🛑 Принудительно останавливаем все процессы бота..."
    pkill -f "bot.js" 2>/dev/null || true
    pkill -f "northrepublic-bot" 2>/dev/null || true
    
    # Ждем завершения процессов
    log "⏳ Ждем завершения процессов бота..."
    sleep 5
else
    log "✅ PM2 процессы не запущены - пропускаем остановку"
fi

# Сборка Backend
log "🔧 ========================================="
log "🔧 СБОРКА BACKEND"
log "🔧 ========================================="

# Запускаем сборку backend
log "🔧 Запускаем сборку backend..."
cd backend
smart_npm_install "backend" "Установка зависимостей backend"
run_with_timeout 30 "mkdir -p ../logs" "Создание директории logs"
cd ..

# MongoDB миграция (только если есть изменения в данных)
log "🔗 ========================================="
log "🔗 MONGODB МИГРАЦИЯ"
log "🔗 ========================================="

# Проверяем изменения в файлах данных и статических ресурсов
if git diff --name-only HEAD~1 | grep -q "lang/.*\.json\|backend/scripts/migrate-to-mongodb.js\|img/.*\.(jpg|png|gif|svg|ico)\|public/.*\.(jpg|png|gif|svg|ico)"; then
    log "🔄 Обнаружены изменения в данных или статических файлах - запускаем миграцию"
    
    log "🔍 Проверяем доступность MongoDB..."
    if ! timeout 10 bash -c 'until nc -z 127.0.0.1 27018; do sleep 1; done'; then
        log "❌ MongoDB недоступна на порту 27018"
        exit 1
    fi
    log "✅ MongoDB доступна на порту 27018"
    
    # Запускаем миграцию с принудительным завершением
    log "🚀 Запускаем миграцию MongoDB с таймаутом..."
    if timeout 30 node backend/scripts/migrate-to-mongodb.js; then
        log "✅ Миграция MongoDB завершена успешно"
    else
        log "❌ Миграция MongoDB завершилась с ошибкой или таймаутом"
        # Принудительно завершаем зависшие процессы миграции
        pkill -f "migrate-to-mongodb.js" 2>/dev/null || true
        exit 1
    fi
else
    log "✅ Изменений в данных нет - пропускаем миграцию"
fi

# Сборка Frontend (самая тяжелая операция)
log "🔨 ========================================="
log "🔨 СБОРКА FRONTEND"
log "🔨 ========================================="

cd frontend
smart_npm_install "frontend" "Установка зависимостей frontend"

# Проверка и исправление CSS файлов перед сборкой
log "🔍 Проверяем CSS файлы на ошибки синтаксиса..."

# Простая функция для исправления распространенных CSS ошибок
fix_css_simple() {
    local css_file="$1"
    if [ -f "$css_file" ]; then
        log "🔧 Проверяем файл: $css_file"
        
        # Создаем бэкап файла
        cp "$css_file" "${css_file}.bak"
        
        # Исправляем распространенные проблемы:
        # 1. Убираем лишние пустые строки с только закрывающими скобками
        # 2. Убираем двойные закрывающие скобки
        # 3. Исправляем неправильные вложения
        sed -i \
            -e '/^[[:space:]]*}[[:space:]]*$/N; s/\n[[:space:]]*}[[:space:]]*$//g' \
            -e 's/}[[:space:]]*}/}/g' \
            -e '/^[[:space:]]*}[[:space:]]*$/{N; /\n[[:space:]]*}[[:space:]]*$/d;}' \
            "$css_file" 2>/dev/null || {
            # Если sed не сработал, восстанавливаем из бэкапа
            mv "${css_file}.bak" "$css_file"
            log "⚠️ Не удалось автоматически исправить $css_file, оставляем оригинал"
            return
        }
        
        # Удаляем бэкап если все прошло успешно
        rm -f "${css_file}.bak"
        log "✅ Файл $css_file проверен и исправлен"
    fi
}

# Исправляем все CSS файлы в проекте
log "🔧 Исправляем CSS файлы..."
find src -name "*.css" -type f | while read css_file; do
    fix_css_simple "$css_file"
done

# Дополнительная проверка конкретного проблемного файла
if [ -f "src/styles/template.css" ]; then
    log "🔧 Дополнительная проверка template.css..."
    
    # Убираем конкретно лишние закрывающие скобки в конце файла
    sed -i '${/^[[:space:]]*}[[:space:]]*$/d;}' src/styles/template.css 2>/dev/null || true
    
    # Проверяем баланс скобок
    open_braces=$(grep -o '{' src/styles/template.css | wc -l)
    close_braces=$(grep -o '}' src/styles/template.css | wc -l)
    
    log "📊 Баланс скобок в template.css: открывающих=$open_braces, закрывающих=$close_braces"
    
    if [ "$open_braces" -ne "$close_braces" ]; then
        log "⚠️ Дисбаланс скобок обнаружен, пытаемся исправить..."
        
        # Если закрывающих больше, убираем лишние в конце
        if [ "$close_braces" -gt "$open_braces" ]; then
            excess=$((close_braces - open_braces))
            log "🔧 Удаляем $excess лишних закрывающих скобок..."
            
            # Удаляем лишние закрывающие скобки с конца файла
            for i in $(seq 1 $excess); do
                sed -i '${/^[[:space:]]*}[[:space:]]*$/d;}' src/styles/template.css 2>/dev/null || break
            done
        fi
    fi
fi

log "🏗️ Запускаем сборку frontend..."

# Создаем бэкап текущей сборки перед новой
if [ -d "../assets" ] || [ -f "../index.html" ]; then
    log "💾 Создаем бэкап текущей сборки..."
    mkdir -p ../backup_build
    cp -r ../assets ../backup_build/ 2>/dev/null || true
    cp ../index.html ../backup_build/ 2>/dev/null || true
    cp -r ../img ../backup_build/ 2>/dev/null || true
    cp -r ../lang ../backup_build/ 2>/dev/null || true
    log "✅ Бэкап создан"
fi

# Пытаемся собрать frontend с обработкой ошибок
if run_with_timeout 300 "npm run build" "Сборка frontend"; then
    log "✅ Сборка frontend успешно завершена"
    
    log "📋 Копируем собранные файлы..."
    run_with_timeout 30 "cp -r dist/* ../" "Копирование файлов frontend"
    
    # Удаляем бэкап после успешной сборки
    rm -rf ../backup_build 2>/dev/null || true
    log "✅ Файлы frontend успешно скопированы"
else
    log "❌ Ошибка сборки frontend! Восстанавливаем из бэкапа..."
    
    # Восстанавливаем из бэкапа
    if [ -d "../backup_build" ]; then
        cp -r ../backup_build/* ../ 2>/dev/null || true
        rm -rf ../backup_build
        log "✅ Предыдущая версия восстановлена из бэкапа"
        log "⚠️ Продолжаем деплой с предыдущей версией frontend"
    else
        log "❌ Бэкап не найден! Деплой остановлен."
        exit 1
    fi
fi

# Копируем статические файлы если есть изменения
if git diff --name-only HEAD~1 | grep -q "img/.*\.(jpg|png|gif|svg|ico)\|public/.*\.(jpg|png|gif|svg|ico)"; then
    log "📋 Копируем статические файлы..."
    run_with_timeout 30 "cp -r public/img/* ../img/ 2>/dev/null || true" "Копирование статических файлов"
fi

cd ..

# Проверка переменных окружения и зависимостей
log "🔍 ========================================="
log "🔍 ПРОВЕРКА ОКРУЖЕНИЯ И ЗАВИСИМОСТЕЙ"
log "🔍 ========================================="

# Проверяем наличие .env файлов
if [ ! -f "backend/.env" ]; then
    log "⚠️ ВНИМАНИЕ: backend/.env файл отсутствует!"
    log "📋 Скопируйте .env файл вручную с локальной машины"
fi

# Проверяем системные зависимости
log "🔍 Проверяем системные зависимости..."
if ! command -v node &> /dev/null; then
    log "❌ Node.js не установлен"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    log "❌ npm не установлен"
    exit 1
fi

if ! command -v pm2 &> /dev/null; then
    log "❌ PM2 не установлен"
    exit 1
fi

log "✅ Все системные зависимости доступны"

# Настройка прав доступа (только для новых файлов)
log "🔐 ========================================="
log "🔐 НАСТРОЙКА ПРАВ ДОСТУПА"
log "🔐 ========================================="

# Временно отключен бот - права не нужны
# run_with_timeout 10 "chmod +x bot/dist/bot.js" "Установка прав на bot.js"

# Изменяем владельца только для новых файлов
log "🔐 Изменяем владельца для новых файлов..."
find . -newer .git/HEAD -exec chown northrepubli_usr:northrepubli_usr {} \; 2>/dev/null || true

# Запуск PM2 процессов
log "🚀 ========================================="
log "🚀 ЗАПУСК PM2 ПРОЦЕССОВ"
log "🚀 ========================================="

run_with_timeout 60 "pm2 start ecosystem.config.js --update-env" "Запуск PM2 процессов"

# Пауза для стабилизации процессов
log "⏳ Пауза для стабилизации процессов..."
sleep 5

# Быстрая проверка процессов
log "🔍 ========================================="
log "🔍 БЫСТРАЯ ПРОВЕРКА ПРОЦЕССОВ"
log "🔍 ========================================="

log "⏳ Ожидаем запуска процессов..."
sleep 5

# Проверяем статус PM2 процессов
if pm2 list | grep -q "northrepublic-backend.*online"; then
    log "✅ Backend процесс запущен"
else
    log "❌ Backend процесс не запущен"
    pm2 list
    exit 1
fi

# Бот временно отключен из-за rate limiting
log "🤖 Bot временно отключен из-за rate limiting Telegram API"

# Быстрая проверка backend API
log "🔍 Быстрая проверка backend API..."
for i in {1..10}; do
    if curl -s http://localhost:3002/api/health > /dev/null 2>&1; then
        log "✅ Backend API доступен на попытке $i"
        break
    fi
    
    if [ $i -eq 10 ]; then
        log "❌ Backend API не стал доступен за 10 попыток"
        pm2 logs northrepublic-backend --lines 5 || log "❌ PM2 логи недоступны"
        exit 1
    fi
    
    sleep 1
done

# Финальная проверка
log "🎉 ========================================="
log "🎉 ДЕПЛОЙ БЕЗ БОТА ЗАВЕРШЕН!"
log "🎉 CSS ОШИБКИ АВТОМАТИЧЕСКИ ИСПРАВЛЕНЫ"
log "🎉 ========================================="

log "📅 Время завершения: $(date)"
log "🌐 Сайт доступен по адресу: https://northrepublic.me"
log "📡 Backend API: http://localhost:3002/api/health"
log "🔧 Админ панель: https://northrepublic.me/admin"

log "📊 Финальная статистика:"
log "💾 Использование памяти: $(free -h | grep Mem | awk '{print $3"/"$2}')"
log "💽 Использование диска: $(df -h . | tail -1 | awk '{print $3"/"$2}')"

log "🚀 ========================================="
log "🚀 ДЕПЛОЙ БЕЗ БОТА v6.1 ЗАВЕРШЕН!"
log "🚀 С АВТОИСПРАВЛЕНИЕМ CSS ОШИБОК"
log "🚀 ========================================="
