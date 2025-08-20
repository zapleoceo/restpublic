#!/bin/bash

# RestPublic Auto Build Script v1.0
# Автоматический скрипт для обновления и сборки проекта

set -e  # Остановить выполнение при ошибке

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Функция для логирования
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

step() {
    echo -e "${PURPLE}[STEP]${NC} $1"
}

# Проверка наличия Git
if ! command -v git &> /dev/null; then
    error "Git не установлен. Установите Git и попробуйте снова."
    exit 1
fi

# Проверка наличия Node.js
if ! command -v node &> /dev/null; then
    error "Node.js не установлен. Установите Node.js и попробуйте снова."
    exit 1
fi

# Проверка наличия npm
if ! command -v npm &> /dev/null; then
    error "npm не установлен. Установите npm и попробуйте снова."
    exit 1
fi

log "🚀 Начинаем автоматическую сборку RestPublic проекта..."

# Показываем текущую директорию
log "📁 Текущая директория: $(pwd)"

# Проверяем, что мы в Git репозитории
if [ ! -d ".git" ]; then
    error "Текущая директория не является Git репозиторием"
    exit 1
fi

# Показываем текущую ветку
CURRENT_BRANCH=$(git branch --show-current)
log "🌿 Текущая ветка: $CURRENT_BRANCH"

# Проверяем статус Git
step "Проверяем статус Git репозитория..."
if [ -n "$(git status --porcelain)" ]; then
    warning "Обнаружены несохраненные изменения:"
    git status --short
    read -p "Продолжить сборку? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log "Сборка отменена пользователем"
        exit 0
    fi
else
    success "Рабочая директория чистая"
fi

# Получаем последние изменения
step "Получаем последние изменения из репозитория..."
log "Выполняем git fetch..."
git fetch origin

# Проверяем, есть ли новые коммиты
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse origin/$CURRENT_BRANCH)

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    success "Код уже актуален, новых изменений нет"
else
    log "Обнаружены новые изменения, выполняем pull..."
    git pull origin $CURRENT_BRANCH
    success "Код успешно обновлен"
fi

# Создаем директорию для логов
mkdir -p logs

# Функция для установки зависимостей
install_dependencies() {
    local dir=$1
    local name=$2
    
    step "Устанавливаем зависимости для $name..."
    cd "$dir"
    
    if [ -f "package-lock.json" ]; then
        log "Обнаружен package-lock.json, используем npm ci для быстрой установки..."
        npm ci
    else
        log "Устанавливаем зависимости через npm install..."
        npm install
    fi
    
    if [ $? -eq 0 ]; then
        success "Зависимости для $name установлены успешно"
    else
        error "Ошибка при установке зависимостей для $name"
        exit 1
    fi
    
    cd ..
}

# Функция для сборки проекта
build_project() {
    local dir=$1
    local name=$2
    local build_script=$3
    
    step "Собираем $name..."
    cd "$dir"
    
    log "Выполняем: npm run $build_script"
    npm run $build_script
    
    if [ $? -eq 0 ]; then
        success "$name собран успешно"
    else
        error "Ошибка при сборке $name"
        exit 1
    fi
    
    cd ..
}

# Устанавливаем зависимости для всех проектов
install_dependencies "backend" "Backend"
install_dependencies "frontend" "Frontend"
install_dependencies "bot" "Telegram Bot"

# Собираем проекты
build_project "frontend" "Frontend" "build"
build_project "bot" "Telegram Bot" "build"

# Копируем собранные файлы frontend в корень
step "Копируем собранные файлы frontend в корень проекта..."
if [ -d "frontend/dist" ]; then
    cp -r frontend/dist/* ./
    success "Файлы frontend скопированы в корень"
else
    error "Директория frontend/dist не найдена"
    exit 1
fi

# Проверяем результаты сборки
step "Проверяем результаты сборки..."

# Проверяем frontend
if [ -f "index.html" ]; then
    success "Frontend: index.html найден"
else
    error "Frontend: index.html не найден"
    exit 1
fi

# Проверяем bot
if [ -f "bot/dist/bot.js" ]; then
    success "Bot: bot.js собран"
else
    error "Bot: bot.js не найден"
    exit 1
fi

# Проверяем backend
if [ -f "backend/server.js" ]; then
    success "Backend: server.js найден"
else
    error "Backend: server.js не найден"
    exit 1
fi

# Показываем размеры собранных файлов
step "Информация о собранных файлах:"
if [ -d "frontend/dist" ]; then
    FRONTEND_SIZE=$(du -sh frontend/dist | cut -f1)
    log "Frontend размер: $FRONTEND_SIZE"
fi

if [ -f "bot/dist/bot.js" ]; then
    BOT_SIZE=$(du -h bot/dist/bot.js | cut -f1)
    log "Bot размер: $BOT_SIZE"
fi

# Создаем файл с информацией о сборке
BUILD_INFO="logs/build-info.txt"
echo "=== RestPublic Build Info ===" > $BUILD_INFO
echo "Build Date: $(date)" >> $BUILD_INFO
echo "Git Branch: $CURRENT_BRANCH" >> $BUILD_INFO
echo "Git Commit: $(git rev-parse --short HEAD)" >> $BUILD_INFO
echo "Node Version: $(node --version)" >> $BUILD_INFO
echo "NPM Version: $(npm --version)" >> $BUILD_INFO
echo "Frontend Size: $FRONTEND_SIZE" >> $BUILD_INFO
echo "Bot Size: $BOT_SIZE" >> $BUILD_INFO

success "Информация о сборке сохранена в $BUILD_INFO"

# Финальное сообщение
echo
log "🎉 Сборка проекта завершена успешно!"
log "📋 Следующие шаги:"
log "   1. Запустить backend: cd backend && npm start"
log "   2. Запустить bot: cd bot && npm start"
log "   3. Открыть frontend в браузере"
log "📁 Логи сборки: $BUILD_INFO"

# Показываем команды для запуска
echo
info "Команды для запуска сервисов:"
echo "  Backend:  cd backend && npm start"
echo "  Bot:      cd bot && npm start"
echo "  Frontend: открыть index.html в браузере"
echo
