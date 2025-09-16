<?php
// Скрипт для обновления переводов всех событий в MongoDB
// Работает с существующими событиями в базе данных

echo "🔍 Обновление переводов всех событий в MongoDB...\n\n";

// Словарь переводов
$translations = [
    'en' => [
        '🎭 Мафия' => '🎭 Mafia',
        '🎬 Кино (Детский сеанс)' => '🎬 Cinema (Children\'s Session)',
        '🎬 Кино (Взрослый сеанс)' => '🎬 Cinema (Adult Session)',
        '🎥 Кино (Детский сеанс)' => '🎥 Cinema (Children\'s Session)',
        '🎥 Кино (Взрослый сеанс)' => '🎥 Cinema (Adult Session)',
        '🤝 Вечер знакомств и нетворкинга' => '🤝 Networking and Dating Evening',
        '⚡️ Speed Friending' => '⚡️ Speed Friending',
        '🍲 Лучный бой' => '🍲 Archery Battle',
        '🎶 Борщ & Музыка 90-х' => '🎶 Borscht & 90s Music',
        '🎸🎤 Sax & DJ live set' => '🎸🎤 Sax & DJ Live Set',
        'Ледяная ванна' => 'Ice Bath',
        'Новогодний банкет (тест)' => 'New Year Banquet (Test)',
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
        '🎬 Кино (Детский сеанс)' => '🎬 Phim (Suất chiếu trẻ em)',
        '🎬 Кино (Взрослый сеанс)' => '🎬 Phim (Suất chiếu người lớn)',
        '🎥 Кино (Детский сеанс)' => '🎥 Phim (Suất chiếu trẻ em)',
        '🎥 Кино (Взрослый сеанс)' => '🎥 Phim (Suất chiếu người lớn)',
        '🤝 Вечер знакомств и нетворкинга' => '🤝 Tối làm quen và kết nối',
        '⚡️ Speed Friending' => '⚡️ Kết bạn nhanh',
        '🍲 Лучный бой' => '🍲 Trận chiến cung tên',
        '🎶 Борщ & Музыка 90-х' => '🎶 Borscht & Nhạc thập niên 90',
        '🎸🎤 Sax & DJ live set' => '🎸🎤 Sax & DJ Biểu diễn trực tiếp',
        'Ледяная ванна' => 'Tắm nước đá',
        'Новогодний банкет (тест)' => 'Tiệc tất niên (Thử nghiệm)',
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

// Генерируем MongoDB команды для обновления всех событий
$mongoCommands = [];
$mongoCommands[] = "use northrepublic;";
$mongoCommands[] = "";

// Список всех событий из базы данных
$events = [
    'Новогодний банкет (тест)',
    'Мастер-класс по приготовлению пасты',
    'Ледяная ванна',
    '🎭 Мафия',
    '🎬 Кино (Детский сеанс)',
    '🎬 Кино (Взрослый сеанс)',
    '🎥 Кино (Детский сеанс)',
    '🎥 Кино (Взрослый сеанс)',
    '🤝 Вечер знакомств и нетворкинга',
    '⚡️ Speed Friending',
    '🍲 Лучный бой',
    '🎶 Борщ & Музыка 90-х',
    '🎸🎤 Sax & DJ live set'
];

foreach ($events as $eventTitle) {
    $titleEn = translateText($eventTitle, 'en', $translations);
    $titleVi = translateText($eventTitle, 'vi', $translations);
    
    echo "🔄 Обработка события: " . $eventTitle . "\n";
    echo "  ✅ EN Title: " . $titleEn . "\n";
    echo "  ✅ VI Title: " . $titleVi . "\n\n";
    
    // Создаем MongoDB команду для обновления всех событий с таким заголовком
    $mongoCommands[] = "// Обновление события: " . $eventTitle;
    $mongoCommands[] = "db.events.updateMany(";
    $mongoCommands[] = "  { title: \"" . addslashes($eventTitle) . "\" },";
    $mongoCommands[] = "  {";
    $mongoCommands[] = "    \$set: {";
    $mongoCommands[] = "      title_ru: \"" . addslashes($eventTitle) . "\",";
    $mongoCommands[] = "      title_en: \"" . addslashes($titleEn) . "\",";
    $mongoCommands[] = "      title_vi: \"" . addslashes($titleVi) . "\",";
    $mongoCommands[] = "      updated_at: new Date()";
    $mongoCommands[] = "    }";
    $mongoCommands[] = "  }";
    $mongoCommands[] = ");";
    $mongoCommands[] = "";
}

// Сохраняем MongoDB скрипт
$mongoScript = implode("\n", $mongoCommands);
file_put_contents('admin/database/update_all_events_mongodb.js', $mongoScript);

echo "🎉 Переводы подготовлены!\n";
echo "📝 Создан MongoDB скрипт: admin/database/update_all_events_mongodb.js\n";
echo "📊 Обработано событий: " . count($events) . "\n\n";

echo "📋 Для применения переводов выполните команду:\n";
echo "mongosh < admin/database/update_all_events_mongodb.js\n\n";

echo "🔍 Содержимое MongoDB скрипта:\n";
echo "================================\n";
echo $mongoScript;
?>
