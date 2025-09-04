# Документация деплоя North Republic

## 📚 Содержание

1. **[Правила деплоя](deploy-rules.md)** - Основные правила и процесс деплоя
2. **[Команды деплоя](deploy-commands.md)** - Готовые команды для быстрого доступа
3. **[Конфигурация](deploy-config.md)** - Серверная конфигурация и настройки

## 🚀 Быстрый старт

### Полный деплой (3 команды)
```bash
# 1. Коммит и пуш
git add . && git commit -m "Описание" && git push origin main

# 2. Деплой на сервер
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && git pull origin main && chmod +x deploy.sh && ./deploy.sh"

# 3. Обновление index.html
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html" > index.html && git add index.html && git commit -m "Update index.html" && git push origin main
```

## ⚡ Ключевые особенности

- **Автоматическое восстановление** `index.html` после `git clean -fd`
- **Автоматическое обновление** ссылок на JS файлы
- **Предотвращение ошибок** 403 Forbidden
- **Обеспечение корректной работы** React приложения

## 🔧 Основные файлы

- `deploy.sh` - Скрипт деплоя с автоматическим восстановлением
- `index.html` - Гибридный HTML файл (template + React)
- `static/js/main.*.js` - React приложение
- `static/css/main.*.css` - React стили

## 📞 Поддержка

При возникновении проблем:
1. Проверить [правила деплоя](deploy-rules.md)
2. Использовать [команды отладки](deploy-commands.md)
3. Проверить [конфигурацию сервера](deploy-config.md)

---
*Документация актуальна на: $(date)*
