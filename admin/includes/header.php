<?php
// session_start() уже вызывается в auth-check.php
require_once 'auth-check.php';
?>
<header class="admin-header">
    <div class="header-content">
        <div class="header-left">
            <a href="../index.php" class="logo">
                <img src="../images/logo.png" alt="North Republic" style="height: 40px;">
            </a>
            <h1>Админка</h1>
        </div>
        
        <div class="header-right">
            <div class="user-info">
                <span class="username"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="auth/logout.php" class="logout-btn">Выйти</a>
            </div>
        </div>
    </div>
</header>
