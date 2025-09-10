# Диаграмма связей MongoDB - North Republic

```
┌─────────────────────────────────────────────────────────────────┐
│                    NORTH REPUBLIC DATABASE                     │
│                        (northrepublic)                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   admin_users   │    │   admin_logs    │    │ admin_sessions  │
│                 │    │                 │    │                 │
│ • username      │◄──►│ • action        │    │ • session_id    │
│ • password_hash │    │ • username      │    │ • user_id       │
│ • role          │    │ • timestamp     │    │ • ip_address    │
│ • permissions   │    │ • level         │    │ • expires_at    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  page_content   │    │   admin_texts   │    │      menu       │
│                 │    │                 │    │                 │
│ • page          │    │ • key           │    │ • categories    │
│ • language      │    │ • ru/en/vi      │    │ • products      │
│ • content       │    │ • category      │    │ • updated_at    │
│ • meta          │    │ • updated_at    │    │ • data          │
│ • status        │    └─────────────────┘    └─────────────────┘
└─────────────────┘                                      │
         │                                               │
         │                                               ▼
         │                                    ┌─────────────────┐
         │                                    │  Poster API     │
         │                                    │  (External)     │
         │                                    │                 │
         │                                    │ • categories    │
         │                                    │ • products      │
         │                                    │ • prices        │
         │                                    └─────────────────┘
         │
         ▼
┌─────────────────┐    ┌─────────────────┐
│   sepay_logs    │    │  rate_limits    │
│                 │    │                 │
│ • transaction_id│    │ • identifier    │
│ • amount        │    │ • timestamp     │
│ • status        │    │ • ip            │
│ • account_number│    │ • user_agent    │
│ • timestamp     │    │ • endpoint      │
└─────────────────┘    └─────────────────┘
         │                       │
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐
│   BIDV Sepay    │    │   API Limits    │
│   (External)    │    │   Protection    │
│                 │    │                 │
│ • transactions  │    │ • DDoS protect  │
│ • payments      │    │ • rate limiting │
│ • webhooks      │    │ • spam prevent  │
└─────────────────┘    └─────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND USAGE                          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   index.php     │    │  /admin/pages/  │    │ /admin/users/   │
│                 │    │                 │    │                 │
│ • page_content  │    │ • page_content  │    │ • admin_users   │
│ • menu          │    │ • save/edit     │    │ • CRUD users    │
│ • translations  │    │ • publish       │    │ • permissions   │
└─────────────────┘    └─────────────────┘    └─────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  /admin/logs/   │    │/admin/database/ │    │  /admin/sepay/  │
│                 │    │                 │    │                 │
│ • admin_logs    │    │ • all collections│   │ • sepay_logs    │
│ • view/search   │    │ • statistics    │    │ • transactions  │
│ • filter        │    │ • viewer        │    │ • monitoring    │
└─────────────────┘    └─────────────────┘    └─────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                         API USAGE                              │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  /api/menu/     │    │ /api/poster/    │    │ /api/cache/     │
│                 │    │                 │    │                 │
│ • menu cache    │    │ • Poster API    │    │ • update menu   │
│ • categories    │    │ • proxy         │    │ • refresh       │
│ • products      │    │ • products      │    │ • background    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Poster API    │    │   Menu Cache    │
│   JavaScript    │    │   (External)    │    │   Background    │
│                 │    │                 │    │                 │
│ • AJAX calls    │    │ • categories    │    │ • auto-update   │
│ • dynamic menu  │    │ • products      │    │ • 30min cycle   │
│ • real-time     │    │ • prices        │    │ • error handle  │
└─────────────────┘    └─────────────────┘    └─────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                        DATA FLOW                               │
└─────────────────────────────────────────────────────────────────┘

1. ADMIN LOGIN:
   admin_users → admin_logs → admin_sessions

2. PAGE CONTENT:
   page_content → index.php → Frontend

3. MENU DATA:
   Poster API → menu → /api/menu/ → Frontend

4. PAYMENT LOGS:
   BIDV Sepay → sepay_logs → /admin/sepay/

5. RATE LIMITING:
   API Request → rate_limits → Allow/Block

6. TRANSLATIONS:
   admin_texts → page_content → Frontend

┌─────────────────────────────────────────────────────────────────┐
│                      SECURITY LAYERS                          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Authentication │    │  Authorization  │    │  Rate Limiting  │
│                 │    │                 │    │                 │
│ • admin_users   │    │ • permissions   │    │ • rate_limits   │
│ • password_hash │    │ • role-based    │    │ • IP tracking   │
│ • sessions      │    │ • page access   │    │ • endpoint limit│
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Login System   │    │  Access Control │    │  DDoS Protection│
│                 │    │                 │    │                 │
│ • /admin/auth/  │    │ • auth-check.php│    │ • API limits    │
│ • session mgmt  │    │ • page guards   │    │ • auto-cleanup  │
│ • logout        │    │ • admin panel   │    │ • monitoring    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Ключевые особенности архитектуры:

### 1. **Централизованная аутентификация**
- Все админские действия логируются в `admin_logs`
- Сессии управляются через `admin_sessions`
- Права доступа контролируются через `admin_users.permissions`

### 2. **Многоязычная система**
- `page_content` хранит полный HTML контент
- `admin_texts` для переводов интерфейса
- Поддержка ru, en, vi языков

### 3. **Кэширование внешних API**
- `menu` кэширует данные Poster API
- Автоматическое обновление каждые 30 минут
- Fallback на прямые API вызовы

### 4. **Мониторинг и безопасность**
- `sepay_logs` для отслеживания платежей
- `rate_limits` для защиты от DDoS
- Полное логирование в `admin_logs`

### 5. **Масштабируемость**
- Индексы для быстрого поиска
- Автоматическая очистка старых данных
- Горизонтальное масштабирование через MongoDB
