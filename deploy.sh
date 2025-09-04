#!/bin/bash

# Полноценный скрипт деплоя на сервер
# Использование: bash deploy.sh (на сервере)

set -e  # Остановка при ошибке

echo "🚀 Начинаю полный деплой на сервер..."

# Проверяем, что мы на сервере
if [ ! -d "/var/www/northrepubli_usr/data/www/northrepublic.me" ]; then
    echo "❌ Ошибка: Запустите скрипт на сервере"
    exit 1
fi

cd /var/www/northrepubli_usr/data/www/northrepublic.me

echo "📍 Рабочая директория: $(pwd)"

# Очищаем все изменения на сервере
echo "🗑️  Очищаю все изменения на сервере..."
git reset --hard HEAD
git clean -fd
echo "✅ Сервер очищен"

# Обновляем код с Git
echo "📥 Обновляю код с Git..."
git pull origin main
echo "✅ Код обновлен с Git"

# Устанавливаем зависимости backend
echo "📦 Устанавливаю зависимости backend..."
cd backend
npm install
echo "✅ Backend зависимости установлены"

# Собираем frontend
echo "🔨 Собираю frontend..."
cd ../frontend
npm run build
echo "✅ Frontend собран"

# Копируем новые файлы
echo "📁 Копирую новые файлы..."
cd ..
rm -rf static
cp -r frontend/build/static .
echo "✅ Static файлы скопированы"

# Восстанавливаем index.html из копии в репозитории
echo "📄 Восстанавливаю index.html из копии в репозитории..."
if [ -f "index.html" ]; then
    echo "✅ index.html восстановлен из копии"
    
    # Обновляем ссылку на JS файл в index.html
    echo "🔄 Обновляю ссылку на JS файл в index.html..."
    NEW_JS_FILE=$(ls static/js/main.*.js | head -1 | sed 's/.*\///')
    if [ -n "$NEW_JS_FILE" ]; then
        sed -i "s/main\.[a-zA-Z0-9]*\.js/$NEW_JS_FILE/g" index.html
        echo "✅ Ссылка на JS файл обновлена: $NEW_JS_FILE"
    else
        echo "❌ Ошибка: JS файл не найден в static/js/"
        exit 1
    fi
else
    echo "❌ Ошибка: index.html не найден в репозитории!"
    echo "💡 Создайте копию рабочего index.html в корне репозитория"
    exit 1
fi

# Копируем CSS файлы (исключаем старые файлы шаблона)
echo "🎨 Копирую CSS файлы..."
# Удаляем старые CSS файлы, которые конфликтуют с React
rm -rf css
echo "✅ Старые CSS файлы удалены (конфликт с React)"

# Копируем изображения
echo "🖼️  Копирую изображения..."
cp -r frontend/public/images .
echo "✅ Изображения скопированы"

# Копируем JS файлы (исключаем старые файлы шаблона)
echo "📜 Копирую JS файлы..."
# Удаляем старые JS файлы, которые конфликтуют с React
rm -rf js
echo "✅ Старые JS файлы удалены (конфликт с React)"

# Копируем иконки и favicon
echo "🔗 Копирую иконки и favicon..."
cp frontend/public/apple-touch-icon.png .
cp frontend/public/favicon-16x16.png .
cp frontend/public/favicon-32x32.png .
echo "✅ Иконки скопированы"

# Перезапускаем сервисы
echo "🔄 Перезапускаю сервисы..."
pm2 restart all
echo "✅ Сервисы перезапущены"

# Показываем статус
echo "📊 Статус PM2:"
pm2 list

# Обновляем локальную копию index.html в репозитории
echo "🔄 Обновляю локальную копию index.html в репозитории..."
if [ -f "index.html" ]; then
    # Копируем обновленный index.html обратно в репозиторий
    cp index.html ../index.html
    echo "✅ Локальная копия index.html обновлена"
    
    # Коммитим изменения
    cd ..
    git add index.html
    git commit -m "Update: index.html with new JS file reference ($NEW_JS_FILE)" || echo "⚠️ Нет изменений для коммита"
    git push origin main
    echo "✅ Изменения отправлены в репозиторий"
    cd /var/www/northrepubli_usr/data/www/northrepublic.me
else
    echo "❌ Ошибка: index.html не найден для копирования"
fi

echo ""
echo "🎉 Полный деплой на сервер завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "🧪 Тестируйте сайт через 30 секунд"
