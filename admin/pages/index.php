<?php
/**
 * Управление страницами сайта с WYSIWYG редактором
 * Работает с файловой системой (fallback для MongoDB)
 */

require_once __DIR__ . '/../includes/auth-check.php';

$error = '';
$success = '';

// Определяем текущую страницу и язык для редактирования
$currentPage = $_GET['page'] ?? 'index';
$currentLanguage = $_GET['lang'] ?? 'ru';
$availableLanguages = ['ru', 'en', 'vi'];

// Файлы для хранения контента
$contentFile = __DIR__ . '/../../data/page_content.json';
$contentDir = dirname($contentFile);

if (!is_dir($contentDir)) {
    mkdir($contentDir, 0755, true);
}

// Загружаем контент из файла
$pageContent = [];
if (file_exists($contentFile)) {
    $pageContent = json_decode(file_get_contents($contentFile), true) ?: [];
}

// Получаем контент текущей страницы
$currentContent = $pageContent[$currentPage][$currentLanguage] ?? [
    'content' => '',
    'meta' => [
        'title' => '',
        'description' => '',
        'keywords' => ''
    ],
    'status' => 'draft'
];

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $content = $_POST['content'] ?? '';
        $meta = [
            'title' => $_POST['meta_title'] ?? '',
            'description' => $_POST['meta_description'] ?? '',
            'keywords' => $_POST['meta_keywords'] ?? ''
        ];
        $status = $_POST['status'] ?? 'draft';
        
        // Сохраняем контент
        if (!isset($pageContent[$currentPage])) {
            $pageContent[$currentPage] = [];
        }
        
        $pageContent[$currentPage][$currentLanguage] = [
            'content' => $content,
            'meta' => $meta,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['admin_username'] ?? 'admin'
        ];
        
        if (file_put_contents($contentFile, json_encode($pageContent, JSON_PRETTY_PRINT))) {
            $success = 'Контент сохранен';
            $currentContent = $pageContent[$currentPage][$currentLanguage];
        } else {
            $error = 'Ошибка сохранения';
        }
    }
}

// Получаем список страниц
$pages = array_keys($pageContent);
if (empty($pages)) {
    $pages = ['index', 'about', 'menu', 'contact'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление страницами - Админка</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- TinyMCE WYSIWYG Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <style>
        .page-editor {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .page-selector {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .editor-container {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .page-item {
            margin-bottom: 0.5rem;
        }
        
        .page-item a {
            display: block;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .page-item a:hover,
        .page-item a.active {
            background: #f0f0f0;
        }
        
        .language-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .language-tab {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s;
        }
        
        .language-tab.active {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        
        .meta-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .meta-field {
            display: flex;
            flex-direction: column;
        }
        
        .meta-field label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .meta-field input,
        .meta-field textarea {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .meta-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .editor-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007cba;
            color: white;
        }
        
        .btn-primary:hover {
            background: #005a87;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .status-indicator {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        
        @media (max-width: 768px) {
            .page-editor {
                grid-template-columns: 1fr;
            }
            
            .meta-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>Управление страницами</h1>
                <p>Редактирование полного HTML контента страниц с WYSIWYG редактором</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="page-editor">
                <!-- Селектор страниц -->
                <div class="page-selector">
                    <h3>Страницы сайта</h3>
                    <ul class="page-list">
                        <?php foreach ($pages as $page): ?>
                            <li class="page-item">
                                <a href="?page=<?php echo urlencode($page); ?>&lang=<?php echo urlencode($currentLanguage); ?>" 
                                   class="<?php echo $page === $currentPage ? 'active' : ''; ?>">
                                    <?php echo ucfirst($page); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Редактор -->
                <div class="editor-container">
                    <h3>Редактирование: <?php echo ucfirst($currentPage); ?></h3>
                    
                    <!-- Вкладки языков -->
                    <div class="language-tabs">
                        <?php foreach ($availableLanguages as $lang): ?>
                            <div class="language-tab <?php echo $lang === $currentLanguage ? 'active' : ''; ?>" 
                                 onclick="window.location.href='?page=<?php echo urlencode($currentPage); ?>&lang=<?php echo $lang; ?>'">
                                <?php echo strtoupper($lang); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="save">
                        
                        <!-- Мета-поля -->
                        <div class="meta-fields">
                            <div class="meta-field">
                                <label for="meta_title">Заголовок страницы (Title)</label>
                                <input type="text" id="meta_title" name="meta_title" 
                                       value="<?php echo htmlspecialchars($currentContent['meta']['title'] ?? ''); ?>">
                            </div>
                            <div class="meta-field">
                                <label for="meta_description">Описание (Description)</label>
                                <textarea id="meta_description" name="meta_description"><?php echo htmlspecialchars($currentContent['meta']['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="meta-field">
                                <label for="meta_keywords">Ключевые слова</label>
                                <input type="text" id="meta_keywords" name="meta_keywords" 
                                       value="<?php echo htmlspecialchars($currentContent['meta']['keywords'] ?? ''); ?>">
                            </div>
                            <div class="meta-field">
                                <label>Статус</label>
                                <select name="status">
                                    <option value="draft" <?php echo ($currentContent['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                                    <option value="published" <?php echo ($currentContent['status'] ?? 'draft') === 'published' ? 'selected' : ''; ?>>Опубликовано</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- WYSIWYG редактор -->
                        <div class="editor-wrapper">
                            <textarea id="page_content" name="content"><?php echo htmlspecialchars($currentContent['content'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Действия -->
                        <div class="editor-actions">
                            <button type="submit" class="btn btn-primary">
                                💾 Сохранить
                            </button>
                            <button type="button" class="btn btn-success" onclick="previewContent()">
                                👁️ Предпросмотр
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        let currentPage = '<?php echo $currentPage; ?>';
        let currentLanguage = '<?php echo $currentLanguage; ?>';
        let editor;
        
        // Инициализация TinyMCE
        tinymce.init({
            selector: '#page_content',
            height: 500,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
            setup: function (ed) {
                editor = ed;
            }
        });
        
        // Предпросмотр
        function previewContent() {
            if (!editor) return;
            
            const content = editor.getContent();
            const previewWindow = window.open('', '_blank');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Предпросмотр - ${currentPage}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .preview-header { background: #f0f0f0; padding: 10px; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="preview-header">
                        <h2>Предпросмотр: ${currentPage} (${currentLanguage.toUpperCase()})</h2>
                    </div>
                    ${content}
                </body>
                </html>
            `);
        }
    </script>
</body>
</html>