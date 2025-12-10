# Конфигурация базы данных MongoDB

## Основная БД (Primary)

- **Название:** `veranda2026`
- **Порт:** `27026`
- **URL:** `mongodb://localhost:27026`
- **Статус:** Основная рабочая БД (используется по умолчанию)
- **Systemd сервис:** `mongod-27026.service` (если настроен)

## Резервная БД (Fallback)

- **Название:** `veranda`
- **Порт:** `27017`
- **URL:** `mongodb://localhost:27017`
- **Статус:** Резервная БД для восстановления данных

## Логика работы

1. **При подключении:**
   - Сначала пытается подключиться к основной БД (`veranda2026:27026`)
   - Если основная недоступна → автоматически переключается на резервную (`veranda:27017`)

2. **При чтении данных:**
   - Читает из текущей БД (основной или резервной)
   - Если данные не найдены в резервной → пытается прочитать из основной

3. **При записи данных:**
   - Записывает в текущую БД
   - Если используется резервная БД → также пытается записать в основную (для синхронизации)

## Конфигурация

### Переменные окружения

**`.env` (корень проекта):**
```env
MONGODB_URL=mongodb://localhost:27026
MONGODB_DB_NAME=veranda2026
```

**`backend/config.env`:**
```env
MONGODB_URL=mongodb://localhost:27026
MONGODB_DB_NAME=veranda2026
```

### Класс DatabaseConfig

Все PHP классы используют `DatabaseConfig::connectWithFallback()` для автоматического выбора БД.

**Основные методы:**
- `getPrimaryConfig()` - конфигурация основной БД
- `getFallbackConfig()` - конфигурация резервной БД
- `connectWithFallback()` - подключение с автоматическим fallback
- `getCollection($name)` - получение коллекции с fallback

## Коллекции

Все коллекции созданы в обеих БД для совместимости:

1. `menu` - кэш меню
2. `events` - события
3. `users` - пользователи
4. `admin_texts` - переводы
5. `page_content` - контент страниц
6. `sepay_transactions` - транзакции SePay
7. `admin_logs` - логи администратора
8. `admin_users` - пользователи админки
9. `settings` - настройки
10. `transactions` - история транзакций
11. `user_sessions` - сессии
12. `cache_update_logs` - логи обновления кэша

## Мониторинг

**Проверка статуса:**
```bash
# Основная БД
systemctl status mongod-27026
mongosh veranda2026 --port 27026 --eval 'db.adminCommand({ping: 1})'

# Резервная БД
systemctl status mongod
mongosh veranda --port 27017 --eval 'db.adminCommand({ping: 1})'
```

**Проверка данных:**
```bash
# Основная БД
mongosh veranda2026 --port 27026 --eval 'db.menu.countDocuments()'

# Резервная БД
mongosh veranda --port 27017 --eval 'db.menu.countDocuments()'
```

