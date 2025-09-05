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
    
    # Исправляем кодировку файла (UTF-16 -> UTF-8)
    echo "🔄 Исправляю кодировку index.html..."
    if file index.html | grep -q "UTF-16"; then
        iconv -f UTF-16LE -t UTF-8 index.html > index_utf8.html && mv index_utf8.html index.html
        echo "✅ Кодировка исправлена (UTF-16 -> UTF-8)"
    else
        echo "✅ Кодировка уже корректна"
    fi
    
    # Обновляем ссылку на JS файл в index.html
    echo "🔄 Обновляю ссылку на JS файл в index.html..."
    NEW_JS_FILE=$(ls static/js/main.*.js | head -1 | sed 's/.*\///')
    if [ -n "$NEW_JS_FILE" ]; then
        # Используем более точную замену с полным путем
        sed -i "s|/static/js/main\.[a-zA-Z0-9]*\.js|/static/js/$NEW_JS_FILE|g" index.html
        echo "✅ Ссылка на JS файл обновлена: $NEW_JS_FILE"
        
        # Проверяем, что замена прошла успешно
        if grep -q "/static/js/$NEW_JS_FILE" index.html; then
            echo "✅ Проверка: ссылка на JS файл корректна"
        else
            echo "❌ Ошибка: ссылка на JS файл не обновилась"
            exit 1
        fi
    else
        echo "❌ Ошибка: JS файл не найден в static/js/"
        exit 1
    fi
    
    # Обновляем ссылку на CSS файл в index.html
    echo "🔄 Обновляю ссылку на CSS файл в index.html..."
    NEW_CSS_FILE=$(ls static/css/main.*.css | head -1 | sed 's/.*\///')
    if [ -n "$NEW_CSS_FILE" ]; then
        # Используем более точную замену с полным путем
        sed -i "s|/static/css/main\.[a-zA-Z0-9]*\.css|/static/css/$NEW_CSS_FILE|g" index.html
        echo "✅ Ссылка на CSS файл обновлена: $NEW_CSS_FILE"
        
        # Проверяем, что замена прошла успешно
        if grep -q "/static/css/$NEW_CSS_FILE" index.html; then
            echo "✅ Проверка: ссылка на CSS файл корректна"
        else
            echo "❌ Ошибка: ссылка на CSS файл не обновилась"
            exit 1
        fi
    else
        echo "❌ Ошибка: CSS файл не найден в static/css/"
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

# Очищаем старые кастомные стили из template CSS
echo "🧹 Очищаю старые кастомные стили из template CSS..."
if [ -f "template/css/styles.css" ]; then
    # Удаляем строки, добавленные вручную (с !important)
    sed -i '/Force image cropping and smooth scrolling/d' template/css/styles.css
    sed -i '/\.intro-pic-primary { overflow: hidden !important; }/d' template/css/styles.css
    sed -i '/\.intro-pic-primary img { object-fit: cover !important; width: 100% !important; height: 100% !important; aspect-ratio: unset !important; object-position: center !important; }/d' template/css/styles.css
    sed -i '/html, body { scroll-behavior: smooth !important; }/d' template/css/styles.css
    echo "✅ Старые кастомные стили удалены из template CSS"
fi

# Обновляем кастомные стили
echo "🎨 Обновляю кастомные стили..."
if [ -f "template/css/custom.css" ]; then
    echo "✅ Кастомные стили обновлены"
else
    echo "⚠️  Кастомные стили не найдены, создаю базовые..."
    mkdir -p template/css
    cat > template/css/custom.css << 'EOF'
/* Custom styles for North Republic website */

/* Smooth scrolling for anchor links */
html {
  scroll-behavior: smooth;
}

/* Fix intro-pic-primary image to crop instead of stretch */
.intro-pic-primary {
  overflow: hidden;
}

.intro-pic-primary img {
  object-fit: cover;
  width: 100%;
  height: 100%;
  aspect-ratio: unset;
  object-position: center;
}
EOF
    echo "✅ Базовые кастомные стили созданы"
fi

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
    cd /var/www/northrepubli_usr/data/www/northrepublic.me
    git add index.html
    git commit -m "Update: index.html with new JS file reference ($NEW_JS_FILE)" || echo "⚠️ Нет изменений для коммита"
    git push origin main
    echo "✅ Изменения отправлены в репозиторий"
else
    echo "❌ Ошибка: index.html не найден для копирования"
fi

echo ""
echo "🎉 Полный деплой на сервер завершен!"
echo "🌐 Сайт: https://northrepublic.me"
echo "🧪 Тестируйте сайт через 30 секунд"
