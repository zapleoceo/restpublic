<?php
// Header component for North Republic website
// Usage: include 'components/header.php';

// Initialize translation service if not already initialized
if (!isset($translationService)) {
    require_once __DIR__ . '/../classes/TranslationService.php';
    $translationService = new TranslationService();
}
?>
<!-- # site header 
================================================== -->
<header class="s-header">
    <div class="container s-header__content">
        <div class="s-header__block">
            <div class="header-logo">
                <a class="logo" href="/">
                    <img src="images/logo.png" alt="North Republic">
                </a>
            </div>
            <a class="header-menu-toggle" href="#0"><span>Menu</span></a>
        </div> <!-- end s-header__block -->
    
        <nav class="header-nav">    
            <ul class="header-nav__links">
                <li class="current"><a class="smoothscroll" href="#intro"><?php echo $translationService->get('nav.home', 'Главная'); ?></a></li>
                <li><a class="smoothscroll" href="#about"><?php echo $translationService->get('nav.about', 'О нас'); ?></a></li>
                <li><a href="/menu.php"><?php echo $translationService->get('nav.menu', 'Меню'); ?></a></li>
                <li><a class="smoothscroll" href="#gallery"><?php echo $translationService->get('nav.gallery', 'Галерея'); ?></a></li>
            </ul> <!-- end header-nav__links -->  
            
            <div class="header-actions">
                <!-- Language Switcher -->
                <?php include 'components/language-switcher.php'; ?>
                
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
