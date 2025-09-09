<?php
/**
 * Простой скрипт для обновления вьетнамских переводов
 * Работает без composer, используя прямое подключение к MongoDB
 */

// Проверяем наличие MongoDB расширения
if (!extension_loaded('mongodb')) {
    echo "❌ MongoDB PHP расширение не установлено.\n";
    echo "Попробуем альтернативный способ...\n";
    
    // Попробуем использовать веб-интерфейс
    echo "🌐 Откройте в браузере: http://localhost/admin/update-vietnamese.php\n";
    exit(1);
}

try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $pageContentCollection = $db->page_content;
    
    echo "🔄 Обновление вьетнамских переводов...\n";
    
    // Новые вьетнамские переводы
    $vietnameseContent = [
        'content' => 'Chào mừng đến với North Republic - nơi hội tụ của ẩm thực tinh tế, không gian ấm cúng và những khoảnh khắc khó quên.',
        'meta' => [
            'title' => 'North Republic - Nhà hàng tại Nha Trang',
            'description' => 'North Republic - nhà hàng tinh tế tại Nha Trang với ẩm thực tuyệt vời và không gian ấm cúng. Đặt bàn trực tuyến.',
            'keywords' => 'nhà hàng, nha trang, việt nam, ẩm thực, đồ ăn, bữa tối, bữa trưa, đặt bàn',
            'intro_welcome' => 'Chào mừng đến với',
            'intro_title' => 'North <br>Republic',
            'about_title' => 'Về chúng tôi',
            'about_content' => '<p class="lead">North Republic là một ốc đảo của những cuộc phiêu lưu và khám phá ẩm thực giữa khung cảnh hùng vĩ của phía bắc Nha Trang. Nơi đây, trong vòng tay của thiên nhiên nguyên sơ, dưới chân núi Cô Tiên huyền thoại, sự hiện đại giao hòa với vẻ đẹp hoang sơ của xứ nhiệt đới, tạo nên một không gian với những khả năng vô tận.</p>

<p>Hãy ngước nhìn lên — trước mắt bạn là sườn núi Tiên, chính là ngọn Cô Tiên mà vẻ đẹp thần thoại của nó đã truyền cảm hứng cho các nhà thơ và du khách qua nhiều thế kỷ. Tầm nhìn toàn cảnh ra những ngọn đồi xanh ngọc và vịnh biển lấp lánh biến mỗi khoảnh khắc tại đây thành một khung hình trong câu chuyện cổ tích diệu kỳ. Đây là nơi thời gian trôi chậm lại và tâm hồn tìm thấy sự bình yên mong đợi.</p>

<p>North Republic là một bức tranh đa sắc của những trải nghiệm ngoài trời. Những trận chiến đấu súng laser đầy kịch tính và những cuộc đấu cung hấp dẫn trong trò bắn cung đối kháng xen kẽ với những chiếc lều thư giãn ấm cúng cho các buổi dã ngoại gia đình. Những trò chơi giải đố trí tuệ đan xen với hương thơm của tiệc nướng BBQ, và các sự kiện buổi tối tràn ngập không khí âm nhạc và tiếng cười cho đến tận đêm khuya.</p>

<p>Nhà hàng và quán cà phê của chúng tôi là một cuộc hành trình ẩm thực, nơi các món ăn đặc trưng được sáng tạo từ sự kết hợp giữa truyền thống Nga và sự độc đáo của Việt Nam. Tại đây, mỗi món ăn là một tác phẩm nghệ thuật, và mỗi ngụm cà phê là cầu nối giữa các nền văn hóa. Các hội chợ sáng tạo, đêm nhạc và lễ hội theo chủ đề biến mỗi ngày thành một ngày hội nhỏ.</p>

<p>Tại North Republic, mỗi người sẽ tìm thấy cách tận hưởng thời gian lý tưởng của riêng mình: từ các cuộc phiêu lưu cho đội nhóm công ty đến những bữa tối lãng mạn dưới bầu trời đầy sao, từ các bữa tiệc cho trẻ em đến những cuộc trò chuyện triết lý bên lò sưởi. Đây là nơi những tình bạn mới được nảy nở, tình cảm gia đình thêm bền chặt và những kỷ niệm đáng nhớ được tạo nên cho cả cuộc đời.</p>',
            'menu_title' => 'Thực đơn của chúng tôi',
            'menu_description' => 'Khám phá những món ăn và đồ uống tinh tế tại nhà hàng của chúng tôi.',
            'gallery_title' => 'Thư viện ảnh',
            'gallery_description' => 'Những khoảnh khắc đẹp từ North Republic',
            'menu_full_button' => 'Xem toàn bộ thực đơn',
            'menu_no_items' => 'Đang cập nhật',
            'menu_working_on_it' => 'Chúng tôi đang làm việc để mang đến cho bạn những món ăn tuyệt vời nhất.',
            'menu_error' => 'Thực đơn tạm thời không khả dụng',
            'menu_unavailable' => 'Vui lòng thử lại sau hoặc liên hệ trực tiếp với chúng tôi.'
        ]
    ];
    
    // Обновляем контент для главной страницы на вьетнамском языке
    $result = $pageContentCollection->updateOne(
        [
            'page' => 'index',
            'language' => 'vi'
        ],
        [
            '$set' => [
                'content' => $vietnameseContent['content'],
                'meta' => $vietnameseContent['meta'],
                'status' => 'published',
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_by' => 'admin_script'
            ]
        ],
        ['upsert' => true]
    );
    
    if ($result->getUpsertedCount() > 0) {
        echo "✅ Создана новая запись для вьетнамского контента главной страницы\n";
    } elseif ($result->getModifiedCount() > 0) {
        echo "✅ Обновлен вьетнамский контент главной страницы\n";
    } else {
        echo "ℹ️ Вьетнамский контент главной страницы уже актуален\n";
    }
    
    echo "\n🎉 Вьетнамские переводы успешно обновлены!\n";
    echo "🌐 Проверьте сайт: http://localhost/?lang=vi\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "🌐 Попробуйте веб-интерфейс: http://localhost/admin/update-vietnamese.php\n";
    exit(1);
}
?>
