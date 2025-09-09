<?php
/**
 * Веб-скрипт для обновления вьетнамских переводов
 * Доступ: /admin/update-vietnamese.php
 */

session_start();
require_once 'includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../vendor/autoload.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_translations'])) {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $pageContentCollection = $db->page_content;
        
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
                    'updated_by' => $_SESSION['admin_username'] ?? 'admin'
                ]
            ],
            ['upsert' => true]
        );
        
        if ($result->getUpsertedCount() > 0) {
            $success = '✅ Создана новая запись для вьетнамского контента главной страницы';
        } elseif ($result->getModifiedCount() > 0) {
            $success = '✅ Обновлен вьетнамский контент главной страницы';
        } else {
            $success = 'ℹ️ Вьетнамский контент главной страницы уже актуален';
        }
        
        // Логируем обновление
        logAdminAction('update_vietnamese_translations', 'Обновлены вьетнамские переводы', [
            'page' => 'index',
            'language' => 'vi',
            'updated_by' => $_SESSION['admin_username'] ?? 'admin'
        ]);
        
    } catch (Exception $e) {
        $error = 'Ошибка при обновлении: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обновление вьетнамских переводов - North Republic Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="icon" type="image/png" href="../template/favicon-32x32.png">
    <style>
        .update-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .preview-box {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .preview-box h4 {
            margin-top: 0;
            color: #333;
        }
        
        .btn-update {
            background: #28a745;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-update:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Обновление вьетнамских переводов</h1>
                <p>Обновление основного контента сайта на вьетнамском языке</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="update-container">
                <div class="card">
                    <div class="card-header">
                        <h2>Новые вьетнамские переводы</h2>
                    </div>
                    
                    <div class="card-content">
                        <h3>Основной контент:</h3>
                        <div class="preview-box">
                            <h4>Вьетнамский текст:</h4>
                            <p>Chào mừng đến với North Republic - nơi hội tụ của ẩm thực tinh tế, không gian ấm cúng và những khoảnh khắc khó quên.</p>
                        </div>
                        
                        <h3>Секция "О нас":</h3>
                        <div class="preview-box">
                            <h4>Вьетнамский текст:</h4>
                            <p>North Republic là một ốc đảo của những cuộc phiêu lưu và khám phá ẩm thực giữa khung cảnh hùng vĩ của phía bắc Nha Trang...</p>
                        </div>
                        
                        <form method="POST">
                            <div style="text-align: center; margin-top: 2rem;">
                                <button type="submit" name="update_translations" class="btn-update" 
                                        onclick="return confirm('Вы уверены, что хотите обновить вьетнамские переводы? Это действие заменит существующий контент.')">
                                    🚀 Обновить вьетнамские переводы
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Дополнительные действия</h2>
                    </div>
                    
                    <div class="card-content">
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="texts/index.php" class="btn btn-secondary">Управление текстами</a>
                            <a href="texts/publish.php" class="btn btn-secondary">Публикация изменений</a>
                            <a href="../index.php" class="btn btn-secondary">Просмотр сайта</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>
