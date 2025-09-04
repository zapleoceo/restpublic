# Команды для деплоя North Republic

## 🚀 Быстрый деплой

### Полный деплой (рекомендуется)
```bash
# 1. Коммит и пуш
git add .
git commit -m "Описание изменений"
git push origin main

# 2. Деплой на сервер
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && git pull origin main && chmod +x deploy.sh && ./deploy.sh"

# 3. Обновление index.html
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html" > index.html
git add index.html
git commit -m "Update: index.html with new JS file reference"
git push origin main
```

## 🔧 Отдельные команды

### Проверка статуса
```bash
# Статус Git
git status

# Статус PM2 на сервере
ssh nr "pm2 list"

# Логи PM2
ssh nr "pm2 logs"
```

### Обновление версий
```bash
# Frontend
sed -i 's/"version": "1\.0\.[0-9]*"/"version": "1.0.XX"/' frontend/package.json

# Backend
sed -i 's/"version": "1\.0\.[0-9]*"/"version": "1.0.XX"/' backend/package.json
```

### Проверка файлов на сервере
```bash
# Проверить JS файлы
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && ls -la static/js/"

# Проверить index.html
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html | grep main.*js"

# Проверить доступность сайта
ssh nr "curl -I https://northrepublic.me"
```

### Восстановление файлов
```bash
# Восстановить index.html
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html" > index.html
scp index.html nr:/var/www/northrepubli_usr/data/www/northrepublic.me/

# Дать права на выполнение
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && chmod +x deploy.sh"
```

## 🐛 Отладка

### Проверка ошибок
```bash
# Проверить консоль браузера (вручную)
# Открыть https://northrepublic.me и нажать F12

# Проверить логи сервера
ssh nr "pm2 logs --lines 50"

# Проверить статус сервисов
ssh nr "pm2 status"
```

### Восстановление после ошибок
```bash
# Перезапуск PM2
ssh nr "pm2 restart all"

# Полная перезагрузка
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && ./deploy.sh"
```

## 📋 Чек-лист команд

### Перед деплоем
```bash
# 1. Проверить статус
git status

# 2. Увеличить версию
# (вручную в package.json)

# 3. Протестировать сборку
cd frontend && npm run build && cd ..
```

### После деплоя
```bash
# 1. Проверить сайт
ssh nr "curl -I https://northrepublic.me"

# 2. Обновить index.html
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html" > index.html

# 3. Закоммитить изменения
git add index.html
git commit -m "Update: index.html with new JS file reference"
git push origin main
```

## 🔄 Алиасы (опционально)

Добавить в `~/.bashrc` или `~/.zshrc`:
```bash
# Деплой
alias deploy-nr="ssh nr 'cd /var/www/northrepubli_usr/data/www/northrepublic.me && git pull origin main && chmod +x deploy.sh && ./deploy.sh'"

# Обновление index.html
alias update-index="ssh nr 'cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html' > index.html && git add index.html && git commit -m 'Update index.html' && git push origin main"

# Проверка статуса
alias check-nr="ssh nr 'pm2 list && echo && curl -I https://northrepublic.me'"
```

## 📝 Шаблоны коммитов

### Обычные изменения
```bash
git commit -m "Feature: Описание новой функции"
git commit -m "Fix: Исправление бага"
git commit -m "UI: Улучшение интерфейса"
```

### Версионные изменения
```bash
git commit -m "Release: Version 1.0.XX"
git commit -m "Update: index.html with new JS file reference (main.XXXXX.js)"
```

---
*Используйте эти команды для быстрого и безопасного деплоя*
