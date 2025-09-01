import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from '../../hooks/useTranslation';
import { LanguageSwitcher } from '../ui/LanguageSwitcher';
import { SECTIONS } from '../../constants/routes';

export const Header = () => {
  const { t } = useTranslation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isSticky, setIsSticky] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      const scrollY = window.scrollY;
      setIsSticky(scrollY > 100);
    };

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const smoothScroll = (sectionId) => {
    const element = document.querySelector(sectionId);
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
    setIsMenuOpen(false);
  };

  return (
    <header className={`s-header ${isSticky ? 'sticky' : ''}`}>
      <div className="container s-header__content">
        <div className="s-header__block">
          <div className="header-logo">
            <Link to="/" className="logo">
              <img src="/template/images/logo.svg" alt="North Republic" />
            </Link>
          </div>
          <button 
            className={`header-menu-toggle ${isMenuOpen ? 'is-clicked' : ''}`}
            onClick={() => setIsMenuOpen(!isMenuOpen)}
          >
            <span>Menu</span>
          </button>
        </div>

        <nav className={`header-nav ${isMenuOpen ? 'is-open' : ''}`}>
          <ul className="header-nav__links">
            <li>
              <button 
                className="smoothscroll" 
                onClick={() => smoothScroll(SECTIONS.intro)}
              >
                {t('nav.home')}
              </button>
            </li>
            <li>
              <button 
                className="smoothscroll" 
                onClick={() => smoothScroll(SECTIONS.about)}
              >
                {t('nav.about')}
              </button>
            </li>
            <li>
              <button 
                className="smoothscroll" 
                onClick={() => smoothScroll(SECTIONS.menu)}
              >
                {t('nav.menu')}
              </button>
            </li>
            <li>
              <button 
                className="smoothscroll" 
                onClick={() => smoothScroll(SECTIONS.services)}
              >
                {t('nav.services')}
              </button>
            </li>
            <li>
              <button 
                className="smoothscroll" 
                onClick={() => smoothScroll(SECTIONS.events)}
              >
                {t('nav.events')}
              </button>
            </li>
            <li>
              <Link to="/events">{t('nav.events_calendar')}</Link>
            </li>
            <li>
              <Link to="/menu">{t('nav.full_menu')}</Link>
            </li>
          </ul>

          <div className="header-contact">
            <a href="tel:+84-xxx-xxx-xxxx" className="header-contact__num btn">
              <svg 
                id="Layer_1" 
                data-name="Layer 1" 
                xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 24 24" 
                strokeWidth="1.5" 
                width="24" 
                height="24" 
                color="#000000"
              >
                <defs>
                  <style>
                    {`.cls-6376396cc3a86d32eae6f0dc-1{fill:none;stroke:currentColor;stroke-miterlimit:10;}`}
                  </style>
                </defs>
                <path 
                  className="cls-6376396cc3a86d32eae6f0dc-1" 
                  d="M19.64,21.25c-2.54,2.55-8.38.83-13-3.84S.2,6.9,2.75,4.36L5.53,1.57,10.9,6.94l-2,2A2.18,2.18,0,0,0,8.9,12L12,15.1a2.18,2.18,0,0,0,3.07,0l2-2,5.37,5.37Z"
                />
              </svg>
              +84-xxx-xxx-xxxx
            </a>
          </div>

          <div className="header-actions">
            <LanguageSwitcher />
          </div>
        </nav>
      </div>
    </header>
  );
};
