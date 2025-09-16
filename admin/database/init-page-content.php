<?php
/**
 * Инициализация контента страниц в MongoDB
 * Добавляет базовый контент для главной страницы на всех языках
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../classes/PageContentService.php';

try {
    $pageContentService = new PageContentService();
    
    // Контент для русского языка
    $ruContent = [
        'page' => 'index',
        'language' => 'ru',
        'content' => 'Добро пожаловать в <strong>North Republic</strong> — место, где встречаются изысканная кухня, уютная атмосфера и незабываемые моменты.',
        'meta' => [
            'title' => 'North Republic - Ресторан в Нячанге',
            'description' => 'North Republic - изысканный ресторан в Нячанге с великолепной кухней и уютной атмосферой. Забронируйте столик онлайн.',
            'keywords' => 'ресторан, нячанг, вьетнам, кухня, еда, ужин, обед, бронирование',
            'intro_welcome' => 'Добро пожаловать в',
            'intro_title' => 'North <br>Republic',
            'about_title' => 'О нас',
            'about_content' => '<p class="lead">Добро пожаловать в <strong>«Республику Север»</strong> — оазис приключений и гастономических открытий среди величественных пейзажей северного Нячанга. Здесь, в объятиях первозданной природы, у подножия легендарной горы Ко Тьен, современность встречается с дикой красотой тропического края, создавая пространство безграничных возможностей.</p><p>Взгляните вверх — перед вами раскинулись склоны Горы Феи, той самой Ко Тьен, чья мифическая красота веками вдохновляла поэтов и путешественников. Панорамные виды на изумрудные холмы и сверкающий залив превращают каждый момент здесь в кадр из волшебной сказки. Это место, где время замедляет свой бег, а душа находит долгожданный покой.</p><p><strong>«Республика Север»</strong> — это калейдоскоп впечатлений под открытым небом. Адреналиновые баталии в лазертаге и захватывающие дуэли с луками в арчеритаге соседствуют с уютными беседками для семейных пикников. Интеллектуальные квесты переплетаются с ароматами барбекю, а вечерние мероприятия наполняют воздух музыкой и смехом до поздней ночи.</p><p>Наш ресторан и кофейня — это кулинарное путешествие, где авторские блюда рождаются из слияния русских традиций и вьетнамской экзотики. Здесь каждое блюдо — произведение искусства, а каждый глоток кофе — мост между культурами. Творческие ярмарки, музыкальные вечера и тематические фестивали превращают каждый день в маленький праздник.</p><p>В <strong>«Республике Север»</strong> каждый найдет свой идеальный способ провести время: от корпоративных приключений до романтических ужинов под звездным небом, от детских праздников до философских бесед у камина. Это место, где рождаются новые дружбы, крепнут семейные узы и создаются воспоминания на всю жизнь.</p>',
            'menu_title' => 'Наше меню',
            'menu_error' => 'Упс, что-то с меню не так',
            'menu_no_items' => 'В этой категории пока нет блюд',
            'menu_working_on_it' => 'Мы работаем над пополнением меню',
            'menu_unavailable' => 'К сожалению, меню временно недоступно. Попробуйте обновить страницу.',
            'menu_full_button' => 'Открыть полное меню',
            'menu_top_5' => 'Top 5 позиций',
            'menu_updated' => 'Обновлено',
            'location_nha_trang' => 'Нячанг',
            'events_title' => 'События',
            'events_widget_title' => 'Афиша событий',
            'events_empty_title' => 'Мы еще не придумали что у нас тут будет.',
            'events_empty_text' => 'Есть идеи?',
            'events_empty_link' => 'Свяжитесь с нами!',
            'menu_categories_aria' => 'Категории меню',
            'menu_content_aria' => 'Содержимое меню',
            'events_dates_aria' => 'Выбор даты события',
            'events_posters_aria' => 'Постеры событий',
            'gallery_title' => 'Галерея',
            'intro_image_primary_alt' => 'Главное изображение ресторана North Republic',
            'intro_image_secondary_alt' => 'Дополнительное изображение интерьера ресторана',
            'about_image_primary_alt' => 'Фотография интерьера ресторана North Republic',
            'intro_image_primary' => 'template/images/shawa.png',
            'intro_image_secondary' => 'template/images/intro-pic-secondary.jpg',
            'intro_image_secondary_2x' => 'template/images/intro-pic-secondary@2x.jpg',
            'about_image_primary' => 'template/images/about-pic-primary.jpg',
            'about_image_primary_2x' => 'template/images/about-pic-primary@2x.jpg',
            'gallery_images' => [
                [
                    'thumb' => 'template/images/gallery/gallery-01.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-01.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-01@2x.jpg',
                    'alt' => 'Галерея 1'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-02.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-02.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-02@2x.jpg',
                    'alt' => 'Галерея 2'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-03.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-03.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-03@2x.jpg',
                    'alt' => 'Галерея 3'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-04.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-04.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-04@2x.jpg',
                    'alt' => 'Галерея 4'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-05.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-05.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-05@2x.jpg',
                    'alt' => 'Галерея 5'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-06.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-06.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-06@2x.jpg',
                    'alt' => 'Галерея 6'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-07.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-07.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-07@2x.jpg',
                    'alt' => 'Галерея 7'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-08.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-08.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-08@2x.jpg',
                    'alt' => 'Галерея 8'
                ]
            ]
        ],
        'status' => 'published',
        'updated_by' => 'admin'
    ];
    
    // Контент для английского языка
    $enContent = [
        'page' => 'index',
        'language' => 'en',
        'content' => 'Welcome to <strong>North Republic</strong> — where exquisite cuisine, cozy atmosphere and unforgettable moments meet.',
        'meta' => [
            'title' => 'North Republic - Restaurant in Nha Trang',
            'description' => 'North Republic - exquisite restaurant in Nha Trang with magnificent cuisine and cozy atmosphere. Book a table online.',
            'keywords' => 'restaurant, nha trang, vietnam, cuisine, food, dinner, lunch, booking',
            'intro_welcome' => 'Welcome to',
            'intro_title' => 'North <br>Republic',
            'about_title' => 'About Us',
            'about_content' => '<p class="lead">Welcome to <strong>«North Republic»</strong> — an oasis of adventures and gastronomic discoveries among the majestic landscapes of northern Nha Trang. Here, in the embrace of pristine nature, at the foot of the legendary Co Tien Mountain, modernity meets the wild beauty of the tropical region, creating a space of unlimited possibilities.</p><p>Look up — before you stretch the slopes of the Fairy Mountain, that same Co Tien, whose mythical beauty has inspired poets and travelers for centuries. Panoramic views of emerald hills and sparkling bay turn every moment here into a frame from a magical fairy tale. This is a place where time slows down and the soul finds long-awaited peace.</p><p><strong>«North Republic»</strong> is a kaleidoscope of experiences under the open sky. Adrenaline battles in laser tag and exciting duels with bows in archery coexist with cozy gazebos for family picnics. Intellectual quests intertwine with the aromas of barbecue, and evening events fill the air with music and laughter until late at night.</p><p>Our restaurant and cafe is a culinary journey where signature dishes are born from the fusion of Russian traditions and Vietnamese exoticism. Here every dish is a work of art, and every sip of coffee is a bridge between cultures. Creative fairs, musical evenings and themed festivals turn every day into a small celebration.</p><p>At <strong>«North Republic»</strong> everyone will find their ideal way to spend time: from corporate adventures to romantic dinners under the starry sky, from children\'s parties to philosophical conversations by the fireplace. This is a place where new friendships are born, family bonds are strengthened and memories are created for life.</p>',
            'menu_title' => 'Our Menu',
            'menu_error' => 'Oops, something\'s wrong with the menu',
            'menu_no_items' => 'No dishes in this category yet',
            'menu_working_on_it' => 'We are working on expanding our menu',
            'menu_unavailable' => 'Unfortunately, the menu is temporarily unavailable. Please try refreshing the page.',
            'menu_full_button' => 'View Full Menu',
            'menu_top_5' => 'Top 5 Items',
            'menu_updated' => 'Updated',
            'location_nha_trang' => 'Nha Trang',
            'events_title' => 'Events',
            'events_widget_title' => 'Events Schedule',
            'events_empty_title' => 'We haven\'t figured out what we\'ll have here yet.',
            'events_empty_text' => 'Have ideas?',
            'events_empty_link' => 'Contact us!',
            'menu_categories_aria' => 'Menu categories',
            'menu_content_aria' => 'Menu content',
            'events_dates_aria' => 'Event date selection',
            'events_posters_aria' => 'Event posters',
            'gallery_title' => 'Gallery',
            'intro_image_primary_alt' => 'Main image of North Republic restaurant',
            'intro_image_secondary_alt' => 'Additional interior image of the restaurant',
            'about_image_primary_alt' => 'Interior photo of North Republic restaurant',
            'intro_image_primary' => 'template/images/shawa.png',
            'intro_image_secondary' => 'template/images/intro-pic-secondary.jpg',
            'intro_image_secondary_2x' => 'template/images/intro-pic-secondary@2x.jpg',
            'about_image_primary' => 'template/images/about-pic-primary.jpg',
            'about_image_primary_2x' => 'template/images/about-pic-primary@2x.jpg',
            'gallery_images' => [
                [
                    'thumb' => 'template/images/gallery/gallery-01.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-01.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-01@2x.jpg',
                    'alt' => 'Gallery 1'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-02.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-02.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-02@2x.jpg',
                    'alt' => 'Gallery 2'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-03.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-03.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-03@2x.jpg',
                    'alt' => 'Gallery 3'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-04.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-04.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-04@2x.jpg',
                    'alt' => 'Gallery 4'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-05.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-05.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-05@2x.jpg',
                    'alt' => 'Gallery 5'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-06.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-06.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-06@2x.jpg',
                    'alt' => 'Gallery 6'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-07.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-07.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-07@2x.jpg',
                    'alt' => 'Gallery 7'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-08.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-08.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-08@2x.jpg',
                    'alt' => 'Gallery 8'
                ]
            ]
        ],
        'status' => 'published',
        'updated_by' => 'admin'
    ];
    
    // Контент для вьетнамского языка
    $viContent = [
        'page' => 'index',
        'language' => 'vi',
        'content' => 'Chào mừng đến với North Republic - nơi hội tụ của ẩm thực tinh tế, không gian ấm cúng và những khoảnh khắc khó quên.',
                    'meta' => [
            'title' => 'North Republic - Nhà hàng tại Nha Trang',
            'description' => 'North Republic - nhà hàng tinh tế tại Nha Trang với ẩm thực tuyệt vời và bầu không khí ấm cúng. Đặt bàn trực tuyến.',
            'keywords' => 'nhà hàng, nha trang, việt nam, ẩm thực, thức ăn, bữa tối, bữa trưa, đặt bàn',
            'intro_welcome' => 'Chào mừng đến với',
            'intro_title' => 'North <br>Republic',
            'about_title' => 'Về chúng tôi',
            'about_content' => '<p class="lead">North Republic là một ốc đảo của những cuộc phiêu lưu và khám phá ẩm thực giữa khung cảnh hùng vĩ của phía bắc Nha Trang. Nơi đây, trong vòng tay của thiên nhiên nguyên sơ, dưới chân núi Cô Tiên huyền thoại, sự hiện đại giao hòa với vẻ đẹp hoang sơ của xứ nhiệt đới, tạo nên một không gian với những khả năng vô tận.</p>

<p>Hãy ngước nhìn lên — trước mắt bạn là sườn núi Tiên, chính là ngọn Cô Tiên mà vẻ đẹp thần thoại của nó đã truyền cảm hứng cho các nhà thơ và du khách qua nhiều thế kỷ. Tầm nhìn toàn cảnh ra những ngọn đồi xanh ngọc và vịnh biển lấp lánh biến mỗi khoảnh khắc tại đây thành một khung hình trong câu chuyện cổ tích diệu kỳ. Đây là nơi thời gian trôi chậm lại và tâm hồn tìm thấy sự bình yên mong đợi.</p>

<p>North Republic là một bức tranh đa sắc của những trải nghiệm ngoài trời. Những trận chiến đấu súng laser đầy kịch tính và những cuộc đấu cung hấp dẫn trong trò bắn cung đối kháng xen kẽ với những chiếc lều thư giãn ấm cúng cho các buổi dã ngoại gia đình. Những trò chơi giải đố trí tuệ đan xen với hương thơm của tiệc nướng BBQ, và các sự kiện buổi tối tràn ngập không khí âm nhạc và tiếng cười cho đến tận đêm khuya.</p>

<p>Nhà hàng và quán cà phê của chúng tôi là một cuộc hành trình ẩm thực, nơi các món ăn đặc trưng được sáng tạo từ sự kết hợp giữa truyền thống Nga và sự độc đáo của Việt Nam. Tại đây, mỗi món ăn là một tác phẩm nghệ thuật, và mỗi ngụm cà phê là cầu nối giữa các nền văn hóa. Các hội chợ sáng tạo, đêm nhạc và lễ hội theo chủ đề biến mỗi ngày thành một ngày hội nhỏ.</p>

<p>Tại North Republic, mỗi người sẽ tìm thấy cách tận hưởng thời gian lý tưởng của riêng mình: từ các cuộc phiêu lưu cho đội nhóm công ty đến những bữa tối lãng mạn dưới bầu trời đầy sao, từ các bữa tiệc cho trẻ em đến những cuộc trò chuyện triết lý bên lò sưởi. Đây là nơi những tình bạn mới được nảy nở, tình cảm gia đình thêm bền chặt và những kỷ niệm đáng nhớ được tạo nên cho cả cuộc đời.</p>',
            'menu_title' => 'Thực đơn của chúng tôi',
            'menu_error' => 'Ôi, có gì đó không ổn với thực đơn',
            'menu_no_items' => 'Chưa có món ăn nào trong danh mục này',
            'menu_working_on_it' => 'Chúng tôi đang làm việc để mở rộng thực đơn',
            'menu_unavailable' => 'Rất tiếc, thực đơn tạm thời không khả dụng. Vui lòng thử làm mới trang.',
            'menu_full_button' => 'Xem thực đơn đầy đủ',
            'menu_top_5' => 'Top 5 món',
            'menu_updated' => 'Cập nhật',
            'location_nha_trang' => 'Nha Trang',
            'events_title' => 'Sự kiện',
            'events_widget_title' => 'Lịch sự kiện',
            'events_empty_title' => 'Chúng tôi chưa nghĩ ra sẽ có gì ở đây.',
            'events_empty_text' => 'Có ý tưởng?',
            'events_empty_link' => 'Liên hệ với chúng tôi!',
            'menu_categories_aria' => 'Danh mục thực đơn',
            'menu_content_aria' => 'Nội dung thực đơn',
            'events_dates_aria' => 'Chọn ngày sự kiện',
            'events_posters_aria' => 'Áp phích sự kiện',
            'gallery_title' => 'Thư viện ảnh',
            'intro_image_primary_alt' => 'Hình ảnh chính của nhà hàng North Republic',
            'intro_image_secondary_alt' => 'Hình ảnh nội thất bổ sung của nhà hàng',
            'about_image_primary_alt' => 'Ảnh nội thất nhà hàng North Republic',
            'intro_image_primary' => 'template/images/shawa.png',
            'intro_image_secondary' => 'template/images/intro-pic-secondary.jpg',
            'intro_image_secondary_2x' => 'template/images/intro-pic-secondary@2x.jpg',
            'about_image_primary' => 'template/images/about-pic-primary.jpg',
            'about_image_primary_2x' => 'template/images/about-pic-primary@2x.jpg',
            'gallery_images' => [
                [
                    'thumb' => 'template/images/gallery/gallery-01.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-01.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-01@2x.jpg',
                    'alt' => 'Thư viện ảnh 1'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-02.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-02.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-02@2x.jpg',
                    'alt' => 'Thư viện ảnh 2'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-03.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-03.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-03@2x.jpg',
                    'alt' => 'Thư viện ảnh 3'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-04.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-04.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-04@2x.jpg',
                    'alt' => 'Thư viện ảnh 4'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-05.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-05.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-05@2x.jpg',
                    'alt' => 'Thư viện ảnh 5'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-06.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-06.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-06@2x.jpg',
                    'alt' => 'Thư viện ảnh 6'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-07.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-07.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-07@2x.jpg',
                    'alt' => 'Thư viện ảnh 7'
                ],
                [
                    'thumb' => 'template/images/gallery/gallery-08.jpg',
                    'large' => 'template/images/gallery/large/l-gallery-08.jpg',
                    'thumb2x' => 'template/images/gallery/gallery-08@2x.jpg',
                    'alt' => 'Thư viện ảnh 8'
                ]
            ]
                    ],
                    'status' => 'published',
        'updated_by' => 'admin'
    ];
    
    // Сохраняем контент для всех языков
    $languages = ['ru' => $ruContent, 'en' => $enContent, 'vi' => $viContent];
    
    foreach ($languages as $lang => $content) {
        $result = $pageContentService->savePageContent(
            $content['page'],
            $content['language'],
            $content['content'],
            $content['meta'],
            $content['status'],
            $content['updated_by']
        );
        
        if ($result) {
            echo "✅ Контент для языка '$lang' успешно сохранен\n";
        } else {
            echo "❌ Ошибка сохранения контента для языка '$lang'\n";
        }
    }
    
    echo "\n🎉 Инициализация контента страниц завершена!\n";
    echo "📝 Проверьте страницу: https://northrepublic.me/index_new.php\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}
?>