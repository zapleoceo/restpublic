<?php
session_start();
require_once '../includes/auth-check.php';

// Подключение к MongoDB
require_once __DIR__ . '/../../vendor/autoload.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $client = new MongoDB\Client("mongodb://localhost:27017");
        $db = $client->northrepublic;
        $textsCollection = $db->admin_texts;
        
        $key = trim($_POST['key'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $translations = [
            'ru' => trim($_POST['translation_ru'] ?? ''),
            'en' => trim($_POST['translation_en'] ?? ''),
            'vi' => trim($_POST['translation_vi'] ?? '')
        ];
        
        // Валидация
        if (empty($key)) {
            throw new Exception('Ключ не может быть пустым');
        }
        
        if (empty($category)) {
            throw new Exception('Категория не может быть пустой');
        }
        
        // Проверяем, не существует ли уже такой ключ
        $existing = $textsCollection->findOne(['key' => $key]);
        if ($existing) {
            throw new Exception('Текст с таким ключом уже существует');
        }
        
        // Создаем новый текст
        $textData = [
            'key' => $key,
            'category' => $category,
            'translations' => $translations,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'created_by' => $_SESSION['admin_username'] ?? 'unknown'
        ];
        
        $result = $textsCollection->insertOne($textData);
        
        if ($result->getInsertedId()) {
            // Логируем создание
            logAdminAction('create_text', 'Создан новый текст', [
                'key' => $key,
                'category' => $category,
                'text_id' => (string)$result->getInsertedId()
            ]);
            
            $success = 'Текст успешно создан!';
            
            // Очищаем форму
            $_POST = [];
        } else {
            throw new Exception('Ошибка при создании текста');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Получаем существующие категории
try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $db = $client->northrepublic;
    $textsCollection = $db->admin_texts;
    $categories = $textsCollection->distinct('category');
} catch (Exception $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить текст - North Republic Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="../../template/favicon-32x32.png">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .language-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .language-tab {
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .language-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .language-content {
            display: none;
        }
        
        .language-content.active {
            display: block;
        }
        
        .translation-group {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0 8px 8px 8px;
            border: 2px solid #e1e5e9;
            border-top: none;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e5e9;
        }
        
        .help-text {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #2196f3;
        }
        
        .help-text h4 {
            margin: 0 0 0.5rem 0;
            color: #1976d2;
        }
        
        .help-text p {
            margin: 0;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Добавить текст</h1>
                <p>Создание нового текста для сайта</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="help-text">
                    <h4>💡 Подсказка</h4>
                    <p>Создайте ключ в формате: <code>category_element_name</code> (например: <code>intro_welcome_text</code>). Категория поможет группировать тексты.</p>
                </div>
                
                <form method="POST" class="card">
                    <div class="card-header">
                        <h2>Основная информация</h2>
                    </div>
                    
                    <div class="form-group">
                        <label for="key">Ключ текста *</label>
                        <input type="text" id="key" name="key" 
                               value="<?php echo htmlspecialchars($_POST['key'] ?? ''); ?>" 
                               placeholder="intro_welcome_text" required>
                        <small>Уникальный идентификатор текста (только латинские буквы, цифры и подчеркивания)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Категория *</label>
                        <select id="category" name="category" required>
                            <option value="">Выберите категорию</option>
                            <option value="intro" <?php echo ($_POST['category'] ?? '') === 'intro' ? 'selected' : ''; ?>>Главная страница</option>
                            <option value="about" <?php echo ($_POST['category'] ?? '') === 'about' ? 'selected' : ''; ?>>О нас</option>
                            <option value="menu" <?php echo ($_POST['category'] ?? '') === 'menu' ? 'selected' : ''; ?>>Меню</option>
                            <option value="gallery" <?php echo ($_POST['category'] ?? '') === 'gallery' ? 'selected' : ''; ?>>Галерея</option>
                            <option value="footer" <?php echo ($_POST['category'] ?? '') === 'footer' ? 'selected' : ''; ?>>Подвал</option>
                            <option value="header" <?php echo ($_POST['category'] ?? '') === 'header' ? 'selected' : ''; ?>>Шапка</option>
                            <option value="buttons" <?php echo ($_POST['category'] ?? '') === 'buttons' ? 'selected' : ''; ?>>Кнопки</option>
                            <option value="errors" <?php echo ($_POST['category'] ?? '') === 'errors' ? 'selected' : ''; ?>>Ошибки</option>
                            <?php foreach ($categories as $category): ?>
                                <?php if (!in_array($category, ['intro', 'about', 'menu', 'gallery', 'footer', 'header', 'buttons', 'errors'])): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>" 
                                            <?php echo ($_POST['category'] ?? '') === $category ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small>Группировка текстов по разделам сайта</small>
                    </div>
                    
                    <div class="card-header">
                        <h2>Переводы</h2>
                    </div>
                    
                    <div class="language-tabs">
                        <div class="language-tab active" data-lang="ru">🇷🇺 Русский</div>
                        <div class="language-tab" data-lang="en">🇬🇧 English</div>
                        <div class="language-tab" data-lang="vi">🇻🇳 Tiếng Việt</div>
                    </div>
                    
                    <div class="language-content active" data-lang="ru">
                        <div class="translation-group">
                            <div class="form-group">
                                <label for="translation_ru">Русский текст *</label>
                                <textarea id="translation_ru" name="translation_ru" rows="4" 
                                          placeholder="Введите текст на русском языке" required><?php echo htmlspecialchars($_POST['translation_ru'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="language-content" data-lang="en">
                        <div class="translation-group">
                            <div class="form-group">
                                <label for="translation_en">English text</label>
                                <textarea id="translation_en" name="translation_en" rows="4" 
                                          placeholder="Enter text in English"><?php echo htmlspecialchars($_POST['translation_en'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="language-content" data-lang="vi">
                        <div class="translation-group">
                            <div class="form-group">
                                <label for="translation_vi">Tiếng Việt</label>
                                <textarea id="translation_vi" name="translation_vi" rows="4" 
                                          placeholder="Nhập văn bản bằng tiếng Việt"><?php echo htmlspecialchars($_POST['translation_vi'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Отмена</a>
                        <button type="submit" class="btn">Создать текст</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/admin.js"></script>
    <script>
        // Переключение языков
        document.querySelectorAll('.language-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const lang = this.dataset.lang;
                
                // Убираем активный класс со всех табов и контента
                document.querySelectorAll('.language-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.language-content').forEach(c => c.classList.remove('active'));
                
                // Добавляем активный класс к выбранному табу и контенту
                this.classList.add('active');
                document.querySelector(`.language-content[data-lang="${lang}"]`).classList.add('active');
            });
        });
        
        // Валидация ключа
        document.getElementById('key').addEventListener('input', function() {
            const value = this.value;
            const valid = /^[a-zA-Z0-9_]+$/.test(value);
            
            if (!valid && value.length > 0) {
                this.style.borderColor = '#e74c3c';
                this.title = 'Ключ может содержать только латинские буквы, цифры и подчеркивания';
            } else {
                this.style.borderColor = '#e1e5e9';
                this.title = '';
            }
        });
    </script>
</body>
</html>
