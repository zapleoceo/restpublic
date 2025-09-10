<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$current_section = '';

// Определяем текущий раздел
if ($current_page === 'index' || $current_dir === 'dashboard') {
    $current_section = 'dashboard';
} elseif ($current_dir === 'pages') {
    $current_section = 'pages';
} elseif ($current_dir === 'users') {
    $current_section = 'users';
} elseif ($current_dir === 'database') {
    $current_section = 'database';
}
?>
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
    </ul>
</nav>
