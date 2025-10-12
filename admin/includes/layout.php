<?php
/**
 * Modern Admin Layout
 * Complete UI refactoring with includes structure
 */

// Define layout loaded constant
define('ADMIN_LAYOUT_LOADED', true);

// Session and authentication check
// require_once __DIR__ . '/auth-check.php'; // Временно отключено для тестирования

// Define page variables with defaults
$page_title = $page_title ?? 'Админка - Veranda';
$page_header = $page_header ?? 'Панель управления';
$page_description = $page_description ?? 'Добро пожаловать в панель управления Veranda';

// Determine current section for active menu item
$current_section = getCurrentSection();

// Helper function to get current section
function getCurrentSection() {
    $script_path = $_SERVER['SCRIPT_NAME'];
    $path_parts = explode('/', trim($script_path, '/'));

    // Remove 'admin' from path parts if present
    if ($path_parts[0] === 'admin') {
        array_shift($path_parts);
    }

    $section = $path_parts[0] ?? 'dashboard';

    // Map specific pages to sections
    $section_map = [
        '' => 'dashboard',
        'index.php' => 'dashboard',
        'pages' => 'pages',
        'users' => 'users',
        'guests' => 'guests',
        'database' => 'database',
        'events' => 'events',
        'sepay' => 'sepay',
        'storage' => 'storage',
        'settings' => 'settings',
        'logs' => 'logs',
        'health' => 'health'
    ];

    return $section_map[$section] ?? 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="ru" class="admin-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#667eea">

    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Critical CSS -->
    <link rel="stylesheet" href="/admin/assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">

    <!-- Preload critical resources -->
    <link rel="preload" href="/admin/assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>" as="style">
    <link rel="preload" href="/images/logo_2_options.svg" as="image">
    <link rel="preload" href="/admin/assets/js/admin.js" as="script">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/template/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/template/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/template/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/template/apple-touch-icon.png">

    <!-- Additional CSS files -->
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css_file); ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Page-specific meta tags -->
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="author" content="Veranda Team">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <!-- Sidebar (included) -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="admin-main">
            <!-- Header (included) -->
            <?php require_once __DIR__ . '/header.php'; ?>

            <!-- Content Area -->
            <main class="admin-content">
                <!-- Page Header -->
                <?php if (isset($page_header) || isset($page_description)): ?>
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-header-text">
                            <h1 class="page-title"><?php echo htmlspecialchars($page_header); ?></h1>
                            <?php if (isset($page_description)): ?>
                                <p class="page-description"><?php echo htmlspecialchars($page_description); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($page_actions) && is_array($page_actions)): ?>
                        <div class="page-actions">
                            <?php foreach ($page_actions as $action): ?>
                                <?php echo $action; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Page Content -->
                <div class="page-content">
                    <?php echo $content ?? ''; ?>
                </div>
            </main>

            <!-- Footer -->
            <footer class="admin-footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <p>&copy; <?php echo date('Y'); ?> Veranda Admin Panel</p>
                        <p>Version 2.0 - Complete UI Refactoring</p>
                    </div>
                    <div class="footer-right">
                        <div class="footer-user">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                            <a href="/admin/auth/logout.php" class="footer-logout">Выйти</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/admin/assets/js/admin.js" defer></script>

    <!-- Additional JS files -->
    <?php if (isset($additional_js) && is_array($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo htmlspecialchars($js_file); ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Inline scripts for current page -->
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>
