# Правила проекта North Republic

## Git Workflow
- Все изменения в `main` ветке
- Деплой: `git push origin main` → `ssh nr "git pull origin main"`

## Структура проекта
```
NRsite/
├── php/               # PHP Frontend
│   ├── index.php      # Главная страница
│   ├── menu.php       # Страница меню
│   └── classes/       # PHP классы (MenuCache)
├── backend/           # Node.js API (Express + Poster API)
├── template/          # HTML шаблон и ресурсы
└── deploy.sh          # Скрипт деплоя
```

## Технологии
- **Frontend**: PHP, HTML, CSS, JavaScript
- **Backend**: Node.js, Express, MongoDB, Poster POS API v3
- **Deploy**: PM2, Nginx, Apache, SSH

## Важные файлы
- `index.php` - главная страница (копируется из php/)
- `menu.php` - страница меню (копируется из php/)
- `deploy.sh` - автоматический деплой
- `backend/services/posterService.js` - API Poster
- `php/classes/MenuCache.php` - MongoDB кэш

## Правила разработки
1. PHP файлы в папке `php/`
2. API endpoints в `backend/routes/`
3. Конфигурация в `backend/config.env`
4. MongoDB для кэширования меню
5. Деплой через `deploy.sh` (копирует файлы в корень)

## Деплой
- **SSH алиас**: `ssh nr`
- **Полный деплой**: `ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && ./deploy.sh"`
- **Быстрый деплой**: `git push origin main` → `ssh nr "git pull origin main"`

## Архитектура
- **PHP Frontend** + **Node.js Backend** + **MongoDB**
- **Nginx** (reverse proxy) + **Apache** (PHP) + **PM2** (Node.js)
- **Порты**: Nginx (80/443), Apache (81), Node.js (3002), MongoDB (27018)

## Логи
- **PM2**: `ssh nr "pm2 logs"`
- **PHP**: стандартные логи PHP
- **Nginx**: стандартные логи Nginx