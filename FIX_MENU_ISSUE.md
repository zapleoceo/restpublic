# Исправление проблемы с загрузкой меню

## Проблема
Страница `https://northrepublic.me/menu.php` не загружает контент из-за нескольких критических проблем в архитектуре.

## Исправления

### 1. ✅ Исправлен порт MongoDB
**Файл:** `php/classes/MenuCache.php`
- Изменен порт с 27018 на 27017 (стандартный порт MongoDB)

### 2. ✅ Переделана загрузка данных в menu.php
**Файл:** `php/menu.php`
- Теперь использует MongoDB кэш как основной источник данных
- Добавлен fallback на API запросы если MongoDB недоступен
- Улучшена обработка ошибок

### 3. ✅ Исправлена конфигурация API
**Файлы:** 
- `backend/config.env` - добавлен реальный токен Poster API
- `backend/server.js` - исправлен путь к .env файлу
- `backend/services/posterService.js` - исправлено имя переменной окружения

### 4. ✅ Улучшен fallback механизм
**Файл:** `php/classes/MenuCache.php`
- Исправлен URL для обновления кэша (добавлен порт 3002)
- Улучшено логирование ошибок
- Увеличен таймаут для запросов

### 5. ✅ Создан скрипт инициализации кэша
**Файл:** `php/init-cache.php`
- Автоматически заполняет MongoDB кэш данными из API
- Проверяет статус кэша и принудительно обновляет при необходимости

### 6. ✅ Обновлен скрипт развертывания
**Файл:** `deploy.sh`
- Добавлена инициализация кэша меню при деплое
- Автоматический запуск `init-cache.php`

## Архитектура решения

```
PHP Frontend (menu.php)
    ↓
MongoDB Cache (MenuCache.php) ← Основной источник
    ↓ (если недоступен)
Node.js API (backend) ← Fallback
    ↓
Poster API ← Источник данных
```

## Порядок развертывания

1. **На сервере выполнить:**
   ```bash
   cd /var/www/northrepubli_usr/data/www/northrepublic.me
   ./deploy.sh
   ```

2. **Проверить статус сервисов:**
   ```bash
   # MongoDB
   sudo systemctl status mongodb
   
   # Backend API
   pm2 status
   
   # Проверить API
   curl http://127.0.0.1:3002/api/health
   ```

3. **Инициализировать кэш вручную (если нужно):**
   ```bash
   php php/init-cache.php
   ```

## Проверка работы

1. **Проверить MongoDB кэш:**
   ```bash
   mongo northrepublic --eval "db.menu.findOne()"
   ```

2. **Проверить API:**
   ```bash
   curl https://northrepublic.me:3002/api/menu
   ```

3. **Проверить страницу:**
   - Открыть https://northrepublic.me/menu.php
   - Должны загрузиться категории и продукты

## Логи для отладки

- **PHP ошибки:** `/var/log/apache2/error.log`
- **Backend логи:** `pm2 logs northrepublic-backend`
- **MongoDB логи:** `/var/log/mongodb/mongod.log`

## Возможные проблемы

1. **MongoDB не запущен:**
   ```bash
   sudo systemctl start mongodb
   ```

2. **Backend API не отвечает:**
   ```bash
   pm2 restart northrepublic-backend
   ```

3. **Poster API токен неверный:**
   - Проверить токен в `backend/config.env`
   - Обновить из `.cursor/env.txt`

4. **PHP MongoDB расширение не установлено:**
   ```bash
   sudo apt install php-mongodb
   composer install
   ```

## Результат

После применения всех исправлений:
- ✅ Страница menu.php загружает данные из MongoDB кэша
- ✅ Быстрая загрузка страницы (данные из локальной БД)
- ✅ Автоматическое обновление кэша в фоне
- ✅ Fallback на API если MongoDB недоступен
- ✅ Единообразная архитектура для всех страниц
