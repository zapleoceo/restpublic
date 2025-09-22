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
                <li><a class="smoothscroll" href="#events"><?php echo $translationService->get('nav.events', 'События'); ?></a></li>
                <li><a class="smoothscroll" href="#gallery"><?php echo $translationService->get('nav.gallery', 'Галерея'); ?></a></li>
            </ul> <!-- end header-nav__links -->  
            
            <!-- Language Switcher -->
            <div class="header-language">
                <?php include 'components/language-switcher.php'; ?>
            </div>

            <!-- Контактный телефон -->
            <div class="header-contact">
                <a href="tel:+84349338758" class="header-contact__num btn">
                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" width="24" height="24" color="#000000"><defs><style>.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}</style></defs><path class="cls-6376396cc3a86d32eae6f0dc-1" d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"></path></svg>
                    +84 349 338 758
                </a>
            </div> <!-- end header-contact -->

            <!-- Social links for mobile menu -->
            <div class="header-nav__social">
                <a href="https://facebook.com/vngamezone" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M20,3H4C3.447,3,3,3.448,3,4v16c0,0.552,0.447,1,1,1h8.615v-6.96h-2.338v-2.725h2.338v-2c0-2.325,1.42-3.592,3.5-3.592 c0.699-0.002,1.399,0.034,2.095,0.107v2.42h-1.435c-1.128,0-1.348,0.538-1.348,1.325v1.735h2.697l-0.35,2.725h-2.348V21H20 c0.553,0,1-0.448,1-1V4C21,3.448,20.553,3,20,3z"></path></svg>
                    <span class="u-screen-reader-text">FB</span>
                </a>
                <a href="https://t.me/gamezone_vietnam" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="m20.665 3.717-17.73 6.837c-1.21.486-1.203 1.161-.222 1.462l4.552 1.42 10.532-6.645c.498-.303.953-.14.579.192l-8.533 7.701h-.002l.002.001-.314 4.692c.46 0 .663-.211.921-.46l2.211-2.15 4.599 3.397c.848.467 1.457.227 1.668-.785l3.019-14.228c.309-1.239-.473-1.8-1.282-1.434z"></path></svg>
                    <span class="u-screen-reader-text">TG</span>
                </a>
                <a href="https://www.instagram.com/northrepublic.me" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M11.999,7.377c-2.554,0-4.623,2.07-4.623,4.623c0,2.554,2.069,4.624,4.623,4.624c2.552,0,4.623-2.07,4.623-4.624 C16.622,9.447,14.551,7.377,11.999,7.377L11.999,7.377z M11.999,15.004c-1.659,0-3.004-1.345-3.004-3.003 c0-1.659,1.345-3.003,3.004-3.003s3.002,1.344,3.002,3.003C15.001,13.659,13.658,15.004,11.999,15.004L11.999,15.004z"></path><circle cx="16.806" cy="7.207" r="1.078"></circle><path d="M20.533,6.111c-0.469-1.209-1.424-2.165-2.633-2.632c-0.699-0.263-1.438-0.404-2.186-0.42 c-0.963-0.042-1.268-0.054-3.71-0.054s-2.755,0-3.71,0.054C7.548,3.074,6.809,3.215,6.11,3.479C4.9,3.946,3.945,4.902,3.477,6.111 c-0.263,0.7-0.404,1.438-0.419,2.186c-0.043,0.962-0.056,1.267-0.056,3.71c0,2.442,0,2.753,0.056,3.71 c0.015,0.748,0.156,1.486,0.419,2.187c0.469,1.208,1.424,2.164,2.634,2.632c0.696,0.272,1.435,0.426,2.185,0.45 c0.963,0.042,1.268,0.055,3.71,0.055s2.755,0,3.71-0.055c0.747-0.015,1.486-0.157,2.186-0.419c1.209-0.469,2.164-1.424,2.633-2.633 c0.263-0.7,0.404-1.438,0.419-2.186c0.043-0.962,0.056-1.267,0.056-3.71s0-2.753-0.056-3.71C20.941,7.57,20.801,6.819,20.533,6.111z M19.315,15.643c-0.007,0.576-0.111,1.147-0.311,1.688c-0.305,0.787-0.926,1.409-1.712,1.711c-0.535,0.199-1.099,0.303-1.67,0.311 c-0.95,0.044-1.218,0.055-3.654,0.055c-2.438,0-2.687,0-3.655-0.055c-0.569-0.007-1.135-0.112-1.669-0.311 c-0.789-0.301-1.414-0.923-1.719-1.711c-0.196-0.534-0.302-1.099-0.311-1.669c-0.043-0.95-0.053-1.218-0.053-3.654 c0-2.437,0-2.686,0.053-3.655c0.007-0.576,0.111-1.146,0.311-1.687c0.305-0.789,0.93-1.41,1.719-1.712 c0.534-0.198,1.1-0.303,1.669-0.311c0.951-0.043,1.218-0.055,3.655-0.055c2.437,0,2.687,0,3.654,0.055 c0.571,0.007,1.135,0.112,1.67,0.311c0.786,0.303,1.407,0.925,1.712,1.712c0.196,0.534,0.302,1.099,0.311,1.669 c0.043,0.951,0.054,1.218,0.054,3.655c0,2.436,0,2.698-0.043,3.654H19.315z"></path></svg>
                    <span class="u-screen-reader-text">IG</span>
                </a>
                <a href="https://www.tiktok.com/@gamezone.vn" target="_blank" rel="noopener noreferrer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill:rgba(0, 0, 0, 1);transform:;-ms-filter:"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"></path></svg>
                    <span class="u-screen-reader-text">TT</span>
                </a>
            </div> <!-- end header-nav__social -->
        </nav> <!-- end header-nav -->         
    </div> <!-- end s-header__content -->
</header> <!-- end s-header -->
