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
    <header className="s-header">
      <div className="container s-header__content">
        <div className="s-header__block">
          <div className="header-logo">
            <Link to="/" className="logo">
              <img 
                src="/img/logo.png" 
                alt="North Republic" 
              />
            </Link>
          </div>
          <a 
            className="header-menu-toggle" 
            href="#0"
            onClick={(e) => {
              e.preventDefault();
              toggleMenu();
            }}
          >
            <span>Menu</span>
          </a>
        </div>

        <nav className="header-nav">
          <ul className="header-nav__links">
            <li className="current">
              <a className="smoothscroll" href="#intro">
                {t('nav.home') || "Главная"}
              </a>
            </li>
            <li>
              <a className="smoothscroll" href="#about">
                {t('nav.about') || "О нас"}
              </a>
            </li>
            <li>
              <a className="smoothscroll" href="#menu">
                {t('nav.menu') || "Меню"}
              </a>
            </li>
            <li>
              <a className="smoothscroll" href="#services">
                {t('nav.services') || "Услуги"}
              </a>
            </li>
            <li>
              <a className="smoothscroll" href="#events">
                {t('nav.events') || "Афиша"}
              </a>
            </li>
            <li>
              <Link to="/events">
                {t('nav.events_calendar') || "Календарь"}
              </Link>
            </li>
          </ul>
          
          <div className="header-contact">
            <div className="header-actions">
              <LanguageSwitcher />
              <CartButton />
            </div>
          </div>
        </nav>
      </div>
    </header>
  );
};