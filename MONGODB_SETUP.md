# MongoDB Кэширование для North Republic

## Архитектура

1. **PHP** → **MongoDB** (быстрая загрузка страниц)
2. **PHP** → **API** → **Poster API** → **MongoDB** (ленивое обновление в фоне)

## Установка

### 1. Установка MongoDB на сервере
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install mongodb

# Запуск MongoDB
sudo systemctl start mongodb
sudo systemctl enable mongodb
```

### 2. Установка PHP MongoDB драйвера
```bash
# На сервере
cd /var/www/northrepubli_usr/data/www/northrepublic.me
composer install
```

### 3. Настройка автоматического обновления
```bash
# Обновление происходит автоматически при каждом запросе к сайту
# Если кэш устарел (старше 30 минут) - запускается фоновое обновление
# Никаких дополнительных настроек не требуется
```

## Использование

### Обновление кэша вручную
```bash
# Через API
curl -X POST https://northrepublic.me/api/cache/update-menu

# Или просто откройте сайт - обновление произойдет автоматически
```

### Проверка статуса
```bash
# Проверка MongoDB
mongo northrepublic --eval "db.menu.findOne()"

# Проверка логов
tail -f /var/www/northrepubli_usr/data/logs/menu-update.log
```

## Преимущества

✅ **Быстрая загрузка** - данные из локальной MongoDB  
✅ **Надежность** - нет зависимости от внешнего API  
✅ **Ленивое обновление** - кэш обновляется автоматически при необходимости  
✅ **Фоновое обновление** - не блокирует загрузку страницы  
✅ **Fallback** - если кэш пустой, показываем ошибку  

## Структура данных в MongoDB

```javascript
{
  "_id": "current_menu",
  "data": { /* полные данные от Poster API */ },
  "categories": [ /* массив категорий */ ],
  "products": [ /* массив продуктов */ ],
  "updated_at": ISODate("2024-01-01T00:00:00Z")
}
```

## Мониторинг

- Логи обновления: `/var/www/northrepubli_usr/data/logs/menu-update.log`
- Статус MongoDB: `systemctl status mongodb`
- Проверка кэша: `php -r "require 'classes/MenuCache.php'; $cache = new MenuCache(); var_dump($cache->getMenu());"`
