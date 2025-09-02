#!/bin/bash

# North Republic Deployment Script
# Автоматический деплой backend и frontend на production сервер

set -e  # Остановка при ошибке

echo "🚀 Starting North Republic deployment..."

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функции для логирования
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Проверка подключения к серверу
log_info "Checking server connection..."
if ! ssh nr "echo 'Connection successful'" > /dev/null 2>&1; then
    log_error "Cannot connect to server. Check SSH configuration."
    exit 1
fi

# Переход в директорию проекта на сервере
PROJECT_DIR="/var/www/northrepubli_usr/data/www/northrepublic.me"
log_info "Navigating to project directory: $PROJECT_DIR"

# Остановка старых процессов
log_info "Stopping old processes..."
ssh nr "cd $PROJECT_DIR && pm2 stop northrepublic-backend || true"
ssh nr "cd $PROJECT_DIR && pm2 delete northrepublic-backend || true"

# Обновление кода из Git
log_info "Pulling latest changes from Git..."
ssh nr "cd $PROJECT_DIR && git fetch origin && git reset --hard origin/main && git clean -fd"

# Backend deployment
log_info "Deploying backend..."
ssh nr "cd $PROJECT_DIR/backend && npm install"

# Запуск backend
log_info "Starting backend service..."
ssh nr "cd $PROJECT_DIR/backend && pm2 start server.js --name northrepublic-backend"
ssh nr "pm2 save"

# Frontend deployment
log_info "Deploying frontend..."
ssh nr "cd $PROJECT_DIR/frontend && npm ci --legacy-peer-deps"
ssh nr "cd $PROJECT_DIR/frontend && npm run build"

# Копирование собранных файлов и images
log_info "Copying built files and images..."
ssh nr "cd $PROJECT_DIR && cp -r frontend/build/* ."
ssh nr "cd $PROJECT_DIR && cp -r frontend/public/images ."

# Проверка статуса сервисов
log_info "Checking services status..."
ssh nr "pm2 list"

# Health check
log_info "Performing health check..."
sleep 10
if ssh nr "curl -f http://localhost:3002/api/health" > /dev/null 2>&1; then
    log_success "Backend health check passed"
else
    log_error "Backend health check failed"
    exit 1
fi

# Финальная проверка
log_info "Final production health check..."
sleep 5
if curl -f https://northrepublic.me/api/health > /dev/null 2>&1; then
    log_success "Production health check passed"
else
    log_warning "Production health check failed (may need more time)"
fi

log_success "Deployment completed successfully!"
log_info "Check https://northrepublic.me for the updated site"
