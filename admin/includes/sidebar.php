<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="admin-sidebar">
    <ul class="sidebar-menu">
        <li class="menu-item <?php echo ($current_page === 'index') ? 'active' : ''; ?>">
            <a href="/admin/">
                <span class="menu-icon">🏠</span>
                <span class="menu-text">Главная</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'pages') ? 'active' : ''; ?>">
            <a href="/admin/pages/">
                <span class="menu-icon">📄</span>
                <span class="menu-text">Страницы</span>
            </a>
        </li>
        
        
        <li class="menu-item <?php echo ($current_dir === 'images') ? 'active' : ''; ?>">
            <a href="/admin/images/">
                <span class="menu-icon">🖼️</span>
                <span class="menu-text">Изображения</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'database') ? 'active' : ''; ?>">
            <a href="/admin/database/">
                <span class="menu-icon">🗄️</span>
                <span class="menu-text">База данных</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'sepay') ? 'active' : ''; ?>">
            <a href="/admin/sepay/">
                <span class="menu-icon">💳</span>
                <span class="menu-text">Логи платежей</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'logs') ? 'active' : ''; ?>">
            <a href="/admin/logs/">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Логи админов</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'logs') ? 'active' : ''; ?>">
            <a href="/admin/logs/">
                <span class="menu-icon">📊</span>
                <span class="menu-text">Логи админов</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'users') ? 'active' : ''; ?>">
            <a href="/admin/users/">
                <span class="menu-icon">👥</span>
                <span class="menu-text">Пользователи</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
            <a href="/admin/settings/">
                <span class="menu-icon">⚙️</span>
                <span class="menu-text">Настройки</span>
            </a>
        </li>
    </ul>
</nav>
