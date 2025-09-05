# Правила проекта

## Git Workflow
- Все изменения в `main` ветке
- Коммиты с описательными сообщениями
- После деплоя обновлять локальную копию `index.html`

## Структура проекта
```
NRsite/
├── backend/          # Node.js API (Express + Poster API)
├── frontend/         # React приложение
├── template/         # HTML шаблон
├── index.html        # Гибридный HTML (шаблон + React)
└── deploy.sh         # Скрипт деплоя
```

## Технологии
- **Frontend**: React, CSS, HTML шаблон
- **Backend**: Node.js, Express, Poster POS API v3
- **Deploy**: PM2, Nginx, SSH

## Важные файлы
- `index.html` - гибридный файл (НЕ перезаписывать!)
- `deploy.sh` - автоматический деплой
- `frontend/src/components/DynamicMenu.jsx` - меню
- `backend/services/posterService.js` - API Poster

## Правила разработки
1. Не изменять `index.html` напрямую - только через деплой
2. Все стили в `frontend/src/index.css`
3. API endpoints в `backend/routes/`
4. Конфигурация в `backend/config.env`

## Деплой
- Только через `deploy.sh`
- Скрипт автоматически обновляет все файлы
- Не требует ручных правок