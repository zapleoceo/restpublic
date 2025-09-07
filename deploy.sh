#!/bin/bash

# Современный скрипт деплоя North Republic
# Использование: bash deploy.sh (на сервере)
# Автор: AI Assistant
# Версия: 2.0

set -e  # Остановка при ошибке

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

log "🚀 Начинаю деплой North Republic..."

# Проверяем, что мы на сервере
if [ ! -d "/var/www/northrepubli_usr/data/www/northrepublic.me" ]; then
    error "Запустите скрипт на сервере"
    exit 1
fi

# Переходим в рабочую директорию
cd /var/www/northrepubli_usr/data/www/northrepublic.me
log "📍 Рабочая директория: $(pwd)"

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

# Устанавливаем зависимости backend
log "📦 Устанавливаю зависимости backend..."
if [ -d "backend" ]; then
    cd backend
    if [ -f "package.json" ]; then
        npm install --production
        success "Backend зависимости установлены"
    else
        warning "package.json не найден в backend/"
    fi
    cd ..
else
    warning "Папка backend не найдена"
fi

# Устанавливаем зависимости PHP
log "📦 Устанавливаю зависимости PHP..."
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader --quiet
    success "PHP зависимости установлены"
else
    warning "composer.json не найден"
fi

# Инициализируем кэш меню
log "🔄 Инициализирую кэш меню..."
if [ -f "php/init-cache.php" ]; then
    php php/init-cache.php
    success "Кэш меню инициализирован"
elif [ -f "force-update-cache.php" ]; then
    php force-update-cache.php
    success "Кэш меню инициализирован"
else
    warning "Скрипты инициализации кэша не найдены"
fi

# Проверяем структуру файлов
log "📁 Проверяю структуру файлов..."

# Копируем PHP файлы в корень (если их нет)
if [ ! -f "index.php" ] && [ -f "php/index.php" ]; then
    cp php/index.php .
    success "index.php скопирован в корень"
fi

if [ ! -f "menu.php" ] && [ -f "php/menu.php" ]; then
    cp php/menu.php .
    success "menu.php скопирован в корень"
fi

# Копируем компоненты (если их нет)
if [ ! -d "components" ] && [ -d "php/components" ]; then
    cp -r php/components .
    success "components скопированы"
fi

# Копируем template файлы (если их нет)
if [ ! -d "css" ] && [ -d "template/css" ]; then
    cp -r template/css .
    success "CSS скопированы"
fi

if [ ! -d "js" ] && [ -d "template/js" ]; then
    cp -r template/js .
    success "JS скопированы"
fi

if [ ! -d "images" ] && [ -d "template/images" ]; then
    cp -r template/images .
    success "Images скопированы"
fi

# Копируем иконки (если их нет)
if [ ! -f "apple-touch-icon.png" ] && [ -f "template/apple-touch-icon.png" ]; then
    cp template/apple-touch-icon.png .
    success "apple-touch-icon.png скопирован"
fi

if [ ! -f "favicon.ico" ] && [ -f "template/favicon.ico" ]; then
    cp template/favicon.ico .
    success "favicon.ico скопирован"
fi

success "Структура файлов проверена"

# Перезапускаем сервисы
log "🔄 Перезапускаю сервисы..."
if command -v pm2 > /dev/null 2>&1; then
    pm2 restart all
    success "Сервисы перезапущены"
    
    # Показываем статус PM2
    log "📊 Статус PM2:"
    pm2 list
else
    warning "PM2 не установлен или недоступен"
fi

# Проверяем статус Git
log "📊 Статус Git:"
git status --porcelain || success "Рабочая директория чистая"

# Проверяем доступность API
log "🔍 Проверяю API..."
if curl -s http://127.0.0.1:3002/api/health > /dev/null 2>&1; then
    success "Backend API доступен"
else
    warning "Backend API недоступен"
fi

# Проверяем доступность сайта
log "🔍 Проверяю доступность сайта..."
if curl -s https://northrepublic.me/ > /dev/null 2>&1; then
    success "Сайт доступен"
else
    warning "Сайт недоступен"
fi

# Проверяем MongoDB
log "🔍 Проверяю MongoDB..."
if pgrep mongod > /dev/null 2>&1; then
    success "MongoDB запущен"
else
    warning "MongoDB не запущен"
fi

# Проверяем Nginx
log "🔍 Проверяю Nginx..."
if systemctl is-active nginx > /dev/null 2>&1; then
    success "Nginx активен"
else
    warning "Nginx не активен"
fi

echo ""
success "🎉 Деплой завершен успешно!"
log "🌐 Сайт: https://northrepublic.me"
log "📝 Если нужно перезагрузить Nginx: sudo systemctl reload nginx"
log "🧪 Тестируйте сайт через 30 секунд"

# Показываем последние коммиты
log "📝 Последние коммиты:"
git log --oneline -5

echo ""
log "✨ Деплой North Republic завершен!"