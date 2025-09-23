<?php
// Единый layout для всех страниц админки
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверка авторизации
require_once __DIR__ . '/auth-check.php';

// Определяем текущий раздел для активного пункта меню
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$current_section = '';

// Определяем текущий раздел
if ($current_page === 'index' && $current_dir === 'admin') {
    // Главная страница админки
    $current_section = 'dashboard';
} elseif ($current_dir === 'pages') {
    $current_section = 'pages';
} elseif ($current_dir === 'users') {
    $current_section = 'users';
} elseif ($current_dir === 'database') {
    $current_section = 'database';
} elseif ($current_dir === 'events') {
    $current_section = 'events';
} elseif ($current_dir === 'sepay') {
    $current_section = 'sepay';
} elseif ($current_dir === 'settings') {
    $current_section = 'settings';
} elseif ($current_dir === 'logs') {
    $current_section = 'logs';
} elseif ($current_dir === 'health') {
    $current_section = 'health';
}

// Получаем заголовок страницы
$page_title = $page_title ?? 'Админка - North Republic';
$page_description = $page_description ?? '';

// Статистика для дашборда
$stats = [
    'admin_user' => $_SESSION['admin_username'] ?? 'Unknown'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <link rel="icon" type="image/png" href="../template/favicon-32x32.png">
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="header-content">
            <div class="header-left">
                <button class="mobile-menu-btn">☰</button>
                <a href="/admin/" class="logo">
                    <img src="/images/logo.png" alt="North Republic" style="height: 40px;">
                </a>
                <h1>Админка</h1>
            </div>
            
            <div class="header-right">
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                    <a href="/admin/auth/logout.php" class="logout-btn">Выйти</a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <ul class="sidebar-menu">
                <li class="menu-item <?php echo ($current_section === 'dashboard') ? 'active' : ''; ?>">
                    <a href="/admin/">
                        <span class="menu-icon">🏠</span>
                        <span class="menu-text">Главная</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'pages') ? 'active' : ''; ?>">
                    <a href="/admin/pages/">
                        <span class="menu-icon">📄</span>
                        <span class="menu-text">Страницы</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'users') ? 'active' : ''; ?>">
                    <a href="/admin/users/">
                        <span class="menu-icon">👥</span>
                        <span class="menu-text">Пользователи</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'database') ? 'active' : ''; ?>">
                    <a href="/admin/database/">
                        <span class="menu-icon">🗄️</span>
                        <span class="menu-text">База данных</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'events') ? 'active' : ''; ?>">
                    <a href="/admin/events/">
                        <span class="menu-icon">📅</span>
                        <span class="menu-text">События</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'sepay') ? 'active' : ''; ?>">
                    <a href="/admin/sepay/">
                        <span class="menu-icon">💳</span>
                        <span class="menu-text">SePay</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'settings') ? 'active' : ''; ?>">
                    <a href="/admin/settings/">
                        <span class="menu-icon">⚙️</span>
                        <span class="menu-text">Настройки</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'logs') ? 'active' : ''; ?>">
                    <a href="/admin/logs/">
                        <span class="menu-icon">📊</span>
                        <span class="menu-text">Логи</span>
                    </a>
                </li>
                
                <li class="menu-item <?php echo ($current_section === 'health') ? 'active' : ''; ?>">
                    <a href="/admin/health/">
                        <span class="menu-icon">🏥</span>
                        <span class="menu-text">Здоровье</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Оверлей для мобильного меню -->
        <div class="sidebar-overlay"></div>
        
        <!-- Main Content -->
        <main class="admin-main">
            <?php if (isset($page_header) && $page_header): ?>
                <div class="page-header">
                    <h1><?php echo htmlspecialchars($page_header); ?></h1>
                    <?php if (isset($page_description) && $page_description): ?>
                        <p><?php echo htmlspecialchars($page_description); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Content будет вставлен здесь -->
            <?php echo $content ?? ''; ?>
        </main>
    </div>
    
    <!-- Footer -->
    <footer class="admin-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> North Republic Admin Panel</p>
            <p>Developed by <a href="https://zapleo.com" target="_blank">zapleo.com</a></p>
            <div class="footer-links">
                <a href="/admin/auth/logout.php">Выйти</a>
                <span class="footer-separator">|</span>
                <span class="footer-user">Пользователь: <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/admin/assets/js/admin.js"></script>
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo htmlspecialchars($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
