# Конфигурация деплоя North Republic

## 🌐 Серверная информация

### Основные данные
- **Сервер**: `nr` (SSH алиас)
- **IP**: `159.253.23.113`
- **Путь к проекту**: `/var/www/northrepubli_usr/data/www/northrepublic.me`
- **Домен**: `https://northrepublic.me`
- **PM2 процесс**: `northrepublic-backend`

### SSH конфигурация
```bash
# В ~/.ssh/config
Host nr
    HostName 159.253.23.113
    User northrepubli_usr
    Port 22
```

## 📁 Структура проекта на сервере

```
/var/www/northrepubli_usr/data/www/northrepublic.me/
├── backend/                 # Backend приложение
│   ├── package.json
│   ├── server.js
│   └── ...
├── frontend/                # Frontend приложение
│   ├── package.json
│   ├── src/
│   └── ...
├── template/                # Template файлы
│   ├── css/
│   ├── js/
│   └── images/
├── static/                  # Собранные React файлы
│   ├── css/
│   └── js/
├── index.html              # Гибридный HTML файл
├── deploy.sh               # Скрипт деплоя
└── ...
```

## 🔧 Конфигурация PM2

### Текущий процесс
```json
{
  "name": "northrepublic-backend",
  "script": "server.js",
  "cwd": "/var/www/northrepubli_usr/data/www/northrepublic.me/backend",
  "instances": 1,
  "exec_mode": "fork",
  "watch": false,
  "max_memory_restart": "1G",
  "env": {
    "NODE_ENV": "production",
    "PORT": 3000
  }
}
```

### Команды PM2
```bash
# Статус
pm2 list

# Логи
pm2 logs northrepublic-backend

# Перезапуск
pm2 restart northrepublic-backend

# Перезапуск всех
pm2 restart all

# Мониторинг
pm2 monit
```

## 📦 Зависимости

### Backend (Node.js)
- **Версия Node**: 18.x+
- **Основные пакеты**:
  - `express`
  - `cors`
  - `dotenv`
  - `axios`

### Frontend (React)
- **Версия Node**: 18.x+
- **Основные пакеты**:
  - `react`
  - `react-dom`
  - `react-scripts`

## 🌍 Переменные окружения

### Backend (.env)
```env
NODE_ENV=production
PORT=3000
POSTER_API_URL=https://joinposter.com/api
POSTER_API_TOKEN=your_token_here
```

### Frontend
- **Homepage**: `/` (в package.json)
- **Build path**: `build/`
- **Public path**: `public/`

## 🔄 Процесс сборки

### Frontend сборка
```bash
cd frontend
npm run build
# Создает папку build/ с:
# - static/css/main.*.css
# - static/js/main.*.js
# - index.html (НЕ используется)
```

### Backend сборка
```bash
cd backend
npm install
# Устанавливает зависимости
```

## 📋 Файлы для отслеживания

### Критически важные
- `index.html` - гибридный HTML файл
- `deploy.sh` - скрипт деплоя
- `static/js/main.*.js` - React приложение
- `static/css/main.*.css` - React стили

### Template файлы
- `template/css/vendor.css` - Template стили
- `template/css/styles.css` - Template стили
- `template/js/plugins.js` - Template JS
- `template/js/main.js` - Template JS

## 🚨 Критические моменты

### index.html
- **Важно**: Должен содержать ссылки на оба набора стилей и JS
- **Проблема**: `git clean -fd` удаляет файл
- **Решение**: Автоматическое восстановление в скрипте деплоя

### JS файлы
- **Проблема**: Имена файлов меняются при каждой сборке
- **Решение**: Автоматическое обновление ссылок в скрипте деплоя

### Права доступа
- **Проблема**: `deploy.sh` теряет права на выполнение
- **Решение**: `chmod +x deploy.sh` в скрипте

## 📊 Мониторинг

### Проверка здоровья
```bash
# HTTP статус
curl -I https://northrepublic.me

# PM2 статус
pm2 list

# Логи
pm2 logs --lines 20
```

### Метрики
- **Время ответа**: < 2 секунд
- **Память**: < 100MB
- **CPU**: < 50%

## 🔧 Резервное копирование

### Важные файлы для бэкапа
```bash
# Создать бэкап
tar -czf backup-$(date +%Y%m%d).tar.gz \
  /var/www/northrepubli_usr/data/www/northrepublic.me/index.html \
  /var/www/northrepubli_usr/data/www/northrepublic.me/deploy.sh \
  /var/www/northrepubli_usr/data/www/northrepublic.me/backend/config.env
```

## 📞 Контакты

### Техническая поддержка
- **SSH доступ**: `ssh nr`
- **Логи**: `pm2 logs`
- **Статус**: `pm2 list`

### Мониторинг
- **Сайт**: https://northrepublic.me
- **API**: https://northrepublic.me/api/health

---
*Конфигурация актуальна на: $(date)*
