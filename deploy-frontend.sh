#!/bin/bash

# Frontend Deployment Script
# Деплой только frontend

set -e

echo "🎨 Frontend Deployment..."

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

PROJECT_DIR="/var/www/northrepubli_usr/data/www/northrepublic.me"

# Проверка подключения
log_info "Checking server connection..."
if ! ssh nr "echo 'Connection successful'" > /dev/null 2>&1; then
    log_error "Cannot connect to server"
    exit 1
fi

# Обновление кода
log_info "Updating code..."
ssh nr "cd $PROJECT_DIR && git fetch origin && git reset --hard origin/main"

# Установка зависимостей и сборка
log_info "Installing dependencies and building..."
ssh nr "cd $PROJECT_DIR/frontend && npm ci --legacy-peer-deps"
ssh nr "cd $PROJECT_DIR/frontend && npm run build"

# Копирование файлов
log_info "Copying files..."
ssh nr "cd $PROJECT_DIR && cp -r frontend/build/* ."
ssh nr "cd $PROJECT_DIR && cp -r frontend/public/images ."

log_success "Frontend deployment completed!"
