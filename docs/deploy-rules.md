# Правила деплоя проекта North Republic

## 🚀 Процесс деплоя

### 1. Подготовка к деплою
- [ ] Убедиться, что все изменения закоммичены локально
- [ ] Проверить, что нет незакоммиченных файлов (`git status`)
- [ ] Увеличить версию в `package.json` (frontend/backend)
- [ ] Протестировать сборку локально (`npm run build`)

### 2. Коммит и пуш
```bash
git add .
git commit -m "Описание изменений"
git push origin main
```

### 3. Деплой на сервер
```bash
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && git pull origin main && chmod +x deploy.sh && ./deploy.sh"
```

### 4. Проверка после деплоя
- [ ] Проверить доступность сайта: https://northrepublic.me
- [ ] Убедиться, что React приложение загружается
- [ ] Проверить, что стили применяются корректно
- [ ] Протестировать функциональность меню

## ⚠️ Важные моменты

### Автоматическое обновление JS файлов
Скрипт `deploy.sh` автоматически:
- Восстанавливает `index.html` из копии в репозитории
- Находит новый JS файл после сборки frontend
- Обновляет ссылку на JS файл в `index.html`

### Обновление локальной копии index.html
После каждого деплоя необходимо:
```bash
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html" > index.html
git add index.html
git commit -m "Update: index.html with new JS file reference"
git push origin main
```

### Права на выполнение скрипта
Если возникает ошибка "Permission denied":
```bash
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && chmod +x deploy.sh"
```

## 🔧 Структура скрипта deploy.sh

### Последовательность операций:
1. **Очистка сервера**: `git reset --hard HEAD` и `git clean -fd`
2. **Обновление кода**: `git pull origin main`
3. **Установка зависимостей backend**: `npm install`
4. **Сборка frontend**: `npm run build`
5. **Копирование файлов**: static файлы, изображения, иконки
6. **Восстановление index.html**: из копии в репозитории
7. **Обновление JS ссылки**: автоматическое обновление ссылки на новый JS файл
8. **Перезапуск сервисов**: `pm2 restart all`

### Ключевые особенности:
- Автоматическое восстановление `index.html` после `git clean -fd`
- Автоматическое обновление ссылки на JS файл
- Предотвращение ошибок 403 Forbidden
- Обеспечение корректной работы React приложения

## 📋 Чек-лист деплоя

### Перед деплоем:
- [ ] Версия увеличена в `package.json`
- [ ] Все изменения закоммичены
- [ ] Код протестирован локально
- [ ] Нет конфликтов в Git

### После деплоя:
- [ ] Сайт доступен по адресу https://northrepublic.me
- [ ] React приложение загружается без ошибок
- [ ] Стили применяются корректно
- [ ] Меню отображается и работает
- [ ] Локальная копия `index.html` обновлена
- [ ] Изменения закоммичены в репозиторий

## 🚨 Решение проблем

### Ошибка 403 Forbidden
**Причина**: Отсутствует `index.html` на сервере
**Решение**: 
```bash
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html" > index.html
scp index.html nr:/var/www/northrepubli_usr/data/www/northrepublic.me/
```

### React приложение не загружается
**Причина**: Неправильная ссылка на JS файл в `index.html`
**Решение**: Скрипт автоматически исправляет это, но можно проверить:
```bash
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && ls -la static/js/"
ssh nr "cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html | grep main.*js"
```

### Стили не применяются
**Причина**: Конфликт между template CSS и React CSS
**Решение**: Убедиться, что в `index.html` подключены оба набора стилей:
- Template CSS: `template/css/vendor.css`, `template/css/styles.css`
- React CSS: `/static/css/main.*.css`

### Меню не отображается
**Причина**: Проблемы с интеграцией Template JS и React
**Решение**: Проверить консоль браузера на ошибки JavaScript

## 📝 Логи деплоя

Скрипт выводит подробные логи:
- ✅ Успешные операции
- ❌ Ошибки с описанием
- 📊 Статус PM2 сервисов
- 🔄 Информация о перезапуске

## 🔄 Автоматизация

Для упрощения процесса можно создать алиасы:
```bash
# В ~/.bashrc или ~/.zshrc
alias deploy-nr="ssh nr 'cd /var/www/northrepubli_usr/data/www/northrepublic.me && git pull origin main && chmod +x deploy.sh && ./deploy.sh'"
alias update-index="ssh nr 'cd /var/www/northrepubli_usr/data/www/northrepublic.me && cat index.html' > index.html && git add index.html && git commit -m 'Update index.html' && git push origin main"
```

## 📞 Контакты для поддержки

При возникновении проблем:
1. Проверить логи PM2: `ssh nr "pm2 logs"`
2. Проверить статус сервисов: `ssh nr "pm2 list"`
3. Проверить доступность сайта: `ssh nr "curl -I https://northrepublic.me"`

---
*Последнее обновление: $(date)*
*Версия скрипта: 1.0.30*
