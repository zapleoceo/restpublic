import React, { useState } from 'react';
import Logo from './Logo';
import LanguageSwitcher from './LanguageSwitcher';
import CartIcon from './CartIcon';
import AuthButton from './AuthButton';
import './MenuHeader.css';

const MenuHeader = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  return (
    <header className="menu-header">
      <div className="container">
        <div className="menu-header__content">
          {/* Логотип */}
          <div className="menu-header__logo">
            <a href="/" className="logo-link">
              <Logo />
            </a>
          </div>

          {/* Мобильное меню кнопка */}
          <button 
            className="menu-toggle"
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            aria-label="Toggle menu"
          >
            <span></span>
            <span></span>
            <span></span>
          </button>

          {/* Навигация */}
          <nav className={`menu-header__nav ${isMenuOpen ? 'menu-header__nav--open' : ''}`}>
            <ul className="menu-header__links">
              <li>
                <Link to="/" className="menu-header__link">
                  Главная
                </Link>
              </li>
              <li>
                <Link to="/#about" className="menu-header__link">
                  О нас
                </Link>
              </li>
              <li>
                <Link to="/#gallery" className="menu-header__link">
                  Галерея
                </Link>
              </li>
            </ul>

            {/* Дополнительные элементы */}
            <div className="menu-header__actions">
              <LanguageSwitcher />
              <AuthButton />
              <CartIcon />
            </div>
          </nav>
        </div>
      </div>
    </header>
  );
};

export default MenuHeader;
