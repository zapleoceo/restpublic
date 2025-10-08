
<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Словарь описаний блюд на трех языках
$descriptions = [
    // Русские описания
    'ru' => [
        'Паштет Страусиный' => 'Нежный паштет из печени страуса, подается с брускетами, карамелизированным луком и ягодным соусом',
        'брускетта Наполитано' => 'Сочное канкассе из свежих овощей, оливок на подрумяненном хлебе, с ароматом базилика и оливкового масла',
        'Антипасти' => 'Классические итальянские закуски для аппетитного старта трапезы',
        'Kabanosy' => 'Тонкие вяленые колбаски с пряным вкусом, идеальная закуска к пиву и вину',
        'Куриные джерки' => 'Вяленое мясо с насыщенным вкусом специй, идеальная закуска к пиву и крепким напиткам',
        'Куриные наггетсы' => 'Сочные кусочки курицы в хрустящей панировке, любимая закуска и настоящий фаворит детей',
        'Спринг роллы' => 'Лёгкие рулетики из тонкого рисового теста со свежими овощами и зеленью и мясом, подаются с соусом',
        'Картофель Айдахо' => 'Золотистые дольки картофеля с пряными специями, аппетитная закуска к пиву и мясным блюдам',
        'Луковые кольца' => 'Хрустящие колечки в золотистой панировке, идеальная закуска к пиву и соусам',
        'креветка темпура' => 'Сочные тигровые креветки в хрустящей японской панировке, подаются с соусом, отличная закуска к пиву и вину',
        'пивное плато' => 'Пивное ассорти которое включает в себя: наггетсы 4, луковые кольца 4, спринг роллы 4, креветки 2, картофель айдахо, картофель фри, джерки',
        'Салат с курицей' => 'Свежие листья салата с сочной курицей-гриль, хрустящими брускетами, тёртым сыром и нежным соусом',
        'Креветка в ананасе' => 'Нежные креветки с ананасом в сливочном соусе и сыром, подаются в половинке свежего ананаса',
        'Арбуз с Фетой' => 'Сочный арбуз с нежной фетой, свежей зеленью и лёгким акцентом оливкового масла',
        'Греческий' => 'Классическое сочетание свежих овощей, оливок, феты и оливкового масла с ароматными травами',
        'крем суп грибной' => 'Нежный суп из свежих грибов со сливками, насыщенный ароматом и бархатной текстурой',
        'Суп лапша' => 'Лёгкий бульон с курицей, домашней лапшой',
        'Мидии' => 'Нежные мидии, приготовленные в белом вине со сливками, чесноком и ароматными травами',
        'Лосось со спаржей' => 'Нежный стейк из свежего лосося на гриле, поданный с хрустящей спаржей и лимонным акцентом',
        'Тунец с картофелем' => 'Сочный стейк из тунца на гриле, поданный с румяным картофелем и оливковым маслом',
        'Куриный шашлык' => 'Сочные кусочки курицы, маринованные в специях и обжаренные на гриле до золотистой корочки, подаётся с овощами, картофелем и лавашом',
        'Шашлык свиной' => 'Маринованные кусочки свинины, обжаренные на гриле до румяной корочки, подаётся с овощами, картофелем и лавашом',
        'медальоны говяжьи' => 'Нежные говяжьи медальоны, обжаренные до сочности, подаются с хрустящей спаржей и ароматным соусом',
        'Бургер Классик' => 'Румяная булочка с сочной котлетой, сыром, свежими овощами и фирменным соусом. Подаётся с картофелем и салатом коул-слоу',
        'Бургер грибной' => 'Сочная котлета с сыром, свежими овощами и фирменным грибным соусом в мягкой булочке. Подаётся с картофелем и салатом коул-слоу',
        'Бургер континенталь' => 'Сочная котлета с сыром, свежими овощами, яйцом и хрустящим беконом в мягкой булочке. Суперсытный бургер, подаётся с картофелем и салатом коул-слоу',
        'Бургер Мега' => 'Двойная котлета с сыром и щедрой порцией хрустящего бекона, свежими овощами и соусом в мягкой булочке. Подаётся с картофелем и салатом коул-слоу',
        'Шаурма' => 'Нежное куриное мясо на гриле со свежими овощами и соусом, завёрнутое в ароматный лаваш',
        'рикота боул' => 'Свежий сыр рикотта с сочными фруктами, лёгкий и яркий боул в тропическом стиле',
        'Шакшука' => 'Традиционное блюдо из яиц, приготовленных в соусе из томатов, болгарского перца и специй, с ароматом чеснока и зелени',
        'сырники' => 'Золотистые творожные лепёшки с нежной текстурой и лёгкой сладостью, подаются с йогуртовым соусом, напоминая вкус домашнего уюта',
        'Авокадо тост' => 'Поджаренный хлеб с кремовым авокадо, свежими овощами, ароматными травами и яйцом',
        'Будда боул' => 'Лёгкое и питательное сочетание киноа, свежих овощей и поджаренного тофу. Идеально подходит для вегетарианцев и тех, кто заботится о фигуре',
        'киноа пашот боул' => 'Лёгкий боул с киноа, яйцом пашот и свежими овощами, питательное и сбалансированное блюдо',
        'Боул с тунцом' => 'Рис с овощами и сочным тунцом, обжаренным на гриле, лёгкое и сбалансированное блюдо',
        'Креветка манго боул' => 'Рис с сочными креветками, спелым манго и свежими овощами, лёгкое блюдо с тропическим акцентом',
        'Картофель фри' => 'Хрустящий золотистый картофель, классическая закуска и идеальное дополнение к любому блюду',
        'помидор сыр шаурма' => 'Тонкий лаваш с расплавленным сыром, сочным томатом и свежей зеленью, лёгкая и ароматная закуска'
    ],
    
    // Английские описания
    'en' => [
        'Паштет Страусиный' => 'Tender ostrich liver pâté served with bruschetta, caramelized onions and berry sauce',
        'брускетта Наполитано' => 'Juicy cancan with fresh vegetables and olives on toasted bread with basil and olive oil',
        'Антипасти' => 'Classic Italian appetizers for an appetizing start to the meal',
        'Kabanosy' => 'Thin dried sausages with spicy flavor, perfect appetizer for beer and wine',
        'Куриные джерки' => 'Dried meat with rich spice flavor, perfect appetizer for beer and strong drinks',
        'Куриные наггетсы' => 'Juicy chicken pieces in crispy breading, favorite appetizer and real children\'s favorite',
        'Спринг роллы' => 'Light rolls of thin rice dough with fresh vegetables, herbs and meat, served with sauce',
        'Картофель Айдахо' => 'Golden potato wedges with spicy seasonings, appetizing appetizer for beer and meat dishes',
        'Луковые кольца' => 'Crispy rings in golden breading, perfect appetizer for beer and sauces',
        'креветка темпура' => 'Juicy tiger shrimp in crispy Japanese breading, served with sauce, excellent appetizer for beer and wine',
        'пивное плато' => 'Beer assortment which includes: nuggets 4, onion rings 4, spring rolls 4, shrimp 2, Idaho potatoes, french fries, jerky',
        'Салат с курицей' => 'Fresh lettuce with juicy grilled chicken, crispy bruschetta, grated cheese and delicate sauce',
        'Креветка в ананасе' => 'Tender shrimp with pineapple in creamy sauce and cheese, served in half a fresh pineapple',
        'Арбуз с Фетой' => 'Juicy watermelon with tender feta, fresh herbs and light olive oil accent',
        'Греческий' => 'Classic combination of fresh vegetables, olives, feta and olive oil with aromatic herbs',
        'крем суп грибной' => 'Tender soup of fresh mushrooms with cream, rich in aroma and velvety texture',
        'Суп лапша' => 'Light broth with chicken, homemade noodles',
        'Мидии' => 'Tender mussels cooked in white wine with cream, garlic and aromatic herbs',
        'Лосось со спаржей' => 'Tender grilled fresh salmon steak served with crispy asparagus and lemon accent',
        'Тунец с картофелем' => 'Juicy grilled tuna steak served with golden potatoes and olive oil',
        'Куриный шашлык' => 'Juicy chicken pieces marinated in spices and grilled to golden crust, served with vegetables, potatoes and lavash',
        'Шашлык свиной' => 'Marinated pork pieces grilled to golden crust, served with vegetables, potatoes and lavash',
        'медальоны говяжьи' => 'Tender beef medallions grilled to juiciness, served with crispy asparagus and aromatic sauce',
        'Бургер Классик' => 'Golden bun with juicy patty, cheese, fresh vegetables and signature sauce. Served with potatoes and coleslaw',
        'Бургер грибной' => 'Juicy patty with cheese, fresh vegetables and signature mushroom sauce in soft bun. Served with potatoes and coleslaw',
        'Бургер континенталь' => 'Juicy patty with cheese, fresh vegetables, egg and crispy bacon in soft bun. Super filling burger, served with potatoes and coleslaw',
        'Бургер Мега' => 'Double patty with cheese and generous portion of crispy bacon, fresh vegetables and sauce in soft bun. Served with potatoes and coleslaw',
        'Шаурма' => 'Tender grilled chicken with fresh vegetables and sauce, wrapped in aromatic lavash',
        'рикота боул' => 'Fresh ricotta cheese with juicy fruits, light and bright bowl in tropical style',
        'Шакшука' => 'Traditional dish of eggs cooked in tomato sauce, bell peppers and spices, with garlic and herbs aroma',
        'сырники' => 'Golden cottage cheese pancakes with tender texture and light sweetness, served with yogurt sauce, reminiscent of home comfort',
        'Авокадо тост' => 'Toasted bread with creamy avocado, fresh vegetables, aromatic herbs and egg',
        'Будда боул' => 'Light and nutritious combination of quinoa, fresh vegetables and grilled tofu. Perfect for vegetarians and those who care about figure',
        'киноа пашот боул' => 'Light bowl with quinoa, poached egg and fresh vegetables, nutritious and balanced dish',
        'Боул с тунцом' => 'Rice with vegetables and juicy grilled tuna, light and balanced dish',
        'Креветка манго боул' => 'Rice with juicy shrimp, ripe mango and fresh vegetables, light dish with tropical accent',
        'Картофель фри' => 'Crispy golden potatoes, classic appetizer and perfect addition to any dish',
        'помидор сыр шаурма' => 'Thin lavash with melted cheese, juicy tomato and fresh herbs, light and aromatic appetizer'
    ],
    
    // Вьетнамские описания
    'vi' => [
        'Паштет Страусиный' => 'Pate gan đà điểu mềm mại, phục vụ với bánh mì nướng, hành tây caramel và sốt quả mọng',
        'брускетта Наполитано' => 'Bánh mì nướng với rau tươi, ô liu trên bánh mì nướng vàng, có hương vị húng quế và dầu ô liu',
        'Антипасти' => 'Món khai vị Ý cổ điển để bắt đầu bữa ăn ngon miệng',
        'Kabanosy' => 'Xúc xích khô mỏng với vị cay, món khai vị hoàn hảo cho bia và rượu vang',
        'Куриные джерки' => 'Thịt khô với hương vị gia vị đậm đà, món khai vị hoàn hảo cho bia và đồ uống mạnh',
        'Куриные наггетсы' => 'Những miếng gà ngon ngọt trong lớp bột chiên giòn, món khai vị yêu thích và thực sự là món yêu thích của trẻ em',
        'Спринг роллы' => 'Cuộn nhẹ từ bột gạo mỏng với rau tươi, thảo mộc và thịt, phục vụ với sốt',
        'Картофель Айдахо' => 'Khoai tây vàng với gia vị cay, món khai vị ngon miệng cho bia và món thịt',
        'Луковые кольца' => 'Vòng hành tây giòn trong lớp bột vàng, món khai vị hoàn hảo cho bia và sốt',
        'креветка темпура' => 'Tôm càng xanh ngon ngọt trong lớp bột chiên giòn kiểu Nhật, phục vụ với sốt, món khai vị tuyệt vời cho bia và rượu vang',
        'пивное плато' => 'Bộ sưu tập bia bao gồm: nuggets 4, vòng hành tây 4, cuộn xuân 4, tôm 2, khoai tây Idaho, khoai tây chiên, thịt khô',
        'Салат с курицей' => 'Rau xanh tươi với gà nướng ngon ngọt, bánh mì nướng giòn, phô mai bào và sốt mềm mại',
        'Креветка в ананасе' => 'Tôm mềm mại với dứa trong sốt kem và phô mai, phục vụ trong nửa quả dứa tươi',
        'Арбуз с Фетой' => 'Dưa hấu ngon ngọt với feta mềm mại, thảo mộc tươi và chút dầu ô liu nhẹ',
        'Греческий' => 'Sự kết hợp cổ điển của rau tươi, ô liu, feta và dầu ô liu với thảo mộc thơm',
        'крем суп грибной' => 'Súp kem từ nấm tươi với kem, đậm đà hương vị và kết cấu mượt mà',
        'Суп лапша' => 'Nước dùng nhẹ với gà, mì tự làm',
        'Мидии' => 'Trai mềm mại nấu trong rượu vang trắng với kem, tỏi và thảo mộc thơm',
        'Лосось со спаржей' => 'Phi lê cá hồi tươi nướng mềm mại, phục vụ với măng tây giòn và chút chanh',
        'Тунец с картофелем' => 'Phi lê cá ngừ ngon ngọt nướng, phục vụ với khoai tây vàng và dầu ô liu',
        'Куриный шашлык' => 'Những miếng gà ngon ngọt ướp gia vị và nướng đến lớp vỏ vàng, phục vụ với rau, khoai tây và bánh mì dẹt',
        'Шашлык свиной' => 'Những miếng thịt heo ướp gia vị nướng đến lớp vỏ vàng, phục vụ với rau, khoai tây và bánh mì dẹt',
        'медальоны говяжьи' => 'Phi lê bò mềm mại nướng đến độ ngon ngọt, phục vụ với măng tây giòn và sốt thơm',
        'Бургер Классик' => 'Bánh mì vàng với thịt viên ngon ngọt, phô mai, rau tươi và sốt đặc biệt. Phục vụ với khoai tây và salad bắp cải',
        'Бургер грибной' => 'Thịt viên ngon ngọt với phô mai, rau tươi và sốt nấm đặc biệt trong bánh mì mềm. Phục vụ với khoai tây và salad bắp cải',
        'Бургер континенталь' => 'Thịt viên ngon ngọt với phô mai, rau tươi, trứng và thịt xông khói giòn trong bánh mì mềm. Burger siêu no, phục vụ với khoai tây và salad bắp cải',
        'Бургер Мега' => 'Thịt viên đôi với phô mai và phần thịt xông khói giòn hào phóng, rau tươi và sốt trong bánh mì mềm. Phục vụ với khoai tây và salad bắp cải',
        'Шаурма' => 'Thịt gà nướng mềm mại với rau tươi và sốt, cuộn trong bánh mì dẹt thơm',
        'рикота боул' => 'Phô mai ricotta tươi với trái cây ngon ngọt, bát nhẹ và sáng trong phong cách nhiệt đới',
        'Шакшука' => 'Món ăn truyền thống từ trứng nấu trong sốt cà chua, ớt chuông và gia vị, với hương tỏi và thảo mộc',
        'сырники' => 'Bánh kếp phô mai vàng với kết cấu mềm mại và vị ngọt nhẹ, phục vụ với sốt sữa chua, gợi nhớ hương vị gia đình',
        'Авокадо тост' => 'Bánh mì nướng với bơ kem, rau tươi, thảo mộc thơm và trứng',
        'Будда боул' => 'Sự kết hợp nhẹ và bổ dưỡng của quinoa, rau tươi và đậu phụ nướng. Hoàn hảo cho người ăn chay và những người quan tâm đến vóc dáng',
        'киноа пашот боул' => 'Bát nhẹ với quinoa, trứng luộc chín và rau tươi, món ăn bổ dưỡng và cân bằng',
        'Боул с тунцом' => 'Cơm với rau và cá ngừ nướng ngon ngọt, món ăn nhẹ và cân bằng',
        'Креветка манго боул' => 'Cơm với tôm ngon ngọt, xoài chín và rau tươi, món ăn nhẹ với chút nhiệt đới',
        'Картофель фри' => 'Khoai tây vàng giòn, món khai vị cổ điển và bổ sung hoàn hảo cho bất kỳ món ăn nào',
        'помидор сыр шаурма' => 'Bánh mì dẹt mỏng với phô mai tan chảy, cà chua ngon ngọt và thảo mộc tươi, món khai vị nhẹ và thơm'
    ]
];

