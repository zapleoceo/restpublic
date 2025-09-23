<?php
/**
 * Управление страницами сайта с WYSIWYG редактором
 * Использует PageContentService для работы с MongoDB
 */

// Загружаем переменные окружения
require_once __DIR__ . '/../../vendor/autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

// Настройки страницы для layout
$page_title = 'Управление страницами - North Republic';
$page_header = 'Управление страницами';
$page_description = 'Редактирование контента страниц сайта с WYSIWYG редактором';

// Breadcrumbs для навигации
$breadcrumb = [
    ['title' => 'Управление страницами']
];

$error = '';
$success = '';

// Определяем текущую страницу и язык для редактирования
$currentPage = $_GET['page'] ?? 'index';
$currentLanguage = $_GET['lang'] ?? 'ru';
$availableLanguages = ['ru', 'en', 'vi'];

// Инициализируем PageContentService
require_once __DIR__ . '/../../classes/PageContentService.php';
$pageContentService = new PageContentService();

// Получаем контент текущей страницы
$pageContent = $pageContentService->getPageContent($currentPage, $currentLanguage);
$currentContent = [
    'content' => $pageContent['content'] ?? '',
    'meta' => $pageContent['meta'] ?? [
        'title' => '',
        'description' => '',
        'keywords' => ''
    ],
    'status' => 'published'
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
        
        // Сохраняем через PageContentService
        $updatedBy = $_SESSION['admin_username'] ?? 'admin';
        
        if ($pageContentService->savePageContent($currentPage, $currentLanguage, $content, $meta, $status, $updatedBy)) {
            $success = 'Страница успешно сохранена!';
            
            // Обновляем текущий контент
            $pageContent = $pageContentService->getPageContent($currentPage, $currentLanguage);
            $currentContent = [
                'content' => $pageContent['content'] ?? '',
                'meta' => $pageContent['meta'] ?? $meta,
                'status' => $status
            ];
        } else {
            $error = 'Ошибка при сохранении страницы.';
        }
    }
}

// Получаем список всех страниц из PageContentService
$availablePages = $pageContentService->getAllPages();

// Генерируем контент
ob_start();
?>

