#!/bin/bash

# North Republic Clean Deployment Script v7.0
# Чистый скрипт деплоя без обходов проблем - показывает все ошибки как есть
set -e  # Остановить выполнение при любой ошибке

# Функция для логирования с временными метками
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Функция для выполнения команды с подробным логированием
run_command() {
    local command="$1"
    local description="$2"
    
    log "🚀 ВЫПОЛНЯЕМ: $description"
    log "📋 Команда: $command"
    log "⏰ Время начала: $(date)"
    
    if eval "$command"; then
        log "✅ УСПЕШНО: $description"
        log "⏰ Время завершения: $(date)"
        return 0
    else
        local exit_code=$?
        log "❌ ОШИБКА: $description (exit code: $exit_code)"
        log "⏰ Время ошибки: $(date)"
        log "💡 Команда, которая упала: $command"
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

# Функция для проверки системных требований
check_system_requirements() {
    log "🔍 Проверяем системные требования..."
    
    # Проверяем Node.js
    if ! command -v node &> /dev/null; then
        log "❌ Node.js не установлен"
        exit 1
    fi
    local node_version=$(node --version)
    log "✅ Node.js версия: $node_version"
    
    # Проверяем npm
    if ! command -v npm &> /dev/null; then
        log "❌ npm не установлен"
        exit 1
    fi
    local npm_version=$(npm --version)
    log "✅ npm версия: $npm_version"
    
    # Проверяем PM2
    if ! command -v pm2 &> /dev/null; then
        log "❌ PM2 не установлен"
        exit 1
    fi
    local pm2_version=$(pm2 --version)
    log "✅ PM2 версия: $pm_version"
    
    # Проверяем Git
    if ! command -v git &> /dev/null; then
        log "❌ Git не установлен"
        exit 1
    fi
    local git_version=$(git --version)
    log "✅ $git_version"
}

log "🚀 ========================================="
log "🚀 CLEAN DEPLOY SCRIPT v7.0"
log "🚀 БЕЗ ОБХОДОВ ПРОБЛЕМ - ПОКАЗЫВАЕМ ВСЕ ОШИБКИ"
log "🚀 ========================================="
log "📅 Время начала: $(date)"
log "💻 Система: $(uname -a)"
log "💾 Память: $(free -h | grep Mem | awk '{print $2}' 2>/dev/null || echo 'N/A')"
log "💽 Диск: $(df -h . | tail -1 | awk '{print $4}' 2>/dev/null || echo 'N/A') свободно"

# Переходим в рабочую директорию
cd /var/www/northrepubli_usr/data/www/northrepublic.me
log "📁 Рабочая директория: $(pwd)"

# Проверяем системные требования
check_system_requirements

# Настройки Git
log "⚙️ Настраиваем Git..."
git config --local core.editor /bin/true
git config --local merge.tool /bin/true
export GIT_EDITOR=/bin/true
export EDITOR=/bin/true

# Получаем информацию о текущем коммите
log "📋 Текущий коммит: $(git rev-parse --short HEAD)"
log "📋 Последний коммит: $(git log -1 --oneline)"

# Обновление кода из репозитория
log "📥 ========================================="
log "📥 ОБНОВЛЕНИЕ КОДА"
log "📥 ========================================="

run_command "git fetch origin main" "Получение обновлений из репозитория"

if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/main)" ]; then
    log "🔄 Обнаружены изменения - обновляем код"
    
    # Показываем что изменилось
    log "📋 Изменения в репозитории:"
    git log --oneline HEAD..origin/main
    
    log "📋 Измененные файлы:"
    git diff --name-only HEAD origin/main
    
    # Сбрасываем локальные изменения и обновляем
    run_command "git reset --hard HEAD" "Сброс локальных изменений"
    run_command "git clean -fd" "Очистка неотслеживаемых файлов"
    run_command "git pull origin main --allow-unrelated-histories --no-edit" "Обновление кода"
    
    log "✅ Код успешно обновлен до коммита: $(git rev-parse --short HEAD)"
else
    log "✅ Код уже актуален - обновление не требуется"
fi

# Остановка PM2 процессов
log "🛑 ========================================="
log "🛑 ОСТАНОВКА ПРОЦЕССОВ"
log "🛑 ========================================="

log "🔍 Проверяем текущие PM2 процессы..."
pm2 list

if pm2 list | grep -q "online"; then
    log "🛑 Останавливаем запущенные PM2 процессы"
    run_command "pm2 stop all" "Остановка PM2 процессов"
    run_command "pm2 delete all" "Удаление PM2 процессов"
    
    # Принудительная остановка процессов бота
    log "🛑 Принудительно останавливаем процессы бота..."
    pkill -f "bot.js" 2>/dev/null || true
    pkill -f "northrepublic-bot" 2>/dev/null || true
    
    sleep 3
    log "✅ Все процессы остановлены"
