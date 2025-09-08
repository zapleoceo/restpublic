<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="admin-sidebar">
    <ul class="sidebar-menu">
        <li class="menu-item <?php echo ($current_page === 'index') ? 'active' : ''; ?>">
            <a href="../index.php">
                <span class="menu-icon">🏠</span>
                <span class="menu-text">Главная</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'texts') ? 'active' : ''; ?>">
            <a href="../texts/">
                <span class="menu-icon">📝</span>
                <span class="menu-text">Тексты</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'images') ? 'active' : ''; ?>">
            <a href="../images/">
                <span class="menu-icon">🖼️</span>
                <span class="menu-text">Изображения</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'database') ? 'active' : ''; ?>">
            <a href="../database/">
                <span class="menu-icon">🗄️</span>
                <span class="menu-text">База данных</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'sepay') ? 'active' : ''; ?>">
            <a href="../sepay/">
                <span class="menu-icon">💳</span>
                <span class="menu-text">Логи платежей</span>
            </a>
        </li>
        
        <li class="menu-item <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
            <a href="../settings/">
                <span class="menu-icon">⚙️</span>
                <span class="menu-text">Настройки</span>
            </a>
        </li>
    </ul>
</nav>
