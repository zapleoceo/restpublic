# Структура данных MongoDB - North Republic

## Обзор базы данных
**База данных:** `northrepublic`  
**Порт:** `27018` (на продакшене)  
**Подключение:** `mongodb://localhost:27018`

---

## 📊 Коллекции и их назначение

### 1. **admin_users** - Пользователи админки
**Назначение:** Хранение учетных данных администраторов сайта

**Структура документа:**
```json
{
  "_id": ObjectId,
  "username": "zapleosoft",
  "password_hash": "$2y$10$...",
  "email": "admin@northrepublic.me",
  "role": "admin",
  "status": "active",
  "created_at": ISODate,
  "last_login": ISODate,
  "login_attempts": 0,
  "locked_until": null,
  "permissions": ["pages", "users", "logs", "database", "sepay"]
}
```

**Использование:**
- Аутентификация в админке (`/admin/auth/login.php`)
- Управление пользователями (`/admin/users/`)
- Проверка прав доступа (`admin/includes/auth-check.php`)

---

### 2. **admin_logs** - Логи действий администраторов
**Назначение:** Аудит всех действий в админке

**Структура документа:**
```json
{
  "_id": ObjectId,
  "action": "login",
  "message": "Успешный вход в систему",
  "data": {
    "username": "zapleosoft",
    "ip": "192.168.1.1"
  },
  "metadata": {
    "user_agent": "Mozilla/5.0...",
    "session_id": "abc123"
  },
  "timestamp": ISODate,
  "level": "info"
}
```

**Использование:**
- Отображение в админке (`/admin/logs/`)
- Безопасность и аудит
- Отладка проблем

---

### 3. **page_content** - Контент страниц сайта
**Назначение:** Хранение HTML-контента страниц на разных языках

**Структура документа:**
```json
{
  "_id": ObjectId,
  "page": "home",
  "language": "ru",
  "content": "<div>Полный HTML контент страницы...</div>",
  "meta": {
    "title": "North Republic - Главная",
    "description": "Ресторан в Нячанге",
    "keywords": "ресторан, нячанг, вьетнам"
  },
  "status": "published",
  "updated_at": ISODate,
  "updated_by": "admin"
}
```

**Использование:**
- Отображение контента на сайте (`index.php`)
- Редактирование в админке (`/admin/pages/`)
- Многоязычность (ru, en, vi)

---

### 4. **admin_texts** - Текстовые переводы
**Назначение:** Хранение переводов интерфейса (устаревшая система)

**Структура документа:**
```json
{
  "_id": ObjectId,
  "key": "menu.home",
  "ru": "Главная",
  "en": "Home",
  "vi": "Trang chủ",
  "category": "navigation",
  "updated_at": ISODate
}
```

**Использование:**
- Переводы интерфейса (заменяется на `page_content`)
- Многоязычная поддержка

---

### 5. **menu** - Кэш меню ресторана
**Назначение:** Кэширование данных меню из Poster API

**Структура документа:**
```json
{
  "_id": "current_menu",
  "data": {
    "categories": [...],
    "products": [...]
  },
  "categories": [
    {
      "category_id": "123",
      "category_name": "Основные блюда",
      "category_hidden": "0",
      "visible": [{"spot": "1", "visible": "1"}]
    }
  ],
  "products": [
    {
      "product_id": "456",
      "product_name": "Фо Бо",
      "menu_category_id": "123",
      "price": {"1": 150000},
      "hidden": "0",
      "spots": [{"spot": "1", "visible": "1"}],
      "sort_order": 10
    }
  ],
  "updated_at": ISODate
}
```

**Использование:**
- Отображение меню на сайте (`index.php`)
- Кэширование данных Poster API
- Сортировка по популярности

---

### 6. **sepay_logs** - Логи платежей Sepay
**Назначение:** Мониторинг транзакций BIDV

**Структура документа:**
```json
{
  "_id": ObjectId,
  "transaction_id": "TXN123456789",
  "amount": 500000,
  "status": "success",
  "description": "Оплата заказа #12345",
  "account_number": "1234567890",
  "bank_code": "BIDV",
  "timestamp": ISODate,
  "raw_data": {...},
  "processed_at": ISODate
}
```