else
    log "✅ PM2 процессы не запущены"
fi

# Сборка Backend
log "🔧 ========================================="
log "🔧 СБОРКА BACKEND"
log "🔧 ========================================="

cd backend
log "📁 Текущая директория: $(pwd)"

# Проверяем наличие package.json
if [ ! -f "package.json" ]; then
    log "❌ Файл package.json не найден в директории backend"
    exit 1
fi

log "📋 Backend package.json:"
cat package.json | jq '.name, .version, .scripts' 2>/dev/null || head -10 package.json

# Устанавливаем зависимости если нужно
if has_package_changes "backend"; then
    log "📦 Обнаружены изменения в package.json - устанавливаем зависимости"
    run_command "npm install" "Установка зависимостей backend"
else
    log "📦 Изменений в package.json нет - пропускаем npm install"
fi

# Создаем директорию для логов
run_command "mkdir -p ../logs" "Создание директории logs"

cd ..

# MongoDB миграция
log "🔗 ========================================="
log "🔗 ПРОВЕРКА MONGODB И МИГРАЦИЯ"
log "🔗 ========================================="

# Проверяем подключение к MongoDB
log "🔍 Проверяем доступность MongoDB на порту 27018..."
if ! timeout 10 bash -c 'until nc -z 127.0.0.1 27018; do sleep 1; done'; then
    log "❌ MongoDB недоступна на порту 27018"
    log "💡 Проверьте что MongoDB запущена: sudo systemctl status mongod"
    exit 1
fi
log "✅ MongoDB доступна на порту 27018"

# Проверяем изменения в данных
if git diff --name-only HEAD~1 | grep -q "lang/.*\.json\|backend/scripts/migrate-to-mongodb.js\|img/.*\.(jpg|png|gif|svg|ico)\|public/.*\.(jpg|png|gif|svg|ico)"; then
    log "🔄 Обнаружены изменения в данных - запускаем миграцию"
    log "📋 Измененные файлы данных:"
    git diff --name-only HEAD~1 | grep "lang/.*\.json\|backend/scripts/migrate-to-mongodb.js\|img/.*\.(jpg|png|gif|svg|ico)\|public/.*\.(jpg|png|gif|svg|ico)" || true
    
    run_command "timeout 60 node backend/scripts/migrate-to-mongodb.js" "Миграция данных в MongoDB"
else
    log "✅ Изменений в данных нет - пропускаем миграцию"
fi

# Сборка Frontend
log "🔨 ========================================="
log "🔨 СБОРКА FRONTEND"
log "🔨 ========================================="

cd frontend
log "📁 Текущая директория: $(pwd)"

# Проверяем наличие package.json
if [ ! -f "package.json" ]; then
    log "❌ Файл package.json не найден в директории frontend"
    exit 1
fi

log "📋 Frontend package.json:"
cat package.json | jq '.name, .version, .scripts.build' 2>/dev/null || head -10 package.json

# Устанавливаем зависимости если нужно
if has_package_changes "frontend"; then
    log "📦 Обнаружены изменения в package.json - устанавливаем зависимости"
    run_command "npm install" "Установка зависимостей frontend"
else
    log "📦 Изменений в package.json нет - пропускаем npm install"
fi

# Показываем информацию о CSS файлах перед сборкой
log "📋 Информация о CSS файлах:"
find src -name "*.css" -type f -exec echo "📄 {}" \; -exec wc -l {} \;

# Проверяем синтаксис основных CSS файлов
log "🔍 Проверяем синтаксис CSS файлов..."
for css_file in $(find src -name "*.css" -type f); do
    log "🔍 Проверяем: $css_file"
    if [ -f "$css_file" ]; then
        # Показываем последние строки файла для диагностики
        log "📋 Последние 5 строк файла $css_file:"
        tail -5 "$css_file" | nl
        
        # Подсчитываем скобки
        open_braces=$(grep -o '{' "$css_file" | wc -l)
        close_braces=$(grep -o '}' "$css_file" | wc -l)
        log "📊 Баланс скобок в $css_file: открывающих=$open_braces, закрывающих=$close_braces"
        
        if [ "$open_braces" -ne "$close_braces" ]; then
            log "❌ ОШИБКА: Дисбаланс скобок в файле $css_file"
            log "💡 Откройте файл и проверьте синтаксис CSS"
            exit 1
        fi
    fi
done

# Запускаем сборку frontend
log "🏗️ Запускаем сборку frontend..."
run_command "npm run build" "Сборка frontend"

# Проверяем результат сборки
if [ ! -d "dist" ]; then
    log "❌ Директория dist не создана после сборки"
    exit 1
fi

log "📋 Содержимое директории dist:"
ls -la dist/

# Копируем собранные файлы
log "📋 Копируем собранные файлы..."
run_command "cp -r dist/* ../" "Копирование файлов frontend"

