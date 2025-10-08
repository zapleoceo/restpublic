# North Republic Backend API

Backend сервис для работы с Poster API и предоставления данных меню для сайта North Republic.

## 🚀 Быстрый старт

### Установка зависимостей
```bash
npm install
```

### Настройка переменных окружения
Скопируйте `config.env.example` в `config.env` и настройте переменные:

```bash
cp config.env.example config.env
```

Обязательные переменные:
- `POSTER_API_TOKEN` - токен для доступа к Poster API
- `PORT` - порт сервера (по умолчанию 3002)

### Запуск
```bash
# Продакшн
npm start

# Разработка
npm run dev
```

## 📡 API Endpoints

### Health Check
```
GET /api/health
```

### Меню
```
GET /api/menu                    # Полное меню (категории + продукты)
GET /api/menu/categories         # Только категории
GET /api/menu/popular?limit=5    # Популярные продукты
GET /api/menu/categories/:id/products  # Продукты по категории
GET /api/menu/products/:id       # Конкретный продукт
```

### Poster API Proxy
```
GET /api/poster/:method          # Прокси для Poster API методов
POST /api/poster/:method         # POST запросы к Poster API
```

### Управление кэшем
```
GET /api/menu/cache/stats        # Статистика кэша
POST /api/menu/cache/clear       # Очистка кэша
```

## 🔧 Конфигурация

### Переменные окружения

| Переменная | Описание | По умолчанию |
|------------|----------|--------------|
| `PORT` | Порт сервера | 3002 |
| `NODE_ENV` | Окружение | production |
| `POSTER_API_TOKEN` | Токен Poster API | - |
| `POSTER_API_BASE_URL` | Базовый URL Poster API | https://joinposter.com/api |
| `CACHE_TTL` | Время жизни кэша (мс) | 300000 (5 мин) |
| `CORS_ORIGIN` | Разрешенный origin | https://veranda.my |

### Кэширование

- **Категории**: кэшируются на 5 минут
- **Продукты**: кэшируются на 5 минут  
- **Популярные продукты**: кэшируются на 30 минут
- **Статистика продаж**: запрашивается для определения популярности

## 📊 Структура данных

### Категория
```json
{
  "category_id": "123",
  "name": "Кофе",
  "name_en": "Coffee",
  "visible": "1"
}
```

### Продукт
```json
{
  "product_id": "456",
  "name": "Эспрессо",
  "name_en": "Espresso",
  "description": "Классический эспрессо",
  "price": "15000",
  "price_normalized": 150.00,
  "price_formatted": "150.00",
  "image_url": "https://joinposter.com/api/image?image_id=789&size=300x300",
  "category_id": "123",
  "hidden": "0",
  "spots": [
    {
      "spot_id": "1",
      "visible": "1"
    }
  ]
}
```

## 🔒 Безопасность

- Токен Poster API хранится только на сервере
- CORS настроен только для домена veranda.my
- Все запросы к Poster API проходят через backend
- Helmet.js для защиты заголовков

## 📝 Логирование

Используется Morgan для логирования HTTP запросов:
- Формат: `combined`
- Включает IP, время, метод, статус, размер ответа

## 🚨 Обработка ошибок

- Все ошибки логируются
- Пользователю возвращается безопасное сообщение об ошибке
- Fallback механизмы для критических функций

## 🔄 Развертывание

### PM2
```bash
pm2 start server.js --name veranda-backend
pm2 save
pm2 startup
```

### Docker (опционально)
```bash
docker build -t veranda-backend .
docker run -p 3002:3002 veranda-backend
```

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи: `pm2 logs veranda-backend`
2. Проверьте статус: `pm2 status`
3. Перезапустите: `pm2 restart veranda-backend`
