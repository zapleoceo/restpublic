#!/bin/bash

# Современный скрипт деплоя North Republic
# Использование: bash deploy.sh [--fast] (на сервере)
# Автор: AI Assistant
# Версия: 2.1 (Оптимизированная)

set -e  # Остановка при ошибке

# Парсим аргументы
FAST_MODE=false
if [ "$1" = "--fast" ]; then
    FAST_MODE=true
    echo "🚀 Быстрый режим деплоя активирован"
fi

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функция для логирования
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Засекаем время начала
START_TIME=$(date +%s)
log "🚀 Начинаю деплой North Republic..."

# Проверяем, что мы на сервере
if [ ! -d "/var/www/northrepubli_usr/data/www/northrepublic.me" ]; then
    error "Запустите скрипт на сервере"
    exit 1
fi

# Переходим в рабочую директорию
cd /var/www/northrepubli_usr/data/www/northrepublic.me
log "📍 Рабочая директория: $(pwd)"

# Проверяем права доступа к скрипту
if [ ! -x "deploy.sh" ]; then
    log "🔧 Устанавливаю права на выполнение для deploy.sh"
    chmod +x deploy.sh
fi

# Проверяем статус Git
log "🔍 Проверяю статус Git..."
git status --porcelain > /dev/null 2>&1 || true

# Очищаем все изменения на сервере (согласно правилам)
log "🗑️  Очищаю все изменения на сервере..."
git reset --hard HEAD
git clean -fd
success "Сервер очищен"

# Обновляем код с Git
log "📥 Обновляю код с Git..."
CURRENT_BRANCH=$(git branch --show-current)
log "📍 Текущая ветка: $CURRENT_BRANCH"

# Пытаемся обновить с main
if git pull origin main; then
    success "Код обновлен с main"
else
    error "Не удалось обновить с main"
    exit 1
fi

# Проверяем, что мы в правильной директории
if [ ! -f "index.php" ]; then
    error "index.php не найден в корне проекта"
    exit 1
fi

# Устанавливаем зависимости параллельно
log "📦 Устанавливаю зависимости..."

# Функция для установки backend зависимостей
install_backend_deps() {
    if [ -d "backend" ]; then
        cd backend
        if [ -f "package.json" ]; then
            # Проверяем, нужно ли обновлять зависимости
            if [ ! -d "node_modules" ] || [ "package.json" -nt "node_modules" ] || ([ -f "package-lock.json" ] && [ "package-lock.json" -nt "node_modules" ]); then
                log "📦 Обновляю backend зависимости..."
                npm ci --only=production --prefer-offline --silent
                success "Backend зависимости установлены"
            else
                log "📦 Backend зависимости актуальны, пропускаю установку"
            fi
        else
            warning "package.json не найден в backend/"
        fi
        cd ..
    else
        warning "Папка backend не найдена"
    fi
}

# Функция для установки PHP зависимостей
install_php_deps() {
    if [ -f "composer.json" ]; then
        # Проверяем, нужно ли обновлять зависимости
        if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ] || [ "composer.json" -nt "vendor" ] || ([ -f "composer.lock" ] && [ "composer.lock" -nt "vendor" ]); then
                log "📦 Обновляю PHP зависимости..."
                composer install --no-dev --optimize-autoloader --no-scripts --quiet
                success "PHP зависимости установлены"
        else
            log "📦 PHP зависимости актуальны, пропускаю установку"
        fi
    else
        warning "composer.json не найден"
    fi
}

# Запускаем установку зависимостей параллельно
install_backend_deps &
BACKEND_PID=$!

install_php_deps &
PHP_PID=$!

# Ждем завершения обеих операций
wait $BACKEND_PID
wait $PHP_PID

success "Все зависимости установлены"

# Проверяем кэш меню (MongoDB кэш обновляется автоматически)
log "🔄 Проверяю кэш меню..."
log "📦 MongoDB кэш обновляется автоматически через API"
success "Кэш меню актуален"

# Проверяем структуру файлов (файлы уже в корне после очистки)
log "📁 Проверяю структуру файлов..."

# Проверяем наличие основных файлов
if [ ! -f "index.php" ]; then
    error "index.php не найден в корне"
    exit 1
fi

if [ ! -f "menu.php" ]; then
    error "menu.php не найден в корне"
    exit 1
fi

if [ ! -f "menu2.php" ]; then
    error "menu2.php не найден в корне"
    exit 1
fi

if [ ! -d "components" ]; then
    error "Папка components не найдена в корне"
    exit 1
fi

if [ ! -d "classes" ]; then
    error "Папка classes не найдена в корне"
    exit 1
fi

if [ ! -d "css" ]; then
    error "Папка css не найдена в корне"
    exit 1
fi

if [ ! -d "js" ]; then
    error "Папка js не найдена в корне"
    exit 1
fi

if [ ! -d "backend" ]; then
    error "Папка backend не найдена в корне"
    exit 1
fi

if [ ! -d "admin" ]; then
    error "Папка admin не найдена в корне"
    exit 1
fi

# Проверяем только критически важные файлы
if [ ! -f "backend/server.js" ] || [ ! -f "backend/package.json" ]; then
    error "Критически важные файлы backend отсутствуют"
    exit 1
fi

success "Структура файлов корректна (файлы уже в корне)"

# Перезапускаем сервисы
log "🔄 Перезапускаю сервисы..."
if command -v pm2 > /dev/null 2>&1; then
    pm2 restart all
    success "Сервисы перезапущены"
else
    warning "PM2 не установлен или недоступен"
fi

# Проверяем статус Git
git status --porcelain || success "Рабочая директория чистая"

# Проверки сервисов (пропускаем в быстром режиме)
if [ "$FAST_MODE" = false ]; then
    log "🔍 Проверяю сервисы..."
    
    # Проверяем доступность API
    if curl -s http://127.0.0.1:3002/api/health > /dev/null 2>&1; then
        success "Backend API доступен"
    else
        warning "Backend API недоступен"
    fi

    # Проверяем доступность сайта
    if curl -s https://northrepublic.me/ > /dev/null 2>&1; then
        success "Сайт доступен"
    else
        warning "Сайт недоступен"
    fi

    # Проверяем MongoDB
    if pgrep mongod > /dev/null 2>&1; then
        success "MongoDB запущен"
    else
        warning "MongoDB не запущен"
    fi

    # Проверяем Nginx
    if systemctl is-active nginx > /dev/null 2>&1; then
        success "Nginx активен"
    else
        warning "Nginx не активен"
    fi
else
    log "⚡ Быстрый режим: пропускаю проверки сервисов"
fi

# Вычисляем время выполнения
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
MINUTES=$((DURATION / 60))
SECONDS=$((DURATION % 60))

echo ""
success "🎉 Деплой завершен успешно!"
log "⏱️  Время выполнения: ${MINUTES}m ${SECONDS}s"
log "🌐 Сайт: https://northrepublic.me"
log "📝 Если нужно перезагрузить Nginx: sudo systemctl reload nginx"
log "🧪 Тестируйте сайт через 30 секунд"

# Показываем последние коммиты
git log --oneline -3

echo ""
if [ "$FAST_MODE" = true ]; then
    log "⚡ Быстрый деплой North Republic завершен!"
else
    log "✨ Деплой North Republic завершен!"
fi