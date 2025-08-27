# Настройка GitHub Secrets для Production Деплоя

## 🔑 GitHub Secrets для настройки

Для работы автодеплоя на production нужно добавить следующие секреты в GitHub репозиторий:

### 1. PRODUCTION_HOST
**Значение:** `159.253.23.113`

### 2. PRODUCTION_USER  
**Значение:** `northrepubli_usr`

### 3. PRODUCTION_SSH_KEY
**Значение:** (весь приватный ключ)
```
-----BEGIN OPENSSH PRIVATE KEY-----
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
-----END OPENSSH PRIVATE KEY-----
```

## 📋 Инструкция по добавлению секретов

### 1. Перейти в GitHub репозиторий
- Открыть https://github.com/zapleoceo/restpublic

### 2. Перейти в Settings → Secrets and variables → Actions
- Нажать на "New repository secret"

### 3. Добавить каждый секрет:
- **Name:** `PRODUCTION_HOST`
- **Value:** `159.253.23.113`

- **Name:** `PRODUCTION_USER`  
- **Value:** `northrepubli_usr`

- **Name:** `PRODUCTION_SSH_KEY`
- **Value:** (весь приватный ключ выше)

## 🚀 Запуск Production Деплоя

После настройки секретов:

1. Перейти в **Actions** вкладку GitHub репозитория
2. Выбрать **"Deploy to Production"** workflow
3. Нажать **"Run workflow"**
4. Выбрать **"production"** в Environment
5. Поставить галочку **"Confirm deployment to production"**
6. Нажать **"Run workflow"**

## ✅ Проверка деплоя

После успешного деплоя:
- Сайт: https://northrepublic.me
- API: https://northrepublic.me/api/health
- Логи: `ssh northrepublic "pm2 logs"`

## 🔒 Безопасность

- Секреты хранятся в зашифрованном виде
- Доступ только к указанному серверу
- SSH ключ используется только для деплоя
- Подтверждение деплоя обязательно
