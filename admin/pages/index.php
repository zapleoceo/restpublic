<?php
/**
 * Управление страницами сайта с WYSIWYG редактором
 * Полная замена системы переводов на систему полного HTML контента
 */

require_once '../includes/auth-check.php';
require_once '../../classes/PageContentService.php';

$pageContentService = new PageContentService();
$error = '';
$success = '';

// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'save':
                $page = $_POST['page'] ?? '';
                $language = $_POST['language'] ?? '';
                $content = $_POST['content'] ?? '';
                $meta = [
                    'title' => $_POST['meta_title'] ?? '',
                    'description' => $_POST['meta_description'] ?? '',
                    'keywords' => $_POST['meta_keywords'] ?? ''
                ];
                $status = $_POST['status'] ?? 'draft';
                
                if ($pageContentService->savePageContent($page, $language, $content, $meta, $status, $_SESSION['admin_username'])) {
                    echo json_encode(['success' => true, 'message' => 'Контент сохранен']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения']);
                }
                exit;
                
            case 'publish':
                $page = $_POST['page'] ?? '';
                $language = $_POST['language'] ?? '';
                
                if ($pageContentService->publishPage($page, $language, $_SESSION['admin_username'])) {
                    echo json_encode(['success' => true, 'message' => 'Страница опубликована']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Ошибка публикации']);
                }
                exit;
                
            case 'get_content':
                $page = $_POST['page'] ?? '';
                $language = $_POST['language'] ?? '';
                
                $content = $pageContentService->getPageContent($page, $language);
                echo json_encode(['success' => true, 'content' => $content]);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Получаем список страниц и статистику
$pages = $pageContentService->getAllPages();
$pagesStats = $pageContentService->getPagesStats();
$availableLanguages = $pageContentService->getAvailableLanguages();

// Определяем текущую страницу и язык для редактирования
$currentPage = $_GET['page'] ?? ($pages[0] ?? 'index');
$currentLanguage = $_GET['lang'] ?? 'ru';

// Получаем контент текущей страницы
$currentContent = $pageContentService->getPageContent($currentPage, $currentLanguage);
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
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
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
        
        .page-stats {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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
                    
                    <div class="page-stats">
                        <h4>Статистика</h4>
                        <?php foreach ($pagesStats as $stat): ?>
                            <div class="stat-item">
                                <span><?php echo ucfirst($stat['_id']); ?>:</span>
                                <span><?php echo count($stat['languages']); ?> языков</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Редактор -->
                <div class="editor-container">
                    <h3>Редактирование: <?php echo ucfirst($currentPage); ?></h3>
                    
                    <!-- Вкладки языков -->
                    <div class="language-tabs">
                        <?php foreach ($availableLanguages as $lang): ?>
                            <div class="language-tab <?php echo $lang === $currentLanguage ? 'active' : ''; ?>" 
                                 data-lang="<?php echo $lang; ?>">
                                <?php echo strtoupper($lang); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
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
                            <select id="content_status" name="status">
                                <option value="draft">Черновик</option>
                                <option value="published">Опубликовано</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- WYSIWYG редактор -->
                    <div class="editor-wrapper">
                        <textarea id="page_content" name="content"><?php echo htmlspecialchars($currentContent['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Действия -->
                    <div class="editor-actions">
                        <button type="button" class="btn btn-primary" onclick="saveContent()">
                            💾 Сохранить
                        </button>
                        <button type="button" class="btn btn-success" onclick="publishContent()">
                            🚀 Опубликовать
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="previewContent()">
                            👁️ Предпросмотр
                        </button>
                    </div>
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
        
        // Переключение языков
        document.querySelectorAll('.language-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const lang = this.dataset.lang;
                if (lang !== currentLanguage) {
                    window.location.href = `?page=${currentPage}&lang=${lang}`;
                }
            });
        });
        
        // Сохранение контента
        function saveContent() {
            if (!editor) return;
            
            const content = editor.getContent();
            const meta = {
                title: document.getElementById('meta_title').value,
                description: document.getElementById('meta_description').value,
                keywords: document.getElementById('meta_keywords').value
            };
            const status = document.getElementById('content_status').value;
            
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('page', currentPage);
            formData.append('language', currentLanguage);
            formData.append('content', content);
            formData.append('meta_title', meta.title);
            formData.append('meta_description', meta.description);
            formData.append('meta_keywords', meta.keywords);
            formData.append('status', status);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Контент сохранен', 'success');
                } else {
                    showNotification('Ошибка: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка сохранения', 'error');
            });
        }
        
        // Публикация контента
        function publishContent() {
            const formData = new FormData();
            formData.append('action', 'publish');
            formData.append('page', currentPage);
            formData.append('language', currentLanguage);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Страница опубликована', 'success');
                    document.getElementById('content_status').value = 'published';
                } else {
                    showNotification('Ошибка: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Ошибка публикации', 'error');
            });
        }
        
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
        
        // Уведомления
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'error'}`;
            notification.textContent = message;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.padding = '1rem';
            notification.style.borderRadius = '5px';
            notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Автосохранение каждые 30 секунд
        setInterval(() => {
            if (editor && editor.getContent()) {
                saveContent();
            }
        }, 30000);
    </script>
</body>
</html>
