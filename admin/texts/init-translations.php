<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    
    // Базовые переводы для сайта
    $translations = [
        // Навигация
        [
            'key' => 'nav.home',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'Главная',
                'en' => 'Home',
                'vi' => 'Trang chủ'
            ]
        ],
        [
            'key' => 'nav.about',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'О нас',
                'en' => 'About Us',
                'vi' => 'Về chúng tôi'
            ]
        ],
        [
            'key' => 'nav.menu',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'Меню',
                'en' => 'Menu',
                'vi' => 'Thực đơn'
            ]
        ],
        [
            'key' => 'nav.gallery',
            'category' => 'navigation',
            'translations' => [
                'ru' => 'Галерея',
                'en' => 'Gallery',
                'vi' => 'Thư viện ảnh'
            ]
        ],
        
        // Интро секция
        [
            'key' => 'intro.welcome',
            'category' => 'intro',
            'translations' => [
                'ru' => 'Добро пожаловать в',
                'en' => 'Welcome to',
                'vi' => 'Chào mừng đến với'
            ]
        ],
        [
            'key' => 'intro.description',
            'category' => 'intro',
            'translations' => [
                'ru' => 'Добро пожаловать в <strong>North Republic</strong> — место, где встречаются изысканная кухня, уютная атмосфера и незабываемые моменты.',
                'en' => 'Welcome to <strong>North Republic</strong> — a place where exquisite cuisine, cozy atmosphere and unforgettable moments meet.',
                'vi' => 'Chào mừng đến với <strong>North Republic</strong> — nơi gặp gỡ của ẩm thực tinh tế, bầu không khí ấm cúng và những khoảnh khắc khó quên.'
            ]
        ],
        
        // О нас секция
        [
            'key' => 'about.title',
            'category' => 'about',
            'translations' => [
                'ru' => 'О нас',
                'en' => 'About Us',
                'vi' => 'Về chúng tôi'
            ]
        ],
        [
            'key' => 'about.paragraph1',
            'category' => 'about',
            'translations' => [
                'ru' => 'Добро пожаловать в <strong>«Республику Север»</strong> — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга. Здесь, в объятиях первозданной природы, у подножия легендарной горы Ко Тьен, современность встречается с дикой красотой тропического края, создавая пространство безграничных возможностей.',
                'en' => 'Welcome to <strong>«North Republic»</strong> — an oasis of adventures and gastronomic discoveries among the majestic landscapes of northern Nha Trang. Here, in the embrace of pristine nature, at the foot of the legendary Co Tien mountain, modernity meets the wild beauty of the tropical region, creating a space of unlimited possibilities.',
                'vi' => 'Chào mừng đến với <strong>«Cộng hòa Bắc»</strong> — ốc đảo của những cuộc phiêu lưu và khám phá ẩm thực giữa những cảnh quan hùng vĩ của Nha Trang phía bắc. Tại đây, trong vòng tay của thiên nhiên nguyên sơ, dưới chân ngọn núi Co Tien huyền thoại, hiện đại gặp gỡ vẻ đẹp hoang dã của vùng nhiệt đới, tạo nên không gian của những khả năng vô hạn.'
            ]
        ],
        [
            'key' => 'about.paragraph2',
            'category' => 'about',
            'translations' => [
                'ru' => 'Взгляните вверх — перед вами раскинулись склоны Горы Феи, той самой Ко Тьен, чья мифическая красота веками вдохновляла поэтов и путешественников. Панорамные виды на изумрудные холмы и сверкающий залив превращают каждый момент здесь в кадр из волшебной сказки. Это место, где время замедляет свой бег, а душа находит долгожданный покой.',
                'en' => 'Look up — before you spread the slopes of the Fairy Mountain, that very Co Tien, whose mythical beauty has inspired poets and travelers for centuries. Panoramic views of emerald hills and sparkling bay turn every moment here into a frame from a magical fairy tale. This is a place where time slows down and the soul finds long-awaited peace.',
                'vi' => 'Hãy nhìn lên — trước mặt bạn trải ra những sườn núi của Núi Tiên, chính là Co Tien đó, vẻ đẹp huyền thoại đã truyền cảm hứng cho các nhà thơ và du khách trong nhiều thế kỷ. Tầm nhìn toàn cảnh của những ngọn đồi ngọc lục bảo và vịnh lấp lánh biến mỗi khoảnh khắc ở đây thành một khung hình từ câu chuyện cổ tích thần kỳ. Đây là nơi thời gian chậm lại và tâm hồn tìm thấy sự bình yên mong đợi.'
            ]
        ],
        [
            'key' => 'about.paragraph3',
            'category' => 'about',
            'translations' => [
                'ru' => '<strong>«Республика Север»</strong> — это калейдоскоп впечатлений под открытым небом. Адреналиновые баталии в лазертаге и захватывающие дуэли с луками в арчеритаге соседствуют с уютными беседками для семейных пикников. Интеллектуальные квесты переплетаются с ароматами барбекю, а вечерние мероприятия наполняют воздух музыкой и смехом до поздней ночи.',
                'en' => '<strong>«North Republic»</strong> is a kaleidoscope of impressions under the open sky. Adrenaline battles in laser tag and exciting archery duels coexist with cozy gazebos for family picnics. Intellectual quests intertwine with the aromas of barbecue, and evening events fill the air with music and laughter until late at night.',
                'vi' => '<strong>«Cộng hòa Bắc»</strong> là một caleidoscope của những ấn tượng dưới bầu trời mở. Những trận chiến adrenaline trong laser tag và những cuộc đấu cung tên thú vị cùng tồn tại với những gian hàng ấm cúng cho những buổi dã ngoại gia đình. Những cuộc tìm kiếm trí tuệ đan xen với hương thơm của thịt nướng, và các sự kiện buổi tối lấp đầy không khí bằng âm nhạc và tiếng cười cho đến tận khuya.'
            ]
        ],
        [
            'key' => 'about.paragraph4',
            'category' => 'about',
            'translations' => [
                'ru' => 'Наш ресторан и кофейня — это кулинарное путешествие, где авторские блюда рождаются из слияния русских традиций и вьетнамской экзотики. Здесь каждое блюдо — произведение искусства, а каждый глоток кофе — мост между культурами. Творческие ярмарки, музыкальные вечера и тематические фестивали превращают каждый день в маленький праздник.',
                'en' => 'Our restaurant and cafe is a culinary journey where signature dishes are born from the fusion of Russian traditions and Vietnamese exoticism. Here every dish is a work of art, and every sip of coffee is a bridge between cultures. Creative fairs, musical evenings and themed festivals turn every day into a small celebration.',
                'vi' => 'Nhà hàng và quán cà phê của chúng tôi là một hành trình ẩm thực nơi những món ăn đặc trưng được sinh ra từ sự kết hợp của truyền thống Nga và sự kỳ lạ của Việt Nam. Ở đây mỗi món ăn là một tác phẩm nghệ thuật, và mỗi ngụm cà phê là một cây cầu giữa các nền văn hóa. Những hội chợ sáng tạo, những buổi tối âm nhạc và những lễ hội theo chủ đề biến mỗi ngày thành một lễ kỷ niệm nhỏ.'
            ]
        ],
        [
            'key' => 'about.paragraph5',
            'category' => 'about',
            'translations' => [
                'ru' => 'В <strong>«Республике Север»</strong> каждый найдет свой идеальный способ провести время: от корпоративных приключений до романтических ужинов под звездным небом, от детских праздников до философских бесед у камина. Это место, где рождаются новые дружбы, крепнут семейные узы и создаются воспоминания на всю жизнь.',
                'en' => 'In <strong>«North Republic»</strong> everyone will find their ideal way to spend time: from corporate adventures to romantic dinners under the starry sky, from children\'s parties to philosophical conversations by the fireplace. This is a place where new friendships are born, family bonds are strengthened and memories are created for a lifetime.',
                'vi' => 'Trong <strong>«Cộng hòa Bắc»</strong> mọi người sẽ tìm thấy cách lý tưởng để dành thời gian: từ những cuộc phiêu lưu doanh nghiệp đến những bữa tối lãng mạn dưới bầu trời đầy sao, từ những bữa tiệc trẻ em đến những cuộc trò chuyện triết học bên lò sưởi. Đây là nơi những tình bạn mới được sinh ra, những mối liên kết gia đình được củng cố và những kỷ niệm được tạo ra cho cả đời.'
            ]
        ],
        
        // Меню секция
        [
            'key' => 'menu.title',
            'category' => 'menu',
            'translations' => [
                'ru' => 'Наше меню',
                'en' => 'Our Menu',
                'vi' => 'Thực đơn của chúng tôi'
            ]
        ],
        [
            'key' => 'menu.error',
            'category' => 'menu',
            'translations' => [
                'ru' => 'Упс, что-то с меню не так',
                'en' => 'Oops, something\'s wrong with the menu',
                'vi' => 'Ôi, có gì đó không ổn với thực đơn'
            ]
        ],
        [
            'key' => 'menu.no_items',
            'category' => 'menu',
            'translations' => [
                'ru' => 'В этой категории пока нет блюд',
                'en' => 'No dishes in this category yet',
                'vi' => 'Chưa có món ăn nào trong danh mục này'
            ]
        ],
        [
            'key' => 'menu.working_on_it',
            'category' => 'menu',
            'translations' => [
                'ru' => 'Мы работаем над пополнением меню',
                'en' => 'We are working on expanding the menu',
                'vi' => 'Chúng tôi đang làm việc để mở rộng thực đơn'
            ]
        ],
        [
            'key' => 'menu.unavailable',
            'category' => 'menu',
            'translations' => [
                'ru' => 'К сожалению, меню временно недоступно. Попробуйте обновить страницу.',
                'en' => 'Unfortunately, the menu is temporarily unavailable. Please try refreshing the page.',
                'vi' => 'Thật không may, thực đơn tạm thời không khả dụng. Vui lòng thử làm mới trang.'
            ]
        ],
        [
            'key' => 'menu.full_menu_button',
            'category' => 'menu',
            'translations' => [
                'ru' => 'Открыть полное меню',
                'en' => 'Open Full Menu',
                'vi' => 'Mở thực đơn đầy đủ'
            ]
        ],
        
        // Галерея секция
        [
            'key' => 'gallery.title',
            'category' => 'gallery',
            'translations' => [
                'ru' => 'Галерея',
                'en' => 'Gallery',
                'vi' => 'Thư viện ảnh'
            ]
        ]
    ];
    
    $inserted = 0;
    $updated = 0;
    
    foreach ($translations as $translation) {
        $existing = $textsCollection->findOne(['key' => $translation['key']]);
        
        if ($existing) {
            // Обновляем существующий перевод
            $textsCollection->updateOne(
                ['key' => $translation['key']],
                [
                    '$set' => [
                        'category' => $translation['category'],
                        'translations' => $translation['translations'],
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            $updated++;
        } else {
            // Вставляем новый перевод
            $translation['created_at'] = new MongoDB\BSON\UTCDateTime();
            $translation['updated_at'] = new MongoDB\BSON\UTCDateTime();
            $textsCollection->insertOne($translation);
            $inserted++;
        }
    }
    
    $message = "Инициализация переводов завершена! Добавлено: $inserted, обновлено: $updated";
    $success = true;
    
} catch (Exception $e) {
    $message = "Ошибка: " . $e->getMessage();
    $success = false;
}

// Логируем действие
logAdminAction('init_translations', 'Инициализация переводов', [
    'inserted' => $inserted ?? 0,
    'updated' => $updated ?? 0,
    'success' => $success
]);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Инициализация переводов - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Инициализация переводов</h1>
                <p>Создание базовых переводов для сайта</p>
            </div>
            
            <div class="card">
                <div style="text-align: center; padding: 3rem;">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h3>✅ Успешно!</h3>
                            <p><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <h3>❌ Ошибка!</h3>
                            <p><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 2rem;">
                        <a href="index.php" class="btn">Вернуться к управлению текстами</a>
                        <a href="../index.php" class="btn btn-secondary">В админку</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