<!-- Simple Editor (replacing TinyMCE) -->
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
    
    <style>
        .page-editor {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .editor-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .editor-section h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
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
        
        .page-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .page-selector select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .meta-fields {
            display: grid;
            gap: 1rem;
        }
        
        .editor-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .page-editor {
                grid-template-columns: 1fr;
            }
            
            .page-selector {
                flex-direction: column;
            }
        }
    </style>
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
            
            <!-- Селекторы страницы и языка -->
            <div class="page-selector">
                <div class="form-group">
                    <label for="page-select">Страница:</label>
                    <select id="page-select" onchange="changePage()">
                        <?php foreach ($availablePages as $page): ?>
                            <option value="<?php echo $page; ?>" <?php echo $currentPage === $page ? 'selected' : ''; ?>>
                                <?php echo ucfirst($page); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="lang-select">Язык:</label>
                    <select id="lang-select" onchange="changeLanguage()">
                        <?php foreach ($availableLanguages as $lang): ?>
                            <option value="<?php echo $lang; ?>" <?php echo $currentLanguage === $lang ? 'selected' : ''; ?>>
                                <?php echo strtoupper($lang); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Статус:</label>
                    <span class="status-badge status-<?php echo $currentContent['status']; ?>">
                        <?php echo $currentContent['status'] === 'published' ? 'Опубликовано' : 'Черновик'; ?>
                    </span>
                </div>
            </div>
            
            <!-- Редактор -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="save">
                
                <div class="page-editor">
                    <!-- Основной контент -->
                    <div class="editor-section">
                        <h3>📝 Основной контент</h3>
                        
                        <div class="form-group">
                            <label for="content">HTML контент страницы:</label>
                            <textarea id="content-hidden" name="content" rows="20" style="display: none;"><?php echo htmlspecialchars($currentContent['content']); ?></textarea>
                            <div id="content" style="height: 500px; border: 1px solid #ccc; border-radius: 4px;"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Статус:</label>
                            <select name="status" id="status">
                                <option value="draft" <?php echo $currentContent['status'] === 'draft' ? 'selected' : ''; ?>>Черновик</option>
                                <option value="published" <?php echo $currentContent['status'] === 'published' ? 'selected' : ''; ?>>Опубликовано</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Мета данные -->
                    <div class="editor-section">
                        <h3>🔍 SEO настройки</h3>
                        
                        <div class="meta-fields">
                            <div class="form-group">
                                <label for="meta_title">Заголовок страницы (Title):</label>
                                <input type="text" id="meta_title" name="meta_title" 
                                       value="<?php echo htmlspecialchars($currentContent['meta']['title']); ?>"
                                       placeholder="Заголовок для поисковых систем">
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_description">Описание (Description):</label>
                                <textarea id="meta_description" name="meta_description" rows="3"
                                          placeholder="Краткое описание страницы для поисковых систем"><?php echo htmlspecialchars($currentContent['meta']['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="meta_keywords">Ключевые слова (Keywords):</label>
                                <input type="text" id="meta_keywords" name="meta_keywords" 
                                       value="<?php echo htmlspecialchars($currentContent['meta']['keywords']); ?>"
                                       placeholder="Ключевые слова через запятую">
                            </div>
                        </div>
                        
                        <?php if (isset($currentContent['updated_at'])): ?>
                            <div class="form-group">
                                <label>Информация о редактировании:</label>
                                <p style="font-size: 12px; color: #666; margin: 0;">
                                    Обновлено: <?php echo htmlspecialchars($currentContent['updated_at']); ?><br>
                                    Автор: <?php echo htmlspecialchars($currentContent['updated_by'] ?? 'Неизвестно'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Действия -->
                <div class="editor-actions">
                    <button type="submit" class="btn btn-primary">💾 Сохранить страницу</button>
                    <a href="?page=<?php echo $currentPage; ?>&lang=<?php echo $currentLanguage; ?>" class="btn btn-secondary">🔄 Обновить</a>
                    <a href="/admin/" class="btn btn-secondary">← Назад в админку</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // Инициализация Quill Editor
        let quill;
        
        document.addEventListener('DOMContentLoaded', function() {
            quill = new Quill('#content', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['link', 'image'],
                        ['clean']
                    ]
                },
                placeholder: 'Введите содержимое страницы...'
            });
            
            // Загружаем существующий контент если есть
            const existingContent = document.getElementById('content-hidden').value;
            if (existingContent) {
                quill.root.innerHTML = existingContent;
            }
        });
        
        // Функции для смены страницы и языка
        function changePage() {
            const page = document.getElementById('page-select').value;
            const lang = document.getElementById('lang-select').value;
            window.location.href = `?page=${page}&lang=${lang}`;
        }
        
        function changeLanguage() {
            const page = document.getElementById('page-select').value;
            const lang = document.getElementById('lang-select').value;
            window.location.href = `?page=${page}&lang=${lang}`;
        }
        
        // Функция для получения HTML контента из Quill
        function getQuillContent() {
            if (quill) {
                return quill.root.innerHTML;
            }
            return document.getElementById('content-hidden').value;
        }
        
        // Автосохранение каждые 30 секунд
        setInterval(function() {
            if (quill && quill.getLength() > 1) {
                console.log('Автосохранение...');
                // Здесь можно добавить AJAX сохранение
            }
        }, 30000);
        
        // Обработчик отправки формы
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Обновляем textarea с контентом из Quill перед отправкой
                    const contentTextarea = document.getElementById('content-hidden');
                    if (quill && contentTextarea) {
                        contentTextarea.value = quill.root.innerHTML;
                    }
                });
            }
        });
    </script>

<?php
$content = ob_get_clean();

// Подключаем layout
require_once __DIR__ . '/../includes/layout.php';
?>