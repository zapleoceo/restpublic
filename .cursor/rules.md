# Правила проекта North Republic

## Git Workflow
- Все изменения в `main` ветке
- Коммиты с описательными сообщениями
- Автоматический коммит после деплоя

## Структура проекта
```
NRsite/
├── php/               # PHP Frontend
│   ├── index.php      # Главная страница
│   └── menu.php       # Страница меню
├── backend/           # Node.js API (Express + Poster API)
├── template/          # HTML шаблон и ресурсы
└── deploy.sh          # Скрипт деплоя
```

## Технологии
- **Frontend**: PHP, HTML, CSS, JavaScript (template)
- **Backend**: Node.js, Express, MongoDB, Poster POS API v3
- **Deploy**: PM2, Nginx, Apache, SSH

## Важные файлы
- `php/index.php` - главная страница
- `php/menu.php` - страница меню
- `deploy.sh` - автоматический деплой
- `backend/services/posterService.js` - API Poster
- `template/` - оригинальные стили и ресурсы

## Правила разработки
1. Сохранять оригинальные стили из template/
2. PHP файлы в папке php/
3. API endpoints в backend/routes/
4. Конфигурация в backend/config.env
5. MongoDB для конфигураций и переводов

## Деплой
- **Полный деплой**: `ssh northrepubli_usr@159.253.23.113` → `./deploy.sh`
- **Быстрый деплой**: `./deploy-backend.sh` (если настроен SSH алиас 'nr')
- Скрипт автоматически обновляет все файлы
- Не требует ручных правок

## SSH подключение
- **Полная команда**: `ssh northrepubli_usr@159.253.23.113`
- **SSH алиас**: `nr` (если настроен в ~/.ssh/config)
- **Деплой**: только через скрипты деплоя

## Архитектура
- **PHP Frontend** + **Node.js Backend** + **MongoDB**
- **Nginx** (reverse proxy) + **Apache** (PHP) + **PM2** (Node.js)
- **Порты**: Nginx (80/443), Apache (81), Node.js (3002)