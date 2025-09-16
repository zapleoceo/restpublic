<?php
// Скрипт для обновления переводов событий в MongoDB
// Размещен в admin/database для использования существующей структуры проекта

// Загружаем переменные окружения
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Словарь переводов для событий
$translations = [
    'en' => [
        // Заголовки событий
        '🎭 Мафия' => '🎭 Mafia',
        'Дегустация вин' => 'Wine Tasting',
        'Новогодний банкет' => 'New Year Banquet',
        'Мастер-класс по приготовлению пасты' => 'Pasta Cooking Master Class',
        'Романтический ужин на День Святого Валентина' => 'Romantic Valentine\'s Day Dinner',
        'День рождения ресторана' => 'Restaurant Birthday',
        
        // Описания
        'Вечерняя игра в мафию с друзьями' => 'Evening mafia game with friends',
        'Дегустация лучших вин с сомелье' => 'Tasting of the best wines with sommelier',
        'Праздничный банкет с живой музыкой' => 'Holiday banquet with live music',
        'Учимся готовить настоящую итальянскую пасту' => 'Learn to cook authentic Italian pasta',
        'Специальное романтическое меню для влюбленных' => 'Special romantic menu for lovers',
        'Празднование годовщины ресторана' => 'Restaurant anniversary celebration',
        
        // Условия
        '1500 руб. с человека' => '1500 rubles per person',
        '3000 руб. с человека, предварительная запись' => '3000 rubles per person, advance booking required',
        'Бесплатно при заказе от 2000 руб.' => 'Free with order from 2000 rubles',
        '2500 руб. за пару, специальное меню' => '2500 rubles per couple, special menu',
        'Вход свободный, специальные предложения' => 'Free entry, special offers',
        'Предварительная запись обязательна' => 'Advance booking required',
        'Количество мест ограничено' => 'Limited seating available'
    ],
    'vi' => [
        // Заголовки событий
        '🎭 Мафия' => '🎭 Mafia',
        'Дегустация вин' => 'Nếm thử rượu vang',
        'Новогодний банкет' => 'Tiệc tất niên',
        'Мастер-класс по приготовлению пасты' => 'Lớp học nấu mì Ý',
        'Романтический ужин на День Святого Валентина' => 'Bữa tối lãng mạn ngày Valentine',
        'День рождения ресторана' => 'Sinh nhật nhà hàng',
        
        // Описания
        'Вечерняя игра в мафию с друзьями' => 'Trò chơi mafia buổi tối với bạn bè',
        'Дегустация лучших вин с сомелье' => 'Nếm thử những loại rượu vang ngon nhất với chuyên gia rượu',
        'Праздничный банкет с живой музыкой' => 'Tiệc tất niên với nhạc sống',
        'Учимся готовить настоящую итальянскую пасту' => 'Học nấu mì Ý chính thống',
        'Специальное романтическое меню для влюбленных' => 'Thực đơn lãng mạn đặc biệt cho các cặp đôi',
        'Празднование годовщины ресторана' => 'Lễ kỷ niệm ngày thành lập nhà hàng',
        
        // Условия
        '1500 руб. с человека' => '1500 rúp mỗi người',
        '3000 руб. с человека, предварительная запись' => '3000 rúp mỗi người, cần đặt trước',
        'Бесплатно при заказе от 2000 руб.' => 'Miễn phí khi đặt từ 2000 rúp',
        '2500 руб. за пару, специальное меню' => '2500 rúp cho cặp đôi, thực đơn đặc biệt',
        'Вход свободный, специальные предложения' => 'Vào cửa miễn phí, ưu đãi đặc biệt',
        'Предварительная запись обязательна' => 'Bắt buộc đặt trước',
        'Количество мест ограничено' => 'Số chỗ ngồi có hạn'
    ]
];

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27018';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'northrepublic';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $eventsCollection = $db->events;
    
    echo "🔍 Поиск событий для перевода...\n";
    
    // Получаем все события
    $events = $eventsCollection->find([])->toArray();
    echo "📊 Найдено событий: " . count($events) . "\n\n";
    
    $updatedCount = 0;
    
    foreach ($events as $event) {
        echo "🔄 Обработка события ID: " . $event['_id'] . "\n";
        
        $updateData = [];
        $hasUpdates = false;
        
        // Проверяем и переводим заголовок
        if (isset($event['title']) && !empty($event['title'])) {
            $titleRu = $event['title'];
            
            // Английский перевод
            if (!isset($event['title_en']) || empty($event['title_en']) || $event['title_en'] === $titleRu) {
                $titleEn = translateText($titleRu, 'en', $translations);
                if ($titleEn !== $titleRu) {
                    $updateData['title_ru'] = $titleRu;
                    $updateData['title_en'] = $titleEn;
                    $hasUpdates = true;
                    echo "  ✅ EN Title: " . $titleEn . "\n";
                }
            }
            
            // Вьетнамский перевод
            if (!isset($event['title_vi']) || empty($event['title_vi']) || $event['title_vi'] === $titleRu) {
                $titleVi = translateText($titleRu, 'vi', $translations);
                if ($titleVi !== $titleRu) {
                    $updateData['title_vi'] = $titleVi;
                    $hasUpdates = true;
                    echo "  ✅ VI Title: " . $titleVi . "\n";
                }
            }
        }
        
        // Проверяем и переводим условия
        if (isset($event['conditions']) && !empty($event['conditions'])) {
            $conditionsRu = $event['conditions'];
            
            // Английский перевод
            if (!isset($event['conditions_en']) || empty($event['conditions_en']) || $event['conditions_en'] === $conditionsRu) {
                $conditionsEn = translateText($conditionsRu, 'en', $translations);
                if ($conditionsEn !== $conditionsRu) {
                    $updateData['conditions_ru'] = $conditionsRu;
                    $updateData['conditions_en'] = $conditionsEn;
                    $hasUpdates = true;
                    echo "  ✅ EN Conditions: " . $conditionsEn . "\n";
                }
            }
            
            // Вьетнамский перевод
            if (!isset($event['conditions_vi']) || empty($event['conditions_vi']) || $event['conditions_vi'] === $conditionsRu) {
                $conditionsVi = translateText($conditionsRu, 'vi', $translations);
                if ($conditionsVi !== $conditionsRu) {
                    $updateData['conditions_vi'] = $conditionsVi;
                    $hasUpdates = true;
                    echo "  ✅ VI Conditions: " . $conditionsVi . "\n";
                }
            }
        }
        
        // Добавляем описания на основе заголовков
        if (isset($event['title']) && !empty($event['title'])) {
            $descriptions = [
                'Дегустация вин' => 'Дегустация лучших вин с сомелье',
                'Новогодний банкет' => 'Праздничный банкет с живой музыкой',
                'Мастер-класс по приготовлению пасты' => 'Учимся готовить настоящую итальянскую пасту',
                'Романтический ужин на День Святого Валентина' => 'Специальное романтическое меню для влюбленных',
                'День рождения ресторана' => 'Празднование годовщины ресторана'
            ];
            
            $descriptionRu = $descriptions[$event['title']] ?? 'Описание события';
            
            // Английский перевод описания
            if (!isset($event['description_en']) || empty($event['description_en'])) {
                $descriptionEn = translateText($descriptionRu, 'en', $translations);
                $updateData['description_ru'] = $descriptionRu;
                $updateData['description_en'] = $descriptionEn;
                $hasUpdates = true;
                echo "  ✅ EN Description: " . $descriptionEn . "\n";
            }
            
            // Вьетнамский перевод описания
            if (!isset($event['description_vi']) || empty($event['description_vi'])) {
                $descriptionVi = translateText($descriptionRu, 'vi', $translations);
                $updateData['description_vi'] = $descriptionVi;
                $hasUpdates = true;
                echo "  ✅ VI Description: " . $descriptionVi . "\n";
            }
        }
        
        // Обновляем документ, если есть изменения
        if ($hasUpdates) {
            $updateData['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $eventsCollection->updateOne(
                ['_id' => $event['_id']],
                ['$set' => $updateData]
            );
            
            if ($result->getModifiedCount() > 0) {
                $updatedCount++;
                echo "  💾 Событие обновлено в базе данных\n";
            } else {
                echo "  ⚠️ Ошибка при обновлении события\n";
            }
        } else {
            echo "  ℹ️ Переводы уже существуют или не требуются\n";
        }
        
        echo "\n";
    }
    
    echo "🎉 Перевод завершен!\n";
    echo "📈 Обновлено событий: " . $updatedCount . " из " . count($events) . "\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

/**
 * Функция для перевода текста
 */
function translateText($text, $targetLanguage, $translations) {
    if (!isset($translations[$targetLanguage])) {
        return $text;
    }
    
    $translatedText = $text;
    foreach ($translations[$targetLanguage] as $ru => $translated) {
        $translatedText = str_replace($ru, $translated, $translatedText);
    }
    
    return $translatedText;
}
?>
