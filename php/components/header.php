<?php
// Header component for North Republic website
// Usage: include 'components/header.php';
?>
<!-- # site header 
================================================== -->
<header class="s-header">
    <div class="container s-header__content">
        <div class="s-header__block">
            <div class="header-logo">
                <a class="logo" href="/">
                    <img src="template/images/logo.svg" alt="North Republic">
                </a>
            </div>
            <a class="header-menu-toggle" href="#0"><span>Menu</span></a>
        </div> <!-- end s-header__block -->
    
        <nav class="header-nav">    
            <ul class="header-nav__links">
                <li class="current"><a class="smoothscroll" href="#intro">Главная</a></li>
                <li><a class="smoothscroll" href="#about">О нас</a></li>
                <li><a href="/menu.php">Меню</a></li>
                <li><a class="smoothscroll" href="#gallery">Галерея</a></li>
            </ul> <!-- end header-nav__links -->  
            
            <div class="header-actions">
                <!-- Авторизация через Telegram (пока заглушка) -->
                <div class="header-auth">
                    <button class="btn btn--outline" id="telegram-auth" style="display: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.568 8.16l-1.61 7.59c-.12.54-.44.68-.89.42l-2.46-1.81-1.19 1.15c-.13.13-.24.24-.49.24l.18-2.55 4.57-4.13c.2-.18-.04-.28-.31-.1l-5.64 3.55-2.43-.76c-.53-.16-.54-.53.11-.79l9.57-3.69c.44-.16.83.1.69.79z"/>
                        </svg>
                        Войти
                    </button>
                    <div class="user-info" id="user-info" style="display: none;">
                        <span class="user-name"></span>
                        <button class="btn btn--outline btn--small" id="logout">Выйти</button>
                    </div>
                </div>

                <!-- Корзина -->
                <div class="header-cart">
                    <button class="btn btn--primary" id="cart-toggle">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z"/>
                            <path d="M9 8V17H11V8H9ZM13 8V17H15V8H13Z"/>
                        </svg>
                        <span class="cart-count" id="cart-count">0</span>
                        Корзина
                    </button>
                </div>

                <!-- Контактный телефон -->
                <div class="header-contact">
                    <a href="tel:+84349338758" class="header-contact__num btn">
                        <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" width="24" height="24" color="#000000"><defs><style>.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}</style></defs><path class="cls-6376396cc3a86d32eae6f0dc-1" d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"></path></svg>
                        +84 349 338 758
                    </a>
                </div> <!-- end header-contact -->
            </div> <!-- end header-actions -->
        </nav> <!-- end header-nav -->         
    </div> <!-- end s-header__content -->
</header> <!-- end s-header -->
