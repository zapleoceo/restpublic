#!/bin/bash

# Quick Backend Deployment Script
# Быстрый деплой только backend для быстрых изменений

set -e

echo "🚀 Quick Backend Deployment..."

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

# Перезапуск backend
log_info "Restarting backend..."
ssh nr "cd $PROJECT_DIR && pm2 restart northrepublic-backend"

# Health check
log_info "Health check..."
sleep 5
if ssh nr "curl -f http://localhost:3002/api/health" > /dev/null 2>&1; then
    log_success "Backend restarted successfully"
else
    log_error "Backend health check failed"
    exit 1
fi

log_success "Quick backend deployment completed!"