try {
    $mongodbUrl = $_ENV['MONGODB_URL'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGODB_DB_NAME'] ?? 'veranda';
    
    $client = new MongoDB\Client($mongodbUrl);
    $db = $client->$dbName;
    $menuCollection = $db->menu;
    
    // Получаем текущее меню
    $menu = $menuCollection->findOne(['_id' => 'current_menu']);
    
    if (!$menu) {
        echo "Кэш меню не найден\n";
        exit;
    }
    
    $products = $menu['products'] ?? [];
    $updatedCount = 0;
    
    echo "=== ОБНОВЛЕНИЕ ОПИСАНИЙ БЛЮД ===\n";
    
    // Обновляем каждый продукт
    foreach ($products as $index => $product) {
        $productName = $product['product_name'] ?? $product['name'] ?? '';
        
        if (empty($productName)) {
            continue;
        }
        
        // Ищем описание для этого блюда
        $hasDescription = false;
        $newProduct = $product;
        
        foreach ($descriptions['ru'] as $dishName => $description) {
            if (stripos($productName, $dishName) !== false || stripos($dishName, $productName) !== false) {
                // Добавляем описания на всех языках
                $newProduct['description_ru'] = $descriptions['ru'][$dishName] ?? '';
                $newProduct['description_en'] = $descriptions['en'][$dishName] ?? '';
                $newProduct['description_vi'] = $descriptions['vi'][$dishName] ?? '';
                $hasDescription = true;
                break;
            }
        }
        
        if ($hasDescription) {
            $products[$index] = $newProduct;
            $updatedCount++;
            echo "✓ Обновлено: $productName\n";
        }
    }
    
    // Сохраняем обновленное меню в MongoDB
    $result = $menuCollection->replaceOne(
        ['_id' => 'current_menu'],
        [
            '_id' => 'current_menu',
            'data' => $menu['data'] ?? [],
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'categories' => $menu['categories'] ?? [],
            'products' => $products
        ],
        ['upsert' => true]
    );
    
    echo "\n=== РЕЗУЛЬТАТ ===\n";
    echo "Обновлено блюд: $updatedCount\n";
    echo "Всего блюд в меню: " . count($products) . "\n";
    echo "Операция завершена успешно!\n";
    
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
?>
