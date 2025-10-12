# 🚚 Перемещение остатков со склада 1 на склад 3

## Быстрый старт

```bash
# 1. Проверить остатки
php check_storage_leftovers.php

# 2. Предварительный просмотр (безопасно)
php safe_move_storage.php --dry-run

# 3. Выполнить перемещение
php safe_move_storage.php
```

## 📁 Созданные файлы

| Файл | Назначение |
|------|------------|
| `check_storage_leftovers.php` | Проверка остатков на складах |
| `move_storage_leftovers.php` | Простое автоматическое перемещение |
| `safe_move_storage.php` | Безопасное перемещение с подтверждением |
| `STORAGE_MOVE_INSTRUCTIONS.md` | Подробная инструкция |

## 🔧 Требования

- PHP 8.x с cURL
- Доступ к Poster API
- Настроенный `.env` файл
- SSH доступ к серверу

## 🚀 Запуск на сервере

```bash
# Подключение
ssh veranda

# Переход в директорию
cd /var/www/veranda_my_usr/data/www/veranda.my

# Проверка и перемещение
php check_storage_leftovers.php
php safe_move_storage.php --dry-run
php safe_move_storage.php
```

## ⚠️ Важно

- **Всегда используйте `--dry-run`** для предварительного просмотра
- **Проверяйте результат** после выполнения
- **Делайте backup** перед массовыми операциями
- **Тестируйте** на тестовых данных

## 📖 Подробная документация

См. файл `STORAGE_MOVE_INSTRUCTIONS.md` для полной инструкции.
