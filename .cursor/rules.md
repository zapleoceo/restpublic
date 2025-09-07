# Правила проекта North Republic

## Основные принципы
- **НЕ создавай** ненужные компоненты и файлы
- **Делай оптимально** - минимальный код, максимальная функциональность
- **Следуй структуре** - не нарушай архитектуру проекта
- **Тестируй локально** - все изменения проверяй перед деплоем

## Git Workflow
- Все изменения в `main` ветке
- **Деплой**: `git push origin main` → `ssh nr "git pull origin main"`
- **Тестовые файлы**: копируй по SSH, удаляй после тестов, НЕ коммить в git

## Структура проекта (АКТУАЛЬНАЯ)
```
NRsite/
├── backend/           # Node.js API (Express + Poster API)
├── classes/           # PHP классы (MenuCache)
├── components/        # PHP компоненты (header, footer, cart)
├── css/              # Стили (vendor, styles, custom)
├── fonts/            # Локальные шрифты (Serati)
├── images/           # Основные изображения
├── js/               # JavaScript (main, plugins)
├── template/         # Шаблоны и ресурсы
├── index.php         # Главная страница
├── menu.php          # Страница меню
└── deploy.sh         # Скрипт деплоя
```

## Технологии
- **Frontend**: PHP 8.x, HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: Node.js, Express.js, MongoDB, Poster POS API v3
- **Шрифты**: Roboto Flex (Google Fonts), Serati (локальный)
- **Deploy**: PM2, Nginx, Apache, SSH

## Важные файлы
- `index.php` - главная страница с мини-меню
- `menu.php` - полная страница меню с сортировкой
- `backend/services/posterService.js` - API Poster
- `classes/MenuCache.php` - MongoDB кэш
- `css/styles.css` - основные стили
- `fonts/` - локальные шрифты Serati

## Правила разработки
1. **PHP файлы** в корне проекта
2. **API endpoints** в `backend/routes/`
3. **Конфигурация** в `backend/config.env`
4. **MongoDB** для кэширования меню (порт 27017)
5. **Деплой** через `deploy.sh`

## Деплой
- **SSH алиас**: `ssh nr`
- **Быстрый деплой**: `git push origin main` → `ssh nr "git pull origin main"`
- **Полный деплой**: `ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && ./deploy.sh"`

## Архитектура
- **PHP Frontend** + **Node.js Backend** + **MongoDB**
- **Nginx** (reverse proxy) + **Apache** (PHP) + **PM2** (Node.js)
- **Порты**: Nginx (80/443), Apache (81), Node.js (3002), MongoDB (27017)

## Логи
- **PM2**: `ssh nr "pm2 logs"`
- **PHP**: стандартные логи PHP
- **Nginx**: стандартные логи Nginx