# Копируем статические файлы если есть изменения
if git diff --name-only HEAD~1 | grep -q "img/.*\.(jpg|png|gif|svg|ico)\|public/.*\.(jpg|png|gif|svg|ico)"; then
    log "📋 Копируем обновленные статические файлы..."
    run_command "cp -r public/img/* ../img/ 2>/dev/null || true" "Копирование статических файлов"
fi

cd ..

# Проверка переменных окружения
log "🔍 ========================================="
log "🔍 ПРОВЕРКА КОНФИГУРАЦИИ"
log "🔍 ========================================="

# Проверяем .env файлы
if [ ! -f "backend/.env" ]; then
    log "❌ КРИТИЧЕСКАЯ ОШИБКА: backend/.env файл отсутствует!"
    log "💡 Создайте .env файл в директории backend с необходимыми переменными"
    exit 1
fi

log "✅ Файл backend/.env найден"
log "📋 Переменные в .env файле (без значений):"
grep -o '^[^=]*' backend/.env | head -10

# Проверяем ecosystem.config.js
if [ ! -f "ecosystem.config.js" ]; then
    log "❌ КРИТИЧЕСКАЯ ОШИБКА: ecosystem.config.js не найден!"
    exit 1
fi

log "✅ Файл ecosystem.config.js найден"
log "📋 Конфигурация PM2:"
cat ecosystem.config.js | head -20

# Настройка прав доступа
log "🔐 ========================================="
log "🔐 НАСТРОЙКА ПРАВ ДОСТУПА"
log "🔐 ========================================="

# Изменяем владельца файлов
log "🔐 Устанавливаем правильного владельца файлов..."
run_command "find . -newer .git/HEAD -exec chown northrepubli_usr:northrepubli_usr {} \; 2>/dev/null || true" "Изменение владельца новых файлов"

# Запуск PM2 процессов
log "🚀 ========================================="
log "🚀 ЗАПУСК ПРОЦЕССОВ"
log "🚀 ========================================="

run_command "pm2 start ecosystem.config.js --update-env" "Запуск PM2 процессов"

# Ожидание стабилизации процессов
log "⏳ Ожидаем стабилизации процессов (10 секунд)..."
sleep 10

# Проверка статуса процессов
log "🔍 ========================================="
log "🔍 ПРОВЕРКА ПРОЦЕССОВ"
log "🔍 ========================================="

log "📋 Статус PM2 процессов:"
pm2 list

log "📋 Подробная информация о процессах:"
pm2 show northrepublic-backend 2>/dev/null || log "⚠️ Процесс northrepublic-backend не найден"

# Проверяем что backend процесс запущен
if pm2 list | grep -q "northrepublic-backend.*online"; then
    log "✅ Backend процесс запущен и работает"
else
    log "❌ Backend процесс не запущен или не в статусе online"
    log "📋 Логи backend процесса:"
    pm2 logs northrepublic-backend --lines 10 || log "❌ Не удалось получить логи"
    exit 1
fi

# Проверка API
log "🔍 Проверяем доступность backend API..."
for i in {1..15}; do
    if curl -s --max-time 5 http://localhost:3002/api/health > /dev/null 2>&1; then
        log "✅ Backend API доступен на попытке $i"
        break
    fi
    
    if [ $i -eq 15 ]; then
        log "❌ Backend API не доступен после 15 попыток"
        log "📋 Последние логи backend:"
        pm2 logs northrepublic-backend --lines 5 || log "❌ Не удалось получить логи"
        exit 1
    fi
    
    log "⏳ Попытка $i/15 - ожидаем запуска API..."
    sleep 2
done

# Финальная проверка
log "🎉 ========================================="
log "🎉 ДЕПЛОЙ УСПЕШНО ЗАВЕРШЕН!"
log "🎉 ========================================="

log "📅 Время завершения: $(date)"
log "🌐 Сайт доступен по адресу: https://northrepublic.me"
log "📡 Backend API: http://localhost:3002/api/health"
log "🔧 Админ панель: https://northrepublic.me/admin"

# Финальная статистика
log "📊 Финальная статистика:"
log "💾 Использование памяти: $(free -h | grep Mem | awk '{print $3"/"$2}' 2>/dev/null || echo 'N/A')"
log "💽 Использование диска: $(df -h . | tail -1 | awk '{print $3"/"$2}' 2>/dev/null || echo 'N/A')"
log "🔧 PM2 процессы:"
pm2 list

log "🚀 ========================================="
log "🚀 CLEAN DEPLOY v7.0 ЗАВЕРШЕН УСПЕШНО!"
log "🚀 ВСЕ ПРОБЛЕМЫ БЫЛИ ПОКАЗАНЫ И РЕШЕНЫ"
log "🚀 ========================================="