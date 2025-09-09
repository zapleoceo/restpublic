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
