import React from 'react';
import LanguageSwitcher from '../components/LanguageSwitcher';
import Logo from '../components/Logo';
import DynamicMenu from '../components/DynamicMenu';
import '../components/LanguageSwitcher.css';
import '../components/Logo.css';
import '../components/DynamicMenu.css';

export const HomePage = () => {
  console.log('HomePage component rendering...');
  
  return (
    <div id="page" className="s-pagewrap ss-home">
      {/* preloader */}
      <div id="preloader">
        <div id="loader" className="dots-fade">
          <div></div>
          <div></div>
          <div></div>
        </div>
      </div>

      {/* site header */}
      <header className="s-header">
        <div className="container s-header__content">
          <div className="s-header__block">
            <Logo />
            <a className="header-menu-toggle" href="#0"><span>Menu</span></a>
          </div>
          
          <nav className="header-nav">
            <ul className="header-nav__links">
              <li className="current"><a className="smoothscroll" href="#intro">Intro</a></li>
              <li><a className="smoothscroll" href="#about">About</a></li>
              <li><a className="smoothscroll" href="#menu">Menu</a></li>
              <li><a className="smoothscroll" href="#gallery">Gallery</a></li>
            </ul>
            
            <LanguageSwitcher />
            
            <div className="header-contact">
              <a href="tel:+84349338758" className="header-contact__num btn">
                <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" strokeWidth="1.5" width="24" height="24" color="#000000">
                  <defs><style>{`.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}`}</style></defs>
                  <path className="cls-6376396cc3a86d32eae6f0dc-1" d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"></path>
                </svg>
                +84349338758
              </a>
            </div>
          </nav>
        </div>
      </header>

      {/* intro */}
      <section id="intro" className="container s-intro target-section">
        <div className="grid-block s-intro__content">
          <div className="intro-header">
            <div className="intro-header__overline">Welcome to</div>
            <h1 className="intro-header__big-type">
              North Republic
            </h1>
          </div>

          <figure className="intro-pic-primary">
            <img src="/images/intro-pic-primary.jpg" 
                 srcSet="/images/intro-pic-primary.jpg 1x, /images/intro-pic-primary@2x.jpg 2x" alt="Grilled salmon steak with asparagus and microgreens" />
          </figure>
              
          <div className="intro-block-content">
            <figure className="intro-block-content__pic">
              <img src="/images/intro-pic-secondary.jpg" 
                   srcSet="/images/intro-pic-secondary.jpg 1x, /images/intro-pic-secondary@2x.jpg 2x" alt="" />
            </figure>

            <div className="intro-block-content__text-wrap">
              <p className="intro-block-content__text">
                Savor moments of bliss with every sip, as our expertly 
                crafted coffees and delectable pastries embrace your senses.
              </p>
              
              <ul className="intro-block-content__social">
                <li><a href="https://facebook.com/vngamezone" target="_blank" rel="noopener noreferrer">FB</a></li>
                <li><a href="https://www.instagram.com/gamezone.vn/" target="_blank" rel="noopener noreferrer">IG</a></li>
                <li><a href="https://www.tiktok.com/@gamezone.vn" target="_blank" rel="noopener noreferrer">TT</a></li>
                <li><a href="https://t.me/gamezone_vietnam" target="_blank" rel="noopener noreferrer">TG</a></li>
              </ul>
            </div>
          </div>

          <div className="intro-scroll">
            <a className="smoothscroll" href="#about">
              <span className="intro-scroll__circle-text"></span>
              <span className="intro-scroll__text u-screen-reader-text">Scroll Down</span>
              <div className="intro-scroll__icon">
                <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path d="m5.214 14.522s4.505 4.502 6.259 6.255c.146.147.338.22.53.22s.384-.073.53-.22c1.754-1.752 6.249-6.244 6.249-6.244.144-.144.216-.334.217-.523 0-.193-.074-.386-.221-.534-.293-.293-.766-.294-1.057-.004l-4.968 4.968v-14.692c0-.414-.336-.75-.75-.75s-.75.336-.75.75v14.692l-4.979-4.978c-.289-.289-.761-.287-1.054.006-.148.148-.222.341-.221.534 0 .189.071.377.215.52z" fillRule="nonzero"/>
                </svg>
              </div>
            </a>
          </div>
        </div>
      </section>

      {/* about */}
      <section id="about" className="container s-about target-section">
        <div className="grid-block s-about__content">
          <div className="s-about__text">
            <h3 className="s-about__title">About North Republic</h3>
            <p className="s-about__text-content">
              Experience the perfect blend of modern comfort and traditional charm at North Republic. 
              Our carefully curated menu and welcoming atmosphere create an unforgettable dining experience.
            </p>
          </div>

          <figure className="s-about__pic">
            <img src="/images/about-pic-primary.jpg" 
                 srcSet="/images/about-pic-primary.jpg 1x, /images/about-pic-primary@2x.jpg 2x" alt="About North Republic" />
          </figure>
        </div>
      </section>

      {/* menu */}
      <section id="menu" className="container s-menu target-section">
        <div className="grid-block s-menu__content">
          <DynamicMenu />
        </div>
      </section>

      {/* gallery */}
      <section id="gallery" className="container s-gallery target-section">
        <div className="grid-block s-gallery__content">
          <h3 className="s-gallery__title">Gallery</h3>
          <div className="s-gallery__grid">
            <div className="s-gallery__item">
              <img src="/images/gallery/gallery-01.jpg" alt="Gallery Image 1" />
            </div>
            <div className="s-gallery__item">
              <img src="/images/gallery/gallery-02.jpg" alt="Gallery Image 2" />
            </div>
            <div className="s-gallery__item">
              <img src="/images/gallery/gallery-03.jpg" alt="Gallery Image 3" />
            </div>
            <div className="s-gallery__item">
              <img src="/images/gallery/gallery-04.jpg" alt="Gallery Image 4" />
            </div>
          </div>
        </div>
      </section>

      {/* footer */}
      <footer className="s-footer">
        <div className="container s-footer__content">
          <div className="grid-block s-footer__main">
            <div className="column xl-3 lg-12 footer-block s-footer__main-start">
              <ul className="s-footer__social social-list">
                <li><a href="https://facebook.com/vngamezone" target="_blank" rel="noopener noreferrer">Facebook</a></li>
                <li><a href="https://www.instagram.com/gamezone.vn/" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                <li><a href="https://www.tiktok.com/@gamezone.vn" target="_blank" rel="noopener noreferrer">TikTok</a></li>
                <li><a href="https://t.me/gamezone_vietnam" target="_blank" rel="noopener noreferrer">Telegram</a></li>
              </ul>
            </div>

            <div className="column xl-6 lg-12 footer-block s-footer__main-middle">
              <div className="s-footer__contact">
                <h4>Contact Us</h4>
                <p>Phone: +84349338758</p>
                <p>Email: info@northrepublic.me</p>
              </div>
            </div>

            <div className="column xl-3 lg-12 footer-block s-footer__main-end">
              <div className="s-footer__copyright">
                <p>&copy; 2024 North Republic. All rights reserved.</p>
              </div>
            </div>
          </div>
        </div>
      </footer>

      {/* back to top */}
      <div className="ss-go-top">
        <a className="smoothscroll" href="#top">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <path d="m18 15-6-6-6 6"/>
          </svg>
        </a>
      </div>
    </div>
  );
};
