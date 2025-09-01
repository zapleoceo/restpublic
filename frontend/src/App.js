import React, { useEffect } from 'react';

function App() {
  useEffect(() => {
    // Загружаем скрипты из шаблона
    const script1 = document.createElement('script');
    script1.src = '/js/plugins.js';
    document.body.appendChild(script1);

    const script2 = document.createElement('script');
    script2.src = '/js/main.js';
    document.body.appendChild(script2);

    return () => {
      if (document.body.contains(script1)) document.body.removeChild(script1);
      if (document.body.contains(script2)) document.body.removeChild(script2);
    };
  }, []);

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
            <div className="header-logo">
              <a className="logo" href="index.html">
                <img src="/images/logo.svg" alt="Homepage" />
              </a>
            </div>
            <a className="header-menu-toggle" href="#0"><span>Menu</span></a>
          </div>
          
          <nav className="header-nav">
            <ul className="header-nav__links">
              <li className="current"><a className="smoothscroll" href="#intro">Intro</a></li>
              <li><a className="smoothscroll" href="#about">About</a></li>
              <li><a className="smoothscroll" href="#menu">Menu</a></li>
              <li><a className="smoothscroll" href="#gallery">Gallery</a></li>
            </ul>
            
            <div className="header-contact">
              <a href="tel:+" className="header-contact__num btn">
                <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" strokeWidth="1.5" width="24" height="24" color="#000000">
                  <defs><style>{`.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}`}</style></defs>
                  <path className="cls-6376396cc3a86d32eae6f0dc-1" d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"></path>
                </svg>
                +7 (999) 123-45-67
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
                 srcSet="/images/intro-pic-primary.jpg 1x, /images/intro-pic-primary@2x.jpg 2x" alt="" />
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
                <li><a href="#0">FB</a></li>
                <li><a href="#0">IG</a></li>
                <li><a href="#0">PI</a></li>
                <li><a href="#0">X</a></li>
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
        <div className="row s-about__content">
          <div className="column xl-4 lg-5 md-12 s-about__content-start">
            <div className="section-header" data-num="01">
              <h2 className="text-display-title">Our Story</h2>
            </div>

            <figure className="about-pic-primary">
              <img src="/images/about-pic-primary.jpg" 
                   srcSet="/images/about-pic-primary.jpg 1x, /images/about-pic-primary@2x.jpg 2x" alt="" />
            </figure>
          </div>

          <div className="column xl-6 lg-6 md-12 s-about__content-end">
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Quasi earum, ut consequuntur pariatur fugiat aliquam voluptatem officia blanditiis ipsa laboriosam ad velit voluptate nisi saepe quisquam minima culpa eaque amet.
            </p>
            <p>
              Lorem, ipsum dolor sit amet consectetur adipisicing elit. Dolorem vero sit neque sequi eius illum at porro aperiam. Iusto reiciendis reprehenderit ipsa molestias sit eaque velit, veritatis quod, cum exercitationem doloribus eos cumque, ipsam voluptate! Nam doloribus quibusdam eos ipsum optio animi ea ex. Atque neque nesciunt numquam fugiat inventore!
            </p>
          </div>
        </div>
      </section>

      {/* menu */}
      <section id="menu" className="container s-menu target-section">
        <div className="row s-menu__content">
          <div className="column xl-4 lg-5 md-12 s-menu__content-start">
            <div className="section-header" data-num="02">
              <h2 className="text-display-title">Our Menu</h2>
            </div>

            <nav className="tab-nav">
              <ul className="tab-nav__list">
                <li>
                  <a href="#tab-signature-blends">
                    <span>Signature Blends</span>
                    <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
                    </svg>
                  </a>
                </li>
                <li>
                  <a href="#tab-pastries">
                    <span>Freshly Baked Pastries</span>
                    <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
                    </svg>
                  </a>
                </li>
                <li>
                  <a href="#tab-gourmet-treats">
                    <span>Gourmet Treats</span>
                    <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
                    </svg>
                  </a>
                </li>
              </ul>
            </nav>
          </div>

          <div className="column xl-6 lg-6 md-12 s-menu__content-end">
            <div className="tab-content menu-block">
              <div id="tab-signature-blends" className="menu-block__group tab-content__item">
                <h6 className="menu-block__cat-name">Signature Blends</h6>
                <ul className="menu-list">
                  <li className="menu-list__item">
                    <div className="menu-list__item-desc">
                      <h4>Lounge Elegance Espresso</h4>
                      <p>Rich and full-bodied, our signature espresso blend with notes of dark chocolate and toasted nuts.</p>
                    </div>
                    <div className="menu-list__item-price">
                      <span>$</span>3.50
                    </div>
                  </li>
                  <li className="menu-list__item">
                    <div className="menu-list__item-desc">
                      <h4>Velvet Mocha Delight</h4>
                      <p>Silky mocha infused with a hint of vanilla, crowned with velvety whipped cream.</p>
                    </div>
                    <div className="menu-list__item-price">
                      <span>$</span>4.25
                    </div>
                  </li>
                </ul>
              </div>

              <div id="tab-pastries" className="menu-block__group tab-content__item">
                <h6 className="menu-block__cat-name">Freshly Baked Pastries</h6>
                <ul className="menu-list">
                  <li className="menu-list__item">
                    <div className="menu-list__item-desc">
                      <h4>Buttery Croissants</h4>
                      <p>Flaky and buttery croissants baked to perfection.</p>
                    </div>
                    <div className="menu-list__item-price">
                      <span>$</span>2.50
                    </div>
                  </li>
                  <li className="menu-list__item">
                    <div className="menu-list__item-desc">
                      <h4>Flaky Almond Danishes</h4>
                      <p>Delicate pastries filled with almond paste and sliced almonds.</p>
                    </div>
                    <div className="menu-list__item-price">
                      <span>$</span>3.00
                    </div>
                  </li>
                </ul>
              </div>

              <div id="tab-gourmet-treats" className="menu-block__group tab-content__item">
                <h6 className="menu-block__cat-name">Gourmet Treats</h6>
                <ul className="menu-list">
                  <li className="menu-list__item">
                    <div className="menu-list__item-desc">
                      <h4>Artisanal Dark Chocolate Truffles</h4>
                      <p>Luxurious dark chocolate truffles dusted with cocoa powder.</p>
                    </div>
                    <div className="menu-list__item-price">
                      <span>$</span>2.75
                    </div>
                  </li>
                  <li className="menu-list__item">
                    <div className="menu-list__item-desc">
                      <h4>Handcrafted Praline Bonbons</h4>
                      <p>Praline-filled bonbons topped with a caramelized nut.</p>
                    </div>
                    <div className="menu-list__item-price">
                      <span>$</span>3.00
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* gallery */}
      <section id="gallery" className="container s-gallery target-section">
        <div className="row s-gallery__header">
          <div className="column xl-12 section-header-wrap">
            <div className="section-header" data-num="03">
              <h2 className="text-display-title">Gallery</h2>
            </div>
          </div>
        </div>

        <div className="gallery-items grid-cols grid-cols--wrap">
          <div className="gallery-items__item grid-cols__column">
            <a href="/images/gallery/large/l-gallery-01.jpg" className="gallery-items__item-thumb glightbox">
              <img src="/images/gallery/gallery-01.jpg" 
                   srcSet="/images/gallery/gallery-01.jpg 1x, /images/gallery/gallery-01@2x.jpg 2x" alt="" />
            </a>
          </div>
          <div className="gallery-items__item grid-cols__column">
            <a href="/images/gallery/large/l-gallery-02.jpg" className="gallery-items__item-thumb glightbox">
              <img src="/images/gallery/gallery-02.jpg" 
                   srcSet="/images/gallery/gallery-02.jpg 1x, /images/gallery/gallery-02@2x.jpg 2x" alt="" />
            </a>
          </div>
          <div className="gallery-items__item grid-cols__column">
            <a href="/images/gallery/large/l-gallery-03.jpg" className="gallery-items__item-thumb glightbox">
              <img src="/images/gallery/gallery-03.jpg" 
                   srcSet="/images/gallery/gallery-03.jpg 1x, /images/gallery/gallery-03@2x.jpg 2x" alt="" />
            </a>
          </div>
          <div className="gallery-items__item grid-cols__column">
            <a href="/images/gallery/large/l-gallery-04.jpg" className="gallery-items__item-thumb glightbox">
              <img src="/images/gallery/gallery-04.jpg" 
                   srcSet="/images/gallery/gallery-04.jpg 1x, /images/gallery/gallery-04@2x.jpg 2x" alt="" />
            </a>
          </div>
        </div>
      </section>

      {/* testimonials */}
      <section id="testimonials" className="container s-testimonials">
        <div className="row s-testimonials__content">
          <div className="column xl-12">
            <h3 className="testimonials-title u-text-center">What Our Clients Say</h3>
            <div className="swiper-container testimonials-slider">
              <div className="swiper-wrapper">
                <div className="testimonials-slider__slide swiper-slide">
                  <div className="testimonials-slider__author">
                    <img src="/images/avatars/user-02.jpg" alt="Author image" className="testimonials-slider__avatar" />
                    <cite className="testimonials-slider__cite">
                      John Rockefeller
                      <span>Cleveland, Ohio</span>
                    </cite>
                  </div>
                  <p>
                    Molestiae incidunt consequatur quis ipsa autem nam sit enim magni. Voluptas tempore rem. 
                    Explicabo a quaerat sint autem dolore ducimus ut consequatur neque. Nisi dolores quaerat fuga rem nihil nostrum.
                    Laudantium quia consequatur molestias.
                  </p>
                </div>
                <div className="testimonials-slider__slide swiper-slide">
                  <div className="testimonials-slider__author">
                    <img src="/images/avatars/user-03.jpg" alt="Author image" className="testimonials-slider__avatar" />
                    <cite className="testimonials-slider__cite">
                      Andrew Carnegie
                      <span>Pittsburgh, Pennsylvania</span>
                    </cite>
                  </div>
                  <p>
                    Excepturi nam cupiditate culpa doloremque deleniti repellat. Veniam quos repellat voluptas animi adipisci.
                    Nisi eaque consequatur. Voluptatem dignissimos ut ducimus accusantium perspiciatis.
                    Quasi voluptas eius distinctio. Atque eos maxime.
                  </p>
                </div>
              </div>
              <div className="swiper-pagination"></div>
            </div>
          </div>
        </div>
      </section>

      {/* footer */}
      <footer id="footer" className="container s-footer">
        <div className="row s-footer__top row-x-center">
          <div className="column xl-6 lg-8 md-10 footer-block footer-newsletter">
            <h5>
              Subscribe to our mailing list for <br />
              updates, news, and exclusive offers.
            </h5>
            <div className="subscribe-form">
              <form id="mc-form" className="mc-form">
                <div className="mc-input-wrap">
                  <input type="email" name="EMAIL" id="mce-EMAIL" placeholder="Your Email Address" title="The domain portion of the email address is invalid (the portion after the @)." pattern="^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$" required />
                  <input type="submit" name="subscribe" value="Subscribe" className="btn btn--primary" />
                </div>
                <div className="mc-status"></div>
              </form>
            </div>
          </div>
        </div>

        <div className="row s-footer__main">
          <div className="column xl-3 lg-12 footer-block s-footer__main-start">
            <div className="s-footer__logo">
              <a className="logo" href="index.html">
                <img src="/images/logo.svg" alt="Homepage" />
              </a>
            </div>
            <ul className="s-footer__social social-list">
              <li><a href="#0">FB</a></li>
              <li><a href="#0">IG</a></li>
              <li><a href="#0">PI</a></li>
              <li><a href="#0">X</a></li>
            </ul>
          </div>

          <div className="column xl-9 lg-12 s-footer__main-end grid-cols grid-cols--wrap">
            <div className="grid-cols__column footer-block">
              <h6>Location</h6>
              <p>
                456 Elm Street, Los Angeles <br />
                CA 90001
              </p>
            </div>
            <div className="grid-cols__column footer-block">
              <h6>Contacts</h6>
              <ul className="link-list">
                <li><a href="mailto:#0">contact@northrepublic.com</a></li>
                                        <li><a href="tel:+79991234567">+7 (999) 123-45-67</a></li>
              </ul>
            </div>
            <div className="grid-cols__column footer-block">
              <h6>Opening Hours</h6>
              <ul className="opening-hours">
                <li><span className="opening-hours__days">Weekdays</span><span className="opening-hours__time">10:00am - 9:00pm</span></li>
                <li><span className="opening-hours__days">Weekends</span><span className="opening-hours__time">9:00am - 10:00pm</span></li>
              </ul>
            </div>
          </div>
        </div>

        <div className="row s-footer__bottom">
          <div className="column xl-6 lg-12">
            <p className="ss-copyright">
              <span>© North Republic 2025</span>
              <span>Design by <a href="https://styleshout.com/">StyleShout</a></span>
              Distributed by <a href="https://themewagon.com" target="_blank" rel="noopener noreferrer">ThemeWagon</a>
            </p>
          </div>
        </div>

        <div className="ss-go-top">
          <a className="smoothscroll" title="Back to Top" href="#top">
            <svg clipRule="evenodd" fillRule="evenodd" strokeLinejoin="round" strokeMiterlimit="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="m14.523 18.787s4.501-4.505 6.255-6.26c.146-.146.219-.338.219-.53s-.073-.383-.219-.53c-1.753-1.754-6.255-6.258-6.255-6.258-.144-.145-.334-.217-.524-.217-.193 0-.385.074-.532.221-.293.292-.295.766-.004 1.056l4.978 4.978h-14.692c-.414 0-.75.336-.75.75s.336.75.75.75h14.692l-4.979 4.979c-.289.289-.286.762.006 1.054.148.148.341.222.533.222.19 0 .378-.072.522-.215z" fillRule="nonzero"/>
            </svg>
          </a>
          <span>Back To Top</span>
        </div>
      </footer>
    </div>
  );
}

export default App;
