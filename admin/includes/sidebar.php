<?php
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
} elseif ($current_dir === 'guests') {
    $current_section = 'guests';
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
        
        <li class="menu-item <?php echo ($current_section === 'guests') ? 'active' : ''; ?>">
            <a href="/admin/guests/">
                <span class="menu-icon">👤</span>
                <span class="menu-text">Гости</span>
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