**Использование:**
- Мониторинг платежей (`/admin/sepay/`)
- Фильтрация и поиск транзакций
- Статистика по платежам

---

### 7. **rate_limits** - Ограничения запросов
**Назначение:** Защита от DDoS и злоупотреблений

**Структура документа:**
```json
{
  "_id": ObjectId,
  "identifier": "192.168.1.1",
  "timestamp": ISODate,
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "endpoint": "/api/menu"
}
```

**Использование:**
- Rate limiting API запросов
- Защита от спама
- Автоматическая очистка старых записей

---

### 8. **admin_sessions** - Сессии администраторов
**Назначение:** Управление сессиями (планируется)

**Структура документа:**
```json
{
  "_id": ObjectId,
  "session_id": "abc123def456",
  "user_id": ObjectId,
  "username": "zapleosoft",
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "created_at": ISODate,
  "expires_at": ISODate,
  "last_activity": ISODate,
  "is_active": true
}
```

**Использование:**
- Управление сессиями
- Безопасность
- Принудительный выход

---

## 🔗 Связи между коллекциями

### Основные связи:
1. **admin_users** ↔ **admin_logs** (по username)
2. **page_content** ↔ **admin_texts** (по ключам переводов)
3. **menu** → **Poster API** (внешняя система)
4. **sepay_logs** → **BIDV API** (внешняя система)

### Индексы:
```javascript
// admin_users
db.admin_users.createIndex({"username": 1}, {"unique": true})
db.admin_users.createIndex({"email": 1}, {"unique": true})

// admin_logs
db.admin_logs.createIndex({"timestamp": -1})
db.admin_logs.createIndex({"action": 1})

// page_content
db.page_content.createIndex({"page": 1, "language": 1}, {"unique": true})
db.page_content.createIndex({"status": 1})

// sepay_logs
db.sepay_logs.createIndex({"timestamp": -1})
db.sepay_logs.createIndex({"transaction_id": 1}, {"unique": true})
db.sepay_logs.createIndex({"status": 1})

// rate_limits
db.rate_limits.createIndex({"identifier": 1, "timestamp": 1})
db.rate_limits.createIndex({"timestamp": 1}, {"expireAfterSeconds": 900})
```

---

## 📈 Статистика использования

### По коллекциям:
- **admin_users**: ~5-10 документов
- **admin_logs**: ~1000+ документов (растет)
- **page_content**: ~50-100 документов
- **admin_texts**: ~200-500 документов
- **menu**: 1 документ (обновляется)
- **sepay_logs**: ~100-1000 документов
- **rate_limits**: ~100-1000 документов (автоочистка)

### Размеры данных:
- **admin_users**: ~1KB
- **admin_logs**: ~1-10MB
- **page_content**: ~100KB-1MB
- **menu**: ~50-200KB
- **sepay_logs**: ~1-5MB

---

## 🛠️ Управление данными

### Админка:
- **Пользователи**: `/admin/users/`
- **Страницы**: `/admin/pages/`
- **Логи**: `/admin/logs/`
- **База данных**: `/admin/database/`
- **Платежи**: `/admin/sepay/`

### API:
- **Меню**: `/api/menu/` (Node.js)
- **Poster**: `/api/poster/` (Node.js)
- **Кэш**: `/api/cache/` (Node.js)

### Автоматические процессы:
- Обновление кэша меню (каждые 30 минут)
- Очистка rate_limits (каждые 15 минут)
- Ротация логов (по размеру/времени)

---

## 🔒 Безопасность

### Доступ:
- MongoDB доступна только локально
- Аутентификация через admin_users
- Rate limiting для API
- Логирование всех действий

### Резервное копирование:
- Ежедневные бэкапы MongoDB
- Хранение в отдельном хранилище
- Тестирование восстановления

---

## 📝 Примечания

1. **Миграция**: Проект мигрирован с JSON файлов на MongoDB
2. **Fallback**: Удалены все fallback механизмы (по требованию)
3. **Кэширование**: Отключено в пользу Cloudflare
4. **Многоязычность**: Поддержка ru, en, vi
5. **API**: Интеграция с Poster API и BIDV Sepay
