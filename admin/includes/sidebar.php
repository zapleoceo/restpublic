<?php
/**
 * Admin Sidebar Component
 * Modern sidebar with dynamic menu and active states
 */

// This file should only be included from layout.php
if (!defined('ADMIN_LAYOUT_LOADED')) {
    define('ADMIN_LAYOUT_LOADED', true);
    header('Location: /admin/');
    exit;
}

// Define menu structure
$menu_items = [
    [
        'id' => 'dashboard',
        'title' => 'Главная',
        'url' => '/admin/',
        'icon' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
        'icon_alt' => '🏠',
        'badge' => null
    ],
    [
        'id' => 'pages',
        'title' => 'Страницы',
        'url' => '/admin/pages/',
        'icon' => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8',
        'icon_alt' => '📄',
        'badge' => null
    ],
    [
        'id' => 'users',
        'title' => 'Пользователи',
        'url' => '/admin/users/',
        'icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8z M23 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
        'icon_alt' => '👥',
        'badge' => null
    ],
    [
        'id' => 'guests',
        'title' => 'Гости',
        'url' => '/admin/guests/',
        'icon' => 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z M12 14c-5.33 0-8 2.67-8 4v2h16v-2c0-1.33-2.67-4-8-4z',
        'icon_alt' => '👤',
        'badge' => null
    ],
    [
        'id' => 'database',
        'title' => 'База данных',
        'url' => '/admin/database/',
        'icon' => 'M4 7v10c0 2.21 3.03 4 8 4s8-1.79 8-4V7 M4 7c0 2.21 3.03 4 8 4s8-1.79 8-4 M4 7c0-2.21 3.03-4 8-4s8 1.79 8 4m0 5c0 2.21-3.03 4-8 4s-8-1.79-8-4',
        'icon_alt' => '🗄️',
        'badge' => null
    ],
    [
        'id' => 'events',
        'title' => 'События',
        'url' => '/admin/events/',
        'icon' => 'M8 3v3m8-3v3 M4 11h16 M5 7h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z',
        'icon_alt' => '📅',
        'badge' => null
    ],
    [
        'id' => 'sepay',
        'title' => 'SePay',
        'url' => '/admin/sepay/',
        'icon' => 'M3 10h18 M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3z',
        'icon_alt' => '💳',
        'badge' => null
    ],
    [
        'id' => 'settings',
        'title' => 'Настройки',
        'url' => '/admin/settings/',
        'icon' => 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm10-10V7a4 4 0 0 0-8 0v4h8z',
        'icon_alt' => '⚙️',
        'badge' => null
    ],
    [
        'id' => 'logs',
        'title' => 'Логи',
        'url' => '/admin/logs/',
        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z',
        'icon_alt' => '📊',
        'badge' => null
    ],
    [
        'id' => 'health',
        'title' => 'Здоровье',
        'url' => '/admin/health/',
        'icon' => 'M22 12h-4l-3 9L9 3l-3 9H2 M13 21h6a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-6 M8 21H2a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h6',
        'icon_alt' => '🏥',
        'badge' => null
    ]
];
?>

<!-- Sidebar -->
<nav class="admin-sidebar" id="admin-sidebar" role="navigation" aria-label="Main navigation">
    <div class="admin-sidebar-content">
        <!-- Brand -->
        <div class="admin-sidebar-brand">
            <a href="/admin/" class="brand-link">
                <img src="/images/logo_2_options.svg" alt="Veranda - restaurant & bar in Nha Trang" class="brand-logo" loading="lazy">
                <span class="brand-title">Veranda</span>
                <span class="brand-subtitle">Admin Panel</span>
            </a>
        </div>

        <!-- Navigation Menu -->
        <ul class="admin-sidebar-menu" role="menubar">
            <?php foreach ($menu_items as $item): ?>
            <li class="sidebar-menu-item <?php echo ($current_section === $item['id']) ? 'active' : ''; ?>" role="none">
                <a href="<?php echo htmlspecialchars($item['url']); ?>"
                   class="menu-link <?php echo ($current_section === $item['id']) ? 'active' : ''; ?>"
                   role="menuitem"
                   aria-current="<?php echo ($current_section === $item['id']) ? 'page' : 'false'; ?>">
                    <div class="menu-icon">
                        <svg class="icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="<?php echo htmlspecialchars($item['icon']); ?>"></path>
                        </svg>
                        <span class="icon-fallback" aria-hidden="true"><?php echo htmlspecialchars($item['icon_alt']); ?></span>
                    </div>
                    <span class="menu-text"><?php echo htmlspecialchars($item['title']); ?></span>
                    <?php if (isset($item['badge']) && $item['badge'] !== null): ?>
                        <span class="menu-badge" aria-label="<?php echo htmlspecialchars($item['badge']['label'] ?? ''); ?>">
                            <?php echo htmlspecialchars($item['badge']['text']); ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Sidebar Footer -->
        <div class="admin-sidebar-footer">
            <div class="sidebar-info">
                <div class="info-item">
                    <span class="info-label">Версия:</span>
                    <span class="info-value">2.0.0</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Статус:</span>
                    <span class="info-value status-active">Активен</span>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="sidebar-actions">
                <a href="/admin/auth/logout.php" class="action-link logout-link" title="Выйти из системы">
                    <svg class="action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16,17 21,12 16,7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span class="action-text">Выйти</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Overlay -->
    <div class="admin-sidebar-overlay" id="sidebar-overlay"></div>
</nav>
