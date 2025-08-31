import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from '../../hooks/useTranslation';
import LanguageSwitcher from '../LanguageSwitcher';
import CartButton from '../CartButton';

export const Header = () => {
  const { t } = useTranslation();
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const toggleMenu = () => {
    setIsMenuOpen(!isMenuOpen);
  };

  return (
    <header className="header fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-neutral-200">
      <div className="container mx-auto px-4">
        <div className="header__content flex items-center justify-between h-16">
          {/* Логотип */}
          <div className="header__logo">
            <Link to="/" className="flex items-center space-x-2">
              <img 
                src="/img/logo.png" 
                alt="North Republic" 
                className="h-8 w-auto"
              />
              <span className="text-xl font-serif font-bold text-primary-900 hidden sm:block">
                North Republic
              </span>
            </Link>
          </div>

          {/* Навигация */}
          <nav className={`header__nav ${isMenuOpen ? 'block' : 'hidden'} md:block absolute md:relative top-full left-0 right-0 md:top-auto bg-white md:bg-transparent border-t md:border-t-0 border-neutral-200 md:border-none`}>
            <ul className="header__nav-list flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-8 p-4 md:p-0">
              <li>
                <a 
                  href="#intro" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.home') || "Главная"}
                </a>
              </li>
              <li>
                <a 
                  href="#about" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.about') || "О нас"}
                </a>
              </li>
              <li>
                <a 
                  href="#menu" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.menu') || "Меню"}
                </a>
              </li>
              <li>
                <a 
                  href="#services" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.services') || "Услуги"}
                </a>
              </li>
              <li>
                <a 
                  href="#events" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.events') || "Афиша"}
                </a>
              </li>
              <li>
                <Link 
                  to="/events" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.events_calendar') || "Календарь"}
                </Link>
              </li>
              <li>
                <Link 
                  to="/menu" 
                  className="block py-2 md:py-0 text-neutral-700 hover:text-primary-600 transition-colors font-medium"
                  onClick={() => setIsMenuOpen(false)}
                >
                  {t('nav.full_menu') || "Полное меню"}
                </Link>
              </li>
            </ul>
          </nav>

          {/* Действия */}
          <div className="header__actions flex items-center space-x-4">
            <LanguageSwitcher />
            <CartButton />
            
            {/* Мобильное меню */}
            <button
              className="md:hidden p-2 text-neutral-700 hover:text-primary-600 transition-colors"
              onClick={toggleMenu}
              aria-label="Toggle menu"
            >
              <svg 
                className="w-6 h-6" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                {isMenuOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>
  );
};
