# Многоэтапная сборка для North Republic
FROM node:18-alpine AS base

# Устанавливаем зависимости для сборки
RUN apk add --no-cache git

# Этап сборки frontend
FROM base AS frontend-builder
WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm ci --only=production
COPY frontend/ ./
RUN npm run build

# Этап сборки backend
FROM base AS backend-builder
WORKDIR /app/backend
COPY backend/package*.json ./
RUN npm ci --only=production
COPY backend/ ./

# Этап сборки bot
FROM base AS bot-builder
WORKDIR /app/bot
COPY bot/package*.json ./
RUN npm ci --only=production
COPY bot/ ./
RUN npm run build

# Финальный образ
FROM node:18-alpine AS production

# Устанавливаем PM2 глобально
RUN npm install -g pm2

# Создаем пользователя для безопасности
RUN addgroup -g 1001 -S nodejs
RUN adduser -S nodejs -u 1001

# Создаем рабочую директорию
WORKDIR /app

# Копируем собранные файлы
COPY --from=frontend-builder /app/frontend/dist ./frontend/dist
COPY --from=backend-builder /app/backend ./backend
COPY --from=bot-builder /app/bot/dist ./bot/dist

# Копируем конфигурационные файлы
COPY ecosystem.config.js ./
COPY deploy.sh ./
COPY .env ./

# Создаем директорию для логов
RUN mkdir -p logs

# Устанавливаем права доступа
RUN chown -R nodejs:nodejs /app
RUN chmod +x deploy.sh
RUN chmod +x bot/dist/bot.js

# Переключаемся на пользователя nodejs
USER nodejs

# Открываем порт
EXPOSE 3002

# Запускаем PM2
CMD ["pm2-runtime", "start", "ecosystem.config.js"]
