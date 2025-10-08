<?php
/**
 * Admin Header Component
 * Modern header with responsive design and user info
 */

// This file should only be included from layout.php
if (!defined('ADMIN_LAYOUT_LOADED')) {
    define('ADMIN_LAYOUT_LOADED', true);
    header('Location: /admin/');
    exit;
}
?>

<header class="admin-header">
    <div class="admin-header-content">
        <!-- Left Section -->
        <div class="admin-header-left">
            <!-- Mobile menu toggle -->
            <button class="admin-header-toggle" id="sidebar-toggle" aria-label="Toggle navigation">
                <span class="toggle-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>

            <!-- Brand -->
            <div class="admin-header-brand">
                <a href="/admin/" class="brand-link">
                    <img src="/images/logo_2_options.svg" alt="Veranda - restaurant & bar in Nha Trang" class="brand-logo" loading="lazy">
                    <span class="brand-text">Админка</span>
                </a>
            </div>

            <!-- Breadcrumbs -->
            <nav class="admin-header-breadcrumbs" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="/admin/" class="breadcrumb-link">Главная</a>
                    </li>
                    <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
                        <?php foreach ($breadcrumb as $crumb): ?>
                            <li class="breadcrumb-item">
                                <?php if (isset($crumb['url'])): ?>
                                    <a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="breadcrumb-link">
                                        <?php echo htmlspecialchars($crumb['title']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="breadcrumb-current"><?php echo htmlspecialchars($crumb['title']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>

        <!-- Right Section -->
        <div class="admin-header-right">
            <!-- Search (if needed) -->
            <?php if (isset($show_search) && $show_search): ?>
            <div class="admin-header-search">
                <form class="search-form" method="GET" action="/admin/search/">
                    <input type="search" name="q" class="search-input" placeholder="Поиск..." aria-label="Search">
                    <button type="submit" class="search-button" aria-label="Search">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Notifications -->
            <div class="admin-header-notifications">
                <button class="notification-button" aria-label="Notifications" title="Уведомления">
                    <svg class="notification-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <?php if (isset($notification_count) && $notification_count > 0): ?>
                        <span class="notification-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- User Menu -->
            <div class="admin-header-user">
                <div class="user-dropdown">
                    <button class="user-button" id="user-menu-toggle" aria-expanded="false" aria-haspopup="true">
                        <div class="user-avatar">
                            <?php
                            $username = $_SESSION['admin_username'] ?? 'A';
                            $initial = strtoupper(substr($username, 0, 1));
                            echo htmlspecialchars($initial);
                            ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                            <span class="user-role">Администратор</span>
                        </div>
                        <svg class="user-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>

                    <div class="user-menu" id="user-menu" role="menu" aria-hidden="true">
                        <div class="user-menu-header">
                            <div class="user-avatar large">
                                <?php echo htmlspecialchars($initial); ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@veranda.my'); ?></div>
                            </div>
                        </div>

                        <div class="user-menu-divider"></div>

                        <a href="/admin/profile/" class="user-menu-item" role="menuitem">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Профиль
                        </a>

                        <a href="/admin/settings/" class="user-menu-item" role="menuitem">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            Настройки
                        </a>

                        <div class="user-menu-divider"></div>

                        <a href="/admin/auth/logout.php" class="user-menu-item logout-item" role="menuitem">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16,17 21,12 16,7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Выйти
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
