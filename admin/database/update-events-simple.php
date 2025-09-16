<?php
// Упрощенный скрипт для обновления переводов событий
// Работает без Composer, используя прямые MongoDB команды

echo "🔍 Обновление переводов событий в MongoDB...\n\n";

// Словарь переводов
$translations = [
    'en' => [
        '🎭 Мафия' => '🎭 Mafia',
        'Дегустация вин' => 'Wine Tasting',
        'Новогодний банкет' => 'New Year Banquet',
        'Мастер-класс по приготовлению пасты' => 'Pasta Cooking Master Class',
        'Романтический ужин на День Святого Валентина' => 'Romantic Valentine\'s Day Dinner',
        'День рождения ресторана' => 'Restaurant Birthday',
        'Дегустация лучших вин с сомелье' => 'Tasting of the best wines with sommelier',
        'Праздничный банкет с живой музыкой' => 'Holiday banquet with live music',
        'Учимся готовить настоящую итальянскую пасту' => 'Learn to cook authentic Italian pasta',
        'Специальное романтическое меню для влюбленных' => 'Special romantic menu for lovers',
        'Празднование годовщины ресторана' => 'Restaurant anniversary celebration',
        '1500 руб. с человека' => '1500 rubles per person',
        '3000 руб. с человека, предварительная запись' => '3000 rubles per person, advance booking required',
        'Бесплатно при заказе от 2000 руб.' => 'Free with order from 2000 rubles',
        '2500 руб. за пару, специальное меню' => '2500 rubles per couple, special menu',
        'Вход свободный, специальные предложения' => 'Free entry, special offers'
    ],
    'vi' => [
        '🎭 Мафия' => '🎭 Mafia',
        'Дегустация вин' => 'Nếm thử rượu vang',
        'Новогодний банкет' => 'Tiệc tất niên',
        'Мастер-класс по приготовлению пасты' => 'Lớp học nấu mì Ý',
        'Романтический ужин на День Святого Валентина' => 'Bữa tối lãng mạn ngày Valentine',
        'День рождения ресторана' => 'Sinh nhật nhà hàng',
        'Дегустация лучших вин с сомелье' => 'Nếm thử những loại rượu vang ngon nhất với chuyên gia rượu',
        'Праздничный банкет с живой музыкой' => 'Tiệc tất niên với nhạc sống',
        'Учимся готовить настоящую итальянскую пасту' => 'Học nấu mì Ý chính thống',
        'Специальное романтическое меню для влюбленных' => 'Thực đơn lãng mạn đặc biệt cho các cặp đôi',
        'Празднование годовщины ресторана' => 'Lễ kỷ niệm ngày thành lập nhà hàng',
        '1500 руб. с человека' => '1500 rúp mỗi người',
        '3000 руб. с человека, предварительная запись' => '3000 rúp mỗi người, cần đặt trước',
        'Бесплатно при заказе от 2000 руб.' => 'Miễn phí khi đặt từ 2000 rúp',
        '2500 руб. за пару, специальное меню' => '2500 rúp cho cặp đôi, thực đơn đặc biệt',
        'Вход свободный, специальные предложения' => 'Vào cửa miễn phí, ưu đãi đặc biệt'
    ]
];

// События для обновления
$events = [
    [
        'title' => 'Дегустация вин',
        'description' => 'Дегустация лучших вин с сомелье',
        'conditions' => '1500 руб. с человека'
    ],
    [
        'title' => 'Новогодний банкет',
        'description' => 'Праздничный банкет с живой музыкой',
        'conditions' => '3000 руб. с человека, предварительная запись'
    ],
    [
        'title' => 'Мастер-класс по приготовлению пасты',
        'description' => 'Учимся готовить настоящую итальянскую пасту',
        'conditions' => 'Бесплатно при заказе от 2000 руб.'
    ],
    [
        'title' => 'Романтический ужин на День Святого Валентина',
        'description' => 'Специальное романтическое меню для влюбленных',
        'conditions' => '2500 руб. за пару, специальное меню'
    ],
    [
        'title' => 'День рождения ресторана',
        'description' => 'Празднование годовщины ресторана',
        'conditions' => 'Вход свободный, специальные предложения'
    ]
];

// Функция перевода
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

// Генерируем MongoDB команды
$mongoCommands = [];
$mongoCommands[] = "use northrepublic;";
$mongoCommands[] = "";

foreach ($events as $event) {
    $titleEn = translateText($event['title'], 'en', $translations);
    $titleVi = translateText($event['title'], 'vi', $translations);
    $descriptionEn = translateText($event['description'], 'en', $translations);
    $descriptionVi = translateText($event['description'], 'vi', $translations);
    $conditionsEn = translateText($event['conditions'], 'en', $translations);
    $conditionsVi = translateText($event['conditions'], 'vi', $translations);
    
    echo "🔄 Обработка события: " . $event['title'] . "\n";
    echo "  ✅ EN Title: " . $titleEn . "\n";
    echo "  ✅ VI Title: " . $titleVi . "\n";
    echo "  📝 EN Description: " . $descriptionEn . "\n";
    echo "  📝 VI Description: " . $descriptionVi . "\n";
    echo "  💰 EN Conditions: " . $conditionsEn . "\n";
    echo "  💰 VI Conditions: " . $conditionsVi . "\n\n";
    
    // Создаем MongoDB команду
    $mongoCommands[] = "// Обновление события: " . $event['title'];
    $mongoCommands[] = "db.events.updateOne(";
    $mongoCommands[] = "  { title: \"" . addslashes($event['title']) . "\" },";
    $mongoCommands[] = "  {";
    $mongoCommands[] = "    \$set: {";
    $mongoCommands[] = "      title_ru: \"" . addslashes($event['title']) . "\",";
    $mongoCommands[] = "      title_en: \"" . addslashes($titleEn) . "\",";
    $mongoCommands[] = "      title_vi: \"" . addslashes($titleVi) . "\",";
    $mongoCommands[] = "      description_ru: \"" . addslashes($event['description']) . "\",";
    $mongoCommands[] = "      description_en: \"" . addslashes($descriptionEn) . "\",";
    $mongoCommands[] = "      description_vi: \"" . addslashes($descriptionVi) . "\",";
    $mongoCommands[] = "      conditions_ru: \"" . addslashes($event['conditions']) . "\",";
    $mongoCommands[] = "      conditions_en: \"" . addslashes($conditionsEn) . "\",";
    $mongoCommands[] = "      conditions_vi: \"" . addslashes($conditionsVi) . "\",";
    $mongoCommands[] = "      updated_at: new Date()";
    $mongoCommands[] = "    }";
    $mongoCommands[] = "  }";
    $mongoCommands[] = ");";
    $mongoCommands[] = "";
}

// Сохраняем MongoDB скрипт
$mongoScript = implode("\n", $mongoCommands);
file_put_contents('admin/database/update_events_mongodb.js', $mongoScript);

echo "🎉 Переводы подготовлены!\n";
echo "📝 Создан MongoDB скрипт: admin/database/update_events_mongodb.js\n";
echo "📊 Обработано событий: " . count($events) . "\n\n";

echo "📋 Для применения переводов выполните одну из команд:\n";
echo "1. mongo < admin/database/update_events_mongodb.js\n";
echo "2. mongosh < admin/database/update_events_mongodb.js\n";
echo "3. Скопируйте содержимое файла в MongoDB shell\n\n";

echo "🔍 Содержимое MongoDB скрипта:\n";
echo "================================\n";
echo $mongoScript;
?>
